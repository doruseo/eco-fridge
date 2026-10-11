<?php
/**
 * "Spare parts for this range": parts picked by hand, plus parts whose name contains the model
 * code of a product in the category (e.g. "Front Grill for SD60/180CC"), plus links to every
 * type of part.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

final class EFB_Landing_Parts {

	/**
	 * Model codes as used in product and part names: SD60/180CC, ECO-P75/120CC, SUC60, UHGF120.
	 * At least two letters, so refrigerants (R290) and electrics (12V, 60W) never match.
	 */
	const CODE_RE = '~\b(?:[A-Z]{2,5}-)?[A-Z]{2,5}\d{2,3}(?:/\d{2,4}[A-Z]{0,4})?\b~';

	/** The top-level parts category (slug "parts"), or null if the shop has none. */
	public static function root() {
		static $root = false;
		if ( false === $root ) {
			$term = get_term_by( 'slug', apply_filters( 'efb_landing_parts_category_slug', 'parts' ), 'product_cat' );
			$root = $term && ! is_wp_error( $term ) ? $term : null;
		}
		return $root;
	}

	/** No parts section on the parts categories themselves. */
	public static function applies_to( $term_id ) {
		$root = self::root();
		if ( ! $root ) {
			return false;
		}
		$chain = array_merge( array( (int) $term_id ), array_map( 'intval', get_ancestors( $term_id, 'product_cat' ) ) );
		return ! in_array( (int) $root->term_id, $chain, true );
	}

	/** IDs of every published product in the parts category tree (once per request). */
	public static function all_part_ids() {
		static $ids = null;
		if ( null === $ids ) {
			$ids = self::root() ? array_map( 'intval', self::product_ids( self::root()->term_id ) ) : array();
		}
		return $ids;
	}

	/** Keeps only IDs of actual parts, so a fridge can never be shown as a "part". */
	public static function only_parts( array $ids ) {
		return array_values( array_intersect( array_map( 'intval', $ids ), self::all_part_ids() ) );
	}

	/** Admin AJAX: search parts by name or SKU. */
	public static function ajax_search() {
		check_ajax_referer( 'efb_landing_search_posts', 'nonce' );
		if ( ! current_user_can( 'manage_product_terms' ) || ! self::root() ) {
			wp_send_json_error( null, 403 );
		}
		$term  = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				's'              => $term,
				'posts_per_page' => 30,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => self::root()->term_id,
						'include_children' => true,
					),
				),
			)
		);
		$ids = $query->posts;
		// Also match SKUs, which the normal search ignores.
		$by_sku = wc_get_product_id_by_sku( $term );
		if ( $by_sku ) {
			array_unshift( $ids, $by_sku );
		}
		$results = array();
		foreach ( self::only_parts( array_unique( $ids ) ) as $id ) {
			$results[] = array(
				'id'   => $id,
				'text' => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			);
		}
		wp_send_json( array( 'results' => $results ) );
	}

	private static function codes( $title ) {
		preg_match_all( self::CODE_RE, html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ), $m );
		$codes = array();
		foreach ( $m[0] as $code ) {
			$codes[ $code ]                   = true;
			$codes[ strtok( $code, '/' ) ]    = true; // Base code: a "Gasket for SD60" fits SD60/180CC.
		}
		return $codes;
	}

	private static function product_ids( $term_id ) {
		return get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => $term_id,
						'include_children' => true,
					),
				),
			)
		);
	}

	/** Parts whose name shares a model code with a product in the category (cached). */
	public static function matched( $term_id ) {
		$key = 'efb_landing_parts_' . $term_id . '_' . EFB_Landing_Meta::cache_version();
		$ids = get_transient( $key );
		if ( false !== $ids ) {
			return (array) $ids;
		}

		$ids   = array();
		$codes = array();
		foreach ( self::product_ids( $term_id ) as $id ) {
			$codes += self::codes( get_the_title( $id ) );
		}
		if ( $codes ) {
			foreach ( self::all_part_ids() as $part_id ) {
				if ( array_intersect_key( self::codes( get_the_title( $part_id ) ), $codes ) ) {
					$ids[] = $part_id;
				}
			}
		}

		$ids = self::mix_types( $ids );
		set_transient( $key, $ids, 12 * HOUR_IN_SECONDS );
		return $ids;
	}

	/**
	 * Interleaves parts by type (Grills, Gaskets, Heaters…) so the first cards are not all
	 * one kind: a multideck range matches many grills, and a gasket or heater is just as useful.
	 */
	private static function mix_types( array $ids ) {
		$groups = array();
		foreach ( $ids as $id ) {
			$type = 0;
			foreach ( wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'all' ) ) as $cat ) {
				if ( (int) $cat->parent === (int) self::root()->term_id ) {
					$type = (int) $cat->term_id;
					break;
				}
			}
			$groups[ $type ][] = $id;
		}
		$mixed = array();
		while ( $groups ) {
			foreach ( $groups as $type => &$list ) {
				$mixed[] = array_shift( $list );
				if ( ! $list ) {
					unset( $groups[ $type ] );
				}
			}
			unset( $list );
		}
		return $mixed;
	}

	public static function render( $term_id, array $data ) {
		if ( ! self::applies_to( $term_id ) ) {
			return '';
		}

		// Hand-picked parts first, then automatic matches; hidden or unpublished parts are skipped.
		$ids   = array_unique( array_merge( self::only_parts( $data['parts_manual'] ), $data['parts_auto'] ? self::matched( $term_id ) : array() ) );
		$limit    = (int) apply_filters( 'efb_landing_parts_limit', 24, $term_id );
		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
				continue;
			}
			$products[] = $product;
			if ( count( $products ) >= $limit ) {
				break;
			}
		}
		$cards = EFB_Landing_Cards::carousel( $products, __( 'Spare parts', 'ecofridge-blocks' ) );

		$types = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => self::root()->term_id,
				'hide_empty' => true,
				'menu_order' => 'ASC',
			)
		);
		$chips = '';
		foreach ( is_wp_error( $types ) ? array() : $types as $type ) {
			$chips .= '<li><a class="efbl-chip" href="' . esc_url( get_term_link( $type ) ) . '">' . esc_html( $type->name ) . '</a></li>';
		}
		$chips .= '<li><a class="efbl-chip efbl-chip--all" href="' . esc_url( get_term_link( self::root() ) ) . '">' . esc_html__( 'All parts', 'ecofridge-blocks' ) . '</a></li>';

		$html  = $cards;
		$html .= '<p class="efbl-parts__browse">' . esc_html( $cards ? __( 'Looking for something else? Browse parts by type:', 'ecofridge-blocks' ) : __( 'Need a replacement part? Browse by type:', 'ecofridge-blocks' ) ) . '</p>';
		$html .= '<ul class="efbl-chips">' . $chips . '</ul>';

		return $html;
	}
}
