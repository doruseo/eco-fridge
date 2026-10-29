<?php
/**
 * Converts one image file to WebP / AVIF, next to the source: photo.jpg →
 * photo.jpg.webp, photo.jpg.avif (the source extension stays in the name, so
 * photo.jpg and photo.png never collide).
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Imagick first (better AVIF encoder control), GD as the fallback.
 */
class O2IO_Converter {

	const MIMES = array(
		'webp' => 'image/webp',
		'avif' => 'image/avif',
	);

	/**
	 * Whether this server can write a format.
	 *
	 * @param string $format webp|avif.
	 * @return bool
	 */
	public static function supports( $format ) {
		static $cache = array();
		if ( isset( $cache[ $format ] ) ) { return $cache[ $format ]; }
		$ok = false;
		if ( self::engine_for( $format ) ) { $ok = true; }
		return $cache[ $format ] = $ok;
	}

	/**
	 * The engine that will write a format: imagick | gd | ''.
	 *
	 * @param string $format webp|avif.
	 * @return string
	 */
	public static function engine_for( $format ) {
		$want     = O2IO_Settings::get( 'engine' );
		$imagick  = class_exists( 'Imagick' ) && in_array( strtoupper( $format ), (array) Imagick::queryFormats( strtoupper( $format ) ), true );
		$gd       = function_exists( 'image' . $format );
		if ( 'gd' !== $want && $imagick ) { return 'imagick'; }
		if ( 'imagick' !== $want && $gd ) { return 'gd'; }
		if ( $imagick ) { return 'imagick'; }
		return $gd ? 'gd' : '';
	}

	/**
	 * Path of the converted copy of a file.
	 *
	 * @param string $file   Source path.
	 * @param string $format webp|avif.
	 * @return string
	 */
	public static function target( $file, $format ) {
		return $file . '.' . $format;
	}

	/**
	 * Convert a file to every enabled format.
	 *
	 * @param string $file  Source path.
	 * @param bool   $force Re-create files that already exist.
	 * @return array format => array( bytes, source_bytes, status ) — status: done|exists|larger|error:<msg>|skipped
	 */
	public static function convert( $file, $force = false ) {
		$out = array();
		if ( ! is_readable( $file ) ) {
			foreach ( O2IO_Settings::formats() as $f ) { $out[ $f ] = array( 0, 0, 'error:missing' ); }
			return $out;
		}
		$mime = wp_get_image_mime( $file );
		if ( ! in_array( $mime, O2IO_Settings::source_mimes(), true ) ) {
			foreach ( O2IO_Settings::formats() as $f ) { $out[ $f ] = array( 0, 0, 'skipped' ); }
			return $out;
		}
		$src_bytes = (int) filesize( $file );
		foreach ( O2IO_Settings::formats() as $format ) {
			$dest = self::target( $file, $format );
			if ( ! $force && file_exists( $dest ) ) {
				$out[ $format ] = array( (int) filesize( $dest ), $src_bytes, 'exists' );
				continue;
			}
			$result = self::write( $file, $dest, $format );
			if ( true !== $result ) {
				$out[ $format ] = array( 0, $src_bytes, 'error:' . $result );
				continue;
			}
			clearstatcache( true, $dest );
			$bytes = (int) filesize( $dest );
			$floor = $src_bytes * ( 1 - O2IO_Settings::get( 'min_saving' ) / 100 );
			if ( O2IO_Settings::get( 'skip_larger' ) && $bytes >= $floor ) {
				wp_delete_file( $dest );
				$out[ $format ] = array( $bytes, $src_bytes, 'larger' );
				continue;
			}
			$out[ $format ] = array( $bytes, $src_bytes, 'done' );
		}
		return $out;
	}

	/**
	 * Write one converted copy.
	 *
	 * @param string $file   Source.
	 * @param string $dest   Target.
	 * @param string $format webp|avif.
	 * @return true|string True, or an error message.
	 */
	private static function write( $file, $dest, $format ) {
		$quality = (int) O2IO_Settings::get( $format . '_quality' );
		$engine  = self::engine_for( $format );
		$tmp     = $dest . '.o2io-tmp';
		try {
			if ( 'imagick' === $engine ) {
				$im = new Imagick( $file );
				if ( $im->getNumberImages() > 1 ) {
					$im = $im->getImage(); // First frame of an animation.
				}
				$im->autoOrient();
				if ( O2IO_Settings::get( 'strip_metadata' ) ) {
					// Keep the colour profile, drop EXIF/XMP/comments.
					$profiles = $im->getImageProfiles( 'icc', true );
					$im->stripImage();
					if ( ! empty( $profiles['icc'] ) ) { $im->profileImage( 'icc', $profiles['icc'] ); }
				}
				$im->setImageFormat( $format );
				$im->setImageCompressionQuality( $quality );
				if ( 'webp' === $format ) {
					$im->setOption( 'webp:method', '6' );
					if ( 'image/png' === wp_get_image_mime( $file ) ) { $im->setOption( 'webp:alpha-quality', '95' ); }
				} else {
					$im->setOption( 'heic:speed', (string) O2IO_Settings::get( 'avif_speed' ) );
				}
				$ok = $im->writeImage( $format . ':' . $tmp );
				$im->clear();
				if ( ! $ok ) { return 'write failed'; }
			} elseif ( 'gd' === $engine ) {
				$img = self::gd_load( $file );
				if ( ! $img ) { return 'gd could not read the source'; }
				if ( 'webp' === $format ) {
					$ok = imagewebp( $img, $tmp, $quality );
				} else {
					$ok = imageavif( $img, $tmp, $quality, (int) O2IO_Settings::get( 'avif_speed' ) );
				}
				imagedestroy( $img );
				if ( ! $ok ) { return 'write failed'; }
			} else {
				return 'no engine for ' . $format;
			}
		} catch ( Throwable $e ) {
			if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
			return substr( $e->getMessage(), 0, 120 );
		}
		if ( ! file_exists( $tmp ) || ! filesize( $tmp ) ) {
			if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
			return 'empty output';
		}
		// Atomic swap, so a half-written file is never served.
		if ( ! @rename( $tmp, $dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			wp_delete_file( $tmp );
			return 'rename failed';
		}
		@chmod( $dest, 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return true;
	}

	/**
	 * A GD image from a JPEG/PNG/GIF, with alpha kept and JPEG orientation applied.
	 *
	 * @param string $file Path.
	 * @return GdImage|false
	 */
	private static function gd_load( $file ) {
		switch ( wp_get_image_mime( $file ) ) {
			case 'image/jpeg':
				$img = @imagecreatefromjpeg( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( $img && function_exists( 'exif_read_data' ) ) {
					$exif = @exif_read_data( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					$rot  = array( 3 => 180, 6 => -90, 8 => 90 )[ (int) ( $exif['Orientation'] ?? 1 ) ] ?? 0;
					if ( $rot ) { $img = imagerotate( $img, $rot, 0 ); }
				}
				return $img;
			case 'image/png':
				$img = @imagecreatefrompng( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( $img ) {
					imagepalettetotruecolor( $img );
					imagealphablending( $img, true );
					imagesavealpha( $img, true );
				}
				return $img;
			case 'image/gif':
				$img = @imagecreatefromgif( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( $img ) { imagepalettetotruecolor( $img ); }
				return $img;
		}
		return false;
	}

	/**
	 * Remove the converted copies of a file.
	 *
	 * @param string $file Source path.
	 * @return int Files removed.
	 */
	public static function remove( $file ) {
		$n = 0;
		foreach ( array_keys( self::MIMES ) as $format ) {
			$dest = self::target( $file, $format );
			if ( file_exists( $dest ) ) {
				wp_delete_file( $dest );
				$n++;
			}
		}
		return $n;
	}
}
