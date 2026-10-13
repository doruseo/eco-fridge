<?php
/**
 * Range carousel block — product category cards (whole image + name) in the
 * theme's category carousel (big round arrows; script printed by the theme).
 *
 * Each card: a category, an optional label (the category name otherwise) and
 * an optional image (the category's own image otherwise).
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_cards = array();
foreach ( (array) $attributes['items'] as $efb_item ) {
	$efb_term = get_term( (int) ( $efb_item['categoryId'] ?? 0 ), 'product_cat' );
	if ( ! $efb_term || is_wp_error( $efb_term ) ) {
		continue;
	}
	$efb_link = get_term_link( $efb_term );
	$efb_img  = '';
	if ( ! empty( $efb_item['imageId'] ) ) {
		$efb_img = (string) wp_get_attachment_image_url( (int) $efb_item['imageId'], 'woocommerce_single' );
	} elseif ( ! empty( $efb_item['imageUrl'] ) ) {
		$efb_img = $efb_item['imageUrl'];
	} elseif ( function_exists( 'ef_term_image_url' ) ) {
		$efb_img = ef_term_image_url( $efb_term->term_id, 'woocommerce_single' );
	} else {
		$efb_img = efb_term_image_url( $efb_term->term_id );
	}
	$efb_cards[] = array(
		'title' => '' !== trim( (string) ( $efb_item['title'] ?? '' ) ) ? $efb_item['title'] : $efb_term->name,
		'link'  => is_wp_error( $efb_link ) ? '#' : $efb_link,
		'img'   => $efb_img,
	);
}
if ( ! $efb_cards ) {
	return;
}

$GLOBALS['ef_cc_used'] = true; // the theme prints the carousel script in the footer.
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ef-cat-carousel-wrap ef-home-cats' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	if ( function_exists( 'ef_render_category_carousel' ) ) {
		ef_render_category_carousel( $efb_cards );
	} else {
		echo '<div class="ef-cat-carousel"><div class="ef-cc-track" role="list">';
		foreach ( $efb_cards as $efb_card ) {
			echo '<a class="ef-cc-card" role="listitem" href="' . esc_url( $efb_card['link'] ) . '"><span class="ef-cc-img">' . ( $efb_card['img'] ? '<img src="' . esc_url( $efb_card['img'] ) . '" alt="' . esc_attr( $efb_card['title'] ) . '" loading="lazy" />' : '' ) . '</span><span class="ef-cc-name">' . esc_html( $efb_card['title'] ) . '</span></a>';
		}
		echo '</div></div>';
	}
	?>
</div>
