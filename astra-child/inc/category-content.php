<?php
/**
 * Product categories' long-form content (imported 1:1 from the original
 * eco-fridge Toolset field `range-content-2`, stored in the `_ef_range_content` term meta).
 *
 *  - render: BELOW the product grid on category archives (as on the original site);
 *    on the commercial categories the ECO-Fridge Blocks landing sections render it
 *  - edit: a WYSIWYG box on the category edit screen (Products → Categories)
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Display the category content below the product grid.
 */
add_action( 'woocommerce_after_shop_loop', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	// Commercial categories: the ECO-Fridge Blocks landing sections show this text
	// as their "Long description" section, in the order chosen on the category.
	if ( function_exists( 'efb_landing_applies' ) && efb_landing_applies( $term->term_id ) ) { return; }
	$content = get_term_meta( $term->term_id, '_ef_range_content', true );
	if ( '' === trim( (string) $content ) ) { return; }

	echo '<section class="ef-range-content">'
		. apply_filters( 'the_content', $content ) // wpautop + do_shortcode ([table], [caption], embeds)
		. '</section>';
}, 20 );

/**
 * Content editing box (WYSIWYG, with media) on the "Edit category" screen, and
 * on the product tag and market edit screens (their landing pages show it too).
 */
$ef_range_content_field = function ( $term ) {
	$content = get_term_meta( $term->term_id, '_ef_range_content', true );
	wp_nonce_field( 'ef_range_content_save', 'ef_range_content_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ef_range_content"><?php esc_html_e( 'Long description (below products)', 'eco-fridge-child' ); ?></label></th>
		<td>
			<?php
			wp_editor(
				$content,
				'ef_range_content',
				array(
					'textarea_name' => 'ef_range_content',
					'textarea_rows' => 14,
					'media_buttons' => true,
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'The long-form content shown below the product grid on this category page.', 'eco-fridge-child' ); ?></p>
		</td>
	</tr>
	<?php
};
add_action( 'product_cat_edit_form_fields', $ef_range_content_field, 10 );
add_action( 'product_tag_edit_form_fields', $ef_range_content_field, 10 );
add_action( 'market_edit_form_fields', $ef_range_content_field, 10 );

/**
 * Save the edited content.
 */
$ef_range_content_save = function ( $term_id ) {
	if ( ! isset( $_POST['ef_range_content_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ef_range_content_nonce'] ) ), 'ef_range_content_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) ) { return; }
	if ( isset( $_POST['ef_range_content'] ) ) {
		update_term_meta( $term_id, '_ef_range_content', wp_kses_post( wp_unslash( $_POST['ef_range_content'] ) ) );
	}
};
add_action( 'edited_product_cat', $ef_range_content_save, 10 );
add_action( 'edited_product_tag', $ef_range_content_save, 10 );
add_action( 'edited_market', $ef_range_content_save, 10 );
