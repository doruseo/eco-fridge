<?php
/**
 * Category landing pages — fields on Products > Categories > Edit.
 *
 *  - every product category: the plain "Description" textarea becomes a
 *    rich-text "Short description" editor (the text under the title, above the products);
 *  - commercial categories: the landing sections shown after the product grid.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

final class EFB_Landing_Admin {

	public function __construct() {
		add_action( 'product_cat_edit_form_fields', array( $this, 'render_short_description' ), 1 );
		add_action( 'product_cat_edit_form_fields', array( $this, 'render_fields' ), 20 );
		add_action( 'edited_product_cat', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_action( 'wp_ajax_efb_landing_search_posts', array( $this, 'search_posts' ) );
		add_action( 'wp_ajax_efb_landing_search_parts', array( 'EFB_Landing_Parts', 'ajax_search' ) );
	}

	/**
	 * The current term on the edit screen, if it is a commercial category.
	 *
	 * @return int Term ID or 0.
	 */
	private static function landing_term() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$term_id = isset( $_GET['tag_ID'] ) ? absint( $_GET['tag_ID'] ) : 0;
		return $term_id && efb_landing_applies( $term_id ) ? $term_id : 0;
	}

	public function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( 'term.php' !== $hook || ! $screen || 'product_cat' !== $screen->taxonomy ) {
			return;
		}

		// Every category: hide the plain Description row; the rich-text editor
		// below it posts the same "description" field. Disabled fields are not
		// submitted, so only the editor's value is saved.
		wp_add_inline_style( 'common', '.term-php .term-description-wrap { display: none; }' );
		wp_add_inline_script( 'common', "jQuery( function ( $ ) { $( '.term-description-wrap' ).hide().find( 'textarea' ).prop( 'disabled', true ); } );" );

		if ( ! self::landing_term() ) {
			return;
		}
		wp_enqueue_style( 'woocommerce_admin_styles' ); // Carries the select2 styles.
		wp_enqueue_style( 'efb-landing-admin', EFB_URL . 'assets/landing-admin.css', array(), EFB_VERSION );
		wp_enqueue_script( 'efb-landing-admin', EFB_URL . 'assets/landing-admin.js', array( 'jquery', 'jquery-ui-sortable', 'selectWoo' ), EFB_VERSION, true );
		wp_localize_script(
			'efb-landing-admin',
			'efbLandingAdmin',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'efb_landing_search_posts' ),
				'i18n'  => array(
					'addRow'       => __( 'Add row', 'ecofridge-blocks' ),
					'addCol'       => __( 'Add column', 'ecofridge-blocks' ),
					'remove'       => __( 'Remove', 'ecofridge-blocks' ),
					'confirmClear' => __( 'Remove the whole comparison table?', 'ecofridge-blocks' ),
					'clear'        => __( 'Clear table', 'ecofridge-blocks' ),
					'search'       => __( 'Search articles…', 'ecofridge-blocks' ),
					'searchParts'  => __( 'Search parts by name or SKU…', 'ecofridge-blocks' ),
				),
			)
		);
	}

	/**
	 * Rich-text "Short description" in place of the plain Description textarea.
	 *
	 * @param WP_Term $term Category.
	 */
	public function render_short_description( $term ) {
		?>
		<tr class="form-field efbl-short-description">
			<th scope="row"><label for="efb_short_description"><?php esc_html_e( 'Short description', 'ecofridge-blocks' ); ?></label></th>
			<td>
				<?php
				// The $term passed here is escaped for the edit context, so read the raw value.
				wp_editor(
					(string) get_term_field( 'description', $term->term_id, 'product_cat', 'raw' ),
					'efb_short_description',
					array(
						'textarea_name' => 'description',
						'textarea_rows' => 6,
						'media_buttons' => true,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'Shown under the category title, above the products. Keep it to one to three short paragraphs.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Landing section fields (commercial categories only).
	 *
	 * @param WP_Term $term Category.
	 */
	public function render_fields( $term ) {
		if ( ! efb_landing_applies( $term->term_id ) ) {
			return;
		}
		$d        = EFB_Landing_Meta::get( $term->term_id );
		$sections = EFB_Landing_Meta::sections();
		wp_nonce_field( 'efb_landing_save_' . $term->term_id, 'efb_landing_nonce' );
		?>
		<tr class="efbl-heading-row">
			<th colspan="2">
				<h2><?php esc_html_e( 'Category landing page', 'ecofridge-blocks' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Page layout: short description → subcategory carousel → products → content sections (in the order below) → related categories → markets → reviews. Empty sections are not shown. The content sections appear on the first page of the category only.', 'ecofridge-blocks' ); ?></p>
			</th>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Section order & headings', 'ecofridge-blocks' ); ?></th>
			<td>
				<ul id="efbl-order" class="efbl-order">
					<?php foreach ( $d['order'] as $key ) : ?>
						<li>
							<span class="dashicons dashicons-menu efbl-handle" aria-hidden="true"></span>
							<strong><?php echo esc_html( $sections[ $key ] ); ?></strong>
							<input type="hidden" name="efbl[order][]" value="<?php echo esc_attr( $key ); ?>">
							<input type="text" name="efbl[titles][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $d['titles'][ $key ] ); ?>" placeholder="<?php esc_attr_e( 'No heading', 'ecofridge-blocks' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: section name */ __( '%s heading', 'ecofridge-blocks' ), $sections[ $key ] ) ); ?>">
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="description"><?php esc_html_e( 'Drag to reorder. Leave a heading empty to show the section without one. {category} in a heading is replaced with the category name.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Features', 'ecofridge-blocks' ); ?></th>
			<td>
				<div id="efbl-features" class="efbl-repeater">
					<?php
					foreach ( $d['features'] as $item ) {
						$this->feature_row( $item['icon'], $item['title'], $item['text'] );
					}
					?>
				</div>
				<template id="efbl-features-tpl"><?php $this->feature_row( 'check', '', '' ); ?></template>
				<button type="button" class="button" id="efbl-features-add"><?php esc_html_e( 'Add feature', 'ecofridge-blocks' ); ?></button>
				<p class="description"><?php esc_html_e( 'Shown as cards with an icon. Keep titles short (2–5 words) and the text to one or two sentences; it accepts basic HTML (links, bold).', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'FAQ', 'ecofridge-blocks' ); ?></th>
			<td>
				<div id="efbl-faq" class="efbl-faq-list">
					<?php
					foreach ( $d['faq'] as $item ) {
						$this->faq_row( $item['q'], $item['a'] );
					}
					?>
				</div>
				<template id="efbl-faq-tpl"><?php $this->faq_row( '', '' ); ?></template>
				<button type="button" class="button" id="efbl-faq-add"><?php esc_html_e( 'Add question', 'ecofridge-blocks' ); ?></button>
				<p class="description"><?php esc_html_e( 'Answers accept basic HTML (links, bold, lists). Also output as FAQPage structured data.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Comparison table', 'ecofridge-blocks' ); ?></th>
			<td>
				<input type="hidden" name="efbl[table_json]" id="efbl-table-json" value="<?php echo esc_attr( wp_json_encode( $d['table'] ) ); ?>">
				<div id="efbl-table-editor" class="efbl-table-editor"></div>
				<p class="description"><?php esc_html_e( 'The first row is the header, the first column labels each row. Type [yes] or [no] in a cell to show a tick or a cross. Basic HTML (bold, links) is allowed.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><label for="efbl-articles"><?php esc_html_e( 'Related articles', 'ecofridge-blocks' ); ?></label></th>
			<td>
				<select id="efbl-articles" name="efbl[articles][]" multiple="multiple" style="width:100%">
					<?php foreach ( $d['articles'] as $post_id ) : ?>
						<?php if ( get_post( $post_id ) ) : ?>
							<option value="<?php echo esc_attr( $post_id ); ?>" selected><?php echo esc_html( get_the_title( $post_id ) ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Blog posts shown as cards, in the order selected.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><label for="efbl-parts-manual"><?php esc_html_e( 'Spare parts', 'ecofridge-blocks' ); ?></label></th>
			<td>
				<?php if ( ! EFB_Landing_Parts::root() ) : ?>
					<p class="description"><?php esc_html_e( 'Shown once the shop has a "Parts" product category (slug "parts"): parts whose name contains the model code of a product in this category, plus parts picked by hand.', 'ecofridge-blocks' ); ?></p>
				<?php elseif ( ! EFB_Landing_Parts::applies_to( $term->term_id ) ) : ?>
					<p class="description"><?php esc_html_e( 'Not shown on the parts categories themselves.', 'ecofridge-blocks' ); ?></p>
				<?php else : ?>
					<label><input type="checkbox" name="efbl[parts]" value="1" <?php checked( $d['parts'] ); ?>> <?php esc_html_e( 'Show the spare parts section', 'ecofridge-blocks' ); ?></label><br>
					<label><input type="checkbox" name="efbl[parts_auto]" value="1" <?php checked( $d['parts_auto'] ); ?>> <?php esc_html_e( 'Add parts automatically when their name contains the model code of a product in this category', 'ecofridge-blocks' ); ?></label>
					<p style="margin-top:10px">
						<select id="efbl-parts-manual" multiple="multiple" style="width:100%" name="efbl[parts_manual][]">
							<?php foreach ( EFB_Landing_Parts::only_parts( $d['parts_manual'] ) as $part_id ) : ?>
								<option value="<?php echo esc_attr( $part_id ); ?>" selected><?php echo esc_html( html_entity_decode( get_the_title( $part_id ), ENT_QUOTES, 'UTF-8' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p class="description"><?php esc_html_e( 'Hand-picked parts are shown first, then the automatic matches. Links to every type of part are shown underneath.', 'ecofridge-blocks' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Long description', 'ecofridge-blocks' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'The long description is the "Long description (below products)" field above. It is shown in the position chosen under "Section order & headings".', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Products carousel', 'ecofridge-blocks' ); ?></th>
			<td>
				<label><input type="checkbox" name="efbl[products_carousel]" value="1" <?php checked( $d['products_carousel'] ); ?>> <?php esc_html_e( 'Show a carousel with every product in this category and its subcategories, just below the product grid', 'ecofridge-blocks' ); ?></label>
				<p><input type="text" class="regular-text" name="efbl[titles][products_carousel]" value="<?php echo esc_attr( $d['titles']['products_carousel'] ); ?>" placeholder="<?php esc_attr_e( 'No heading', 'ecofridge-blocks' ); ?>" aria-label="<?php esc_attr_e( 'Products carousel heading', 'ecofridge-blocks' ); ?>"></p>
				<p class="description"><?php esc_html_e( 'Only shown when the Display type above is "Subcategories" (the grid then lists subcategories, not products). Each product has a quantity box and an Add to cart button.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Related categories', 'ecofridge-blocks' ); ?></th>
			<td>
				<label><input type="checkbox" name="efbl[related_cats]" value="1" <?php checked( $d['related_cats'] ); ?>> <?php esc_html_e( 'Show the other categories under the same parent (sibling categories in the Commercial menu), just above the markets', 'ecofridge-blocks' ); ?></label>
				<p><input type="text" class="regular-text" name="efbl[titles][related_cats]" value="<?php echo esc_attr( $d['titles']['related_cats'] ); ?>" placeholder="<?php esc_attr_e( 'No heading', 'ecofridge-blocks' ); ?>" aria-label="<?php esc_attr_e( 'Related categories heading', 'ecofridge-blocks' ); ?>"></p>
				<p class="description"><?php esc_html_e( 'Not shown on Commercial Fridge and Commercial Freezer themselves.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Refrigeration markets', 'ecofridge-blocks' ); ?></th>
			<td>
				<label><input type="checkbox" name="efbl[markets]" value="1" <?php checked( $d['markets'] ); ?>> <?php esc_html_e( 'Show a carousel of the market pages (the child pages of /markets/), below the related categories', 'ecofridge-blocks' ); ?></label>
				<p><input type="text" class="regular-text" name="efbl[titles][markets]" value="<?php echo esc_attr( $d['titles']['markets'] ); ?>" placeholder="<?php esc_attr_e( 'No heading', 'ecofridge-blocks' ); ?>" aria-label="<?php esc_attr_e( 'Refrigeration markets heading', 'ecofridge-blocks' ); ?>"></p>
				<p class="description"><?php esc_html_e( 'Uses the DF Carousel plugin, which must be active.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Reviews', 'ecofridge-blocks' ); ?></th>
			<td>
				<label><input type="checkbox" name="efbl[reviews]" value="1" <?php checked( $d['reviews'] ); ?>> <?php esc_html_e( 'Show reviews from every product in this category at the bottom of the page', 'ecofridge-blocks' ); ?></label>
				<p><input type="text" class="regular-text" name="efbl[titles][reviews]" value="<?php echo esc_attr( $d['titles']['reviews'] ); ?>" placeholder="<?php esc_attr_e( 'No heading', 'ecofridge-blocks' ); ?>" aria-label="<?php esc_attr_e( 'Reviews heading', 'ecofridge-blocks' ); ?>"></p>
				<p class="description"><?php esc_html_e( 'Shown once the products have approved reviews with a star rating.', 'ecofridge-blocks' ); ?></p>
			</td>
		</tr>
		<?php
	}

	private function feature_row( $icon, $title, $text ) {
		?>
		<div class="efbl-faq-item efbl-feature-item">
			<div class="efbl-faq-fields">
				<div class="efbl-feature-head">
					<select name="efbl[features][icon][]" aria-label="<?php esc_attr_e( 'Icon', 'ecofridge-blocks' ); ?>">
						<option value=""><?php esc_html_e( 'No icon', 'ecofridge-blocks' ); ?></option>
						<?php foreach ( EFB_Landing_Features::icons() as $key => $icon_data ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( $icon_data[0] ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" name="efbl[features][title][]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Feature, e.g. Double-glazed curved glass', 'ecofridge-blocks' ); ?>">
				</div>
				<textarea name="efbl[features][text][]" rows="2" placeholder="<?php esc_attr_e( 'Short explanation (optional)', 'ecofridge-blocks' ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
			</div>
			<div class="efbl-faq-actions">
				<button type="button" class="button-link" data-efbl-move="-1" aria-label="<?php esc_attr_e( 'Move up', 'ecofridge-blocks' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="button-link" data-efbl-move="1" aria-label="<?php esc_attr_e( 'Move down', 'ecofridge-blocks' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
				<button type="button" class="button-link button-link-delete" data-efbl-remove aria-label="<?php esc_attr_e( 'Remove feature', 'ecofridge-blocks' ); ?>"><span class="dashicons dashicons-trash"></span></button>
			</div>
		</div>
		<?php
	}

	private function faq_row( $q, $a ) {
		?>
		<div class="efbl-faq-item">
			<div class="efbl-faq-fields">
				<input type="text" name="efbl[faq][q][]" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Question', 'ecofridge-blocks' ); ?>">
				<textarea name="efbl[faq][a][]" rows="3" placeholder="<?php esc_attr_e( 'Answer', 'ecofridge-blocks' ); ?>"><?php echo esc_textarea( $a ); ?></textarea>
			</div>
			<div class="efbl-faq-actions">
				<button type="button" class="button-link" data-efbl-move="-1" aria-label="<?php esc_attr_e( 'Move up', 'ecofridge-blocks' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="button-link" data-efbl-move="1" aria-label="<?php esc_attr_e( 'Move down', 'ecofridge-blocks' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
				<button type="button" class="button-link button-link-delete" data-efbl-remove aria-label="<?php esc_attr_e( 'Remove question', 'ecofridge-blocks' ); ?>"><span class="dashicons dashicons-trash"></span></button>
			</div>
		</div>
		<?php
	}

	public function save( $term_id ) {
		// Quick Edit also fires this hook without our nonce; leave the data alone then.
		if ( ! isset( $_POST['efb_landing_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['efb_landing_nonce'] ), 'efb_landing_save_' . $term_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_term', $term_id ) || ! efb_landing_applies( $term_id ) ) {
			return;
		}

		$in   = isset( $_POST['efbl'] ) ? wp_unslash( (array) $_POST['efbl'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised per field below.
		$data = EFB_Landing_Meta::defaults();

		$icons  = EFB_Landing_Features::icons();
		$titles = (array) ( $in['features']['title'] ?? array() );
		foreach ( $titles as $i => $title ) {
			$title = sanitize_text_field( $title );
			$icon  = sanitize_key( $in['features']['icon'][ $i ] ?? '' );
			if ( '' !== $title ) {
				$data['features'][] = array(
					'icon'  => isset( $icons[ $icon ] ) ? $icon : '',
					'title' => $title,
					'text'  => self::clean_html( $in['features']['text'][ $i ] ?? '' ),
				);
			}
		}

		$questions = (array) ( $in['faq']['q'] ?? array() );
		$answers   = (array) ( $in['faq']['a'] ?? array() );
		foreach ( $questions as $i => $q ) {
			$q = sanitize_text_field( $q );
			$a = self::clean_html( $answers[ $i ] ?? '' );
			if ( '' !== $q && '' !== trim( $a ) ) {
				$data['faq'][] = array(
					'q' => $q,
					'a' => $a,
				);
			}
		}

		$data['table']    = self::clean_table( json_decode( (string) ( $in['table_json'] ?? '' ), true ) );
		$data['articles'] = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $in['articles'] ?? array() ) ) ) ) );

		$order         = array_map( 'sanitize_key', (array) ( $in['order'] ?? array() ) );
		$data['order'] = array_values( array_intersect( $order, array_keys( EFB_Landing_Meta::sections() ) ) );

		foreach ( array_keys( EFB_Landing_Meta::default_titles() ) as $key ) {
			// Swap the placeholder before sanitising, or "%ca" is stripped from "%category%".
			$data['titles'][ $key ] = sanitize_text_field( str_replace( '%category%', EFB_Landing_Meta::CATEGORY_TOKEN, (string) ( $in['titles'][ $key ] ?? '' ) ) );
		}

		if ( EFB_Landing_Parts::applies_to( $term_id ) ) {
			$data['parts']        = ! empty( $in['parts'] );
			$data['parts_auto']   = ! empty( $in['parts_auto'] );
			$data['parts_manual'] = EFB_Landing_Parts::only_parts( array_unique( array_filter( array_map( 'absint', (array) ( $in['parts_manual'] ?? array() ) ) ) ) );
		}

		$data['products_carousel'] = ! empty( $in['products_carousel'] );
		$data['related_cats']      = ! empty( $in['related_cats'] );
		$data['markets']           = ! empty( $in['markets'] );
		$data['reviews']           = ! empty( $in['reviews'] );

		EFB_Landing_Meta::save( $term_id, $data );
	}

	private static function clean_html( $html ) {
		$html = (string) $html;
		return current_user_can( 'unfiltered_html' ) ? $html : wp_kses_post( $html );
	}

	private static function clean_cell( $value ) {
		$allowed = array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
		return trim( wp_kses( (string) $value, $allowed ) );
	}

	private static function clean_table( $table ) {
		$clean = array(
			'head' => array(),
			'rows' => array(),
		);
		if ( ! is_array( $table ) ) {
			return $clean;
		}

		$head = array_map( array( __CLASS__, 'clean_cell' ), (array) ( $table['head'] ?? array() ) );
		$cols = count( $head );

		foreach ( (array) ( $table['rows'] ?? array() ) as $row ) {
			$row = array_slice( array_map( array( __CLASS__, 'clean_cell' ), (array) $row ), 0, $cols );
			$row = array_pad( $row, $cols, '' );
			if ( '' !== implode( '', $row ) ) {
				$clean['rows'][] = $row;
			}
		}

		if ( $clean['rows'] ) {
			$clean['head'] = $head;
		}
		return $clean;
	}

	public function search_posts() {
		check_ajax_referer( 'efb_landing_search_posts', 'nonce' );
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			wp_send_json_error( null, 403 );
		}

		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				's'                   => sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ),
				'posts_per_page'      => 20,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);

		$results = array();
		foreach ( $query->posts as $id ) {
			$results[] = array(
				'id'   => $id,
				'text' => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			);
		}
		wp_send_json( array( 'results' => $results ) );
	}
}
