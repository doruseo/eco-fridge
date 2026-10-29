<?php
/**
 * Consent log: proof of consent (UK GDPR accountability). Each choice is
 * stored with its consent ID, time, method, categories and services, policy
 * version, a truncated IP address and the page. Old records are purged daily.
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Consent records.
 */
class EFCC_Log {

	const DB_VERSION = 1;
	const CRON       = 'efcc_purge_log';

	/** Hooks. */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( self::CRON, array( __CLASS__, 'purge' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_install' ) );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'efcc_consent_log';
	}

	/** Create / upgrade the table and schedule the purge. */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t       = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE $t (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			consent_id varchar(40) NOT NULL,
			created datetime NOT NULL,
			method varchar(20) NOT NULL,
			categories varchar(255) NOT NULL,
			services text NOT NULL,
			version smallint(5) unsigned NOT NULL DEFAULT 1,
			ip varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			url varchar(255) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY consent_id (consent_id),
			KEY created (created)
		) $charset;" );
		update_option( 'efcc_db_version', self::DB_VERSION, false );
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
	}

	/** Install when the plugin files were updated without re-activation. */
	public static function maybe_install() {
		if ( (int) get_option( 'efcc_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/** Deactivation: stop the purge. */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	/** REST route the banner posts to. */
	public static function routes() {
		register_rest_route( 'efcc/v1', '/consent', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_consent' ),
			'permission_callback' => '__return_true', // Public by design: visitors record their own choice.
		) );
	}

	/**
	 * Truncated client IP (last octet / last 80 bits zeroed).
	 *
	 * @return string
	 */
	public static function anon_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) )[0] );
		}
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		}
		return function_exists( 'wp_privacy_anonymize_ip' ) ? wp_privacy_anonymize_ip( $ip ) : '';
	}

	/**
	 * Store a consent record.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function rest_consent( $req ) {
		if ( ! EFCC_Settings::get( 'general', 'log' ) ) {
			return new WP_REST_Response( array( 'ok' => true, 'logged' => false ), 200 );
		}
		$p  = (array) $req->get_json_params();
		$id = strtolower( (string) ( $p['id'] ?? '' ) );
		if ( ! preg_match( '/^[a-f0-9-]{16,40}$/', $id ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}
		// At most 30 records an hour from one (truncated) address.
		$ip  = self::anon_ip();
		$key = 'efcc_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 30 ) {
			return new WP_REST_Response( array( 'ok' => false ), 429 );
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );

		$cats = array();
		foreach ( (array) ( $p['cats'] ?? array() ) as $k => $v ) {
			if ( in_array( $k, EFCC_Library::category_keys(), true ) && 'necessary' !== $k ) { $cats[ $k ] = $v ? 1 : 0; }
		}
		$svc = array();
		foreach ( (array) ( $p['svc'] ?? array() ) as $k => $v ) {
			$k = sanitize_key( $k );
			if ( '' !== $k ) { $svc[ $k ] = $v ? 1 : 0; }
		}
		$method = in_array( $p['method'] ?? '', array( 'accept', 'reject', 'custom', 'gpc' ), true ) ? $p['method'] : 'custom';

		global $wpdb;
		$wpdb->insert( self::table(), array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			'consent_id' => $id,
			'created'    => current_time( 'mysql', true ),
			'method'     => $method,
			'categories' => wp_json_encode( $cats ),
			'services'   => wp_json_encode( $svc ),
			'version'    => (int) ( $p['v'] ?? 1 ),
			'ip'         => $ip,
			'user_agent' => substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, 255 ),
			'url'        => substr( esc_url_raw( (string) ( $p['url'] ?? '' ) ), 0, 255 ),
			'user_id'    => get_current_user_id(),
		) );
		return new WP_REST_Response( array( 'ok' => true, 'logged' => true ), 200 );
	}

	/** Delete records older than the retention period. */
	public static function purge() {
		global $wpdb;
		$days = (int) EFCC_Settings::get( 'general', 'log_days' );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created < %s', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Records for the admin list.
	 *
	 * @param int    $page     Page.
	 * @param int    $per      Per page.
	 * @param string $search   Consent ID search.
	 * @return array{rows:array,total:int}
	 */
	public static function query( $page = 1, $per = 50, $search = '' ) {
		global $wpdb;
		$t     = self::table();
		$where = '1=1';
		$args  = array();
		if ( '' !== $search ) {
			$where .= ' AND consent_id LIKE %s';
			$args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE $where", $args ) : "SELECT COUNT(*) FROM $t" );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $per, max( 0, ( $page - 1 ) * $per ) ) ) ), ARRAY_A );
		// phpcs:enable
		return array( 'rows' => (array) $rows, 'total' => $total );
	}

	/**
	 * Choices in the last N days by method.
	 *
	 * @param int $days Days.
	 * @return array method => count
	 */
	public static function stats( $days = 30 ) {
		global $wpdb;
		$t    = self::table();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT method, COUNT(*) n FROM $t WHERE created >= %s GROUP BY method", gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$out  = array( 'accept' => 0, 'reject' => 0, 'custom' => 0, 'gpc' => 0 );
		foreach ( (array) $rows as $r ) { $out[ $r->method ] = (int) $r->n; }
		return $out;
	}
}
