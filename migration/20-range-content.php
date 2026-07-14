<?php
/**
 * Pasul 20: importă CONȚINUTUL LONG-FORM al categoriilor/range-urilor din originalul
 * eco-fridge (câmp Toolset Types `wpcf-range-content-2` pe termenii legacy), 1:1 cu
 * site-ul live. NU e nevoie de crawl — conținutul e exact în DB-ul legacy, iar toate
 * cele 127 de imagini referite există deja în media sincronizată.
 *
 * Îl stochează în term meta `_ef_range_content` pe categoriile noastre product_cat
 * (mapate după slug), cu URL-urile absolute rescrise relativ (portabil la orice domeniu).
 * Randarea (sub grila de produse) + editarea se fac din tema copil (inc/category-content.php).
 *
 * Idempotent. Necesită containerul `ef-legacy-db` pornit (vezi memoria de reluare).
 * Rulare: cp migration/20-range-content.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Nu mă pot conecta la legacy DB (pornește containerul ef-legacy-db).' ); }
mysqli_set_charset( $db, 'utf8mb4' );

$res = mysqli_query( $db,
	"SELECT t.slug, tm.meta_value
	 FROM {$LP}termmeta tm
	 JOIN {$LP}terms t ON t.term_id = tm.term_id
	 WHERE tm.meta_key = 'wpcf-range-content-2' AND CHAR_LENGTH(tm.meta_value) > 50"
);
if ( ! $res ) { WP_CLI::error( 'Query legacy eșuat: ' . mysqli_error( $db ) ); }

/** Rescrie URL-urile absolute (live + copie) în relative → portabil la orice domeniu. */
function ef_rewrite_urls( $html ) {
	return str_replace(
		array(
			'https://www.eco-fridge.co.uk', 'http://www.eco-fridge.co.uk',
			'https://eco-fridge.co.uk', 'http://eco-fridge.co.uk',
			'https://eco-fridge.o2dev.de', 'http://eco-fridge.o2dev.de',
		),
		'',
		(string) $html
	);
}

$imported = 0; $skipped = array(); $chars = 0;
while ( $row = mysqli_fetch_assoc( $res ) ) {
	$slug    = $row['slug'];
	$content = trim( (string) $row['meta_value'] );
	if ( '' === $content ) { continue; }

	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) { $skipped[] = $slug; continue; }

	$content = ef_rewrite_urls( $content );
	update_term_meta( $term->term_id, '_ef_range_content', $content );
	$imported++;
	$chars += strlen( $content );
}
mysqli_close( $db );

WP_CLI::log( "Conținut categorie importat: $imported termeni (" . number_format( $chars ) . ' caractere total).' );
if ( $skipped ) {
	WP_CLI::warning( 'Slug-uri legacy fără corespondent product_cat (' . count( $skipped ) . '): ' . implode( ', ', $skipped ) );
}
WP_CLI::success( 'Conținut long-form categorii importat (term meta _ef_range_content).' );
