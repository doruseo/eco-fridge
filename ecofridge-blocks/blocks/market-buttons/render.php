<?php
/**
 * Market buttons block — one button per market (Products → Markets), linking
 * to its landing page (/markets/<slug>/, or /market/<slug>/ without one).
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'market' ) ) {
	return;
}
$efb_terms = get_terms(
	array(
		'taxonomy'   => 'market',
		'hide_empty' => true,
		'orderby'    => 'name',
		'exclude'    => array_filter( array_map( 'absint', (array) $attributes['exclude'] ) ),
	)
);
if ( is_wp_error( $efb_terms ) || ! $efb_terms ) {
	return;
}
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'ef-home-markets' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php esc_attr_e( 'Refrigeration by market', 'ecofridge-blocks' ); ?>">
	<ul>
		<?php
		foreach ( $efb_terms as $efb_term ) :
			$efb_link = get_term_link( $efb_term );
			if ( is_wp_error( $efb_link ) ) {
				continue;
			}
			?>
			<li><a class="ef-pill" href="<?php echo esc_url( $efb_link ); ?>"><?php echo esc_html( $efb_term->name ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</nav>
