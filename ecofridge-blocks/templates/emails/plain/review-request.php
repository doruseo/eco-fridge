<?php
/**
 * "Review request" — plain text (EFB_Email_Review_Request).
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo ( $first_name ? sprintf( 'Hi %s,', esc_html( $first_name ) ) : 'Hi,' ) . "\n\n";
echo esc_html( sprintf( 'Thank you for your order #%s. We hope everything arrived safely and is working well. Would you take a minute to tell other buyers what you think? Your review helps them choose – and helps us improve.', $order_number ) ) . "\n\n";

foreach ( $items as $efb_item ) {
	echo esc_html( $efb_item['name'] ) . "\nWrite a review: " . esc_url_raw( $efb_item['link'] ) . "\n\n";
}
echo "No account or password needed – the link is personal to your order. Reviews are checked by our team before they appear, and cannot contain links.\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}
echo "----------------------------------------\n\n";
echo "You are receiving this because you bought from our website. Don't ask me for reviews: " . esc_url_raw( $optout_url ) . "\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
