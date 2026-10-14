<?php
/**
 * "Abandoned cart" reminder — plain text (EFB_Email_Abandoned_Cart).
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo ( $first_name ? sprintf( 'Hi %s,', esc_html( $first_name ) ) : 'Hi,' ) . "\n\n";
echo $second
	? "Your cart is still saved – the products below are ready for you whenever you are.\n\n"
	: "You left some products in your cart. We have saved them for you, so you can pick up where you left off:\n\n";

foreach ( $items as $efb_item ) {
	echo esc_html( $efb_item['name'] ) . ' × ' . (int) $efb_item['quantity'] . ' – ' . esc_html( wp_strip_all_tags( html_entity_decode( $efb_item['price'] ) ) ) . "\n";
	if ( $efb_item['extras'] ) {
		echo '  + ' . esc_html( implode( ', ', $efb_item['extras'] ) ) . "\n";
	}
}
echo "\nCart total (inc. VAT): " . esc_html( wp_strip_all_tags( html_entity_decode( $total ) ) ) . "\n\n";
echo 'Complete your order: ' . esc_url_raw( $restore_url ) . "\n\n";
echo "Prices are trade prices shown excluding VAT; delivery to UK mainland addresses is free.\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}
echo "----------------------------------------\n\n";
echo 'You are receiving this because you added products to your cart on our website. Stop cart reminders: ' . esc_url_raw( $unsubscribe_url ) . "\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
