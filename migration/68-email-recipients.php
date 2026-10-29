<?php
/**
 * 68 — Who receives the website's emails (Doru, 2026-10-28).
 *
 *  - Contact form enquiries (efb/contact-form on every page that has it)
 *    → mads@eco-fridge.co.uk, kass@eco-fridge.co.uk.
 *  - Every order email meant for the company (new, cancelled, failed,
 *    shipped, completed… – each WooCommerce email that has a recipient
 *    field) → mads@eco-fridge.co.uk, orders@eco-fridge.co.uk.
 *  - Sender "ECO-Fridge Ltd" <noreply@eco-fridge.co.uk>; replies to the
 *    customer emails (WooCommerce Reply-to) → mads@eco-fridge.co.uk.
 *    Company notifications keep the customer as reply-to, and contact form
 *    emails reply to the person who sent the enquiry.
 *
 * Safe to run again. Run: wp eval-file migration/68-email-recipients.php
 *
 * @package EcoFridgeMigration
 */

$enquiries = 'mads@eco-fridge.co.uk, kass@eco-fridge.co.uk';
$orders    = 'mads@eco-fridge.co.uk, orders@eco-fridge.co.uk';
$reply_to  = 'mads@eco-fridge.co.uk';

// Contact forms.
global $wpdb;
$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','private') AND post_content LIKE '%<!-- wp:efb/contact-form%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
foreach ( $ids as $id ) {
	$content = get_post_field( 'post_content', $id );
	$blocks  = parse_blocks( $content );
	$walk    = static function ( array &$list ) use ( &$walk, $enquiries ) {
		foreach ( $list as &$b ) {
			if ( 'efb/contact-form' === $b['blockName'] ) {
				$b['attrs']['recipient'] = $enquiries;
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( $blocks );
	$new = serialize_blocks( $blocks );
	if ( $new !== $content ) {
		$wpdb->update( $wpdb->posts, array( 'post_content' => $new ), array( 'ID' => $id ) );
		clean_post_cache( $id );
		WP_CLI::log( "Contact form on #$id (" . get_the_title( $id ) . ") → $enquiries" );
	}
}

// Company order emails: every WooCommerce email with a recipient setting.
foreach ( WC()->mailer()->get_emails() as $email ) {
	if ( $email->is_customer_email() || ! array_key_exists( 'recipient', $email->form_fields ) ) {
		continue;
	}
	// Security notices for the site admin, not about orders: leave them alone.
	if ( 'admin_payment_gateway_enabled' === $email->id ) {
		continue;
	}
	$key              = 'woocommerce_' . $email->id . '_settings';
	$opts             = (array) get_option( $key, array() );
	$opts['recipient'] = $orders;
	update_option( $key, $opts );
	WP_CLI::log( sprintf( '%-28s %s → %s', $email->id, $email->get_title(), $orders ) );
}

// Sender and Reply-to.
update_option( 'woocommerce_email_from_name', 'ECO-Fridge Ltd' );
update_option( 'woocommerce_email_from_address', 'noreply@eco-fridge.co.uk' );
update_option( 'woocommerce_email_reply_to_enabled', 'yes' );
update_option( 'woocommerce_email_reply_to_name', 'ECO-Fridge Ltd' );
update_option( 'woocommerce_email_reply_to_address', $reply_to );
WP_CLI::success( "Sender noreply@eco-fridge.co.uk, customer replies → $reply_to." );
