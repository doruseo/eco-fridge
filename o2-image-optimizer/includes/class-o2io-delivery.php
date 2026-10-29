<?php
/**
 * Delivery: each browser gets the best format it supports.
 *
 *  - picture: every <img> from the uploads folder is wrapped in a <picture>
 *    with an AVIF and a WebP <source> (only formats whose files exist for
 *    every candidate of the srcset); the browser picks the first type it
 *    supports and falls back to the original <img>. Safe behind any CDN.
 *  - rewrite: uploads/.htaccess serves photo.jpg.avif / .webp in place of
 *    photo.jpg when the browser's Accept header lists the format (with
 *    "Vary: Accept"). Only for servers/CDNs that honour Vary.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Front-end delivery.
 */
class O2IO_Delivery {

	const MARKER = 'O2 Image Optimizer';

	/** Uploads base URL path and directory, set once. */
	private static $base_path = '';
	private static $base_dir  = '';
	private static $hosts     = array();

	/** Existence checks already made in this request. */
	private static $exists = array();

	/** Hooks. */
	public static function init() {
		$mode = O2IO_Settings::get( 'delivery' );
		if ( in_array( $mode, array( 'picture', 'both' ), true ) ) {
			if ( 'page' === O2IO_Settings::get( 'picture_scope' ) ) {
				add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), PHP_INT_MAX );
			} else {
				add_filter( 'wp_content_img_tag', array( __CLASS__, 'filter_tag' ), 99 );
				add_filter( 'wp_get_attachment_image', array( __CLASS__, 'filter_tag' ), 99 );
			}
			add_action( 'wp_head', array( __CLASS__, 'css' ), 1 );
		}
		add_action( 'update_option_' . O2IO_Settings::OPTION, array( __CLASS__, 'write_htaccess' ) );
	}

	/** The <picture> wrapper must not change the layout. */
	public static function css() {
		echo "<style id=\"o2io-css\">picture.o2io-picture{display:contents}</style>\n";
	}

	/** Whether this request's HTML may be rewritten. */
	private static function applies() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_embed() || is_customize_preview() ) { return false; }
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return false; }
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) { return false; }
		if ( O2IO_Settings::get( 'exclude_admin' ) && is_user_logged_in() && current_user_can( 'edit_posts' ) && isset( $_GET['o2io_off'] ) ) { return false; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- debug switch.
		return (bool) O2IO_Settings::formats();
	}

	/** Buffer the whole page (scope "page"). */
	public static function start_buffer() {
		if ( self::applies() ) {
			ob_start( array( __CLASS__, 'filter_html' ) );
		}
	}

	/**
	 * Rewrite a fragment that is a single <img> (scope "content").
	 *
	 * @param string $html Tag HTML.
	 * @return string
	 */
	public static function filter_tag( $html ) {
		return self::applies() ? self::filter_html( $html ) : $html;
	}

	/**
	 * Wrap the <img> tags of an HTML document, leaving alone the ones already
	 * inside <picture> and anything in <script>, <style>, <noscript>,
	 * <template>, <textarea> or HTML comments.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function filter_html( $html ) {
		if ( '' === $html || false === stripos( $html, '<img' ) ) { return $html; }
		self::setup();
		$parts = preg_split( '#(<!--.*?-->|<(picture|script|style|noscript|template|textarea)\b[^>]*>.*?</\2\s*>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) { return $html; }
		$out  = '';
		$skip = false;
		foreach ( $parts as $i => $part ) {
			// With DELIM_CAPTURE: text, whole block, tag name, text, whole block, tag name …
			if ( $skip ) { $skip = false; continue; } // The captured tag name.
			if ( preg_match( '#^(<!--|<(picture|script|style|noscript|template|textarea)\b)#i', $part ) ) {
				$out .= $part;
				$skip = 0 !== strpos( $part, '<!--' );
				continue;
			}
			$out .= preg_replace_callback( '#<img\b[^>]*>#i', array( __CLASS__, 'wrap' ), $part );
		}
		return $out;
	}

	/** Uploads location, once per request. */
	private static function setup() {
		if ( self::$base_dir ) { return; }
		$up              = wp_get_upload_dir();
		self::$base_dir  = untrailingslashit( $up['basedir'] );
		self::$base_path = untrailingslashit( (string) wp_parse_url( $up['baseurl'], PHP_URL_PATH ) );
		self::$hosts     = array_unique( array_filter( array( wp_parse_url( home_url(), PHP_URL_HOST ), wp_parse_url( $up['baseurl'], PHP_URL_HOST ) ) ) );
	}

	/**
	 * <img> → <picture> with AVIF/WebP sources, or the tag unchanged.
	 *
	 * @param array $m Regex match (0 = tag).
	 * @return string
	 */
	public static function wrap( $m ) {
		$tag = $m[0];
		$p   = new WP_HTML_Tag_Processor( $tag );
		if ( ! $p->next_tag( 'img' ) ) { return $tag; }

		$class = ' ' . (string) $p->get_attribute( 'class' ) . ' ';
		foreach ( O2IO_Settings::lines( 'exclude_classes' ) as $c ) {
			if ( false !== strpos( $class, ' ' . $c . ' ' ) ) { return $tag; }
		}

		$src    = html_entity_decode( (string) $p->get_attribute( 'src' ), ENT_QUOTES );
		$srcset = html_entity_decode( (string) $p->get_attribute( 'srcset' ), ENT_QUOTES );
		$sizes  = (string) $p->get_attribute( 'sizes' );
		// A placeholder src (data: URI or none) means a script swaps in the real
		// image later (mega menu, lazy loaders): leave those alone.
		if ( '' === $src || 0 === strpos( $src, 'data:' ) ) { return $tag; }
		if ( O2IO_Settings::get( 'skip_lazy_data' ) ) {
			foreach ( array( 'data-srcset', 'data-lazy-src', 'data-lazy-srcset' ) as $a ) {
				if ( null !== $p->get_attribute( $a ) ) { return $tag; }
			}
		}

		// Candidates: the srcset entries, or the src alone.
		$cands = array();
		if ( '' !== trim( $srcset ) ) {
			foreach ( explode( ',', $srcset ) as $entry ) {
				$bits = preg_split( '/\s+/', trim( $entry ), 2 );
				if ( '' !== $bits[0] ) { $cands[] = array( $bits[0], $bits[1] ?? '' ); }
			}
		}
		if ( ! $cands ) { $cands[] = array( $src, '' ); }
		$paths = array();
		foreach ( $cands as $c ) {
			$path = self::path_of( $c[0] );
			if ( ! $path ) { return $tag; }
			$paths[] = $path;
		}

		// Per format, every candidate in the converted version, or the original
		// where that size has no converted copy (e.g. it came out larger).
		$sources = '';
		foreach ( O2IO_Settings::formats() as $format ) {
			$set = array();
			$any = false;
			foreach ( $cands as $i => $c ) {
				$has   = self::exists( $paths[ $i ] . '.' . $format );
				$any   = $any || $has;
				$set[] = trim( ( $has ? $c[0] . '.' . $format : $c[0] ) . ' ' . $c[1] );
			}
			if ( $any ) {
				$sources .= '<source type="' . O2IO_Converter::MIMES[ $format ] . '" srcset="' . esc_attr( implode( ', ', $set ) ) . '"'
					. ( '' !== $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : '' ) . '>';
			}
		}
		return '' === $sources ? $tag : '<picture class="o2io-picture">' . $sources . $tag . '</picture>';
	}

	/**
	 * The file behind an uploads URL, or '' (other hosts, other folders,
	 * excluded paths, query strings, formats that aren't converted).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function path_of( $url ) {
		if ( false !== strpos( $url, '?' ) || false !== strpos( $url, '#' ) ) { return ''; }
		if ( ! preg_match( '/\.(jpe?g|png|gif)$/i', $url ) ) { return ''; }
		if ( 0 === strpos( $url, '//' ) ) { $url = 'https:' . $url; }
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( $host && ! in_array( $host, self::$hosts, true ) ) { return ''; }
		$path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( 0 !== strpos( $path, self::$base_path . '/' ) || false !== strpos( $path, '..' ) ) { return ''; }
		foreach ( O2IO_Settings::lines( 'exclude_paths' ) as $x ) {
			if ( false !== strpos( $path, $x ) ) { return ''; }
		}
		return self::$base_dir . substr( $path, strlen( self::$base_path ) );
	}

	/**
	 * Cached file_exists for this request.
	 *
	 * @param string $file Path.
	 * @return bool
	 */
	private static function exists( $file ) {
		if ( ! isset( self::$exists[ $file ] ) ) {
			self::$exists[ $file ] = file_exists( $file );
		}
		return self::$exists[ $file ];
	}

	/**
	 * uploads/.htaccess: MIME types for the new formats (always), plus the
	 * Accept-header rewrites when the delivery mode uses them.
	 */
	public static function write_htaccess() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$file  = trailingslashit( wp_get_upload_dir()['basedir'] ) . '.htaccess';
		$lines = array(
			'<IfModule mod_mime.c>',
			'AddType image/avif .avif',
			'AddType image/webp .webp',
			'</IfModule>',
		);
		if ( in_array( O2IO_Settings::get( 'delivery' ), array( 'rewrite', 'both' ), true ) ) {
			$lines = array_merge( $lines, array(
				'<IfModule mod_rewrite.c>',
				'RewriteEngine On',
				'RewriteCond %{HTTP_ACCEPT} image/avif',
				'RewriteCond %{REQUEST_FILENAME}.avif -f',
				'RewriteRule ^(.+\.(?:jpe?g|png|gif))$ $1.avif [T=image/avif,E=O2IO:1,L]',
				'RewriteCond %{HTTP_ACCEPT} image/webp',
				'RewriteCond %{REQUEST_FILENAME}.webp -f',
				'RewriteRule ^(.+\.(?:jpe?g|png|gif))$ $1.webp [T=image/webp,E=O2IO:1,L]',
				'</IfModule>',
				'<IfModule mod_headers.c>',
				'<FilesMatch "\.(jpe?g|png|gif)$">',
				'Header append Vary Accept',
				'</FilesMatch>',
				'</IfModule>',
			) );
		}
		if ( is_writable( dirname( $file ) ) && ( ! file_exists( $file ) || is_writable( $file ) ) ) {
			insert_with_markers( $file, self::MARKER, $lines );
		}
	}

	/** Remove the block (deactivation). */
	public static function remove_htaccess() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$file = trailingslashit( wp_get_upload_dir()['basedir'] ) . '.htaccess';
		if ( file_exists( $file ) && is_writable( $file ) ) {
			insert_with_markers( $file, self::MARKER, array() );
		}
	}
}
