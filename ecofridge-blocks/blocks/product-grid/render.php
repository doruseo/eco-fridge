<?php
/**
 * Product grid block.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

$efb_args = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'posts_per_page'      => max( 1, min( 48, (int) $attributes['count'] ) ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		),
	),
);
$efb_ids  = array_filter( array_map( 'absint', (array) $attributes['ids'] ) );
if ( $efb_ids ) {
	$efb_args['post__in'] = $efb_ids;
	$efb_args['orderby']  = 'post__in';
} else {
	if ( $attributes['category'] ) {
		$efb_args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => (int) $attributes['category'],
		);
	}
	if ( $attributes['market'] && taxonomy_exists( 'market' ) ) {
		$efb_args['tax_query'][] = array(
			'taxonomy' => 'market',
			'field'    => 'term_id',
			'terms'    => (int) $attributes['market'],
		);
	}
	switch ( $attributes['orderby'] ) {
		case 'date':
			$efb_args['orderby'] = 'date';
			$efb_args['order']   = 'DESC';
			break;
		case 'title':
			$efb_args['orderby'] = 'title';
			$efb_args['order']   = 'ASC';
			break;
		case 'price':
			$efb_args['orderby']  = 'meta_value_num';
			$efb_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$efb_args['order']    = 'ASC';
			break;
		case 'rand':
			$efb_args['orderby'] = 'rand';
			break;
		default:
			$efb_args['orderby'] = array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			);
	}
}

$efb_query = new WP_Query( $efb_args );
if ( ! $efb_query->have_posts() ) {
	return;
}

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-products',
		'style' => '--efb-cols:' . max( 1, min( 6, (int) $attributes['columns'] ) ),
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	foreach ( $efb_query->posts as $efb_post ) :
		$efb_link = get_permalink( $efb_post );
		?>
		<div class="efb-product">
			<a class="efb-product__image" href="<?php echo esc_url( $efb_link ); ?>">
				<?php echo get_the_post_thumbnail( $efb_post, 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); ?>
			</a>
			<h3 class="efb-product__title"><a href="<?php echo esc_url( $efb_link ); ?>"><?php echo esc_html( get_the_title( $efb_post ) ); ?></a></h3>
			<?php if ( $attributes['showPrice'] ) : ?>
				<?php echo efb_from_price_html( $efb_post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<?php if ( $attributes['showButton'] ) : ?>
				<a class="efb-button efb-product__button" href="<?php echo esc_url( $efb_link ); ?>"><?php esc_html_e( 'View product', 'ecofridge-blocks' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
