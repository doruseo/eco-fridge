<?php
/**
 * 28 — Pages rebuilt from the LIVE site on ECO-Fridge Blocks, third-party
 * block/search/form/table plugins retired.
 *
 *  - /markets/<slug>/  : live intro + efb/product-grid (market) + live long text
 *  - /graded-units/    : live intro + efb/product-grid (Graded Units category)
 *  - /support/         : brochures (PDF links, live order) + videos (embedded)
 *  - /contact-us/      : live text + efb/contact-form (fields 1:1 with the live form)
 *  - /eco-fridge/      : created (live page)
 *  - /cookie-policy-uk/: the raw [cmplz-document] shortcode (broken on live too)
 *                        replaced with a short cookie notice
 *  - every remaining uagb/container → efb/section
 *  - remaining [table id=N] (unpublished products) → efb/spec-table
 *  - Astra header HTML element: [fibosearch] → [efb_search]
 *  - deactivates Spectra, Essential Blocks, FiboSearch, WPForms, TablePress
 *
 * Idempotent. Needs source-dump/_live-content.json + _lib-html-to-blocks.php.
 *
 * @package EcoFridgeMigration
 */

require_once WP_CONTENT_DIR . '/source-dump/_lib-html-to-blocks.php';

$live = json_decode( (string) file_get_contents( WP_CONTENT_DIR . '/source-dump/_live-content.json' ), true );
if ( ! $live ) {
	WP_CLI::error( 'Live content JSON missing.' );
}

$section = function ( $inner, $attrs = array() ) {
	return efm_block( 'efb/section', array_merge( array( 'paddingTop' => 32, 'paddingBottom' => 40 ), $attrs ), $inner );
};
$save = function ( $page_id, $content ) {
	wp_update_post( array( 'ID' => $page_id, 'post_content' => wp_slash( $content ) ) );
	update_post_meta( $page_id, '_efb_built_from_live', gmdate( 'c' ) );
};
$strip_comments = function ( $html ) {
	return preg_replace( '~<!--.*?-->~s', '', $html );
};
/** Split live content around the product list: [before, after]. */
$split_products = function ( $html ) {
	$i = strpos( $html, '<ul class="postsIndex products' );
	if ( false === $i ) {
		return array( $html, '' );
	}
	// Find the matching </ul> (product <li>s contain no nested <ul>).
	$j = strpos( $html, '</ul>', $i );
	return array( substr( $html, 0, $i ), substr( $html, $j + 5 ) );
};
$unwrap_fields = function ( $html ) {
	return preg_replace( '~<div><div id="wpcf-field-[^"]*" class="[^"]*">(.*?)</div></div>~s', '$1', $html );
};

/* Markets -------------------------------------------------------------------- */
foreach ( $live as $path => $l ) {
	if ( ! preg_match( '~^/markets/([a-z0-9-]+)/$~', $path, $m ) ) {
		continue;
	}
	$page = get_page_by_path( 'markets/' . $m[1] );
	if ( ! $page ) {
		WP_CLI::warning( "No local page for $path" );
		continue;
	}
	list( $before, $after ) = $split_products( $unwrap_fields( $strip_comments( $l['content'] ) ) );
	$term    = get_term_by( 'slug', $m[1], 'market' );
	$inner   = efm_html_to_blocks( $before );
	if ( $term ) {
		$inner .= efm_block( 'efb/product-grid', array( 'market' => $term->term_id, 'count' => 48, 'columns' => 4 ) );
	}
	$inner .= efm_html_to_blocks( $after );
	$save( $page->ID, $section( $inner ) );
	WP_CLI::log( "market page $path" . ( $term ? ' + products' : '' ) );
}

/* Graded units ----------------------------------------------------------------- */
$graded_page = get_page_by_path( 'graded-units' );
$graded_cat  = get_term_by( 'slug', 'graded-units', 'product_cat' );
if ( $graded_page && isset( $live['/graded-units/'] ) ) {
	list( $before, $after ) = $split_products( $unwrap_fields( $strip_comments( $live['/graded-units/']['content'] ) ) );
	$inner = efm_html_to_blocks( $before )
		. efm_block( 'efb/product-grid', array( 'category' => $graded_cat ? $graded_cat->term_id : 0, 'count' => 48, 'columns' => 4, 'showPrice' => false ) )
		. efm_html_to_blocks( $after );
	$save( $graded_page->ID, $section( $inner ) );
	WP_CLI::log( 'graded-units rebuilt' );
}

/* Support: brochures + videos ------------------------------------------------- */
$support = get_page_by_path( 'support' );
if ( $support && isset( $live['/support/'] ) ) {
	$html = $live['/support/']['content'];
	// Brochures in the live order, matched by title.
	preg_match_all( '~<h3>(.*?)</h3>~', substr( $html, 0, (int) strpos( $html, 'videos-index' ) ), $titles );
	$ids = array();
	foreach ( $titles[1] as $t ) {
		$found = get_posts( array( 'post_type' => 'brochure', 'title' => html_entity_decode( $t ), 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $found ) {
			$ids[] = $found[0];
		}
	}
	// PDF links exactly as on the live support page (incl. the new retarder manual).
	if ( preg_match_all( '~<a href="([^"]+\.pdf)"[^>]*class="brochure-title"><h3>(.*?)</h3>~', $html, $pdfs, PREG_SET_ORDER ) ) {
		foreach ( $pdfs as $pdf ) {
			$found = get_posts( array( 'post_type' => 'brochure', 'title' => html_entity_decode( $pdf[2] ), 'numberposts' => 1, 'fields' => 'ids' ) );
			if ( $found ) {
				update_post_meta( $found[0], '_efb_file_url', efm_relative_urls( $pdf[1] ) );
			}
		}
	}
	$inner = efm_block( 'heading', array(), '<h2 class="wp-block-heading">ECO-Fridge Brochures</h2>' )
		. efm_block( 'paragraph', array(), '<p>Click the links below to download our brochures in PDF format…</p>' )
		. efm_block( 'efb/post-cards', array( 'postType' => 'brochure', 'ids' => $ids, 'columns' => 4, 'linkToFile' => true, 'showDate' => false, 'showExcerpt' => false ) )
		. efm_block( 'heading', array(), '<h2 class="wp-block-heading">ECO-Fridge Videos</h2>' )
		. efm_block( 'efb/post-cards', array( 'postType' => 'video', 'count' => 24, 'columns' => 2, 'order' => 'asc', 'embedVideo' => true, 'showDate' => false ) );
	$save( $support->ID, $section( $inner ) );
	WP_CLI::log( 'support rebuilt (' . count( $ids ) . ' brochures)' );
}

/* Contact ---------------------------------------------------------------------- */
$contact = get_page_by_path( 'contact-us' );
if ( $contact && isset( $live['/contact-us/'] ) ) {
	$html  = $live['/contact-us/']['content'];
	$intro = substr( $html, 0, (int) strpos( $html, '<div role="form"' ) );
	$intro = preg_replace( '~^\s*<div class="content-1">~', '', $intro );
	$after = substr( $html, (int) strpos( $html, '</form>' ) );
	$after = preg_replace( '~^</form>\s*</div>~', '', $after );
	$after = preg_replace( '~^\s*</div>~', '', trim( $after ) );
	$form  = efm_block(
		'efb/contact-form',
		array(
			'formId'         => 'contact',
			'formName'       => 'Contact us',
			'fields'         => array(
				array( 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
				array( 'name' => 'company', 'label' => 'Company', 'type' => 'text', 'required' => false ),
				array( 'name' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true ),
				array( 'name' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'required' => true ),
				array( 'name' => 'enquiry', 'label' => 'Enquiry', 'type' => 'textarea', 'required' => false ),
			),
			'recipient'      => 'sales@eco-fridge.co.uk',
			'subject'        => 'Enquiry from ECO-Fridge website contact form',
			'successMessage' => 'Thank you for your message. It has been sent.',
			'buttonText'     => 'Send',
		)
	);
	$save( $contact->ID, $section( efm_html_to_blocks( $strip_comments( $intro ) ) . $form . efm_html_to_blocks( $strip_comments( $after ) ) ) );
	WP_CLI::log( 'contact-us rebuilt with efb/contact-form' );
}

/* /eco-fridge/ page (exists on live, missing locally) ------------------------ */
if ( isset( $live['/eco-fridge/'] ) && ! get_page_by_path( 'eco-fridge' ) ) {
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'eco-fridge',
			'post_title'   => 'ECO-Fridge',
			'post_content' => wp_slash( $section( efm_html_to_blocks( $live['/eco-fridge/']['content'] ) ) ),
		)
	);
	WP_CLI::log( '/eco-fridge/ page created' );
}

/* Cookie policy: replace the raw Complianz shortcode --------------------------- */
$cookie = get_page_by_path( 'cookie-policy-uk' );
if ( $cookie && false !== strpos( $cookie->post_content, 'cmplz-document' ) ) {
	$privacy = get_page_by_path( 'privacy-cookies-policy' );
	$link    = $privacy ? wp_make_link_relative( get_permalink( $privacy ) ) : '/privacy-cookies-policy/';
	$save(
		$cookie->ID,
		$section(
			efm_block( 'paragraph', array(), '<p>This website uses cookies that are necessary for it to work (for example to keep the contents of your basket) and, where you allow it, cookies that help us understand how the site is used.</p>' )
			. efm_block( 'paragraph', array(), '<p>Full details of the cookies we use, why we use them and how to control them are set out in our <a href="' . esc_attr( $link ) . '">Privacy &amp; Cookies Policy</a>.</p>' )
		)
	);
	WP_CLI::log( 'cookie-policy-uk: shortcode replaced' );
}

/* uagb/container → efb/section everywhere ------------------------------------- */
$convert = function ( $blocks ) use ( &$convert ) {
	foreach ( $blocks as &$b ) {
		if ( ! empty( $b['innerBlocks'] ) ) {
			$b['innerBlocks'] = $convert( $b['innerBlocks'] );
		}
		if ( 'uagb/container' === $b['blockName'] ) {
			$a     = $b['attrs'];
			$attrs = array(
				'paddingTop'    => isset( $a['topPaddingDesktop'] ) ? (int) $a['topPaddingDesktop'] : 32,
				'paddingBottom' => isset( $a['bottomPaddingDesktop'] ) ? (int) $a['bottomPaddingDesktop'] : 40,
			);
			if ( ! empty( $a['className'] ) ) {
				$attrs['className'] = $a['className'];
			}
			$b = array(
				'blockName'    => 'efb/section',
				'attrs'        => $attrs,
				'innerBlocks'  => $b['innerBlocks'],
				'innerHTML'    => '',
				'innerContent' => array_fill( 0, count( $b['innerBlocks'] ), null ),
			);
		}
	}
	return $blocks;
};
$converted = 0;
foreach ( get_posts( array( 'post_type' => array( 'page', 'post', 'product', 'wp_block', 'astra-advanced-hook' ), 'post_status' => 'any', 'numberposts' => -1 ) ) as $p ) {
	if ( false === strpos( $p->post_content, 'wp:uagb/' ) ) {
		continue;
	}
	$new = serialize_blocks( $convert( parse_blocks( $p->post_content ) ) );
	if ( false !== strpos( $new, 'wp:uagb/' ) ) {
		WP_CLI::warning( "{$p->post_name}: other Spectra blocks remain" );
	}
	$save( $p->ID, $new );
	$converted++;
}
WP_CLI::log( "Spectra containers converted in $converted posts" );

/* Remaining TablePress shortcodes (unpublished products) ---------------------- */
$tp      = json_decode( (string) get_option( 'tablepress_tables' ), true );
$tp_map  = $tp['table_post'] ?? array();
$tables  = 0;
global $wpdb;
$with_tables = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('product','page','post') AND post_content LIKE '%[table id=%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
foreach ( array_map( 'get_post', $with_tables ) as $p ) {
	$content = preg_replace_callback(
		'~\[table id=(\d+)\s*/?\]~',
		function ( $m ) use ( $tp_map, &$tables ) {
			$tid = (int) $m[1];
			$tpp = isset( $tp_map[ $tid ] ) ? get_post( $tp_map[ $tid ] ) : null;
			// Skip the "… Warranty" tables: they are added as a click-to-show toggle by 31-warranty-toggle.php.
			if ( ! $tpp || preg_match( '~warranty$~i', trim( $tpp->post_title ) ) ) {
				return '';
			}
			$data = json_decode( $tpp->post_content, true );
			$vis  = json_decode( (string) get_post_meta( $tpp->ID, '_tablepress_table_visibility', true ), true );
			$out  = array();
			foreach ( (array) $data as $r => $row ) {
				if ( isset( $vis['rows'][ $r ] ) && ! $vis['rows'][ $r ] ) {
					continue;
				}
				$cells = array();
				foreach ( $row as $c => $cell ) {
					if ( isset( $vis['columns'][ $c ] ) && ! $vis['columns'][ $c ] ) {
						continue;
					}
					$cells[] = efm_relative_urls( trim( $cell ) );
				}
				$out[] = $cells;
			}
			$tables++;
			return "\n" . efm_block( 'efb/spec-table', array( 'head' => array_shift( $out ), 'rows' => $out ) );
		},
		$p->post_content
	);
	if ( $content !== $p->post_content ) {
		// Classic description → blocks so the table sits among native blocks.
		if ( false === strpos( $content, '<!-- wp:paragraph' ) && false === strpos( $content, '<!-- wp:heading' ) ) {
			$parts   = preg_split( '~(<!-- wp:efb/spec-table .*? /-->)~s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
			$content = '';
			foreach ( $parts as $part ) {
				$content .= 0 === strpos( $part, '<!-- wp:efb/' ) ? $part . "\n" : efm_html_to_blocks( wpautop( $part ) );
			}
		}
		$save( $p->ID, $content );
	}
}
WP_CLI::log( "TablePress shortcodes converted: $tables" );

/* Astra header HTML element: FiboSearch → own search ------------------------- */
$astra = get_option( 'astra-settings' );
foreach ( $astra as $k => $v ) {
	if ( is_string( $v ) && false !== strpos( $v, '[fibosearch' ) ) {
		$astra[ $k ] = preg_replace( '~\[fibosearch[^\]]*\]~', '[efb_search]', $v );
	}
}
update_option( 'astra-settings', $astra );

/* Retire third-party plugins -------------------------------------------------- */
deactivate_plugins(
	array(
		'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php',
		'essential-blocks/essential-blocks.php',
		'ajax-search-for-woocommerce/ajax-search-for-woocommerce.php',
		'wpforms-lite/wpforms.php',
		'tablepress/tablepress.php',
	)
);
WP_CLI::success( 'Pages rebuilt, third-party plugins deactivated.' );
