<?php
/**
 * Pasul 14: setează ordinea sub-categoriilor Display Cabinets și Storage Cabinets
 * 1:1 cu site-ul original eco-fridge.co.uk (term meta `order` — folosit și de
 * WooCommerce, editabil din Products → Categories). Rulare: wp eval-file _run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Ordinea EXACTĂ din homepage-ul original (slug-uri), Display apoi Storage.
$order = array(
	// Display Cabinets (44)
	'standard-counter-display-chiller',
	'dual-temperature-hot-and-cold-premium-mirrored-stainless-steel-multideck',
	'heated-display-cabinets',
	'back-bar-display-chillers',
	'double-glazed-counter-display-chiller',
	'double-glazed-counter-top-display',
	'premium-grab-and-go-display-fridges',
	'premium-mirrored-stainless-steel-multidecks',
	'premium-mirrored-stainless-steel-shutter-multidecks',
	'premium-patisserie-display-fridges',
	'upright-cake-displays',
	'upright-display-freezers',
	'upright-display-chillers',
	'serve-over-display',
	'glass-lid-chest-freezer',
	'black-steel-slimline-multideck-display-fridge',
	'standard-grab-and-go-display-fridges',
	'patisserie-display-fridges',
	'standard-patisserie-slimline-display-fridges',
	'silver-steel-multideck-display-chillers',
	'standard-upright-display-freezers',
	'upright-display-chiller',
	// Storage Cabinets (45) — ordine custom din original
	'stainless-steel-counter-chillers',
	'stainless-steel-counter-freezers',
	'standard-upright-storage-freezer',
	'premium-stainless-steel-under-counter-freezer',
	'stainless-steel-under-counter-chiller',
	'upright-stainless-steel-gastronorm-chillers',
	'upright-stainless-steel-gastronorm-freezers',
	'upright-storage-chillers',
	'upright-storage-freezers',
	'standard-stainless-steel-under-counter-chiller',
	'standard-stainless-steel-under-counter-freezer',
	'stainless-steel-upright-bakery-cabinets',
	'upright-storage-chiller',
	'upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet',
	'standard-upright-stainless-steel-gastronorm-freezers',
);

$set = 0; $missing = array();
foreach ( $order as $i => $slug ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t || is_wp_error( $t ) ) { $missing[] = $slug; continue; }
	update_term_meta( $t->term_id, 'order', $i + 1 );
	$set++;
}

WP_CLI::log( 'Ordine setată pe ' . $set . ' categorii.' );
if ( $missing ) { WP_CLI::warning( 'Slug-uri negăsite: ' . implode( ', ', $missing ) ); }
WP_CLI::success( 'Ordine sub-categorii Display/Storage finalizată.' );
