<?php
/**
 * Migrare produse eco-fridge: legacy (Woo 3.5.10 + Toolset Types) -> Woo modern.
 *
 * Rulare (din container, sursă mapată în source-dump):
 *   wp eval-file wp-content/source-dump/_run.php
 *
 * Citește direct din containerul MariaDB `ef-legacy-db` (db `legacy`, prefix 43lz2ypq8_).
 * Creează produse simple prin WooCommerce CRUD (regenerează lookup tables).
 *
 * Remapări (vezi AUDIT.md §6):
 *   - product_cat + range + ranges + condition  -> product_cat (consolidat, dedupe pe slug, ierarhie)
 *   - product_tag                               -> product_tag
 *   - pa_model                                  -> atribut global (ascuns, ca în original)
 *   - wpcf-features        -> descriere scurtă (short description)
 *   - wpcf-spec-table(+2)  -> descriere lungă (shortcode-uri [table id=X /] TablePress)
 *   - wpcf-model-dimensions-> atribut local vizibil "Dimensions"
 *   - wpcf-from-price      -> meta _ef_from_price (afișare £POA via temă copil)
 *   - wpcf-youtube-id-number, wpcf-brochure-link, wpcf-graded-unit-* -> meta _ef_*
 *   - _thumbnail_id / _product_image_gallery    -> meta _ef_legacy_thumb/_gallery (atașare după sync media)
 *   - _upsell_ids / _crosssell_ids              -> meta _ef_legacy_upsell/_crosssell (remap în pasul 2)
 * Idempotent: sare peste produsele deja importate (meta _ef_legacy_id).
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { WP_CLI::error( 'WooCommerce nu e activ.' ); }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Nu pot conecta la legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

$Q = function ( $sql ) use ( $db ) {
	$r = mysqli_query( $db, $sql );
	if ( false === $r ) { WP_CLI::error( mysqli_error( $db ) . ' | ' . $sql ); }
	return $r;
};

/* ---------------------------------------------------------------------------
 * 1. Arbore categorii: product_cat + range + ranges + condition -> product_cat
 * ------------------------------------------------------------------------- */
$cat_tax = "'product_cat','range','ranges','condition'";
$legacy_terms = array(); // legacy term_id => row
$res = $Q( "SELECT tt.term_id, t.name, t.slug, tt.parent, tt.taxonomy, tt.description
            FROM {$LP}term_taxonomy tt JOIN {$LP}terms t ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($cat_tax)" );
while ( $row = mysqli_fetch_assoc( $res ) ) { $legacy_terms[ $row['term_id'] ] = $row; }

$slug_to_new = array();     // slug => new product_cat term_id
$legacy_cat_to_new = array(); // legacy term_id => new product_cat term_id

$create_cat = function ( $legacy_id ) use ( &$create_cat, &$legacy_terms, &$slug_to_new, &$legacy_cat_to_new ) {
	if ( isset( $legacy_cat_to_new[ $legacy_id ] ) ) { return $legacy_cat_to_new[ $legacy_id ]; }
	if ( ! isset( $legacy_terms[ $legacy_id ] ) ) { return 0; }
	$t    = $legacy_terms[ $legacy_id ];
	$slug = $t['slug'];
	if ( isset( $slug_to_new[ $slug ] ) ) { $legacy_cat_to_new[ $legacy_id ] = $slug_to_new[ $slug ]; return $slug_to_new[ $slug ]; }
	$parent_new = 0;
	if ( $t['parent'] && isset( $legacy_terms[ $t['parent'] ] ) ) { $parent_new = $create_cat( $t['parent'] ); }
	$existing = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $existing ) {
		$new_id = (int) $existing->term_id;
	} else {
		$ins = wp_insert_term( $t['name'], 'product_cat', array( 'slug' => $slug, 'parent' => $parent_new, 'description' => $t['description'] ) );
		if ( is_wp_error( $ins ) ) {
			$ex = get_term_by( 'name', $t['name'], 'product_cat' );
			$new_id = $ex ? (int) $ex->term_id : 0;
		} else {
			$new_id = (int) $ins['term_id'];
		}
	}
	$slug_to_new[ $slug ] = $new_id;
	$legacy_cat_to_new[ $legacy_id ] = $new_id;
	return $new_id;
};
foreach ( array_keys( $legacy_terms ) as $lid ) { $create_cat( $lid ); }

/* ---------------------------------------------------------------------------
 * 2. Taguri produs
 * ------------------------------------------------------------------------- */
$tag_to_new = array();
$res = $Q( "SELECT tt.term_id, t.name, t.slug FROM {$LP}term_taxonomy tt JOIN {$LP}terms t ON t.term_id = tt.term_id WHERE tt.taxonomy='product_tag'" );
while ( $row = mysqli_fetch_assoc( $res ) ) {
	$ex = get_term_by( 'slug', $row['slug'], 'product_tag' );
	if ( $ex ) { $id = (int) $ex->term_id; }
	else { $ins = wp_insert_term( $row['name'], 'product_tag', array( 'slug' => $row['slug'] ) ); $id = is_wp_error( $ins ) ? 0 : (int) $ins['term_id']; }
	if ( $id ) { $tag_to_new[ $row['term_id'] ] = $id; }
}

/* ---------------------------------------------------------------------------
 * 3. Atribut global: Model (pa_model)
 * ------------------------------------------------------------------------- */
$model_attr_id = wc_attribute_taxonomy_id_by_name( 'model' );
if ( ! $model_attr_id ) {
	$model_attr_id = wc_create_attribute( array( 'name' => 'Model', 'slug' => 'model', 'type' => 'select', 'order_by' => 'name', 'has_archives' => false ) );
	if ( is_wp_error( $model_attr_id ) ) { WP_CLI::error( 'attr model: ' . $model_attr_id->get_error_message() ); }
}
if ( ! taxonomy_exists( 'pa_model' ) ) {
	register_taxonomy( 'pa_model', 'product', array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
}
$model_to_new = array();
$res = $Q( "SELECT tt.term_id, t.name, t.slug FROM {$LP}term_taxonomy tt JOIN {$LP}terms t ON t.term_id = tt.term_id WHERE tt.taxonomy='pa_model'" );
while ( $row = mysqli_fetch_assoc( $res ) ) {
	$ex = get_term_by( 'slug', $row['slug'], 'pa_model' );
	if ( $ex ) { $id = (int) $ex->term_id; }
	else { $ins = wp_insert_term( $row['name'], 'pa_model', array( 'slug' => $row['slug'] ) ); $id = is_wp_error( $ins ) ? 0 : (int) $ins['term_id']; }
	if ( $id ) { $model_to_new[ $row['term_id'] ] = $id; }
}

/* ---------------------------------------------------------------------------
 * 4. Produse
 * ------------------------------------------------------------------------- */
$res = $Q( "SELECT * FROM {$LP}posts WHERE post_type='product' AND post_status IN('publish','draft','private','pending') ORDER BY ID" );
$products = array();
while ( $row = mysqli_fetch_assoc( $res ) ) { $products[] = $row; }

$graded_keys = array( 'price', 'original-price', 'model', 'damage', 'dimensions', 'shelves', 'temperature', 'weight' );
$id_map = array();
$count = 0; $skipped = 0; $errors = array();

foreach ( $products as $p ) {
	$old_id = (int) $p['ID'];

	// idempotency
	$exist = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'meta_key' => '_ef_legacy_id', 'meta_value' => $old_id, 'fields' => 'ids', 'posts_per_page' => 1 ) );
	if ( $exist ) { $id_map[ $old_id ] = (int) $exist[0]; $skipped++; continue; }

	// meta
	$meta = array();
	$mr = $Q( "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id" );
	while ( $m = mysqli_fetch_assoc( $mr ) ) { $meta[ $m['meta_key'] ] = $m['meta_value']; }

	// terms
	$cat_ids = array(); $tag_ids = array(); $model_ids = array();
	$tr = $Q( "SELECT tt.taxonomy, tt.term_id FROM {$LP}term_relationships r JOIN {$LP}term_taxonomy tt ON tt.term_taxonomy_id=r.term_taxonomy_id WHERE r.object_id=$old_id" );
	while ( $t = mysqli_fetch_assoc( $tr ) ) {
		$tid = $t['term_id'];
		if ( in_array( $t['taxonomy'], array( 'product_cat', 'range', 'ranges', 'condition' ), true ) ) {
			if ( isset( $legacy_cat_to_new[ $tid ] ) && $legacy_cat_to_new[ $tid ] ) { $cat_ids[] = $legacy_cat_to_new[ $tid ]; }
		} elseif ( 'product_tag' === $t['taxonomy'] ) {
			if ( isset( $tag_to_new[ $tid ] ) ) { $tag_ids[] = $tag_to_new[ $tid ]; }
		} elseif ( 'pa_model' === $t['taxonomy'] ) {
			if ( isset( $model_to_new[ $tid ] ) ) { $model_ids[] = $model_to_new[ $tid ]; }
		}
	}

	try {
		$prod = new WC_Product_Simple();
		$prod->set_name( $p['post_title'] );
		$prod->set_slug( $p['post_name'] );
		$prod->set_status( $p['post_status'] );
		$date = ( $p['post_date_gmt'] && '0000-00-00 00:00:00' !== $p['post_date_gmt'] ) ? $p['post_date_gmt'] : $p['post_date'];
		$prod->set_date_created( $date );
		$prod->set_menu_order( (int) $p['menu_order'] );

		// descrieri
		$features = isset( $meta['wpcf-features'] ) ? $meta['wpcf-features'] : '';
		$spec     = trim( ( isset( $meta['wpcf-spec-table'] ) ? $meta['wpcf-spec-table'] : '' ) . "\n" . ( isset( $meta['wpcf-spec-table-2'] ) ? $meta['wpcf-spec-table-2'] : '' ) );
		$long     = trim( trim( $p['post_content'] ) . "\n\n" . $spec );
		$prod->set_short_description( $features );
		$prod->set_description( $long );

		// preț / stoc
		if ( isset( $meta['_regular_price'] ) && '' !== $meta['_regular_price'] ) { $prod->set_regular_price( $meta['_regular_price'] ); }
		if ( isset( $meta['_sale_price'] ) && '' !== $meta['_sale_price'] ) { $prod->set_sale_price( $meta['_sale_price'] ); }
		if ( isset( $meta['_sku'] ) && '' !== $meta['_sku'] ) {
			$dupe = wc_get_product_id_by_sku( $meta['_sku'] );
			if ( ! $dupe ) { $prod->set_sku( $meta['_sku'] ); }
		}
		$prod->set_manage_stock( ( isset( $meta['_manage_stock'] ) ? $meta['_manage_stock'] : '' ) === 'yes' );
		if ( isset( $meta['_stock'] ) && '' !== $meta['_stock'] ) { $prod->set_stock_quantity( (int) $meta['_stock'] ); }
		$prod->set_stock_status( isset( $meta['_stock_status'] ) ? $meta['_stock_status'] : 'instock' );
		foreach ( array( '_weight' => 'set_weight', '_length' => 'set_length', '_width' => 'set_width', '_height' => 'set_height' ) as $mk => $fn ) {
			if ( isset( $meta[ $mk ] ) && '' !== $meta[ $mk ] ) { $prod->$fn( $meta[ $mk ] ); }
		}
		if ( ! empty( $meta['_tax_class'] ) ) { $prod->set_tax_class( $meta['_tax_class'] ); }
		$prod->set_sold_individually( ( isset( $meta['_sold_individually'] ) ? $meta['_sold_individually'] : '' ) === 'yes' );
		if ( ! empty( $meta['_purchase_note'] ) ) { $prod->set_purchase_note( $meta['_purchase_note'] ); }
		$prod->set_virtual( ( isset( $meta['_virtual'] ) ? $meta['_virtual'] : '' ) === 'yes' );
		$prod->set_downloadable( ( isset( $meta['_downloadable'] ) ? $meta['_downloadable'] : '' ) === 'yes' );
		$prod->set_backorders( isset( $meta['_backorders'] ) ? $meta['_backorders'] : 'no' );
		if ( ! empty( $meta['_visibility'] ) && in_array( $meta['_visibility'], array( 'visible', 'catalog', 'search', 'hidden' ), true ) ) { $prod->set_catalog_visibility( $meta['_visibility'] ); }
		$prod->set_featured( ( isset( $meta['_featured'] ) ? $meta['_featured'] : '' ) === 'yes' );

		// categorii / taguri
		if ( $cat_ids ) { $prod->set_category_ids( array_values( array_unique( $cat_ids ) ) ); }
		if ( $tag_ids ) { $prod->set_tag_ids( array_values( array_unique( $tag_ids ) ) ); }

		// atribute
		$attrs = array(); $pos = 0;
		if ( ! empty( $meta['wpcf-model-dimensions'] ) ) {
			$a = new WC_Product_Attribute();
			$a->set_name( 'Dimensions' );
			$a->set_options( array( $meta['wpcf-model-dimensions'] ) );
			$a->set_visible( true );
			$a->set_position( $pos++ );
			$attrs[] = $a;
		}
		if ( $model_ids ) {
			$a = new WC_Product_Attribute();
			$a->set_id( $model_attr_id );
			$a->set_name( 'pa_model' );
			$a->set_options( array_values( array_unique( $model_ids ) ) );
			$a->set_visible( false );
			$a->set_position( $pos++ );
			$attrs[] = $a;
		}
		if ( $attrs ) { $prod->set_attributes( $attrs ); }

		// meta remapate + referințe legacy
		$prod->update_meta_data( '_ef_legacy_id', $old_id );
		if ( isset( $meta['wpcf-from-price'] ) && '' !== $meta['wpcf-from-price'] ) { $prod->update_meta_data( '_ef_from_price', $meta['wpcf-from-price'] ); }
		if ( ! empty( $meta['wpcf-youtube-id-number'] ) ) { $prod->update_meta_data( '_ef_youtube_id', $meta['wpcf-youtube-id-number'] ); }
		if ( ! empty( $meta['wpcf-brochure-link'] ) ) { $prod->update_meta_data( '_ef_brochure_link', $meta['wpcf-brochure-link'] ); }
		foreach ( $graded_keys as $gk ) {
			$src = 'wpcf-graded-unit-' . $gk;
			if ( isset( $meta[ $src ] ) && '' !== $meta[ $src ] ) { $prod->update_meta_data( '_ef_graded_' . str_replace( '-', '_', $gk ), $meta[ $src ] ); }
		}
		if ( isset( $meta['_wapo_disable_global'] ) ) { $prod->update_meta_data( '_wapo_disable_global', $meta['_wapo_disable_global'] ); }
		if ( ! empty( $meta['_thumbnail_id'] ) ) { $prod->update_meta_data( '_ef_legacy_thumb', $meta['_thumbnail_id'] ); }
		if ( ! empty( $meta['_product_image_gallery'] ) ) { $prod->update_meta_data( '_ef_legacy_gallery', $meta['_product_image_gallery'] ); }
		if ( ! empty( $meta['_upsell_ids'] ) ) { $prod->update_meta_data( '_ef_legacy_upsell', $meta['_upsell_ids'] ); }
		if ( ! empty( $meta['_crosssell_ids'] ) ) { $prod->update_meta_data( '_ef_legacy_crosssell', $meta['_crosssell_ids'] ); }

		$new_id = $prod->save();
		if ( ! $new_id ) { throw new Exception( 'save() a returnat 0' ); }
		$id_map[ $old_id ] = (int) $new_id;
		$count++;
	} catch ( Exception $e ) {
		$errors[] = "produs $old_id ({$p['post_title']}): " . $e->getMessage();
	}
}

// harta id vechi->nou pentru pasul 2 (upsells) + media
file_put_contents( ABSPATH . 'wp-content/source-dump/_product-id-map.json', wp_json_encode( $id_map ) );

WP_CLI::log( 'Categorii create/consolidate: ' . count( $slug_to_new ) );
WP_CLI::log( 'Taguri: ' . count( $tag_to_new ) . ' | Modele (attr): ' . count( $model_to_new ) );
WP_CLI::log( 'Produse importate: ' . $count . ' | sărite (deja existau): ' . $skipped );
if ( $errors ) {
	WP_CLI::warning( 'Erori (' . count( $errors ) . '):' );
	foreach ( $errors as $e ) { WP_CLI::log( '  - ' . $e ); }
}
WP_CLI::success( 'Migrare produse (pasul 1) finalizată.' );
