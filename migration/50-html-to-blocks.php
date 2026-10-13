<?php
/**
 * 50 — Custom HTML blocks in pages and posts → editable Gutenberg blocks.
 *
 * Every `core/html` block is rebuilt as core blocks: paragraphs, headings,
 * lists, images (with their link and alignment), tables, quotes, separators
 * and YouTube embeds. Only iframes that have no block of their own (the
 * Google map on Contact Us) stay in a Custom HTML block. The /markets/ hub's
 * grid of links becomes the Market Buttons block, and [embed] paragraphs with a
 * YouTube link become Embed blocks.
 *
 * The previous content is kept in the `_ef_content_before_50` post meta.
 * Idempotent (nothing left to convert on a second run).
 *
 * @package EcoFridgeMigration
 */

global $wpdb;

/** Inline markup of a paragraph/heading/list item, cleaned of legacy attributes. */
function efm50_inline( array $nodes ) {
	$doc  = new DOMDocument();
	$wrap = $doc->createElement( 'div' );
	$doc->appendChild( $wrap );
	foreach ( $nodes as $n ) {
		$wrap->appendChild( $doc->importNode( $n, true ) );
	}
	// Unwrap spans (font-size etc.), drop styles/classes, keep link targets.
	$xp = new DOMXPath( $doc );
	foreach ( iterator_to_array( $xp->query( './/span', $wrap ) ) as $span ) {
		while ( $span->firstChild ) {
			$span->parentNode->insertBefore( $span->firstChild, $span );
		}
		$span->parentNode->removeChild( $span );
	}
	foreach ( iterator_to_array( $xp->query( './/*', $wrap ) ) as $el ) {
		$keep = 'a' === $el->nodeName ? array( 'href', 'target', 'rel' ) : ( 'img' === $el->nodeName ? array( 'src', 'alt' ) : array() );
		foreach ( iterator_to_array( $el->attributes ) as $attr ) {
			if ( ! in_array( $attr->name, $keep, true ) ) {
				$el->removeAttribute( $attr->name );
			}
		}
	}
	$html = '';
	foreach ( $wrap->childNodes as $c ) {
		$html .= $doc->saveHTML( $c );
	}
	$html = preg_replace( '~^(\s|&nbsp;|\x{00A0}|<br\s*/?>)+|(\s|&nbsp;|\x{00A0}|<br\s*/?>)+$~u', '', $html );
	$html = preg_replace( '~<(strong|em|b|i)>(\s|&nbsp;|\x{00A0}|<br\s*/?>)*</\1>~u', '', $html ); // empty bold/italic
	$html = preg_replace( '~<br>~', '<br>', $html );
	return trim( $html );
}

function efm50_has_text( $html ) {
	return '' !== trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' ), " \t\n\r\0\x0B\xC2\xA0" );
}

function efm50_paragraph( $inner ) {
	return "<!-- wp:paragraph -->\n<p>" . $inner . "</p>\n<!-- /wp:paragraph -->";
}

/** Image block from an <img> (and the <a> around it). */
function efm50_image( DOMElement $img, $href = '', $caption = '' ) {
	$src   = $img->getAttribute( 'src' );
	$alt   = $img->getAttribute( 'alt' );
	$class = ' ' . $img->getAttribute( 'class' ) . ' ' . ( $img->parentNode instanceof DOMElement ? $img->parentNode->getAttribute( 'class' ) : '' ) . ' ';
	$attrs = array();
	$id    = preg_match( '~wp-image-(\d+)~', $class, $m ) ? (int) $m[1] : 0;
	if ( $id ) {
		$attrs['id'] = $id;
	}
	$align = preg_match( '~\balign(left|right|center)\b~', $class, $m ) ? $m[1] : '';
	if ( $align ) {
		$attrs['align'] = $align;
	}
	if ( $href ) {
		$attrs['linkDestination'] = 'custom';
	}
	$img_html = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>';
	if ( $href ) {
		$img_html = '<a href="' . esc_url( $href ) . '">' . $img_html . '</a>';
	}
	$fig_class = 'wp-block-image' . ( $align ? ' align' . $align : '' );
	$cap       = '' !== $caption ? '<figcaption class="wp-element-caption">' . $caption . '</figcaption>' : '';
	return '<!-- wp:image ' . ( $attrs ? wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . ' ' : '' ) . "-->\n<figure class=\"" . $fig_class . '">' . $img_html . $cap . "</figure>\n<!-- /wp:image -->";
}

function efm50_iframe( DOMElement $f ) {
	$src = $f->getAttribute( 'src' );
	if ( preg_match( '~youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})~', $src, $m ) ) {
		$url = 'https://www.youtube.com/watch?v=' . $m[1];
		return '<!-- wp:embed {"url":"' . $url . '","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->' . "\n"
			. '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">' . "\n" . $url . "\n</div></figure>\n<!-- /wp:embed -->";
	}
	// No block for this one (e.g. Google Maps): a Custom HTML block with just the iframe.
	return "<!-- wp:html -->\n" . $f->ownerDocument->saveHTML( $f ) . "\n<!-- /wp:html -->";
}

function efm50_list( DOMElement $list ) {
	$items = '';
	foreach ( $list->childNodes as $li ) {
		if ( ! $li instanceof DOMElement || 'li' !== $li->nodeName ) {
			continue;
		}
		$inner = efm50_inline( iterator_to_array( $li->childNodes ) );
		if ( efm50_has_text( $inner ) ) {
			$items .= "<!-- wp:list-item -->\n<li>" . $inner . "</li>\n<!-- /wp:list-item -->\n\n";
		}
	}
	if ( '' === $items ) {
		return '';
	}
	$ordered = 'ol' === $list->nodeName;
	return '<!-- wp:list' . ( $ordered ? ' {"ordered":true}' : '' ) . " -->\n<" . ( $ordered ? 'ol' : 'ul' ) . ' class="wp-block-list">' . rtrim( $items ) . '</' . ( $ordered ? 'ol' : 'ul' ) . ">\n<!-- /wp:list -->";
}

function efm50_table( DOMElement $table ) {
	$rows = '';
	$head = '';
	foreach ( ( new DOMXPath( $table->ownerDocument ) )->query( './/tr', $table ) as $tr ) {
		$cells = '';
		$is_head = false;
		foreach ( $tr->childNodes as $cell ) {
			if ( ! $cell instanceof DOMElement || ! in_array( $cell->nodeName, array( 'td', 'th' ), true ) ) {
				continue;
			}
			$is_head = $is_head || 'th' === $cell->nodeName;
			$cells  .= '<' . $cell->nodeName . '>' . efm50_inline( iterator_to_array( $cell->childNodes ) ) . '</' . $cell->nodeName . '>';
		}
		if ( $is_head && '' === $rows && '' === $head ) {
			$head = '<thead><tr>' . $cells . '</tr></thead>';
		} else {
			$rows .= '<tr>' . $cells . '</tr>';
		}
	}
	return "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table class=\"has-fixed-layout\">" . $head . '<tbody>' . $rows . "</tbody></table></figure>\n<!-- /wp:table -->";
}

/** Blocks for the children of a container element. */
function efm50_children( DOMNode $parent ) {
	$out    = array();
	$buffer = array();
	$flush  = static function () use ( &$buffer, &$out ) {
		if ( $buffer ) {
			$inner = efm50_inline( $buffer );
			if ( efm50_has_text( $inner ) ) {
				$out[] = efm50_paragraph( $inner );
			}
			$buffer = array();
		}
	};
	$inline = array( 'a', 'strong', 'b', 'em', 'i', 'span', 'br', 'small', 'u', 'sup', 'sub', 'code', 'abbr' );
	foreach ( iterator_to_array( $parent->childNodes ) as $n ) {
		if ( $n instanceof DOMText ) {
			if ( '' !== trim( $n->nodeValue, " \t\n\r\0\x0B\xC2\xA0" ) ) {
				$buffer[] = $n;
			}
			continue;
		}
		if ( ! $n instanceof DOMElement ) {
			continue;
		}
		$tag = $n->nodeName;
		// A link that only wraps an image is an image block.
		if ( 'a' === $tag && 1 === $n->getElementsByTagName( 'img' )->length && ! efm50_has_text( $n->ownerDocument->saveHTML( $n ) ) ) {
			$flush();
			$out[] = efm50_image( $n->getElementsByTagName( 'img' )->item( 0 ), $n->getAttribute( 'href' ) );
			continue;
		}
		if ( in_array( $tag, $inline, true ) ) {
			$buffer[] = $n;
			continue;
		}
		$flush();
		switch ( $tag ) {
			case 'p':
				$has_media = $n->getElementsByTagName( 'img' )->length || $n->getElementsByTagName( 'iframe' )->length;
				if ( $has_media ) {
					$out = array_merge( $out, efm50_children( $n ) ); // images/iframes become their own blocks
				} else {
					$inner = efm50_inline( iterator_to_array( $n->childNodes ) );
					if ( efm50_has_text( $inner ) ) {
						$out[] = efm50_paragraph( $inner );
					}
				}
				break;
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$level = max( 2, (int) substr( $tag, 1 ) );
				$inner = efm50_inline( iterator_to_array( $n->childNodes ) );
				if ( efm50_has_text( $inner ) ) {
					$out[] = '<!-- wp:heading' . ( 2 === $level ? '' : ' {"level":' . $level . '}' ) . " -->\n<h" . $level . ' class="wp-block-heading">' . $inner . '</h' . $level . ">\n<!-- /wp:heading -->";
				}
				break;
			case 'ul':
			case 'ol':
				$list = efm50_list( $n );
				if ( '' !== $list ) {
					$out[] = $list;
				}
				break;
			case 'img':
				$out[] = efm50_image( $n );
				break;
			case 'figure':
				$img = $n->getElementsByTagName( 'img' )->item( 0 );
				if ( $img ) {
					$a   = $img->parentNode instanceof DOMElement && 'a' === $img->parentNode->nodeName ? $img->parentNode->getAttribute( 'href' ) : '';
					$cap = $n->getElementsByTagName( 'figcaption' )->item( 0 );
					$out[] = efm50_image( $img, $a, $cap ? efm50_inline( iterator_to_array( $cap->childNodes ) ) : '' );
				} else {
					$out = array_merge( $out, efm50_children( $n ) );
				}
				break;
			case 'iframe':
				$out[] = efm50_iframe( $n );
				break;
			case 'table':
				$out[] = efm50_table( $n );
				break;
			case 'blockquote':
				$out[] = "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">" . implode( "\n\n", efm50_children( $n ) ) . "</blockquote>\n<!-- /wp:quote -->";
				break;
			case 'hr':
				$out[] = "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->";
				break;
			case 'script':
			case 'style':
			case 'form':
				$out[] = "<!-- wp:html -->\n" . $n->ownerDocument->saveHTML( $n ) . "\n<!-- /wp:html -->";
				break;
			default: // div, section, article… – their content
				$out = array_merge( $out, efm50_children( $n ) );
		}
	}
	$flush();
	return $out;
}

/** Blocks for a Custom HTML block's markup. */
function efm50_convert( $html ) {
	$html = wpautop( $html );
	$doc  = new DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?><div id="efm50-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	$root = $doc->getElementById( 'efm50-root' );
	return $root ? implode( "\n\n", efm50_children( $root ) ) : '';
}

/** A paragraph holding only [embed]URL[/embed] (YouTube) becomes an Embed block. */
function efm50_embed_paragraphs( $content ) {
	return preg_replace_callback(
		'~<!-- wp:paragraph -->\s*<p>\s*\[embed\]\s*(https?://[^\[\s<]+)\s*\[/embed\]\s*</p>\s*<!-- /wp:paragraph -->~',
		static function ( $m ) {
			if ( ! preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/))([A-Za-z0-9_-]{6,})~', $m[1], $id ) ) {
				return $m[0];
			}
			$url = 'https://www.youtube.com/watch?v=' . $id[1];
			return '<!-- wp:embed {"url":"' . $url . '","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->' . "\n"
				. '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">' . "\n" . $url . "\n</div></figure>\n<!-- /wp:embed -->";
		},
		$content
	);
}

$rows = $wpdb->get_results( "SELECT ID, post_name, post_type, post_content FROM {$wpdb->posts} WHERE post_status IN ('publish','private','draft') AND post_type IN ('page','post') AND ( post_content LIKE '%<!-- wp:html -->%' OR post_content LIKE '%[embed]%' )" );
$done = 0;
foreach ( $rows as $r ) {
	$content = preg_replace_callback(
		'~<!-- wp:html -->\s*(.*?)\s*<!-- /wp:html -->~s',
		static function ( $m ) use ( $r ) {
			$html = $m[1];
			// A lone iframe without a block (the map) stays as it is.
			if ( preg_match( '~^<iframe\b[^>]*>\s*</iframe>$~s', trim( $html ) ) && ! preg_match( '~youtube~', $html ) ) {
				return $m[0];
			}
			// The /markets/ hub: the grid of market links is the Market Buttons block.
			if ( false !== strpos( $html, 'ef-markets-grid' ) ) {
				$intro = preg_match( '~<p>(.*?)</p>~s', $html, $p ) ? efm50_paragraph( trim( $p[1] ) ) . "\n\n" : '';
				return $intro . '<!-- wp:efb/market-buttons /-->';
			}
			$blocks = efm50_convert( $html );
			if ( '' === $blocks && ! efm50_has_text( $html ) && ! preg_match( '~<(img|iframe|video|embed|object|form|script)\b~i', $html ) ) {
				return ''; // an empty leftover (e.g. <div class="yoast-link-suggestion"></div>)
			}
			return '' !== $blocks ? $blocks : $m[0];
		},
		$r->post_content
	);
	$content = efm50_embed_paragraphs( $content );
	if ( $content !== $r->post_content ) {
		if ( '' === (string) get_post_meta( $r->ID, '_ef_content_before_50', true ) ) {
			update_post_meta( $r->ID, '_ef_content_before_50', wp_slash( $r->post_content ) );
		}
		$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $r->ID ) );
		clean_post_cache( $r->ID );
		$done++;
		WP_CLI::log( $r->post_type . ' ' . $r->post_name );
	}
}
WP_CLI::success( "$done posts/pages converted." );
