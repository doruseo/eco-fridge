<?php
/**
 * Call-to-action banner ("Call us today - 01280 811411").
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_bg  = $attributes['bgImageId'] ? wp_get_attachment_image_url( (int) $attributes['bgImageId'], 'full' ) : $attributes['bgImageUrl'];
$efb_tel = preg_replace( '/[^0-9+]/', '', (string) $attributes['phone'] );
if ( 0 === strpos( $efb_tel, '0' ) ) {
	$efb_tel = '+44' . substr( $efb_tel, 1 );
}

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-cta' . ( $efb_bg ? ' has-bg-image' : '' ),
		'style' => efb_style_vars(
			array(
				'--efb-bg'       => sanitize_text_field( $attributes['bgColor'] ),
				'--efb-bg-image' => $efb_bg ? 'url("' . esc_url_raw( $efb_bg ) . '")' : '',
			)
		),
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="efb-cta__inner">
		<p class="efb-cta__text">
			<?php echo esc_html( $attributes['text'] ); ?>
			<?php if ( $attributes['phone'] ) : ?>
				<a class="efb-cta__phone" href="tel:<?php echo esc_attr( $efb_tel ); ?>"><?php echo esc_html( $attributes['phone'] ); ?></a>
			<?php endif; ?>
		</p>
		<?php if ( $attributes['buttonText'] && $attributes['buttonUrl'] ) : ?>
			<a class="efb-button efb-cta__button" href="<?php echo esc_url( $attributes['buttonUrl'] ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a>
		<?php endif; ?>
	</div>
</div>
