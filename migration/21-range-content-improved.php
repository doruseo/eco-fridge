<?php
/**
 * Pasul 21: suprascrie conținutul de categorie cu versiunile ÎMBUNĂTĂȚITE (text rescris
 * în engleză britanică profesională, cu TOATE imaginile și linkurile păstrate 1:1).
 * Rulează DUPĂ 20-range-content.php (care importă originalul spam din legacy).
 *
 * Sursa = fișierele din migration/category-content/<slug>.html (urmărite în git).
 * Rulare: cp migration/21-range-content-improved.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$dir = dirname( __DIR__ ) . '/category-content/';
// în container, migration/ e mapat? nu — folosim source-dump. Copiem la rulare (vezi mai jos).
$dir = '/var/www/html/wp-content/source-dump/_improved/';
if ( ! is_dir( $dir ) ) { WP_CLI::error( 'Lipsește ' . $dir . ' — copiază migration/category-content/* acolo întâi.' ); }
$n = 0;
foreach ( glob( $dir . '*.html' ) as $f ) {
	$slug = basename( $f, '.html' );
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t || is_wp_error( $t ) ) { WP_CLI::warning( 'slug fără termen: ' . $slug ); continue; }
	update_term_meta( $t->term_id, '_ef_range_content', file_get_contents( $f ) );
	$n++;
}

// Arhivele de categorie: afișează SUB-CATEGORIILE mai întâi, apoi PRODUSELE.
update_option( 'woocommerce_category_archive_display', 'both' );
update_option( 'woocommerce_shop_page_display', 'both' );
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
	update_term_meta( $term->term_id, 'display_type', '' ); // moștenește setarea globală ('both')
}

WP_CLI::success( "Conținut îmbunătățit importat: $n categorii; afișare = sub-categorii + produse." );
