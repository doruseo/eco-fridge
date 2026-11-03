<?php
/**
 * Plugin Name: ECO-Fridge TLS fix for Migrate Guru
 * Description: Lets Migrate Guru reach its API from this server by pointing those requests at an up-to-date CA bundle. Temporary helper: deactivate and delete it once the migration is finished.
 * Version:     1.0.0
 * Author:      ECO-Fridge migration
 * License:     GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hosts whose certificates this old CA list cannot validate.
 *
 * Migrate Guru calls https://mg.blogvault.net, whose certificate chains to
 * GlobalSign Root R46. That root is missing from the CA bundle shipped with
 * this WordPress version, so cURL cannot build a trust path and reports
 * "error 60: unable to get local issuer certificate".
 */
function ecofridge_tls_fix_hosts() {
	return array( 'blogvault.net', 'migrateguru.com' );
}

/**
 * Tell whether a host is one of ours, matching the domain or any subdomain.
 *
 * @param string $host Host taken from the request URL.
 * @return bool
 */
function ecofridge_tls_fix_is_target_host( $host ) {
	if ( ! is_string( $host ) || '' === $host ) {
		return false;
	}

	foreach ( ecofridge_tls_fix_hosts() as $domain ) {
		if ( $host === $domain ) {
			return true;
		}

		$suffix = '.' . $domain;
		if ( strlen( $host ) > strlen( $suffix )
			&& substr( $host, - strlen( $suffix ) ) === $suffix ) {
			return true;
		}
	}

	return false;
}

/**
 * Swap the trust store for those hosts only.
 *
 * Certificate verification stays ON. Only the list of trusted roots changes,
 * and only for the hosts above: every other request keeps using WordPress's
 * own bundle.
 *
 * @param array  $args HTTP request arguments.
 * @param string $url  Request URL.
 * @return array
 */
function ecofridge_tls_fix_http_request_args( $args, $url ) {
	if ( ! ecofridge_tls_fix_is_target_host( wp_parse_url( $url, PHP_URL_HOST ) ) ) {
		return $args;
	}

	$bundle = __DIR__ . '/ca-bundle.crt';
	if ( is_readable( $bundle ) ) {
		$args['sslcertificates'] = $bundle;
	}

	return $args;
}
add_filter( 'http_request_args', 'ecofridge_tls_fix_http_request_args', 10, 2 );

/**
 * Show on the Plugins screen whether the API is now reachable.
 *
 * This runs one short request so the fix can be confirmed before starting a
 * migration, instead of finding out from a failed migration.
 */
function ecofridge_tls_fix_admin_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$response = wp_remote_get(
		'https://mg.blogvault.net/',
		array(
			'timeout'   => 10,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		printf(
			'<div class="notice notice-error"><p><strong>ECO-Fridge TLS fix:</strong> the Migrate Guru API is still unreachable: %s</p></div>',
			esc_html( $response->get_error_message() )
		);
		return;
	}

	printf(
		'<div class="notice notice-success"><p><strong>ECO-Fridge TLS fix:</strong> the Migrate Guru API answered (HTTP %s) with certificate verification on. The migration can be started.</p></div>',
		esc_html( (string) wp_remote_retrieve_response_code( $response ) )
	);
}
add_action( 'admin_notices', 'ecofridge_tls_fix_admin_notice' );
