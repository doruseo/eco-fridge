<?php
/**
 * "Review request" — HTML (EFB_Email_Review_Request).
 *
 * @var string   $email_heading
 * @var string   $first_name
 * @var string   $order_number
 * @var array[]  $items        name, image, link (review link)
 * @var string   $optout_url
 * @var string   $additional_content
 * @var WC_Email $email
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_color = get_option( 'woocommerce_email_base_color', '#1f821f' );

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo $first_name ? esc_html( sprintf( 'Hi %s,', $first_name ) ) : 'Hi,'; ?></p>
<p><?php echo esc_html( sprintf( 'Thank you for your order #%s. We hope everything arrived safely and is working well. Would you take a minute to tell other buyers what you think? Your review helps them choose – and helps us improve.', $order_number ) ); ?></p>

<table cellspacing="0" cellpadding="8" border="0" width="100%" style="border-collapse:collapse;margin:0 0 16px;">
	<?php foreach ( $items as $efb_item ) : ?>
		<tr style="border-bottom:1px solid #e5e5e5;">
			<td width="72" style="vertical-align:middle;">
				<?php if ( $efb_item['image'] ) : ?>
					<img src="<?php echo esc_url( $efb_item['image'] ); ?>" alt="" width="64" style="display:block;width:64px;height:auto;border:0;">
				<?php endif; ?>
			</td>
			<td style="vertical-align:middle;font-weight:bold;"><?php echo esc_html( $efb_item['name'] ); ?></td>
			<td style="vertical-align:middle;text-align:right;white-space:nowrap;">
				<a href="<?php echo esc_url( $efb_item['link'] ); ?>" style="display:inline-block;padding:10px 18px;background:<?php echo esc_attr( $efb_color ); ?>;color:#ffffff;font-weight:bold;text-decoration:none;border-radius:6px;">Write a review</a>
			</td>
		</tr>
	<?php endforeach; ?>
</table>

<p style="font-size:13px;">No account or password needed – the link is personal to your order. Reviews are checked by our team before they appear, and cannot contain links.</p>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>
<p style="font-size:12px;color:#888888;">You are receiving this because you bought from our website. <a href="<?php echo esc_url( $optout_url ); ?>" style="color:#888888;">Don't ask me for reviews</a>.</p>
<?php
do_action( 'woocommerce_email_footer', $email );
