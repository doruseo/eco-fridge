<?php
/**
 * 61 — Blog: one "Also read" box for the related-article links.
 *
 * The articles came from the old site with their related links in many shapes:
 * a "Related articles:" / "Also read about:" / "You may also read about:" /
 * "Read more about:" label as a paragraph, a heading or a table cell, followed
 * by paragraphs, list items or table rows that hold only links. Each such run
 * of blocks becomes one group block:
 *
 *   <div class="wp-block-group ef-also-read">
 *     <p class="ef-also-read__label">Also read</p>
 *     <ul class="ef-also-read__list"><li><a href="…">Title</a></li>…</ul>
 *   </div>
 *
 * styled in astra-child/assets/blog.css (each link shown as a card with the
 * article's image, added at render time in inc/blog.php). Sentences with a link
 * inside the text ("Read more about Eatery Equipment.") are left alone.
 *
 * Dry run by default; set EFM61_APPLY to true to save. The content before this
 * migration is kept per post in the meta _ef_content_before_61.
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

if ( ! defined( 'EFM61_APPLY' ) ) {
	define( 'EFM61_APPLY', false );
}

const EFM61_LABEL = '~^(?:you\s+may\s+also\s+read(?:\s+about)?|you\s+can\s+also\s+read(?:\s+about)?|also\s+read(?:\s+about)?|read\s+also(?:\s+about)?|read\s+more\s+about|related\s+(?:articles|posts|reading))\s*:?~i';

/**
 * Plain text of an HTML fragment.
 *
 * @param string $html HTML.
 * @return string
 */
function efm61_text( $html ) {
	$t = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '~[\s\x{00a0}]+~u', ' ', $t ) );
}

/**
 * The links in an HTML fragment.
 *
 * @param string $html HTML.
 * @return array[] Each: href, title.
 */
function efm61_links( $html ) {
	$out = array();
	if ( preg_match_all( '~<a\s[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>~is', (string) $html, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $a ) {
			$title = efm61_text( $a[3] );
			if ( '' !== $title ) {
				$out[] = array( 'href' => html_entity_decode( $a[2] ), 'title' => $title );
			}
		}
	}
	return $out;
}

/**
 * Whether an HTML fragment is only links (and separators / punctuation).
 *
 * @param string $html HTML.
 * @return bool
 */
function efm61_links_only( $html ) {
	if ( ! efm61_links( $html ) ) {
		return false;
	}
	$rest = efm61_text( preg_replace( '~<a\s[^>]*>.*?</a>~is', '', (string) $html ) );
	return '' === trim( $rest, " \t\n\r\0\x0B-–—•|,.;:!?»›→" );
}

/**
 * The label + links of one block, or null.
 *
 * @param array $block Parsed block.
 * @return array|null array( 'label' => bool, 'links' => array[], 'links_only' => bool )
 */
function efm61_inspect( $block ) {
	$name = (string) $block['blockName'];

	if ( 'efb/spec-table' === $name ) {
		$cells = array_merge( (array) ( $block['attrs']['head'] ?? array() ) );
		foreach ( (array) ( $block['attrs']['rows'] ?? array() ) as $row ) {
			$cells = array_merge( $cells, (array) $row );
		}
		$label = false;
		$links = array();
		foreach ( $cells as $cell ) {
			$cell = (string) $cell;
			$text = efm61_text( $cell );
			if ( '' === $text ) {
				continue;
			}
			if ( preg_match( EFM61_LABEL, $text ) ) {
				$label = true;
				$after = preg_replace( '~^.*?(?:about|articles|posts|reading|read)\s*:?\s*(</[a-z0-9]+>\s*)*~is', '', $cell, 1 );
				$links = array_merge( $links, efm61_links( $after ) );
				continue;
			}
			if ( ! efm61_links_only( $cell ) ) {
				return null; // a real table
			}
			$links = array_merge( $links, efm61_links( $cell ) );
		}
		return $label && $links ? array( 'label' => true, 'links' => $links, 'links_only' => false ) : null;
	}

	if ( in_array( $name, array( 'core/paragraph', 'core/heading' ), true ) ) {
		$html = (string) $block['innerHTML'];
		$text = efm61_text( $html );
		if ( preg_match( EFM61_LABEL, $text, $m ) ) {
			// The label alone, or the label followed only by links.
			$rest = trim( substr( $text, strlen( $m[0] ) ) );
			if ( '' === $rest ) {
				return array( 'label' => true, 'links' => array(), 'links_only' => false );
			}
			$after = preg_replace( '~^.*?' . preg_quote( rtrim( $m[0] ), '~' ) . '\s*(</[a-z0-9]+>\s*)*(<br\s*/?>\s*)*~is', '', $html, 1 );
			if ( efm61_links_only( strip_tags( $after, '<a><br>' ) ) ) {
				return array( 'label' => true, 'links' => efm61_links( $after ), 'links_only' => false );
			}
			return null; // a sentence that starts like a label
		}
		if ( efm61_links_only( $html ) ) {
			return array( 'label' => false, 'links' => efm61_links( $html ), 'links_only' => true );
		}
		return null;
	}

	if ( 'core/list' === $name ) {
		$html = render_block( $block );
		if ( efm61_links_only( preg_replace( '~</?(ul|ol|li)[^>]*>~i', ' ', $html ) ) ) {
			return array( 'label' => false, 'links' => efm61_links( $html ), 'links_only' => true );
		}
		return null;
	}

	if ( 'core/quote' === $name && ! empty( $block['innerBlocks'] ) ) {
		// A quote that holds only a label + links.
		$all = array();
		$lab = false;
		foreach ( $block['innerBlocks'] as $inner ) {
			$i = efm61_inspect( $inner );
			if ( null === $i ) {
				return null;
			}
			$lab = $lab || $i['label'];
			$all = array_merge( $all, $i['links'] );
		}
		return $lab && $all ? array( 'label' => true, 'links' => $all, 'links_only' => false ) : null;
	}

	return null;
}

/**
 * The "Also read" group block markup.
 *
 * @param array[] $links Links.
 * @return string
 */
function efm61_box( $links ) {
	$seen  = array();
	$items = '';
	foreach ( $links as $l ) {
		$href = preg_replace( '~^https?://[^/]+~i', '', $l['href'] ); // site-relative, like the rest of the content
		if ( isset( $seen[ $href ] ) ) {
			continue;
		}
		$seen[ $href ] = true;
		$items        .= '<!-- wp:list-item -->' . "\n" . '<li><a href="' . esc_url( $href ) . '">' . esc_html( $l['title'] ) . '</a></li>' . "\n" . '<!-- /wp:list-item -->';
	}
	return '<!-- wp:group {"className":"ef-also-read","layout":{"type":"default"}} -->' . "\n"
		. '<div class="wp-block-group ef-also-read"><!-- wp:paragraph {"className":"ef-also-read__label"} -->' . "\n"
		. '<p class="ef-also-read__label">Also read</p>' . "\n"
		. '<!-- /wp:paragraph -->' . "\n\n"
		. '<!-- wp:list {"className":"ef-also-read__list"} -->' . "\n"
		. '<ul class="wp-block-list ef-also-read__list">' . $items . '</ul>' . "\n"
		. '<!-- /wp:list --></div>' . "\n"
		. '<!-- /wp:group -->';
}

$efm61_ids   = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
$efm61_total = 0;
foreach ( $efm61_ids as $efm61_id ) {
	$content = get_post_field( 'post_content', $efm61_id );
	if ( false !== strpos( $content, 'ef-also-read' ) && ! preg_match( EFM61_LABEL, efm61_text( str_replace( 'Also read</p>', '', $content ) ) ) ) {
		// already converted; fall through so new label runs are still found
	}
	$blocks = parse_blocks( $content );
	$out    = array();
	$found  = 0;
	$report = array();
	$n      = count( $blocks );
	for ( $i = 0; $i < $n; $i++ ) {
		$b   = $blocks[ $i ];
		$ins = null === $b['blockName'] ? null : efm61_inspect( $b );
		if ( ! $ins || ! $ins['label'] ) {
			$out[] = $b;
			continue;
		}
		$links = $ins['links'];
		$used  = array( $b['blockName'] );
		$j     = $i + 1;
		// Following blocks made only of links (blank "freeform" gaps between blocks are skipped).
		while ( $j < $n ) {
			$nb = $blocks[ $j ];
			if ( null === $nb['blockName'] && '' === trim( $nb['innerHTML'] ) ) {
				++$j;
				continue;
			}
			$ni = null === $nb['blockName'] ? null : efm61_inspect( $nb );
			if ( ! $ni || ! $ni['links_only'] ) {
				break;
			}
			$links  = array_merge( $links, $ni['links'] );
			$used[] = $nb['blockName'];
			++$j;
		}
		if ( ! $links ) {
			$out[] = $b; // a label with nothing after it
			continue;
		}
		$out[]    = array(
			'blockName'    => null,
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => "\n" . efm61_box( $links ) . "\n",
			'innerContent' => array( "\n" . efm61_box( $links ) . "\n" ),
		);
		$report[] = count( $links ) . ' links from ' . implode( '+', array_count_values_str( $used ) );
		++$found;
		$i = $j - 1;
	}
	if ( ! $found ) {
		continue;
	}
	$efm61_total += $found;
	echo $efm61_id, ' ', get_post_field( 'post_name', $efm61_id ), ': ', implode( '; ', $report ), "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( EFM61_APPLY ) {
		if ( '' === (string) get_post_meta( $efm61_id, '_ef_content_before_61', true ) ) {
			update_post_meta( $efm61_id, '_ef_content_before_61', wp_slash( $content ) );
		}
		wp_update_post( array( 'ID' => $efm61_id, 'post_content' => wp_slash( serialize_blocks( $out ) ) ) );
	}
}
echo 'Boxes: ', $efm61_total, EFM61_APPLY ? ' (saved)' : ' (dry run)', "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * "core/paragraph x3" style summary of block names.
 *
 * @param string[] $names Block names.
 * @return string[]
 */
function array_count_values_str( $names ) {
	$out = array();
	foreach ( array_count_values( $names ) as $name => $count ) {
		$out[] = str_replace( 'core/', '', $name ) . ( $count > 1 ? ' x' . $count : '' );
	}
	return $out;
}
