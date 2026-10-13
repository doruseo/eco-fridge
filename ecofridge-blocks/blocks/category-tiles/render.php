<?php
/**
 * Category tiles block — sub-categories of a parent (ordered by the `order`
 * term meta, 1:1 with the original site) or a hand-picked list of categories.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_items   = array_values( array_filter( (array) $attributes['items'], 'is_array' ) );
$efb_include = array_filter( array_map( 'absint', (array) $attributes['include'] ) );
$efb_terms   = array();
if ( $efb_include ) {
	$efb_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'include'    => $efb_include,
			'orderby'    => 'include',
			'hide_empty' => (bool) $attributes['hideEmpty'],
		)
	);
} elseif ( $attributes['parent'] ) {
	$efb_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => (int) $attributes['parent'],
			'hide_empty' => (bool) $attributes['hideEmpty'],
		)
	);
	if ( ! is_wp_error( $efb_terms ) ) {
		$efb_terms = efb_sort_terms_by_order( $efb_terms );
	}
}
if ( is_wp_error( $efb_terms ) ) {
	$efb_terms = array();
}
if ( ! $efb_terms && ! $efb_items ) {
	return;
}

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-tiles efb-tiles--' . sanitize_html_class( $attributes['style'] ),
		'style' => '--efb-cols:' . max( 1, min( 8, (int) $attributes['columns'] ) ),
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	if ( '' !== $attributes['heading'] ) {
		$efb_parent_link = $attributes['parent'] && $attributes['headingLink'] ? get_term_link( (int) $attributes['parent'], 'product_cat' ) : '';
		echo '<h2 class="efb-tiles__heading">';
		echo ( $efb_parent_link && ! is_wp_error( $efb_parent_link ) ) ? '<a href="' . esc_url( $efb_parent_link ) . '">' . esc_html( $attributes['heading'] ) . '</a>' : esc_html( $attributes['heading'] );
		echo '</h2>';
	}
	?>
	<div class="efb-tiles__grid">
		<?php foreach ( $efb_terms as $efb_term ) : ?>
			<?php
			$efb_link = get_term_link( $efb_term );
			if ( is_wp_error( $efb_link ) ) {
				continue;
			}
			$efb_img = efb_term_image_url( $efb_term->term_id );
			?>
			<a class="efb-tile" href="<?php echo esc_url( $efb_link ); ?>">
				<span class="efb-tile__image">
					<?php if ( $efb_img ) : ?>
						<img src="<?php echo esc_url( $efb_img ); ?>"<?php echo efb_img_size_attrs( $efb_img ); // phpcs:ignore WordPress.Security.EscapeOutput ?> alt="<?php echo esc_attr( $efb_term->name ); ?>" loading="lazy" decoding="async" />
					<?php endif; ?>
				</span>
				<span class="efb-tile__name"><?php echo esc_html( $efb_term->name ); ?></span>
			</a>
		<?php endforeach; ?>
		<?php foreach ( $efb_items as $efb_item ) : ?>
			<a class="efb-tile" href="<?php echo esc_url( $efb_item['url'] ?? '' ); ?>">
				<span class="efb-tile__image">
					<?php echo efb_image( $efb_item['imageId'] ?? 0, $efb_item['imageUrl'] ?? '', $efb_item['title'] ?? '', 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</span>
				<span class="efb-tile__name"><?php echo esc_html( $efb_item['title'] ?? '' ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
