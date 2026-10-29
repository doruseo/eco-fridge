<?php
/**
 * Settings: defaults, sanitising, access.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * One option array, `o2io_settings`.
 */
class O2IO_Settings {

	const OPTION = 'o2io_settings';

	/**
	 * Every setting with its default.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Formats.
			'webp'              => 1,
			'avif'              => 1,
			'webp_quality'      => 82,
			'avif_quality'      => 62,
			'avif_speed'        => 6,         // Imagick/GD encoder speed 0 (smallest) – 10 (fastest).
			'engine'            => 'auto',    // auto | imagick | gd.
			'strip_metadata'    => 1,
			'skip_larger'       => 1,         // Drop a converted file that isn't smaller than its source.
			'min_saving'        => 5,         // …or that saves less than this many percent.
			'convert_sizes'     => 1,         // Every generated size, not only the original.
			'convert_png'       => 1,
			'convert_jpeg'      => 1,
			'convert_gif'       => 0,         // Only the first frame would survive.
			// New uploads.
			'on_upload'         => 'background', // background | immediate | off.
			'max_width'         => 2560,      // WordPress "big image" threshold; 0 = off.
			// Delivery.
			'delivery'          => 'picture', // picture | rewrite | both | off.
			'picture_scope'     => 'page',    // page = whole page HTML | content = filters only.
			'skip_lazy_data'    => 1,         // Leave <img data-src> (script-lazy-loaded) images alone.
			'exclude_classes'   => "no-o2io\nskip-webp",
			'exclude_paths'     => '',
			'exclude_admin'     => 1,
			// Bulk.
			'batch_size'        => 4,         // Attachments per background run.
			'batch_pause'       => 20,        // Seconds between background runs.
			// Housekeeping.
			'delete_on_uninstall' => 0,
		);
	}

	/**
	 * The saved settings over the defaults.
	 *
	 * @return array
	 */
	public static function all() {
		static $cache = null;
		if ( null === $cache ) {
			$cache = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		}
		return $cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Clean the posted settings.
	 *
	 * @param array $in Raw values.
	 * @return array
	 */
	public static function sanitize( $in ) {
		$d   = self::defaults();
		$out = array();
		foreach ( $d as $key => $default ) {
			$v = $in[ $key ] ?? null;
			if ( is_int( $default ) && in_array( $key, array( 'webp', 'avif', 'strip_metadata', 'skip_larger', 'convert_sizes', 'convert_png', 'convert_jpeg', 'convert_gif', 'skip_lazy_data', 'exclude_admin', 'delete_on_uninstall' ), true ) ) {
				$out[ $key ] = empty( $v ) ? 0 : 1;
			} elseif ( is_int( $default ) ) {
				$out[ $key ] = (int) $v;
			} else {
				$out[ $key ] = sanitize_textarea_field( (string) $v );
			}
		}
		$out['webp_quality'] = max( 1, min( 100, $out['webp_quality'] ) );
		$out['avif_quality'] = max( 1, min( 100, $out['avif_quality'] ) );
		$out['avif_speed']   = max( 0, min( 10, $out['avif_speed'] ) );
		$out['min_saving']   = max( 0, min( 90, $out['min_saving'] ) );
		$out['max_width']    = max( 0, $out['max_width'] );
		$out['batch_size']   = max( 1, min( 50, $out['batch_size'] ) );
		$out['batch_pause']  = max( 0, min( 600, $out['batch_pause'] ) );
		$choices = array(
			'engine'        => array( 'auto', 'imagick', 'gd' ),
			'on_upload'     => array( 'background', 'immediate', 'off' ),
			'delivery'      => array( 'picture', 'rewrite', 'both', 'off' ),
			'picture_scope' => array( 'page', 'content' ),
		);
		foreach ( $choices as $key => $allowed ) {
			if ( ! in_array( $out[ $key ], $allowed, true ) ) { $out[ $key ] = $d[ $key ]; }
		}
		return $out;
	}

	/**
	 * Formats to produce, in delivery order (best first).
	 *
	 * @return string[] e.g. array( 'avif', 'webp' )
	 */
	public static function formats() {
		$f = array();
		if ( self::get( 'avif' ) && O2IO_Converter::supports( 'avif' ) ) { $f[] = 'avif'; }
		if ( self::get( 'webp' ) && O2IO_Converter::supports( 'webp' ) ) { $f[] = 'webp'; }
		return $f;
	}

	/**
	 * Source MIME types that get converted.
	 *
	 * @return string[]
	 */
	public static function source_mimes() {
		$m = array();
		if ( self::get( 'convert_jpeg' ) ) { $m[] = 'image/jpeg'; }
		if ( self::get( 'convert_png' ) ) { $m[] = 'image/png'; }
		if ( self::get( 'convert_gif' ) ) { $m[] = 'image/gif'; }
		return $m;
	}

	/**
	 * Lines of a textarea setting.
	 *
	 * @param string $key Key.
	 * @return string[]
	 */
	public static function lines( $key ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) self::get( $key ) ) ) ) );
	}
}
