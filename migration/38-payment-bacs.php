<?php
/**
 * 38 — Payment: "Direct bank transfer" enabled again, 1:1 with the original site.
 *
 * The checkout had no payment method at all ("There are no payment methods
 * available"). The original site offered Direct bank transfer (BACS) and card
 * payments through the Lloyds Cardnet premium plugin. BACS settings (title,
 * description, instructions) and its bank account list are copied from the
 * legacy DB; nothing is printed. Card payments need the Cardnet plugin + the
 * merchant credentials — a launch task for the client.
 *
 * Idempotent. Run via source-dump/_run.php (needs the ef-legacy-db container).
 *
 * @package EcoFridgeMigration
 */

$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
if ( ! $db ) {
	WP_CLI::error( 'Legacy DB not reachable (container ef-legacy-db).' );
}
mysqli_set_charset( $db, 'utf8mb4' );

$get = function ( $name ) use ( $db ) {
	$res = mysqli_query( $db, "SELECT option_value FROM 43lz2ypq8_options WHERE option_name = '" . mysqli_real_escape_string( $db, $name ) . "'" );
	$row = $res ? mysqli_fetch_row( $res ) : null;
	return $row ? maybe_unserialize( $row[0] ) : null;
};

$settings = $get( 'woocommerce_bacs_settings' );
$accounts = $get( 'woocommerce_bacs_accounts' );
mysqli_close( $db );

if ( ! is_array( $settings ) ) {
	WP_CLI::error( 'woocommerce_bacs_settings not found in the legacy DB.' );
}
$settings['enabled'] = 'yes';
update_option( 'woocommerce_bacs_settings', $settings );
if ( is_array( $accounts ) ) {
	update_option( 'woocommerce_bacs_accounts', $accounts );
}

// Bank transfer first in the checkout's payment list, as on the original.
$order = (array) get_option( 'woocommerce_gateway_order', array() );
$order = array( 'bacs' => 0 ) + $order;
update_option( 'woocommerce_gateway_order', $order );

WP_CLI::success( sprintf( 'Direct bank transfer enabled (%d bank account(s) copied).', is_array( $accounts ) ? count( $accounts ) : 0 ) );
