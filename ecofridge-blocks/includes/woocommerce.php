<?php
/**
 * WooCommerce integration: product descriptions in the classic editor.
 *
 *  - products are edited with the classic editor (TinyMCE), not the block editor;
 *  - the editor gets a Table button (TinyMCE's official table plugin, bundled in
 *    assets/vendor/tinymce-table): insert a table, add/remove rows and columns,
 *    make the first row a header row;
 *  - on the product page every table in the description is shown as a spec
 *    table: green header row, green border, sortable columns, stacked on mobile
 *    (the same look as the Spec Table block);
 *  - [efb_toggle title="Warranty"] … [/efb_toggle] hides content until the
 *    visitor clicks the title (used for the warranty price tables).
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classic editor for products.
 */
add_filter(
	'use_block_editor_for_post_type',
	static function ( $use, $post_type ) {
		return 'product' === $post_type ? false : $use;
	},
	100,
	2
);

/**
 * Whether the current admin screen edits a product.
 *
 * @return bool
 */
function efb_is_product_edit_screen() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'product' === $screen->post_type && 'post' === $screen->base;
}

/**
 * Table button in the product editor.
 */
add_filter(
	'mce_external_plugins',
	static function ( $plugins ) {
		if ( efb_is_product_edit_screen() ) {
			$plugins['table'] = EFB_URL . 'assets/vendor/tinymce-table/plugin.min.js';
		}
		return $plugins;
	}
);

add_filter(
	'mce_buttons',
	static function ( $buttons ) {
		if ( efb_is_product_edit_screen() ) {
			// After the link buttons, so it is visible without the "Toolbar toggle".
			$pos = array_search( 'unlink', $buttons, true );
			array_splice( $buttons, false === $pos ? count( $buttons ) : $pos + 1, 0, array( 'table' ) );
		}
		return $buttons;
	}
);

add_filter(
	'tiny_mce_before_init',
	static function ( $init, $editor_id ) {
		if ( 'content' !== $editor_id || ! efb_is_product_edit_screen() ) {
			return $init;
		}
		// New tables: no inline sizes/borders (the theme styles them); the
		// properties dialogs keep only what matters here (header rows, caption).
		$init['table_default_attributes'] = wp_json_encode( new stdClass() );
		$init['table_default_styles']     = wp_json_encode( new stdClass() );
		$init['table_appearance_options'] = false;
		$init['table_advtab']             = false;
		$init['table_cell_advtab']        = false;
		$init['table_row_advtab']         = false;
		$init['table_resize_bars']        = false;
		$init['table_responsive_width']   = true;
		// Same look as on the site while editing.
		$init['content_css'] = ( empty( $init['content_css'] ) ? '' : $init['content_css'] . ',' ) . EFB_URL . 'assets/editor-classic.css?ver=' . EFB_VERSION;
		return $init;
	},
	10,
	2
);

/**
 * Expose products to the REST API (used by the search and product grid blocks).
 */
add_filter(
	'woocommerce_register_post_type_product',
	static function ( $args ) {
		$args['show_in_rest'] = true;
		return $args;
	}
);

/**
 * [efb_toggle title="Warranty" open="0"]…[/efb_toggle] — same markup as the
 * Toggle block: closed until the visitor clicks the title.
 */
add_shortcode(
	'efb_toggle',
	static function ( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'title' => __( 'Show more', 'ecofridge-blocks' ),
				'open'  => '0',
				'class' => '',
			),
			$atts,
			'efb_toggle'
		);
		// wpautop leaves stray paragraph tags around the enclosed block content.
		$content = trim( preg_replace( '~^\s*</p>|<p>\s*$~', '', (string) $content ) );
		$content = do_shortcode( shortcode_unautop( $content ) );
		if ( '' === trim( wp_strip_all_tags( $content, true ) ) && false === strpos( $content, '<table' ) ) {
			return '';
		}
		wp_enqueue_style( 'efb-blocks' );
		$classes = trim( 'efb-toggle ' . implode( ' ', array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) $atts['class'] ) ) ) );
		return sprintf(
			'<details class="%1$s"%2$s><summary class="efb-toggle__title">%3$s</summary><div class="efb-toggle__body">%4$s</div></details>',
			esc_attr( $classes ),
			in_array( strtolower( (string) $atts['open'] ), array( '1', 'yes', 'true' ), true ) ? ' open' : '',
			esc_html( $atts['title'] ),
			$content
		);
	}
);

/**
 * Product descriptions: tables become spec tables (wrapper + mobile labels + sortable).
 */
add_filter(
	'the_content',
	static function ( $content ) {
		// Tables rendered by a Spec Table block are already in this format.
		if ( 'product' !== get_post_type() || false === stripos( $content, '<table' ) || false !== strpos( $content, 'wp-block-efb-spec-table' ) ) {
			return $content;
		}
		$done = preg_replace_callback(
			'~<table\b[^>]*>.*?</table>~is',
			'efb_spec_table_from_html',
			$content
		);
		if ( null !== $done && $done !== $content ) {
			wp_enqueue_style( 'efb-blocks' );
			wp_enqueue_script( 'efb-table' );
			return $done;
		}
		return $content;
	},
	20
);

/**
 * One <table> from the classic editor → spec table markup.
 *
 * The header row is the <thead> row, or a first row made of <th> cells. Cells
 * get data-label (the column heading) so the table can stack on mobile.
 *
 * @param array $m Regex match: the whole table.
 * @return string
 */
function efb_spec_table_from_html( $m ) {
	$html = $m[0];
	$doc  = new DOMDocument();
	libxml_use_internal_errors( true );
	$ok = $doc->loadHTML( '<?xml encoding="utf-8"?><div id="efb-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	$table = $ok ? $doc->getElementsByTagName( 'table' )->item( 0 ) : null;
	if ( ! $table ) {
		return $html;
	}

	$rows = array();
	foreach ( $table->getElementsByTagName( 'tr' ) as $tr ) {
		$rows[] = $tr;
	}
	if ( ! $rows ) {
		return $html;
	}

	// Header cells: the thead row, else a first row of <th> only.
	$head_row = null;
	$thead    = $table->getElementsByTagName( 'thead' )->item( 0 );
	if ( $thead && $thead->getElementsByTagName( 'tr' )->length ) {
		$head_row = $thead->getElementsByTagName( 'tr' )->item( 0 );
	} else {
		$first = $rows[0];
		$all_th = true;
		foreach ( $first->childNodes as $cell ) {
			if ( XML_ELEMENT_NODE === $cell->nodeType && 'td' === strtolower( $cell->nodeName ) ) {
				$all_th = false;
			}
		}
		if ( $all_th ) {
			$head_row = $first;
		}
	}

	$labels = array();
	if ( $head_row ) {
		foreach ( $head_row->childNodes as $cell ) {
			if ( XML_ELEMENT_NODE === $cell->nodeType ) {
				$labels[] = trim( $cell->textContent );
			}
		}
	}

	$inner = static function ( $node ) use ( $doc ) {
		$out = '';
		foreach ( $node->childNodes as $child ) {
			$out .= $doc->saveHTML( $child );
		}
		return trim( $out );
	};

	$caption = $table->getElementsByTagName( 'caption' )->item( 0 );
	$out     = '<div class="efb-table is-sortable"><table>';
	$out    .= $caption ? '<caption>' . $inner( $caption ) . '</caption>' : '';
	if ( $head_row ) {
		$out .= '<thead><tr>';
		foreach ( $head_row->childNodes as $cell ) {
			if ( XML_ELEMENT_NODE === $cell->nodeType ) {
				$out .= '<th scope="col">' . $inner( $cell ) . '</th>';
			}
		}
		$out .= '</tr></thead>';
	}
	$out .= '<tbody>';
	foreach ( $rows as $tr ) {
		if ( $tr === $head_row ) {
			continue;
		}
		$out .= '<tr>';
		$c    = 0;
		foreach ( $tr->childNodes as $cell ) {
			if ( XML_ELEMENT_NODE !== $cell->nodeType ) {
				continue;
			}
			$tag  = 'th' === strtolower( $cell->nodeName ) ? 'th' : 'td';
			$out .= '<' . $tag . ' data-label="' . esc_attr( $labels[ $c ] ?? '' ) . '">' . $inner( $cell ) . '</' . $tag . '>';
			++$c;
		}
		$out .= '</tr>';
	}
	return $out . '</tbody></table></div>';
}
