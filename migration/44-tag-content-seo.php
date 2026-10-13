<?php
/**
 * 44 — Product tags and markets: landing content (SEO).
 *
 * Product tags and markets (/markets/<slug>/) get the same landing page as the
 * commercial categories (ecofridge-blocks: efb_landing_is_tag). For every term
 * in migration/tag-copy/*.php (product tags) and migration/market-copy/*.php
 * (markets) this sets the short description (term Description),
 * the long description (`_ef_range_content`), the FAQ and related articles,
 * the section headings, and a comparison table built from the tag's products:
 * one row per model on small tags, one row per category on large ones.
 *
 * Re-run after price or product changes to refresh the tables. Idempotent.
 * Run via source-dump/_run.php (copy migration/tag-copy/ and
 * migration/market-copy/ to source-dump/_tag-copy/ and source-dump/_market-copy/
 * first – see the README in those folders). Run migration 45 first.
 *
 * @package EcoFridgeMigration
 */

$sources = array(
	'product_tag' => WP_CONTENT_DIR . '/source-dump/_tag-copy',
	'market'      => WP_CONTENT_DIR . '/source-dump/_market-copy',
);
$copy    = array();
foreach ( $sources as $taxonomy => $dir ) {
	foreach ( glob( $dir . '/*.php' ) as $file ) {
		foreach ( (array) require $file as $slug => $c ) {
			$copy[] = array( $taxonomy, $slug, $c );
		}
	}
}
if ( ! $copy ) {
	WP_CLI::error( 'No tag or market copy found in source-dump/_tag-copy or _market-copy.' );
}

/** Size (W × D × H), width and temperature of a product, from its first spec table. */
$spec = static function ( WC_Product $p ) {
	$out = array( 'size' => '', 'temp' => '', 'width' => 0 );
	if ( ! preg_match( '~<table\b.*?</table>~is', $p->get_description(), $m ) ) {
		return $out;
	}
	preg_match_all( '~<tr\b.*?</tr>~is', $m[0], $trs );
	$rows = array();
	foreach ( $trs[0] as $tr ) {
		preg_match_all( '~<t[hd]\b[^>]*>(.*?)</t[hd]>~is', $tr, $cells );
		$rows[] = array_map( static fn( $c ) => trim( html_entity_decode( wp_strip_all_tags( $c ), ENT_QUOTES, 'UTF-8' ) ), $cells[1] );
	}
	if ( count( $rows ) < 2 ) {
		return $out;
	}
	$di = $ti = null;
	foreach ( array_map( 'strtolower', $rows[0] ) as $i => $h ) {
		if ( null === $di && ( false !== strpos( $h, 'dimension' ) || false !== strpos( str_replace( ' ', '', $h ), 'wxdxh' ) ) ) { $di = $i; }
		if ( null === $ti && false !== strpos( $h, 'temp' ) ) { $ti = $i; }
	}
	$row = $rows[1];
	if ( null !== $di && preg_match( '~(\d{3,4})\s*mm~i', $p->get_name(), $w ) ) {
		foreach ( array_slice( $rows, 1 ) as $r ) {
			if ( isset( $r[ $di ] ) && 0 === strpos( preg_replace( '~\s+~', '', $r[ $di ] ), $w[1] . 'mm' ) ) { $row = $r; break; }
		}
	}
	if ( null !== $di && ! empty( $row[ $di ] ) ) {
		$out['size']  = preg_replace( '~\s*x\s*~i', ' × ', $row[ $di ] );
		$out['width'] = (int) $row[ $di ];
	}
	if ( null !== $ti && ! empty( $row[ $ti ] ) ) {
		$out['temp'] = $row[ $ti ];
	}
	return $out;
};
$money = static fn( $v ) => '£' . number_format( (float) $v, 0 ) . ' +VAT';
$temps = static function ( $text ) {
	preg_match_all( '~[-+−]?\s?\d+~u', str_replace( '−', '-', (string) $text ), $m );
	return array_map( static fn( $n ) => (int) str_replace( ' ', '', $n ), $m[0] );
};
$range_txt = static function ( $min, $max ) {
	$fmt = static fn( $n ) => ( $n > 0 ? '+' : ( $n < 0 ? '−' : '' ) ) . abs( $n );
	return $max < 0 ? $fmt( $max ) . ' to ' . $fmt( $min ) . ' °C' : $fmt( $min ) . ' to ' . $fmt( $max ) . ' °C';
};
$by_price = static function ( array $products ) {
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

/** Comparison table of a tag or market. */
$table_for = function ( WP_Term $tag ) use ( $spec, $money, $temps, $range_txt, $by_price ) {
	$ids      = EFB_Landing_Reviews::product_ids( $tag->term_id );
	$products = $ids ? $by_price( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'include' => $ids ) ) ) : array();
	if ( ! $products ) {
		return array( 'head' => array(), 'rows' => array() );
	}
	// Small tags: one row per model.
	if ( count( $products ) <= 15 ) {
		$rows = array();
		foreach ( $products as $p ) {
			$s      = $spec( $p );
			$rows[] = array(
				'<a href="' . esc_url( wp_make_link_relative( $p->get_permalink() ) ) . '">' . esc_html( $p->get_name() ) . '</a>',
				$s['size'] ? $s['size'] : '–',
				$s['temp'] ? $s['temp'] : '–',
				'' !== (string) $p->get_price() ? $money( $p->get_price() ) : 'POA',
				$p->is_in_stock() ? 'In stock' : 'Out of stock',
			);
		}
		return array( 'head' => array( 'Model', 'Size (W × D × H)', 'Temperature', 'Price', 'Availability' ), 'rows' => $rows );
	}
	// Large tags: one row per category (the tag's models in it).
	$rows = array();
	foreach ( efb_landing_tag_categories( $tag->term_id, 10 ) as $cid ) {
		$cat = get_term( $cid, 'product_cat' );
		if ( ! $cat || is_wp_error( $cat ) ) { continue; }
		$in     = array_filter( $products, static fn( $p ) => has_term( $cid, 'product_cat', $p->get_id() ) );
		$t_nums = array();
		$widths = array();
		$prices = array();
		foreach ( $in as $p ) {
			$s = $spec( $p );
			$t_nums = array_merge( $t_nums, $temps( $s['temp'] ) );
			if ( $s['width'] ) { $widths[] = $s['width']; }
			if ( '' !== (string) $p->get_price() ) { $prices[] = (float) $p->get_price(); }
		}
		if ( ! $in ) { continue; }
		$rows[] = array(
			'<a href="' . esc_url( wp_make_link_relative( get_term_link( $cat ) ) ) . '">' . esc_html( efb_landing_label( $cat->term_id ) ) . '</a>',
			$t_nums ? $range_txt( min( $t_nums ), max( $t_nums ) ) : '–',
			$widths ? ( min( $widths ) === max( $widths ) ? min( $widths ) . ' mm' : min( $widths ) . '–' . max( $widths ) . ' mm' ) : '–',
			(string) count( $in ),
			$prices ? $money( min( $prices ) ) : 'POA',
		);
	}
	return array( 'head' => $rows ? array( 'Range', 'Temperature', 'Widths', 'Models', 'From' ) : array(), 'rows' => $rows );
};

$done = 0;
foreach ( $copy as list( $taxonomy, $slug, $c ) ) {
	$tag = get_term_by( 'slug', $slug, $taxonomy );
	if ( ! $tag ) {
		WP_CLI::warning( "Term not found: $taxonomy/$slug" );
		continue;
	}
	wp_update_term( $tag->term_id, $taxonomy, array( 'description' => $c['short'] ) );
	update_term_meta( $tag->term_id, '_ef_range_content', trim( $c['long'] ) );

	$data             = EFB_Landing_Meta::get( $tag->term_id );
	unset( $data['long_desc'] );
	$data['faq']      = array_map( static fn( $qa ) => array( 'q' => $qa[0], 'a' => $qa[1] ), $c['faq'] );
	$data['articles'] = array_values( array_filter( array_map( 'intval', $c['articles'] ), static fn( $id ) => 'publish' === get_post_status( $id ) ) );
	$data['table']    = $table_for( get_term( $tag->term_id, $taxonomy ) );
	$data['parts']             = false;
	$data['products_carousel'] = false;
	$data['titles']   = array_merge(
		(array) $data['titles'],
		array(
			'faq'          => '{category}: frequently asked questions',
			'comparison'   => 'Compare {category}',
			'articles'     => 'Buying guides & articles',
			'related_cats' => 'Shop by category',
		)
	);
	EFB_Landing_Meta::save( $tag->term_id, $data );
	$done++;
}
WP_CLI::success( "Landing content applied to $done tags and markets." );
