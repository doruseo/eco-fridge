<?php
/**
 * 39 — Commercial categories: SEO content rewrite.
 *
 * For every category written in migration/category-copy/*.php (the Commercial
 * menu's tree + the legacy /ranges/ categories):
 *  - H1: the category name becomes a single clear name ("Commercial Fridges",
 *    not "Commercial Fridge – Commercial Refrigerator");
 *  - short description (1–2 sentences, under the H1) = category Description;
 *  - long description (below the products) = `_ef_range_content`;
 *  - FAQ (starting with what the category is) and related articles → the
 *    landing sections (`_efb_landing`);
 *  - comparison table, built from the live product data: on a parent category
 *    one row per subcategory (temperature, widths, models, from price), on a
 *    leaf category one row per product (size, temperature, price, stock).
 * The previous name / description / long text are kept once in term meta
 * (`_ef_legacy_name`, `_ef_legacy_description`, `_ef_legacy_range_content`).
 *
 * Re-run after price or product changes to refresh the tables. Idempotent.
 * Run via source-dump/_run.php (copies migration/category-copy/ to
 * source-dump/_category-copy/ first — see the README in that folder).
 *
 * @package EcoFridgeMigration
 */

$dir   = WP_CONTENT_DIR . '/source-dump/_category-copy';
$copy  = array();
foreach ( glob( $dir . '/*.php' ) as $file ) {
	$copy = array_merge( $copy, (array) require $file );
}
if ( ! $copy ) {
	WP_CLI::error( "No category copy found in $dir." );
}

/*
 * Extended copy (category-copy/more/*.php): extra long-description sections
 * and FAQs per category. The sections go in before the closing "Buying from
 * ECO-Fridge" / "Trade prices" section when there is one, else at the end.
 */
foreach ( glob( $dir . '/more/*.php' ) as $file ) {
	foreach ( (array) require $file as $slug => $more ) {
		if ( ! isset( $copy[ $slug ] ) ) {
			WP_CLI::warning( "Extended copy for an unknown category: $slug" );
			continue;
		}
		$long = trim( $copy[ $slug ]['long'] );
		$add  = trim( $more['long'] ?? '' );
		if ( '' !== $add ) {
			$at   = preg_match( '~<h2>(Buying from ECO-Fridge|Trade prices and free delivery)</h2>~', $long, $m, PREG_OFFSET_CAPTURE ) ? $m[0][1] : strlen( $long );
			$long = rtrim( substr( $long, 0, $at ) ) . "

" . $add . ( $at < strlen( $long ) ? "

" . substr( $long, $at ) : '' );
		}
		$copy[ $slug ]['long'] = $long;
		$copy[ $slug ]['faq']  = array_merge( $copy[ $slug ]['faq'], $more['faq'] ?? array() );
	}
}

/* ---------------------------------------------------------------------------
 * Product data helpers.
 * ------------------------------------------------------------------------- */

/** First spec table of a product's description: rows of cells (text). */
$spec_rows = function ( WC_Product $p ) {
	if ( ! preg_match( '~<table\b.*?</table>~is', $p->get_description(), $m ) ) {
		return array();
	}
	$rows = array();
	preg_match_all( '~<tr\b.*?</tr>~is', $m[0], $trs );
	foreach ( $trs[0] as $tr ) {
		preg_match_all( '~<t[hd]\b[^>]*>(.*?)</t[hd]>~is', $tr, $cells );
		$rows[] = array_map( static fn( $c ) => trim( html_entity_decode( wp_strip_all_tags( $c ), ENT_QUOTES, 'UTF-8' ) ), $cells[1] );
	}
	return $rows;
};

/** Size (W × D × H mm) and temperature of a product, from its spec table. */
$spec = function ( WC_Product $p ) use ( $spec_rows ) {
	$rows = $spec_rows( $p );
	$out  = array( 'size' => '', 'temp' => '', 'width' => 0 );
	if ( count( $rows ) < 2 ) {
		return $out;
	}
	$head = array_map( 'strtolower', $rows[0] );
	$di   = $ti = null;
	foreach ( $head as $i => $h ) {
		if ( null === $di && ( false !== strpos( $h, 'dimension' ) || false !== strpos( str_replace( ' ', '', $h ), 'wxdxh' ) ) ) { $di = $i; }
		if ( null === $ti && false !== strpos( $h, 'temp' ) ) { $ti = $i; }
	}
	// The row matching the width in the product name ("… (1200mm)"), else the first.
	$row = $rows[1];
	if ( preg_match( '~(\d{3,4})\s*mm~i', $p->get_name(), $w ) && null !== $di ) {
		foreach ( array_slice( $rows, 1 ) as $r ) {
			if ( isset( $r[ $di ] ) && 0 === strpos( preg_replace( '~\s+~', '', $r[ $di ] ), $w[1] . 'mm' ) ) { $row = $r; break; }
		}
	}
	if ( null !== $di && ! empty( $row[ $di ] ) ) {
		$out['size'] = preg_replace( '~\s*x\s*~i', ' × ', $row[ $di ] );
		$out['width'] = (int) $row[ $di ];
	}
	if ( null !== $ti && ! empty( $row[ $ti ] ) ) {
		$out['temp'] = $row[ $ti ];
	}
	return $out;
};

/** Numbers in a temperature text: "-1 / +7 °C" → [-1, 7]. */
$temps = static function ( $text ) {
	preg_match_all( '~[-+−]?\s?\d+~u', str_replace( '−', '-', (string) $text ), $m );
	return array_map( static fn( $n ) => (int) str_replace( ' ', '', $n ), $m[0] );
};

$money = static fn( $v ) => '£' . number_format( (float) $v, 0 ) . ' +VAT';

/** "−1 to +7 °C"; freezers the usual way round: "−18 to −24 °C". */
$range_txt = static function ( $min, $max ) {
	$fmt = static fn( $n ) => ( $n > 0 ? '+' : ( $n < 0 ? '−' : '' ) ) . abs( $n );
	return $max < 0 ? $fmt( $max ) . ' to ' . $fmt( $min ) . ' °C' : $fmt( $min ) . ' to ' . $fmt( $max ) . ' °C';
};

/**
 * Whether a product belongs in a category's comparison table (the shop's
 * category assignments are not always tidy: ovens in Meat Fridge, freezers
 * in Display Fridge…).
 */
$fits = static function ( WC_Product $p, WP_Term $t ) {
	$name = strtolower( $p->get_name() );
	$cat  = strtolower( $t->slug . ' ' . $t->name );
	if ( preg_match( '~\b(oven|grill|stand|prover)\b|bakery~', $name ) ) {
		return (bool) preg_match( '~bakery|storage-cabinets|graded~', $cat );
	}
	if ( preg_match( '~heated|\bhot\b~', $name ) && ! preg_match( '~heat|hot|dual|display-cabinets|graded|deli~', $cat ) ) {
		return false; // Heated displays only in the heated ranges.
	}
	if ( preg_match( '~graded|medical|low-temperature|bakery|deli|discount~', $cat ) ) {
		return true;
	}
	$is_freezer_cat = false !== strpos( $cat, 'freezer' );
	$is_freezer     = false !== strpos( $name, 'freezer' );
	if ( preg_match( '~display-cabinets|storage-cabinets|upright-display-cabinets~', $t->slug ) ) {
		return true; // Mixed fridges and freezers by design.
	}
	return $is_freezer_cat ? $is_freezer : ! $is_freezer;
};

/** Published products of a category (no subcategories). */
$products_of = static function ( WP_Term $t ) {
	$products = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => -1,
			'category' => array( $t->slug ),
		)
	);
	// Cheapest first; products without a price (POA) last.
	usort(
		$products,
		static function ( $a, $b ) {
			$pa = '' === (string) $a->get_price() ? PHP_INT_MAX : (float) $a->get_price();
			$pb = '' === (string) $b->get_price() ? PHP_INT_MAX : (float) $b->get_price();
			return $pa <=> $pb;
		}
	);
	return $products;
};

/** Comparison table for a category. */
$table_for = function ( WP_Term $t ) use ( $products_of, $spec, $temps, $money, $fits, $range_txt ) {
	$children = efb_landing_children( $t->term_id );
	if ( $children ) {
		// Subcategory rows, unless at most one subcategory has products and the
		// category has models of its own outside it (Patisserie Fridges, Counter
		// Fridges): then a one-row table says little, so list the models.
		$ids     = static fn( $ps ) => array_map( static fn( $p ) => $p->get_id(), $ps );
		$own     = $ids( array_filter( $products_of( $t ), static fn( $p ) => $fits( $p, $t ) ) );
		$covered = array();
		$stocked = 0;
		foreach ( $children as $cid ) {
			$c = get_term( $cid, 'product_cat' );
			if ( $c && ! is_wp_error( $c ) && ( $cp = $ids( $products_of( $c ) ) ) ) {
				$covered = array_merge( $covered, $cp );
				$stocked++;
			}
		}
		if ( $stocked <= 1 && array_diff( $own, $covered ) ) {
			$children = array();
		}
	}
	if ( $children ) {
		$head = array( 'Range', 'Temperature', 'Widths', 'Models', 'From' );
		$rows = array();
		foreach ( $children as $cid ) {
			$c = get_term( $cid, 'product_cat' );
			if ( ! $c || is_wp_error( $c ) ) { continue; }
			$ps = array_filter( $products_of( $c ), static fn( $p ) => $fits( $p, $c ) );
			if ( ! $ps ) { continue; }
			$t_nums = array();
			$widths = array();
			$prices = array();
			foreach ( $ps as $p ) {
				$s      = $spec( $p );
				$t_nums = array_merge( $t_nums, $temps( $s['temp'] ) );
				if ( $s['width'] ) { $widths[] = $s['width']; }
				if ( '' !== (string) $p->get_price() ) { $prices[] = (float) $p->get_price(); }
			}
			if ( ! preg_match( '~heat|hot~', $c->slug ) ) {
				// Chilled/frozen ranges: ignore heated models filed in the same category.
				$t_nums = array_filter( $t_nums, static fn( $n ) => $n <= 20 );
			}
			$temp_txt  = $t_nums ? $range_txt( min( $t_nums ), max( $t_nums ) ) : '–';
			$width_txt = $widths ? ( min( $widths ) === max( $widths ) ? min( $widths ) . ' mm' : min( $widths ) . '–' . max( $widths ) . ' mm' ) : '–';
			$rows[]    = array(
				'<a href="' . esc_url( wp_make_link_relative( get_term_link( $c ) ) ) . '">' . esc_html( efb_landing_label( $c->term_id ) ) . '</a>',
				$temp_txt,
				$width_txt,
				(string) count( $ps ),
				$prices ? $money( min( $prices ) ) : 'POA',
			);
		}
		return array( 'head' => $rows ? $head : array(), 'rows' => $rows );
	}

	$head = array( 'Model', 'Size (W × D × H)', 'Temperature', 'Price', 'Availability' );
	$rows = array();
	foreach ( $products_of( $t ) as $p ) {
		if ( ! $fits( $p, $t ) ) { continue; }
		$s      = $spec( $p );
		$rows[] = array(
			'<a href="' . esc_url( wp_make_link_relative( $p->get_permalink() ) ) . '">' . esc_html( $p->get_name() ) . '</a>',
			$s['size'] ? $s['size'] : '–',
			$s['temp'] ? $s['temp'] : '–',
			'' !== (string) $p->get_price() ? $money( $p->get_price() ) : 'POA',
			$p->is_in_stock() ? 'In stock' : 'Out of stock',
		);
		if ( count( $rows ) >= 15 ) { break; }
	}
	return array( 'head' => $rows ? $head : array(), 'rows' => $rows );
};

/* ---------------------------------------------------------------------------
 * Data fix: a spec table typo ("18 / -24 °C" on a freezer) that would show up
 * in the comparison tables.
 * ------------------------------------------------------------------------- */
$typo = get_page_by_path( 'frost-tech-upright-white-heavy-duty-double-door-freezer', OBJECT, 'product' );
if ( $typo && false !== strpos( $typo->post_content, '>18 / -24' ) ) {
	wp_update_post( array( 'ID' => $typo->ID, 'post_content' => wp_slash( str_replace( '>18 / -24', '>-18 / -24', $typo->post_content ) ) ) );
	WP_CLI::log( 'Fixed the temperature typo on ' . $typo->post_name );
}

/* ---------------------------------------------------------------------------
 * Apply.
 * ------------------------------------------------------------------------- */
$done = 0;
foreach ( $copy as $slug => $c ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t ) {
		WP_CLI::warning( "Category not found: $slug" );
		continue;
	}
	// One-time backup of the previous texts.
	if ( '' === (string) get_term_meta( $t->term_id, '_ef_legacy_name', true ) ) {
		update_term_meta( $t->term_id, '_ef_legacy_name', $t->name );
		update_term_meta( $t->term_id, '_ef_legacy_description', $t->description );
		update_term_meta( $t->term_id, '_ef_legacy_range_content', (string) get_term_meta( $t->term_id, '_ef_range_content', true ) );
	}

	wp_update_term( $t->term_id, 'product_cat', array( 'name' => $c['name'], 'description' => $c['short'] ) );
	update_term_meta( $t->term_id, '_ef_range_content', trim( $c['long'] ) );

	$data             = EFB_Landing_Meta::get( $t->term_id );
	unset( $data['long_desc'] );
	$data['faq']      = array_map( static fn( $qa ) => array( 'q' => $qa[0], 'a' => $qa[1] ), $c['faq'] );
	$data['articles'] = array_values( array_filter( array_map( 'intval', $c['articles'] ?? array() ), static fn( $id ) => 'publish' === get_post_status( $id ) ) );
	$data['table']    = $table_for( get_term( $t->term_id, 'product_cat' ) );
	$data['titles']   = array_merge(
		(array) $data['titles'],
		array(
			'faq'        => '{category}: frequently asked questions',
			'comparison' => 'Compare our {category}',
			'articles'   => 'Buying guides & articles',
		)
	);
	EFB_Landing_Meta::save( $t->term_id, $data );
	$done++;
}
/*
 * Menus keep their labels: a menu item without its own title shows the
 * category name, which this migration changed (only the H1 was meant to
 * change). Give those items the previous name as their own title.
 */
$frozen = 0;
foreach ( wp_get_nav_menus() as $menu ) {
	foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
		if ( 'taxonomy' !== $item->type || 'product_cat' !== $item->object || '' !== trim( (string) get_post_field( 'post_title', $item->ID ) ) ) {
			continue;
		}
		$legacy = (string) get_term_meta( (int) $item->object_id, '_ef_legacy_name', true );
		if ( '' !== $legacy ) {
			wp_update_post( array( 'ID' => $item->ID, 'post_title' => wp_slash( html_entity_decode( $legacy, ENT_QUOTES, 'UTF-8' ) ) ) );
			$frozen++;
		}
	}
}

WP_CLI::success( "Category content applied to $done categories; $frozen menu labels kept as before." );
