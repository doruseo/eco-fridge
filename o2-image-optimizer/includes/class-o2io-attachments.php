<?php
/**
 * Attachments: which files belong to an image, converting them, the record
 * of what was done (`_o2io` post meta), and the upload / delete hooks.
 *
 * Original files are never changed: converted copies are written next to them.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Per-attachment processing.
 */
class O2IO_Attachments {

	const META = '_o2io';

	/** Hooks. */
	public static function init() {
		// After WordPress has made the sizes of a new upload (or regenerated them).
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'on_metadata' ), 99, 2 );
		add_action( 'delete_attachment', array( __CLASS__, 'on_delete' ) );
		add_filter( 'big_image_size_threshold', array( __CLASS__, 'threshold' ), 20 );
	}

	/**
	 * WordPress's "big image" threshold: uploads wider than this get a scaled
	 * copy for the site (the original file is kept untouched by WordPress).
	 *
	 * @param int|false $threshold Default.
	 * @return int|false
	 */
	public static function threshold( $threshold ) {
		$w = (int) O2IO_Settings::get( 'max_width' );
		return $w > 0 ? $w : $threshold;
	}

	/**
	 * Whether an attachment is an image this plugin converts.
	 *
	 * @param int $id Attachment ID.
	 * @return bool
	 */
	public static function is_convertible( $id ) {
		return in_array( get_post_mime_type( $id ), O2IO_Settings::source_mimes(), true );
	}

	/**
	 * The image files of an attachment: the file the site uses plus its sizes.
	 *
	 * @param int $id Attachment ID.
	 * @return string[] Absolute paths.
	 */
	public static function files( $id ) {
		$main = get_attached_file( $id );
		if ( ! $main ) { return array(); }
		$files = array( $main );
		if ( O2IO_Settings::get( 'convert_sizes' ) ) {
			$meta = wp_get_attachment_metadata( $id );
			$dir  = dirname( $main );
			foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
				if ( ! empty( $size['file'] ) ) { $files[] = $dir . '/' . $size['file']; }
			}
		}
		return array_values( array_unique( $files ) );
	}

	/**
	 * Convert every file of an attachment and record the result.
	 *
	 * @param int  $id    Attachment ID.
	 * @param bool $force Re-create existing converted files.
	 * @return array The record (see record()).
	 */
	public static function process( $id, $force = false ) {
		if ( ! self::is_convertible( $id ) ) {
			return self::save( $id, array( 'status' => 'skipped', 'files' => array() ) );
		}
		$files = array();
		foreach ( self::files( $id ) as $file ) {
			$files[ basename( $file ) ] = O2IO_Converter::convert( $file, $force );
		}
		return self::save( $id, array( 'files' => $files ) );
	}

	/**
	 * Store a record with its totals and overall status.
	 *
	 * @param int   $id     Attachment ID.
	 * @param array $record Record with `files` (and optional `status`).
	 * @return array
	 */
	private static function save( $id, $record ) {
		$saved  = array();
		$errors = array();
		$done   = 0;
		$total  = 0;
		foreach ( $record['files'] as $name => $formats ) {
			foreach ( $formats as $format => $r ) {
				$total++;
				list( $bytes, $src, $status ) = $r;
				if ( in_array( $status, array( 'done', 'exists' ), true ) ) {
					$done++;
					$saved[ $format ] = ( $saved[ $format ] ?? 0 ) + max( 0, $src - $bytes );
				} elseif ( 'larger' === $status ) {
					$done++; // Nothing better to serve: the original stays, which is correct.
				} elseif ( 0 === strpos( $status, 'error:' ) ) {
					$errors[] = $name . ' → ' . $format . ': ' . substr( $status, 6 );
				}
			}
		}
		if ( empty( $record['status'] ) ) {
			$record['status'] = $errors ? ( $done ? 'partial' : 'error' ) : 'done';
		}
		$record['saved']  = $saved;
		$record['errors'] = array_slice( $errors, 0, 10 );
		$record['time']   = time();
		update_post_meta( $id, self::META, $record );
		return $record;
	}

	/**
	 * The record of an attachment, or null if never processed.
	 *
	 * @param int $id Attachment ID.
	 * @return array|null
	 */
	public static function record( $id ) {
		$r = get_post_meta( $id, self::META, true );
		return is_array( $r ) ? $r : null;
	}

	/**
	 * New upload / regenerated sizes: convert now or queue it.
	 *
	 * @param array $metadata Attachment metadata.
	 * @param int   $id       Attachment ID.
	 * @return array Unchanged metadata.
	 */
	public static function on_metadata( $metadata, $id ) {
		if ( ! self::is_convertible( $id ) || ! O2IO_Settings::formats() ) { return $metadata; }
		$mode = O2IO_Settings::get( 'on_upload' );
		if ( 'immediate' === $mode ) {
			// The sizes in $metadata are not saved yet: store them first so files() sees them.
			wp_update_attachment_metadata( $id, $metadata );
			self::process( $id, true );
		} elseif ( 'background' === $mode ) {
			O2IO_Queue::enqueue_one( $id );
		}
		return $metadata;
	}

	/**
	 * Attachment deleted: remove its converted copies too.
	 *
	 * @param int $id Attachment ID.
	 */
	public static function on_delete( $id ) {
		foreach ( self::files( $id ) as $file ) {
			O2IO_Converter::remove( $file );
		}
	}

	/**
	 * Remove the converted copies of an attachment and its record.
	 *
	 * @param int $id Attachment ID.
	 * @return int Files removed.
	 */
	public static function reset( $id ) {
		$n = 0;
		foreach ( self::files( $id ) as $file ) {
			$n += O2IO_Converter::remove( $file );
		}
		delete_post_meta( $id, self::META );
		return $n;
	}

	/**
	 * Image attachment IDs after a given ID, in ID order.
	 *
	 * @param int  $after   Last ID handled.
	 * @param int  $limit   How many.
	 * @param bool $missing Only images never processed.
	 * @return int[]
	 */
	public static function next_ids( $after, $limit, $missing ) {
		global $wpdb;
		$mimes = O2IO_Settings::source_mimes();
		if ( ! $mimes ) { return array(); }
		$in   = implode( ',', array_fill( 0, count( $mimes ), '%s' ) );
		$join = $missing ? $wpdb->prepare( "LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s", self::META ) : '';
		$cond = $missing ? 'AND m.meta_id IS NULL' : '';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built above.
		$sql = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p $join WHERE p.post_type = 'attachment' AND p.post_mime_type IN ($in) AND p.ID > %d $cond ORDER BY p.ID ASC LIMIT %d", array_merge( $mimes, array( $after, $limit ) ) );
		return array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Library totals for the dashboard.
	 *
	 * @return array
	 */
	public static function stats() {
		global $wpdb;
		$mimes = O2IO_Settings::source_mimes();
		$out   = array( 'images' => 0, 'done' => 0, 'partial' => 0, 'error' => 0, 'pending' => 0, 'saved' => array() );
		if ( ! $mimes ) { return $out; }
		$in = implode( ',', array_fill( 0, count( $mimes ), '%s' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$out['images'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ($in)", $mimes ) );
		$rows          = $wpdb->get_col( $wpdb->prepare( "SELECT m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = %s AND p.post_mime_type IN ($in)", array_merge( array( self::META ), $mimes ) ) );
		// phpcs:enable
		foreach ( $rows as $raw ) {
			$r = maybe_unserialize( $raw );
			if ( ! is_array( $r ) ) { continue; }
			$s = $r['status'] ?? '';
			if ( 'done' === $s || 'skipped' === $s ) { $out['done']++; } elseif ( 'partial' === $s ) { $out['partial']++; } elseif ( 'error' === $s ) { $out['error']++; }
			foreach ( (array) ( $r['saved'] ?? array() ) as $f => $b ) { $out['saved'][ $f ] = ( $out['saved'][ $f ] ?? 0 ) + (int) $b; }
		}
		$out['pending'] = max( 0, $out['images'] - $out['done'] - $out['partial'] - $out['error'] );
		return $out;
	}
}
