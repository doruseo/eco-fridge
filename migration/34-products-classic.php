<?php
/**
 * 34 — Product descriptions back to the classic editor + one "Recently viewed".
 *
 * Products are edited with the classic editor again (ecofridge-blocks
 * includes/woocommerce.php), so their block content becomes plain HTML:
 *  - efb/spec-table → an HTML <table> (first row = <thead> header row), which
 *    the classic editor's Table button edits and the site shows as a spec table
 *    (green header and border, sortable, stacked on mobile);
 *  - efb/toggle (the warranty price tables) → [efb_toggle title="…"]…[/efb_toggle];
 *  - core blocks → their saved HTML (block comments removed).
 * A product is written only if its text stays the same (≥ 99.5% similar).
 *
 * Also turns off Astra Pro's "Recently viewed products" on the product page: the
 * theme prints that section itself (inc/woo-single.php), so it appeared twice.
 *
 * Run AFTER 27, 31 and 32 (they write block content). DRY RUN by default:
 *   npx wp-env run cli env EFM_APPLY=1 wp eval-file wp-content/source-dump/_run.php
 *
 * @package EcoFridgeMigration
 */

$apply = (bool) getenv( 'EFM_APPLY' );

/**
 * Blocks → classic HTML.
 *
 * @param array $blocks Parsed blocks.
 * @return string
 */
$to_html = function ( array $blocks ) use ( &$to_html ) {
	$out = array();
	foreach ( $blocks as $b ) {
		$name = $b['blockName'];
		if ( null === $name ) {
			$html = trim( $b['innerHTML'] );
		} elseif ( 'efb/spec-table' === $name ) {
			$head = array_values( (array) ( $b['attrs']['head'] ?? array() ) );
			$rows = array_values( array_filter( (array) ( $b['attrs']['rows'] ?? array() ), 'is_array' ) );
			$cap  = (string) ( $b['attrs']['caption'] ?? '' );
			$html = '<table>' . ( '' !== $cap ? '<caption>' . esc_html( $cap ) . '</caption>' : '' );
			if ( $head ) {
				$html .= '<thead><tr><th>' . implode( '</th><th>', array_map( 'strval', $head ) ) . '</th></tr></thead>';
			}
			$html .= '<tbody>';
			foreach ( $rows as $row ) {
				$html .= '<tr><td>' . implode( '</td><td>', array_map( 'strval', array_values( $row ) ) ) . '</td></tr>';
			}
			$html .= '</tbody></table>';
		} elseif ( 'efb/toggle' === $name ) {
			$title = str_replace( array( '"', ']' ), array( '&quot;', '' ), (string) ( $b['attrs']['title'] ?? 'Show more' ) );
			$class = (string) ( $b['attrs']['className'] ?? '' );
			$html  = '[efb_toggle title="' . $title . '"' . ( '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . "]\n"
				. $to_html( $b['innerBlocks'] ) . "\n[/efb_toggle]";
		} else {
			// Static block: its saved HTML, with inner blocks in place.
			$html = '';
			$i    = 0;
			foreach ( $b['innerContent'] as $chunk ) {
				$html .= is_string( $chunk ) ? $chunk : $to_html( array( $b['innerBlocks'][ $i++ ] ) );
			}
			$html = trim( $html );
		}
		if ( '' !== $html ) {
			// One line per block: wpautop would turn line breaks inside it into <br>.
			$out[] = preg_replace( '~>\s*\n\s*<~', '><', $html );
		}
	}
	return implode( "\n\n", $out );
};

$norm = function ( $html ) {
	$t = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$t = preg_replace( '~\[/?efb_toggle[^\]]*\]~', '', $t );
	return trim( preg_replace( '~\s+~u', ' ', $t ) );
};

$done = 0;
$skip = array();
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1 ) ) as $p ) {
	if ( false === strpos( $p->post_content, '<!-- wp:' ) ) {
		continue; // Already classic.
	}
	$classic = $to_html( parse_blocks( $p->post_content ) );
	$before  = $norm( do_blocks( $p->post_content ) );
	$after   = $norm( do_shortcode( wpautop( $classic ) ) );
	similar_text( $before, $after, $pct );
	if ( $pct < 99.5 ) {
		$skip[] = sprintf( '%s (%.1f%%)', $p->post_name, $pct );
		continue;
	}
	if ( $apply ) {
		wp_update_post( array( 'ID' => $p->ID, 'post_content' => wp_slash( $classic ) ) );
	}
	$done++;
}

$astra = get_option( 'astra-settings', array() );
if ( ! empty( $astra['single-product-recently-viewed-display'] ) ) {
	if ( $apply ) {
		$astra['single-product-recently-viewed-display'] = false;
		update_option( 'astra-settings', $astra );
	}
	WP_CLI::log( ( $apply ? 'Turned off' : 'Would turn off' ) . " Astra Pro's recently viewed products." );
}

WP_CLI::log( ( $apply ? 'Converted' : 'Would convert' ) . " $done products to classic HTML." );
if ( $skip ) {
	WP_CLI::warning( 'Skipped (text would change): ' . implode( ', ', $skip ) );
}
