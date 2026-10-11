<?php
/**
 * Logo grid block.
 *
 * Logo shape: { id, url, alt, link }.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_logos = array_values( array_filter( (array) $attributes['logos'], 'is_array' ) );
if ( ! $efb_logos ) {
	return;
}

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-logos' . ( $attributes['grayscale'] ? ' is-grayscale' : '' ) . ( $attributes['mobileCarousel'] ? ' is-mobile-carousel' : '' ),
		'style' => '--efb-cols:' . max( 2, min( 12, (int) $attributes['columns'] ) ),
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( '' !== $attributes['heading'] ) : ?>
		<p class="efb-logos__heading"><?php echo esc_html( $attributes['heading'] ); ?></p>
	<?php endif; ?>
	<ul class="efb-logos__list">
		<?php foreach ( $efb_logos as $efb_logo ) : ?>
			<li class="efb-logos__item">
				<?php
				$efb_img = efb_image( $efb_logo['id'] ?? 0, $efb_logo['url'] ?? '', $efb_logo['alt'] ?? '', 'medium' );
				if ( ! empty( $efb_logo['link'] ) ) {
					echo '<a href="' . esc_url( $efb_logo['link'] ) . '" target="_blank" rel="noopener">' . $efb_img . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo $efb_img; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
