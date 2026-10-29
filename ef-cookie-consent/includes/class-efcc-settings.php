<?php
/**
 * Settings: one nested option, `efcc_settings`, with a section per admin tab.
 * Each tab saves its own section only (the others are kept).
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Settings access and sanitising.
 */
class EFCC_Settings {

	const OPTION = 'efcc_settings';

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		$services = array();
		foreach ( EFCC_Library::services() as $id => $s ) {
			$services[ $id ] = array(
				'enabled'  => in_array( $id, array( 'google-tag-manager', 'google-analytics', 'google-ads', 'youtube', 'google-maps', 'woocommerce-attribution' ), true ) ? 1 : 0,
				'name'     => $s['name'],
				'category' => $s['category'],
				'provider' => $s['provider'],
				'purpose'  => $s['purpose'],
				'privacy'  => $s['privacy'],
				'patterns' => implode( "\n", $s['patterns'] ),
				'cookies'  => implode( "\n", $s['cookies'] ),
			);
		}
		return array(
			'general'    => array(
				'enabled'             => 1,
				'consent_days'        => 180,
				'version'             => 1,
				'log'                 => 1,
				'log_days'            => 730,
				'gpc'                 => 1,
				'reload_on_revoke'    => 1,
				'policy_page'         => 0,
				'privacy_page'        => 0,
				'company'             => '',
				'contact_email'       => '',
				'show_floating'       => 1,
				'floating_position'   => 'left',
				'hide_banner_on'      => '',
				'analytics_exemption' => 0,
			),
			'texts'      => array(
				'title'              => 'We value your privacy',
				'message'            => 'We use cookies to make this website work and, with your permission, to understand how it is used and to show you relevant adverts. You can accept all cookies, reject the optional ones, or choose which ones to allow. Read our {policy}.',
				'policy_link'        => 'Cookie Policy',
				'accept'             => 'Accept all',
				'reject'             => 'Reject all',
				'manage'             => 'Manage options',
				'save'               => 'Save my choices',
				'modal_title'        => 'Cookie preferences',
				'modal_intro'        => 'Choose which cookies we may use. Necessary cookies are always on because the website cannot work without them. You can change your choices at any time with the cookie button in the bottom-left corner.',
				'always_active'      => 'Always active',
				'services_label'     => 'Services',
				'cookies_label'      => 'Cookies',
				'floating_label'     => 'Cookie settings',
				'placeholder'        => 'This content is provided by {service}, which may set cookies. To see it, allow {category} cookies.',
				'placeholder_button' => 'Allow and show',
				'consent_id'         => 'Your consent ID',
			),
			'appearance' => array(
				'layout'       => 'bar',
				'button_style' => 'equal',
				'uppercase'    => 1,
				'primary'      => '#1f821f',
				'primary_text' => '#ffffff',
				'background'   => '#ffffff',
				'text'         => '#1d2327',
				'radius'       => 10,
				'font_size'    => 15,
				'max_width'    => 1140,
				'shadow'       => 1,
				'z_index'      => 99999,
				'icon_style'   => 'colorful',
			),
			'categories' => array(
				'necessary'   => array( 'label' => 'Necessary', 'description' => 'Needed for the website to work: the basket, checkout, security and remembering your cookie choices. They cannot be switched off.' ),
				'preferences' => array( 'label' => 'Preferences', 'description' => 'Remember choices you make (such as your region or recently viewed items) to give you a more personal experience.', 'show' => 1 ),
				'statistics'  => array( 'label' => 'Statistics', 'description' => 'Help us understand how visitors use the website, so we can improve it. The information is collected in aggregate.', 'show' => 1 ),
				'marketing'   => array( 'label' => 'Marketing', 'description' => 'Used to measure our advertising and to show you relevant adverts on other websites. Also set by embedded videos and maps.', 'show' => 1 ),
			),
			'services'   => $services,
			'gcm'        => array(
				'enabled'      => 1,
				'gtm_id'       => '',
				'gtm_load'     => 'plugin', // plugin | external | none.
				'mode'         => 'advanced', // advanced | basic.
				'wait'         => 500,
				'redaction'    => 1,
				'passthrough'  => 0,
				'functionality' => 'preferences', // preferences | granted.
			),
			'blocking'   => array(
				'auto_block'   => 1,
				'iframes'      => 1,
				'woo_attribution' => 1,
				'exclude'      => '',
			),
			'scan'       => array(
				'urls'      => '',
				'last_scan' => 0,
			),
		);
	}

	/**
	 * Settings over defaults (deep for the fixed sections).
	 *
	 * @return array
	 */
	public static function all() {
		static $cache = null;
		if ( null !== $cache ) { return $cache; }
		$saved = (array) get_option( self::OPTION, array() );
		$d     = self::defaults();
		foreach ( $d as $section => $values ) {
			$s = (array) ( $saved[ $section ] ?? array() );
			if ( 'services' === $section ) {
				// Saved services (presets + custom) replace the defaults wholesale once saved.
				$d[ $section ] = $s ? $s : $values;
			} elseif ( 'categories' === $section ) {
				foreach ( $values as $k => $v ) { $d[ $section ][ $k ] = wp_parse_args( (array) ( $s[ $k ] ?? array() ), $v ); }
			} else {
				$d[ $section ] = wp_parse_args( $s, $values );
			}
		}
		return $cache = $d;
	}

	/**
	 * One section, or one value of a section.
	 *
	 * @param string      $section Section.
	 * @param string|null $key     Key.
	 * @return mixed
	 */
	public static function get( $section, $key = null ) {
		$all = self::all();
		$sec = $all[ $section ] ?? array();
		return null === $key ? $sec : ( $sec[ $key ] ?? null );
	}

	/**
	 * Enabled services, each with id and lists split.
	 *
	 * @return array[]
	 */
	public static function services() {
		$out = array();
		foreach ( (array) self::get( 'services' ) as $id => $s ) {
			if ( empty( $s['enabled'] ) ) { continue; }
			$s['id']       = $id;
			$s['patterns'] = self::lines( $s['patterns'] ?? '' );
			$s['cookies']  = self::lines( $s['cookies'] ?? '' );
			$out[ $id ]    = $s;
		}
		return $out;
	}

	/**
	 * Categories that are shown (necessary always).
	 *
	 * @return array key => category
	 */
	public static function categories() {
		$out = array();
		foreach ( (array) self::get( 'categories' ) as $k => $c ) {
			if ( 'necessary' === $k || ! empty( $c['show'] ) ) { $out[ $k ] = $c; }
		}
		return $out;
	}

	/**
	 * The cookie policy page ID (setting, or a page whose slug starts with "cookie-policy").
	 *
	 * @return int
	 */
	public static function policy_page() {
		$id = (int) self::get( 'general', 'policy_page' );
		if ( $id && 'publish' === get_post_status( $id ) ) { return $id; }
		$p = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'name' => 'cookie-policy-uk', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( ! $p ) {
			$p = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'name' => 'cookie-policy', 'numberposts' => 1, 'fields' => 'ids' ) );
		}
		return $p ? (int) $p[0] : 0;
	}

	/**
	 * Lines of a text value.
	 *
	 * @param string $text Text.
	 * @return string[]
	 */
	public static function lines( $text ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
	}

	/**
	 * Save one section from a form (other sections untouched).
	 *
	 * @param string $section Section.
	 * @param array  $input   Posted values.
	 */
	public static function save_section( $section, $input ) {
		$saved = (array) get_option( self::OPTION, array() );
		$d     = self::defaults();
		if ( ! isset( $d[ $section ] ) ) { return; }
		$saved[ $section ] = self::clean( $section, $input, $d[ $section ] );
		update_option( self::OPTION, $saved );
	}

	/**
	 * Replace everything (import).
	 *
	 * @param array $all Settings.
	 */
	public static function import( $all ) {
		$d   = self::defaults();
		$out = array();
		foreach ( $d as $section => $def ) {
			if ( isset( $all[ $section ] ) && is_array( $all[ $section ] ) ) {
				$out[ $section ] = self::clean( $section, $all[ $section ], $def );
			}
		}
		update_option( self::OPTION, $out );
	}

	/**
	 * Clean a section.
	 *
	 * @param string $section Section.
	 * @param array  $in      Input.
	 * @param array  $def     Defaults.
	 * @return array
	 */
	private static function clean( $section, $in, $def ) {
		$in = (array) $in;
		switch ( $section ) {
			case 'services':
				$out = array();
				foreach ( $in as $id => $s ) {
					$s  = (array) $s;
					$id = sanitize_key( $id );
					if ( '' === $id || empty( $s['name'] ) ) { continue; }
					$cat = sanitize_key( $s['category'] ?? 'marketing' );
					$out[ $id ] = array(
						'enabled'  => empty( $s['enabled'] ) ? 0 : 1,
						'name'     => sanitize_text_field( $s['name'] ),
						'category' => in_array( $cat, EFCC_Library::category_keys(), true ) ? $cat : 'marketing',
						'provider' => sanitize_text_field( $s['provider'] ?? '' ),
						'purpose'  => sanitize_textarea_field( $s['purpose'] ?? '' ),
						'privacy'  => esc_url_raw( $s['privacy'] ?? '' ),
						'patterns' => sanitize_textarea_field( $s['patterns'] ?? '' ),
						'cookies'  => sanitize_textarea_field( $s['cookies'] ?? '' ),
					);
				}
				return $out;
			case 'categories':
				$out = array();
				foreach ( $def as $k => $c ) {
					$x         = (array) ( $in[ $k ] ?? array() );
					$out[ $k ] = array(
						'label'       => sanitize_text_field( $x['label'] ?? $c['label'] ),
						'description' => sanitize_textarea_field( $x['description'] ?? $c['description'] ),
					);
					if ( 'necessary' !== $k ) { $out[ $k ]['show'] = empty( $x['show'] ) ? 0 : 1; }
				}
				return $out;
		}
		$out = array();
		foreach ( $def as $key => $default ) {
			$v = $in[ $key ] ?? null;
			if ( is_int( $default ) ) {
				$out[ $key ] = null === $v ? 0 : (int) $v;
			} elseif ( in_array( $key, array( 'primary', 'primary_text', 'background', 'text' ), true ) ) {
				$out[ $key ] = sanitize_hex_color( (string) $v ) ?: $default;
			} elseif ( 'contact_email' === $key ) {
				$out[ $key ] = sanitize_email( (string) $v );
			} elseif ( in_array( $key, array( 'message', 'modal_intro', 'placeholder', 'urls', 'exclude', 'hide_banner_on' ), true ) ) {
				$out[ $key ] = sanitize_textarea_field( (string) $v );
			} else {
				$out[ $key ] = sanitize_text_field( (string) ( $v ?? $default ) );
			}
		}
		// Bounds and choices.
		$choice = array(
			'layout'            => array( 'bar', 'box-left', 'box-right', 'center' ),
			'button_style'      => array( 'accent', 'equal' ),
			'icon_style'        => array( 'colorful', 'brand', 'mono' ),
			'floating_position' => array( 'left', 'right' ),
			'gtm_load'          => array( 'plugin', 'external', 'none' ),
			'mode'              => array( 'advanced', 'basic' ),
			'functionality'     => array( 'preferences', 'granted' ),
		);
		foreach ( $choice as $k => $allowed ) {
			if ( isset( $out[ $k ] ) && ! in_array( $out[ $k ], $allowed, true ) ) { $out[ $k ] = $def[ $k ]; }
		}
		if ( isset( $out['gtm_id'] ) ) {
			$out['gtm_id'] = preg_match( '/^GTM-[A-Z0-9]+$/', strtoupper( trim( $out['gtm_id'] ) ) ) ? strtoupper( trim( $out['gtm_id'] ) ) : '';
		}
		if ( isset( $out['consent_days'] ) ) { $out['consent_days'] = max( 1, min( 395, $out['consent_days'] ) ); }
		if ( isset( $out['log_days'] ) ) { $out['log_days'] = max( 30, min( 3650, $out['log_days'] ) ); }
		if ( isset( $out['version'] ) ) { $out['version'] = max( 1, $out['version'] ); }
		if ( isset( $out['wait'] ) ) { $out['wait'] = max( 0, min( 5000, $out['wait'] ) ); }
		if ( isset( $out['radius'] ) ) { $out['radius'] = max( 0, min( 40, $out['radius'] ) ); }
		if ( isset( $out['font_size'] ) ) { $out['font_size'] = max( 11, min( 22, $out['font_size'] ) ); }
		if ( isset( $out['max_width'] ) ) { $out['max_width'] = max( 320, min( 2000, $out['max_width'] ) ); }
		return $out;
	}
}
