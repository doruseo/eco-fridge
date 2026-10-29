<?php
/**
 * WP-CLI: wp o2io status | convert | cleanup.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Converts and inspects images from the command line.
 */
class O2IO_CLI {

	/**
	 * Library totals and server support.
	 *
	 * ## EXAMPLES
	 *     wp o2io status
	 */
	public function status() {
		foreach ( array( 'avif', 'webp' ) as $f ) {
			WP_CLI::log( sprintf( '%-5s %s', strtoupper( $f ), O2IO_Converter::engine_for( $f ) ?: 'not supported' ) );
		}
		$st = O2IO_Attachments::stats();
		WP_CLI::log( sprintf( 'images %d · optimized %d · not yet %d · errors %d', $st['images'], $st['done'], $st['pending'], $st['partial'] + $st['error'] ) );
		foreach ( $st['saved'] as $f => $b ) { WP_CLI::log( strtoupper( $f ) . ' saves ' . size_format( $b, 1 ) ); }
		$q = O2IO_Queue::state();
		WP_CLI::log( 'background queue: ' . $q['status'] );
	}

	/**
	 * Convert images in the foreground.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : At most this many images. Default: all.
	 *
	 * [--all]
	 * : Re-convert images already done (default: only images not converted yet).
	 *
	 * [--id=<id>]
	 * : One attachment.
	 *
	 * ## EXAMPLES
	 *     wp o2io convert --limit=20
	 *     wp o2io convert --id=123
	 *
	 * @param array $args  Args.
	 * @param array $assoc Options.
	 */
	public function convert( $args, $assoc ) {
		if ( ! empty( $assoc['id'] ) ) {
			$r = O2IO_Attachments::process( (int) $assoc['id'], true );
			WP_CLI::log( '#' . (int) $assoc['id'] . ' ' . $r['status'] . ( $r['errors'] ? ' — ' . implode( '; ', $r['errors'] ) : '' ) );
			return;
		}
		$all   = ! empty( $assoc['all'] );
		$limit = isset( $assoc['limit'] ) ? max( 1, (int) $assoc['limit'] ) : PHP_INT_MAX;
		$last  = 0;
		$n     = 0;
		while ( $n < $limit ) {
			$ids = O2IO_Attachments::next_ids( $last, min( 20, $limit - $n ), ! $all );
			if ( ! $ids ) { break; }
			foreach ( $ids as $id ) {
				$r = O2IO_Attachments::process( $id, $all );
				$n++;
				$last = $id;
				WP_CLI::log( sprintf( '#%d %s%s', $id, $r['status'], $r['errors'] ? ' — ' . implode( '; ', $r['errors'] ) : '' ) );
			}
		}
		WP_CLI::success( "$n images processed." );
	}

	/**
	 * Delete every converted copy (originals are not touched).
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * @param array $args  Args.
	 * @param array $assoc Options.
	 */
	public function cleanup( $args, $assoc ) {
		WP_CLI::confirm( 'Delete all .avif/.webp copies made by O2 Image Optimizer?', $assoc );
		O2IO_Queue::stop();
		$n = 0;
		foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => O2IO_Attachments::META ) ) as $id ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$n += O2IO_Attachments::reset( $id );
		}
		WP_CLI::success( "$n converted files deleted." );
	}
}
