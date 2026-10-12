<?php
/**
 * 40 — Products moved into the right categories.
 *
 * The category assignments inherited from the old site had mistakes that
 * showed up in category grids: ovens and bakery cabinets under Meat Fridge /
 * Storage Fridge, low temperature freezers and heated displays under Display
 * Fridge, a chilled counter under Commercial Freezer, a closed glass display
 * under Multidecks, and some products missing from the categories their
 * sibling models are in.
 *
 * Each change names the product ID and its expected name (checked before
 * changing anything). Run migration 39 afterwards to refresh the comparison
 * tables. Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$changes = array(
	// Ovens, grill and oven stand: bakery equipment only.
	151 => array( 'Commercial Stainless Steel Oven', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	140 => array( 'Commercial Stainless Steel Oven and Grill with Steamer', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	150 => array( 'Commercial Stainless Steel Large Oven with Grill and Steamer', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	141 => array( 'Commercial Stainless Steel Large Oven with Steamer', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	152 => array( 'Commercial Stainless Steel Large Oven Stand', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	142 => array( 'Commercial Stainless Steel Contact Grill', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	// Bakery cabinets: not meat fridges; the freezer is a freezer, the prover not a fridge.
	132 => array( 'Stainless Steel Upright Bakery Fridge', array( 'gastronorm-fridge' ), array() ),
	133 => array( 'Stainless Steel Upright Bakery Freezer', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array( 'commercial-freezer', 'storage-freezer', 'upright-freezer-storage-freezer' ) ),
	131 => array( 'Stainless Steel Upright Bakery Retarder Prover', array( 'gastronorm-fridge', 'storage-fridge', 'upright-fridge-storage-fridge' ), array() ),
	// Low temperature freezers are not display fridges.
	128 => array( 'Low Temperature Chest Freezer -45', array( 'commercial-display-fridge' ), array() ),
	129 => array( 'Low Temperature Chest Freezer -65', array( 'commercial-display-fridge' ), array() ),
	139 => array( 'Low Temperature Upright Freezer -45', array( 'commercial-display-fridge' ), array() ),
	// Heated displays are not display fridges (the hot & cold multideck stays).
	124 => array( 'Stainless Mirrored Steel Hot Pastry Display 900MM', array( 'commercial-display-fridge' ), array() ),
	123 => array( 'Stainless Mirrored Steel Heated Grab & Go Open Pastry Display', array( 'commercial-display-fridge' ), array() ),
	24  => array( 'Stainless Mirrored Steel Heated Pastry Display', array( 'commercial-display-fridge' ), array() ),
	91  => array( 'Silver Steel Heated Double Glazed Display Counter', array( 'commercial-display-fridge' ), array() ),
	// A chilled counter display, not a freezer.
	106 => array( 'Premium Glass Counter Display Fridge Stainless Steel Double Glazed UV Bonded', array( 'commercial-freezer' ), array( 'commercial-fridge' ) ),
	// A closed glass display with rear sliding doors, not an open multideck.
	143 => array( 'Upright Mirrored Stainless Steel Large Glass Display Fridge', array( 'multideck-fridge', 'premium-mirrored-stainless-steel-multidecks' ), array() ),
	// Missing from the categories their sibling models are in.
	46  => array( 'White Heavy Duty Large Door Upright Freezer', array(), array( 'commercial-freezer', 'storage-freezer', 'upright-freezer-storage-freezer' ) ),
	30  => array( 'Double Door Upright Display Freezer', array(), array( 'commercial-freezer', 'display-freezer', 'upright-freezer', 'upright-display-cabinets' ) ),
	17  => array( 'Eco Premium Two Door Counter Freezer', array(), array( 'storage-freezer', 'under-counter-freezer' ) ),
	64  => array( 'Slim Serve Over - Standard Silver Steel 1000mm', array(), array( 'commercial-display-fridge' ) ),
);

$changed = 0;
foreach ( $changes as $id => $c ) {
	list( $expected, $remove, $add ) = $c;
	$product = wc_get_product( $id );
	if ( ! $product || 0 !== strpos( html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ), $expected ) ) {
		WP_CLI::warning( "Skipped #$id: expected \"$expected\"." );
		continue;
	}
	$slugs = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
	$new   = array_values( array_unique( array_merge( array_diff( $slugs, $remove ), $add ) ) );
	sort( $slugs );
	sort( $new );
	if ( $new === $slugs ) {
		continue;
	}
	$ids = array();
	foreach ( $new as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $t ) {
			$ids[] = (int) $t->term_id;
		} else {
			WP_CLI::warning( "Category not found: $slug" );
		}
	}
	wp_set_object_terms( $id, $ids, 'product_cat' );
	WP_CLI::log( sprintf( '#%d %s: −[%s] +[%s]', $id, $expected, implode( ', ', array_intersect( $remove, $slugs ) ), implode( ', ', array_diff( $add, $slugs ) ) ) );
	$changed++;
}

// WooCommerce's own product counts per category (they include subcategories).
$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids' ) );
_wc_term_recount( $terms, get_taxonomy( 'product_cat' ), true, false );
if ( class_exists( 'EFB_Landing_Meta' ) ) {
	EFB_Landing_Meta::bump_cache();
}

WP_CLI::success( "$changed products moved into the right categories. Now re-run migration 39 to refresh the comparison tables." );
