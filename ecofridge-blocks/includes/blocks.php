<?php
/**
 * Block registration: shared assets + every block in /blocks (block.json).
 *
 * All blocks are dynamic (rendered by render.php) so markup can evolve without
 * "invalid block" warnings in the editor. The editor UI for every block lives in
 * a single build-free script (assets/editor.js).
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register shared scripts/styles, then the blocks.
 */
function efb_register_blocks() {
	wp_register_style( 'efb-blocks', EFB_URL . 'assets/blocks.css', array(), EFB_VERSION );
	wp_register_style( 'efb-search', EFB_URL . 'assets/search.css', array(), EFB_VERSION );
	wp_register_script( 'efb-search', EFB_URL . 'assets/search.js', array(), EFB_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script( 'efb-slider', EFB_URL . 'assets/slider.js', array(), EFB_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script( 'efb-form', EFB_URL . 'assets/form.js', array(), EFB_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script( 'efb-table', EFB_URL . 'assets/table.js', array(), EFB_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	wp_register_script(
		'efb-editor',
		EFB_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-server-side-render', 'wp-i18n' ),
		EFB_VERSION,
		true
	);
	wp_register_style( 'efb-editor', EFB_URL . 'assets/editor.css', array( 'efb-blocks', 'efb-search' ), EFB_VERSION );

	foreach ( glob( EFB_DIR . 'blocks/*/block.json' ) as $file ) {
		register_block_type( dirname( $file ) );
	}
}
add_action( 'init', 'efb_register_blocks' );

/**
 * Own block category, listed first in the inserter.
 */
add_filter(
	'block_categories_all',
	static function ( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'ecofridge',
				'title' => __( 'ECO-Fridge', 'ecofridge-blocks' ),
				'icon'  => null,
			)
		);
		return $categories;
	}
);

/**
 * Data the editor needs (product categories for the pickers).
 */
add_action(
	'enqueue_block_editor_assets',
	static function () {
		$cats  = array();
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			$by_id = array();
			foreach ( $terms as $t ) {
				$by_id[ $t->term_id ] = $t;
			}
			foreach ( $terms as $t ) {
				$path = array( html_entity_decode( $t->name ) );
				$p    = $t->parent;
				while ( $p && isset( $by_id[ $p ] ) ) {
					array_unshift( $path, html_entity_decode( $by_id[ $p ]->name ) );
					$p = $by_id[ $p ]->parent;
				}
				$cats[] = array(
					'value' => $t->term_id,
					'label' => implode( ' › ', $path ) . ' (' . $t->count . ')',
				);
			}
			usort(
				$cats,
				static function ( $a, $b ) {
					return strcasecmp( $a['label'], $b['label'] );
				}
			);
		}
		$markets = array();
		if ( taxonomy_exists( 'market' ) ) {
			foreach ( (array) get_terms( array( 'taxonomy' => 'market', 'hide_empty' => false ) ) as $m ) {
				if ( $m instanceof WP_Term ) {
					$markets[] = array(
						'value' => $m->term_id,
						'label' => html_entity_decode( $m->name ) . ' (' . $m->count . ')',
					);
				}
			}
		}
		$blog_cats = array();
		foreach ( (array) get_categories( array( 'hide_empty' => false ) ) as $c ) {
			if ( $c instanceof WP_Term && 'uncategorized' !== $c->slug ) {
				$blog_cats[] = array(
					'value' => $c->term_id,
					'label' => html_entity_decode( $c->name ) . ' (' . $c->count . ')',
				);
			}
		}
		$icons = array();
		foreach ( efb_highlight_icons() as $key => $icon ) {
			$icons[] = array(
				'value' => $key,
				'label' => $icon['label'],
			);
		}
		wp_add_inline_script(
			'efb-editor',
			'window.efbData = ' . wp_json_encode(
				array(
					'categories'     => $cats,
					'markets'        => $markets,
					'blogCategories' => $blog_cats,
					'highlightIcons' => $icons,
				)
			) . ';',
			'before'
		);
	}
);

/**
 * Inline style string from a map of CSS custom properties, skipping empty values.
 *
 * @param array $vars Map of --var => value.
 * @return string
 */
function efb_style_vars( $vars ) {
	$out = '';
	foreach ( $vars as $k => $v ) {
		if ( '' === $v || null === $v || false === $v ) {
			continue;
		}
		$out .= $k . ':' . $v . ';';
	}
	return $out;
}

/**
 * Image markup from an attachment ID, falling back to a plain URL.
 *
 * @param int    $id    Attachment ID.
 * @param string $url   Fallback URL.
 * @param string $alt   Alt text.
 * @param string $size  Image size.
 * @param array  $attr  Extra attributes.
 * @return string
 */
function efb_image( $id, $url, $alt = '', $size = 'large', $attr = array() ) {
	$attr = array_merge( array( 'loading' => 'lazy', 'decoding' => 'async' ), $attr );
	if ( '' !== $alt ) {
		$attr['alt'] = $alt;
	}
	if ( $id ) {
		$img = wp_get_attachment_image( (int) $id, $size, false, $attr );
		if ( $img ) {
			return $img;
		}
	}
	if ( ! $url ) {
		return '';
	}
	$html = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"';
	foreach ( $attr as $k => $v ) {
		if ( 'alt' !== $k ) {
			$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
	}
	return $html . ' />';
}
