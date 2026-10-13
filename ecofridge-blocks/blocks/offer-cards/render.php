<?php
/**
 * Offer cards block.
 *
 * Item shape: { label, title, subtitle, url, imageId, imageUrl, imageAlt }.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_items = array_values( array_filter( (array) $attributes['items'], 'is_array' ) );
if ( ! $efb_items ) {
	return;
}

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-offers',
		'style' => efb_style_vars(
			array(
				'--efb-cols'  => max( 1, min( 6, (int) $attributes['columns'] ) ),
				'--efb-label' => sanitize_text_field( $attributes['labelColor'] ),
			)
		),
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php foreach ( $efb_items as $efb_item ) : ?>
		<?php $efb_url = $efb_item['url'] ?? ''; ?>
		<div class="efb-offer">
			<a class="efb-offer__link" href="<?php echo esc_url( $efb_url ); ?>">
				<?php if ( ! empty( $efb_item['label'] ) ) : ?>
					<span class="efb-offer__label"><?php echo esc_html( $efb_item['label'] ); ?></span>
				<?php endif; ?>
				<span class="efb-offer__image">
					<?php echo efb_image( $efb_item['imageId'] ?? 0, $efb_item['imageUrl'] ?? '', $efb_item['imageAlt'] ?? '', 'medium_large', array( 'loading' => 'eager', 'sizes' => '(max-width: 767px) 70vw, 300px' ) ); // offers open the page: no lazy loading, a ~300px file // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</span>
			</a>
			<div class="efb-offer__footer">
				<?php if ( ! empty( $efb_item['title'] ) ) : ?>
					<a class="efb-offer__title" href="<?php echo esc_url( $efb_url ); ?>"><?php echo esc_html( $efb_item['title'] ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $efb_item['subtitle'] ) ) : ?>
					<span class="efb-offer__subtitle"><?php echo esc_html( $efb_item['subtitle'] ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
