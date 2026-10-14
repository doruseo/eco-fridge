<?php
/**
 * "Abandoned cart" reminder — HTML (EFB_Email_Abandoned_Cart).
 *
 * @var string   $email_heading
 * @var string   $first_name
 * @var array[]  $items           name, quantity, price, image, link, extras
 * @var string   $total           Cart total (formatted).
 * @var string   $restore_url
 * @var string   $unsubscribe_url
 * @var bool     $second          Second reminder.
 * @var string   $additional_content
 * @var WC_Email $email
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_color = get_option( 'woocommerce_email_base_color', '#1f821f' );

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo $first_name ? esc_html( sprintf( 'Hi %s,', $first_name ) ) : 'Hi,'; ?></p>
<p>
	<?php
	echo $second
		? 'Your cart is still saved – the products below are ready for you whenever you are.'
		: 'You left some products in your cart. We have saved them for you, so you can pick up where you left off:';
	?>
</p>

<table cellspacing="0" cellpadding="8" border="0" width="100%" style="border-collapse:collapse;margin:0 0 16px;">
	<?php foreach ( $items as $efb_item ) : ?>
		<tr style="border-bottom:1px solid #e5e5e5;">
			<td width="72" style="vertical-align:middle;">
				<?php if ( $efb_item['image'] ) : ?>
					<a href="<?php echo esc_url( $efb_item['link'] ); ?>"><img src="<?php echo esc_url( $efb_item['image'] ); ?>" alt="" width="64" style="display:block;width:64px;height:auto;border:0;"></a>
				<?php endif; ?>
			</td>
			<td style="vertical-align:middle;">
				<a href="<?php echo esc_url( $efb_item['link'] ); ?>" style="color:inherit;font-weight:bold;text-decoration:none;"><?php echo esc_html( $efb_item['name'] ); ?></a>
				<?php if ( $efb_item['extras'] ) : ?>
					<br><small><?php echo esc_html( '+ ' . implode( ', ', $efb_item['extras'] ) ); ?></small>
				<?php endif; ?>
			</td>
			<td style="vertical-align:middle;white-space:nowrap;">× <?php echo (int) $efb_item['quantity']; ?></td>
			<td style="vertical-align:middle;text-align:right;white-space:nowrap;"><?php echo wp_kses_post( $efb_item['price'] ); ?></td>
		</tr>
	<?php endforeach; ?>
	<tr>
		<td colspan="3" style="text-align:right;"><strong>Cart total (inc. VAT)</strong></td>
		<td style="text-align:right;white-space:nowrap;"><strong><?php echo wp_kses_post( $total ); ?></strong></td>
	</tr>
</table>

<p style="text-align:center;margin:24px 0;">
	<a href="<?php echo esc_url( $restore_url ); ?>" style="display:inline-block;padding:14px 28px;background:<?php echo esc_attr( $efb_color ); ?>;color:#ffffff;font-weight:bold;text-decoration:none;border-radius:6px;">Complete your order</a>
</p>
<p style="font-size:13px;">Prices are trade prices shown excluding VAT; delivery to UK mainland addresses is free.</p>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>
<p style="font-size:12px;color:#888888;">You are receiving this because you added products to your cart on our website. <a href="<?php echo esc_url( $unsubscribe_url ); ?>" style="color:#888888;">Don't send me cart reminders</a>.</p>
<?php
do_action( 'woocommerce_email_footer', $email );
