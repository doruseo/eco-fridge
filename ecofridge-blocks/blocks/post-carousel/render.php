<?php
/**
 * Blog carousel block — the latest articles (optionally of one blog category)
 * as the blog's own cards in the theme's carousel, then a button to the blog.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_args = array(
	'numberposts'         => max( 3, min( 12, (int) $attributes['count'] ) ),
	'ignore_sticky_posts' => true,
);
if ( ! empty( $attributes['category'] ) ) {
	$efb_args['category'] = (int) $attributes['category'];
}
$efb_posts = get_posts( $efb_args );
if ( ! $efb_posts ) {
	return;
}

$GLOBALS['ef_cc_used'] = true; // the theme prints the carousel script in the footer.
$efb_blog              = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/blog/' );
if ( ! empty( $attributes['category'] ) ) {
	$efb_cat_link = get_term_link( (int) $attributes['category'], 'category' );
	if ( ! is_wp_error( $efb_cat_link ) ) {
		$efb_blog = $efb_cat_link;
	}
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ef-blog-carousel-wrap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="ef-cat-carousel ef-blog-carousel">
		<button type="button" class="ef-cc-arrow ef-cc-prev" aria-label="<?php esc_attr_e( 'Previous articles', 'ecofridge-blocks' ); ?>">&lsaquo;</button>
		<div class="ef-cc-track" role="list">
			<?php foreach ( $efb_posts as $efb_post ) : ?>
				<div class="ef-cc-slide" role="listitem">
					<?php
					if ( function_exists( 'ef_blog_card' ) ) {
						echo ef_blog_card( $efb_post, '', 'h3' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the theme helper.
					} else {
						echo '<article class="ef-card"><h3 class="ef-card__title"><a href="' . esc_url( get_permalink( $efb_post ) ) . '">' . esc_html( get_the_title( $efb_post ) ) . '</a></h3></article>';
					}
					?>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="ef-cc-arrow ef-cc-next" aria-label="<?php esc_attr_e( 'More articles', 'ecofridge-blocks' ); ?>">&rsaquo;</button>
	</div>
	<?php if ( '' !== trim( (string) $attributes['buttonText'] ) ) : ?>
		<p class="ef-blog-carousel__all"><a class="ef-btn" href="<?php echo esc_url( $efb_blog ); ?>"><?php echo esc_html( $attributes['buttonText'] ); ?></a></p>
	<?php endif; ?>
</div>
