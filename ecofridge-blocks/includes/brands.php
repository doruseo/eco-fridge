<?php
/**
 * Brand pages (WooCommerce Brands, /brand/<slug>/).
 *
 *  - The brand's image (Products → Brands → Thumbnail) is shown at the top of
 *    the page, under the title and above the description, in medium size.
 *  - Products → Brands → Edit: the plain Description textarea becomes a
 *    rich-text editor (headings, lists, links, images). WordPress strips term
 *    descriptions down to a few inline tags on save; for brands the editor's
 *    HTML is kept (the same rules as post content).
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Brand page: image between the title and the description.
 * ------------------------------------------------------------------------- */
add_action(
	'woocommerce_archive_description',
	static function () {
		if ( ! is_tax( 'product_brand' ) ) {
			return;
		}
		$term     = get_queried_object();
		$image_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( ! $image_id ) {
			return;
		}
		echo '<div class="efb-brand-image" style="margin:0 0 1.5em">' . wp_get_attachment_image( $image_id, 'medium', false, array( 'alt' => esc_attr( $term->name ), 'loading' => 'eager' ) ) . '</div>';
	},
	9 // WooCommerce prints the description at 10.
);

/* ---------------------------------------------------------------------------
 * Editor: rich-text description.
 * ------------------------------------------------------------------------- */
add_action(
	'product_brand_edit_form_fields',
	static function ( $term ) {
		?>
		<tr class="form-field efb-brand-description">
			<th scope="row"><label for="efb_brand_description"><?php esc_html_e( 'Description', 'ecofridge-blocks' ); ?></label></th>
			<td>
				<?php
				// The $term passed here is escaped for the edit context, so read the raw value.
				wp_editor(
					(string) get_term_field( 'description', $term->term_id, 'product_brand', 'raw' ),
					'efb_brand_description',
					array(
						'textarea_name' => 'description',
						'textarea_rows' => 10,
						'media_buttons' => true,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'Shown on the brand page under the brand image, and in the Brand tab of its products.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>
		<?php
	},
	1
);

// Hide the plain Description row; disabled fields are not submitted, so only
// the editor's value is saved.
add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		$screen = get_current_screen();
		if ( 'term.php' !== $hook || ! $screen || 'product_brand' !== $screen->taxonomy ) {
			return;
		}
		wp_add_inline_style( 'common', '.term-php .term-description-wrap { display: none; }' );
		wp_add_inline_script( 'common', "jQuery( function ( $ ) { $( '.term-description-wrap' ).hide().find( 'textarea' ).prop( 'disabled', true ); } );" );
	},
	20
);

// Keep the editor's HTML on save: post-content rules instead of the inline-only
// rules WordPress applies to term descriptions (wp_filter_kses at priority 10).
add_filter(
	'pre_term_description',
	static function ( $value, $taxonomy = '' ) {
		$GLOBALS['efb_brand_raw_description'] = 'product_brand' === $taxonomy ? $value : null;
		return $value;
	},
	9,
	2
);
add_filter(
	'pre_term_description',
	static function ( $value, $taxonomy = '' ) {
		$raw = $GLOBALS['efb_brand_raw_description'] ?? null;
		if ( 'product_brand' !== $taxonomy || null === $raw ) {
			return $value;
		}
		$GLOBALS['efb_brand_raw_description'] = null;
		return current_user_can( 'unfiltered_html' ) ? $raw : wp_filter_post_kses( $raw );
	},
	11,
	2
);
