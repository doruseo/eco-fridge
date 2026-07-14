<?php
/**
 * Conținutul long-form al categoriilor de produse (importat 1:1 din originalul
 * eco-fridge, câmp Toolset `range-content-2`, stocat în term meta `_ef_range_content`).
 *
 *  - randare: SUB grila de produse pe arhivele de categorie (ca pe site-ul original)
 *  - editare: casetă WYSIWYG în ecranul de editare al categoriei (Products → Categories)
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Afișează conținutul de categorie sub grila de produse.
 */
add_action( 'woocommerce_after_shop_loop', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	$content = get_term_meta( $term->term_id, '_ef_range_content', true );
	if ( '' === trim( (string) $content ) ) { return; }

	echo '<section class="ef-range-content">'
		. apply_filters( 'the_content', $content ) // wpautop + do_shortcode ([table], [caption], embed-uri)
		. '</section>';
}, 20 );

/**
 * Casetă de editare a conținutului în ecranul „Edit category" (WYSIWYG, cu media).
 */
add_action( 'product_cat_edit_form_fields', function ( $term ) {
	$content = get_term_meta( $term->term_id, '_ef_range_content', true );
	wp_nonce_field( 'ef_range_content_save', 'ef_range_content_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ef_range_content"><?php esc_html_e( 'Category content (below products)', 'eco-fridge-child' ); ?></label></th>
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
			<p class="description"><?php esc_html_e( 'Conținutul long-form afișat sub grila de produse pe pagina acestei categorii.', 'eco-fridge-child' ); ?></p>
		</td>
	</tr>
	<?php
}, 10 );

/**
 * Salvează conținutul editat.
 */
add_action( 'edited_product_cat', function ( $term_id ) {
	if ( ! isset( $_POST['ef_range_content_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ef_range_content_nonce'] ) ), 'ef_range_content_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) ) { return; }
	if ( isset( $_POST['ef_range_content'] ) ) {
		update_term_meta( $term_id, '_ef_range_content', wp_kses_post( wp_unslash( $_POST['ef_range_content'] ) ) );
	}
}, 10 );
