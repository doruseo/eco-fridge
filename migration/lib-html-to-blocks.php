<?php
/**
 * Helpers shared by the migration scripts: convert classic HTML (as served by
 * the live site) into native Gutenberg blocks, and build block comments.
 *
 * Supported top-level nodes: p, h1-h6, ul/ol (→ list + list-item), blockquote,
 * img / a>img (→ image), hr, table (→ efb/spec-table); anything else → core/html.
 *
 * @package EcoFridgeMigration
 */

if ( ! function_exists( 'efm_block' ) ) {

	/**
	 * Serialize one block.
	 *
	 * @param string $name  Block name (without "core/" for core blocks).
	 * @param array  $attrs Attributes.
	 * @param string $inner Inner HTML (empty = self-closing for dynamic blocks).
	 * @return string
	 */
	function efm_block( $name, $attrs = array(), $inner = null ) {
		// Core serializer: escapes quotes/HTML as \u00XX so the JSON survives
		// wp_unslash() on save (plain JSON backslashes would be stripped).
		$json = $attrs ? ' ' . serialize_block_attributes( $attrs ) : '';
		if ( null === $inner ) {
			return "<!-- wp:$name$json /-->\n";
		}
		return "<!-- wp:$name$json -->\n$inner\n<!-- /wp:$name -->\n";
	}

	/**
	 * Make site URLs root-relative (live + legacy hosts).
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	function efm_relative_urls( $html ) {
		return preg_replace( '~https?://(?:www\.)?(?:eco-fridge\.co\.uk|eco-fridge\.o2dev\.de)(?=/)~i', '', $html );
	}

	/**
	 * Clean inline cruft left by the classic editor: font-size spans, empty
	 * paragraphs, srcset/sizes, WordPress image classes, non-breaking spaces.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	function efm_clean_html( $html ) {
		$html = efm_relative_urls( $html );
		$html = preg_replace( '~\s(srcset|sizes|loading|decoding)="[^"]*"~i', '', $html );
		// Unwrap spans that only carry font-size / font-family styling.
		for ( $i = 0; $i < 4; $i++ ) {
			$html = preg_replace( '~<span style="(?:font-(?:size|family|weight)[^"]*)">(.*?)</span>~is', '$1', $html );
		}
		$html = preg_replace( '~<p[^>]*>(\s|&nbsp;|\xC2\xA0|<br\s*/?>)*</p>~i', '', $html );
		return trim( $html );
	}

	/**
	 * Inner HTML of a DOM node.
	 *
	 * @param DOMNode $node Node.
	 * @return string
	 */
	function efm_inner_html( $node ) {
		$out = '';
		foreach ( $node->childNodes as $child ) {
			$out .= $node->ownerDocument->saveHTML( $child );
		}
		return trim( $out );
	}

	/**
	 * Convert an HTML fragment into serialized blocks.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	function efm_html_to_blocks( $html ) {
		$html = efm_clean_html( $html );
		if ( '' === $html ) {
			return '';
		}
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><div id="efm-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		$root = $doc->getElementById( 'efm-root' );
		$out  = '';
		$loose = '';

		$flush = function () use ( &$loose, &$out ) {
			$text = trim( $loose );
			if ( '' !== $text && '' !== trim( wp_strip_all_tags( $text ) ) ) {
				$out .= efm_block( 'paragraph', array(), '<p>' . $text . '</p>' );
			}
			$loose = '';
		};

		foreach ( $root->childNodes as $node ) {
			if ( XML_TEXT_NODE === $node->nodeType || ( XML_ELEMENT_NODE === $node->nodeType && in_array( strtolower( $node->nodeName ), array( 'a', 'strong', 'em', 'b', 'i', 'span', 'br' ), true ) && ! ( 'a' === strtolower( $node->nodeName ) && $node->getElementsByTagName( 'img' )->length ) ) ) {
				$loose .= $doc->saveHTML( $node );
				continue;
			}
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				continue;
			}
			$flush();
			$tag = strtolower( $node->nodeName );
			if ( 'p' === $tag ) {
				$inner = efm_inner_html( $node );
				if ( '' === trim( wp_strip_all_tags( $inner, true ) ) && false === stripos( $inner, '<img' ) ) {
					continue;
				}
				// A paragraph holding only an image → image block.
				if ( preg_match( '~^(?:<a [^>]*>)?\s*<img [^>]*>\s*(?:</a>)?$~i', $inner ) ) {
					$out .= efm_image_block( $inner );
					continue;
				}
				$align = '';
				if ( preg_match( '~text-align:\s*(center|right)~i', (string) $node->getAttribute( 'style' ), $m ) ) {
					$align = strtolower( $m[1] );
				}
				$out .= efm_block( 'paragraph', $align ? array( 'align' => $align ) : array(), '<p' . ( $align ? ' class="has-text-align-' . $align . '"' : '' ) . '>' . $inner . '</p>' );
			} elseif ( preg_match( '~^h([1-6])$~', $tag, $m ) ) {
				$level = (int) $m[1];
				$inner = efm_inner_html( $node );
				if ( '' === trim( wp_strip_all_tags( $inner ) ) ) {
					// Headings used only as image wrappers → image block.
					if ( false !== stripos( $inner, '<img' ) ) {
						$out .= efm_image_block( $inner );
					}
					continue;
				}
				$out .= efm_block( 'heading', 2 === $level ? array() : array( 'level' => $level ), '<h' . $level . ' class="wp-block-heading">' . $inner . '</h' . $level . '>' );
			} elseif ( 'ul' === $tag || 'ol' === $tag ) {
				$items = '';
				foreach ( $node->childNodes as $li ) {
					if ( XML_ELEMENT_NODE === $li->nodeType && 'li' === strtolower( $li->nodeName ) ) {
						$items .= efm_block( 'list-item', array(), '<li>' . efm_inner_html( $li ) . '</li>' );
					}
				}
				$out .= efm_block( 'list', 'ol' === $tag ? array( 'ordered' => true ) : array(), '<' . $tag . ' class="wp-block-list">' . "\n" . $items . '</' . $tag . '>' );
			} elseif ( 'blockquote' === $tag ) {
				$out .= efm_block( 'quote', array(), '<blockquote class="wp-block-quote">' . efm_html_to_blocks( efm_inner_html( $node ) ) . '</blockquote>' );
			} elseif ( 'img' === $tag || ( 'a' === $tag && $node->getElementsByTagName( 'img' )->length ) ) {
				$out .= efm_image_block( $doc->saveHTML( $node ) );
			} elseif ( 'figure' === $tag && $node->getElementsByTagName( 'img' )->length ) {
				$cap = $node->getElementsByTagName( 'figcaption' )->item( 0 );
				$out .= efm_image_block( $doc->saveHTML( $node ), $cap ? efm_inner_html( $cap ) : '', (string) $node->getAttribute( 'data-align' ) );
			} elseif ( 'hr' === $tag ) {
				$out .= efm_block( 'separator', array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>' );
			} elseif ( 'table' === $tag ) {
				$out .= efm_table_block( $node );
			} elseif ( 'div' === $tag && ! $node->attributes->length ) {
				$out .= efm_html_to_blocks( efm_inner_html( $node ) );
			} else {
				$out .= efm_block( 'html', array(), $doc->saveHTML( $node ) );
			}
		}
		$flush();
		return $out;
	}

	/**
	 * Image block from an <img> (optionally wrapped in <a>).
	 *
	 * @param string $html    HTML.
	 * @param string $caption Optional caption HTML.
	 * @param string $align   Optional forced alignment.
	 * @return string
	 */
	function efm_image_block( $html, $caption = '', $align = '' ) {
		if ( ! preg_match( '~<img [^>]*src="([^"]+)"[^>]*>~i', $html, $m ) ) {
			return '';
		}
		$src   = efm_relative_urls( $m[1] );
		$alt   = preg_match( '~alt="([^"]*)"~i', $m[0], $a ) ? $a[1] : '';
		$href  = preg_match( '~<a [^>]*href="([^"]+)"~i', $html, $h ) ? efm_relative_urls( $h[1] ) : '';
		if ( ! in_array( $align, array( 'left', 'right', 'center' ), true ) ) {
			$align = preg_match( '~align(left|right|center)~', $m[0], $al ) ? $al[1] : '';
		}
		// Prefer the full-size attachment when the source is a resized variant.
		$full  = preg_replace( '~-\d+x\d+(\.[a-z]+)$~i', '$1', $src );
		$id    = attachment_url_to_postid( home_url( $full ) );
		$attrs = array();
		if ( $id ) {
			$attrs['id'] = $id;
		}
		$attrs['sizeSlug'] = 'large';
		if ( $align ) {
			$attrs['align'] = $align;
		}
		if ( $href ) {
			$attrs['linkDestination'] = 'custom';
		}
		$img = '<img src="' . esc_attr( $id ? wp_get_attachment_image_url( $id, 'large' ) : $src ) . '" alt="' . esc_attr( $alt ) . '"' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>';
		if ( $href ) {
			$img = '<a href="' . esc_attr( $href ) . '">' . $img . '</a>';
		}
		$cls = 'wp-block-image size-large' . ( $align ? ' align' . $align : '' );
		$cap = '' !== trim( wp_strip_all_tags( $caption ) ) ? '<figcaption class="wp-element-caption">' . trim( $caption ) . '</figcaption>' : '';
		return efm_block( 'image', $attrs, '<figure class="' . $cls . '">' . $img . $cap . '</figure>' );
	}

	/**
	 * Turn [caption] shortcodes into <figure> markup the converter understands.
	 *
	 * @param string $html Classic content.
	 * @return string
	 */
	function efm_captions_to_figures( $html ) {
		return preg_replace_callback(
			'~\[caption([^\]]*)\](.*?)\[/caption\]~is',
			function ( $m ) {
				$align = preg_match( '~align="align(left|right|center|none)"~', $m[1], $a ) ? $a[1] : '';
				$inner = trim( $m[2] );
				$media = preg_match( '~^(?:<a [^>]*>)?\s*<img [^>]*>\s*(?:</a>)?~is', $inner, $mm ) ? $mm[0] : '';
				$text  = trim( substr( $inner, strlen( $media ) ) );
				if ( ! $text && preg_match( '~caption="([^"]*)"~', $m[1], $c ) ) {
					$text = $c[1];
				}
				return "\n\n<figure data-align=\"$align\">$media<figcaption>$text</figcaption></figure>\n\n";
			},
			$html
		);
	}

	/**
	 * efb/spec-table block from a <table> node.
	 *
	 * @param DOMElement $table Table.
	 * @return string
	 */
	function efm_table_block( $table ) {
		$head = array();
		$rows = array();
		foreach ( $table->getElementsByTagName( 'tr' ) as $i => $tr ) {
			$cells = array();
			$is_th = false;
			foreach ( $tr->childNodes as $c ) {
				if ( XML_ELEMENT_NODE === $c->nodeType && in_array( strtolower( $c->nodeName ), array( 'td', 'th' ), true ) ) {
					$cells[] = trim( efm_inner_html( $c ) );
					$is_th   = $is_th || 'th' === strtolower( $c->nodeName );
				}
			}
			if ( ! $head && ( $is_th || 0 === $i ) ) {
				$head = $cells;
			} else {
				$rows[] = $cells;
			}
		}
		return efm_block( 'efb/spec-table', array( 'head' => $head, 'rows' => $rows ) );
	}
}
