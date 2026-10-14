<?php
/**
 * 58 — Security settings.
 *
 *  - Product reviews only from verified buyers: review spam (fake reviews with
 *    links) is no longer possible, and every review is from a real customer.
 *  - Pingbacks and trackbacks off for new content (existing content has none open).
 *  - A first comment / review from a new author waits for approval (kept on).
 *
 * The code side (sign-in limits, headers, XML-RPC, checkout rate limit, form
 * spam filters) is in ecofridge-blocks/includes/security.php. The settings
 * before this migration are kept in the option ef_settings_before_58.
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$settings = array(
	'woocommerce_review_rating_verification_required' => 'yes',
	'default_ping_status'                             => 'closed',
	'comment_previously_approved'                     => '1',
);

if ( false === get_option( 'ef_settings_before_58' ) ) {
	$before = array();
	foreach ( array_keys( $settings ) as $name ) {
		$before[ $name ] = get_option( $name );
	}
	update_option( 'ef_settings_before_58', $before, false );
}

foreach ( $settings as $name => $value ) {
	update_option( $name, $value );
	echo $name, ' = ', $value, "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
