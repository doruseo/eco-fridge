<?php
/**
 * Uninstall: remove the settings, the cookie register and the consent log.
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

global $wpdb;
foreach ( array( 'efcc_settings', 'efcc_cookies', 'efcc_cookies_updated', 'efcc_db_version' ) as $efcc_opt ) {
	delete_option( $efcc_opt );
}
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'efcc_consent_log' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
wp_clear_scheduled_hook( 'efcc_purge_log' );
