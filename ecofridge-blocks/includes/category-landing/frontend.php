<?php
/**
 * Category landing pages — front end.
 *
 * Page layout on a commercial category:
 *   short description (the category Description) → subcategory carousel →
 *   product grid → [products carousel] → content sections in the chosen order
 *   (features, FAQ, comparison table, articles, long description, spare parts)
 *   → related categories → markets → reviews.
 * Empty sections are not shown; the sections appear on page 1 only.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

final class EFB_Landing_Frontend {

	/** Guards against printing the sections twice (loop hook + no-products hook). */
	private static $printed = false;

	/** Term whose FAQ schema this request prints as one merged FAQPage (0 = none). */
	private static $faq_term = 0;

	/** Questions taken out of FAQPage scripts embedded in that term's long description. */
	private static $embedded_faq = array();

	public function __construct() {
		add_action( 'wp', array( $this, 'setup' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'efb-landing', EFB_URL . 'assets/landing.css', array(), EFB_VERSION );
		wp_register_script( 'efb-landing', EFB_URL . 'assets/landing.js', array(), EFB_VERSION, true );
		if ( efb_landing_is_archive() && efb_landing_applies( get_queried_object_id() ) ) {
			wp_enqueue_style( 'efb-landing' );
			wp_enqueue_script( 'efb-landing' );
		}
	}

	public function setup() {
		if ( ! efb_landing_is_archive() || ! efb_landing_applies( get_queried_object_id() ) ) {
			return;
		}
		$term = get_queried_object();
		$data = EFB_Landing_Meta::get( $term->term_id );

		// The short description is the category Description, which WooCommerce
		// already prints above the grid (woocommerce_taxonomy_archive_description).
		if ( ! self::show_sections() ) {
			return;
		}

		// Only when the grid lists subcategories instead of products (Display type
		// "Subcategories"), so the products are not shown twice. Right below the grid,
		// before pagination (10) and the landing content (30).
		if ( EFB_Landing_Meta::has_section( $data, 'products_carousel' ) && get_term_children( $term->term_id, 'product_cat' ) && 'subcategories' === woocommerce_get_loop_display_mode() ) {
			add_action( 'woocommerce_after_shop_loop', array( $this, 'print_products_carousel' ), 5 );
		}

		add_action( 'woocommerce_after_shop_loop', array( $this, 'print_after_loop' ), 30 );
		add_action( 'woocommerce_no_products_found', array( $this, 'print_after_loop' ), 30 );

		// Google allows one FAQPage per page: questions from a FAQPage script inside the
		// long description are merged with the accordion's into a single schema.
		list( , $embedded ) = self::split_faq_schema( $data['long_desc'] );
		if ( ( EFB_Landing_Meta::has_section( $data, 'faq' ) || $embedded ) && apply_filters( 'efb_landing_faq_schema', true, $term ) ) {
			self::$faq_term     = $term->term_id;
			self::$embedded_faq = $embedded;
			add_action( 'wp_footer', array( $this, 'print_faq_schema' ) );
		}
	}

	/** Content sections only on page 1, so paginated archives don't repeat them. */
	private static function show_sections() {
		return ! is_paged() || apply_filters( 'efb_landing_show_on_paged', false );
	}

	public function print_products_carousel() {
		echo self::products_carousel( get_queried_object_id(), EFB_Landing_Meta::get( get_queried_object_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
	}

	/**
	 * Every product of the category and its subcategories, in the shop's product
	 * order, as cards with quantity and Add to cart.
	 */
	private static function products_carousel( $term_id, array $data ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		$products = wc_get_products(
			apply_filters(
				'efb_landing_products_carousel_query',
				array(
					'status'     => 'publish',
					'visibility' => 'catalog',
					'category'   => array( $term->slug ), // Includes subcategories.
					'limit'      => 100,
					'orderby'    => array(
						'menu_order' => 'ASC',
						'title'      => 'ASC',
					),
				),
				$term
			)
		);

		$title = self::title( $data['titles']['products_carousel'] ?? '', $term_id );
		$body  = EFB_Landing_Cards::carousel( $products, '' !== $title ? $title : $term->name );
		if ( '' === $body ) {
			return '';
		}

		$heading = '' !== $title ? '<h2 class="efbl-section__title">' . esc_html( $title ) . '</h2>' : '';
		$html    = '<section class="efbl efbl-section efbl-section--products-carousel" id="efbl-products-carousel">' . $heading . $body . '</section>';

		return apply_filters( 'efb_landing_section_html', $html, 'products_carousel', $term_id, $data );
	}

	public function print_after_loop() {
		if ( self::$printed ) {
			return;
		}
		self::$printed = true;
		echo self::get_html( get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
	}

	/**
	 * All the landing sections of a category.
	 *
	 * @param int $term_id Category ID.
	 * @return string
	 */
	public static function get_html( $term_id ) {
		if ( ! $term_id || ! efb_landing_applies( $term_id ) ) {
			return '';
		}
		$data = EFB_Landing_Meta::get( $term_id );
		$html = '';
		foreach ( $data['order'] as $key ) {
			$html .= self::section( $key, $term_id, $data );
		}
		$html .= self::section( 'related_cats', $term_id, $data );
		$html .= self::section( 'markets', $term_id, $data );
		$html .= self::section( 'reviews', $term_id, $data );
		return '' === $html ? '' : '<div class="efbl efbl-landing">' . $html . '</div>';
	}

	private static function section( $key, $term_id, array $data ) {
		if ( ! EFB_Landing_Meta::has_section( $data, $key ) ) {
			return '';
		}

		switch ( $key ) {
			case 'features':
				$body = EFB_Landing_Features::render( $data['features'] );
				break;
			case 'faq':
				$body = self::faq( $data['faq'] );
				break;
			case 'comparison':
				$body = self::comparison( $data['table'], $data['titles']['comparison'] );
				break;
			case 'articles':
				$body = self::articles( $data['articles'] );
				break;
			case 'long_desc':
				$long = $data['long_desc'];
				if ( self::$faq_term === (int) $term_id ) {
					list( $long ) = self::split_faq_schema( $long ); // Its questions go into the merged schema.
				}
				// Same formatting as the theme used (wpautop, shortcodes, embeds).
				$body = '<div class="efbl-long-desc ef-range-content">' . apply_filters( 'the_content', $long ) . '</div>';
				break;
			case 'related_cats':
				$body = self::related_categories( (int) $term_id );
				break;
			case 'markets':
				$body = self::markets_carousel();
				break;
			case 'parts':
				$body = EFB_Landing_Parts::render( (int) $term_id, $data );
				break;
			case 'reviews':
				$body = EFB_Landing_Reviews::render( $term_id );
				break;
			default:
				return '';
		}

		if ( '' === $body ) {
			return '';
		}

		$title   = self::title( $data['titles'][ $key ] ?? '', $term_id );
		$heading = '' !== $title ? '<h2 class="efbl-section__title">' . esc_html( $title ) . '</h2>' : '';
		$html    = sprintf(
			'<section class="efbl-section efbl-section--%1$s" id="efbl-%1$s">%2$s%3$s</section>',
			esc_attr( str_replace( '_', '-', $key ) ),
			$heading,
			$body
		);

		return apply_filters( 'efb_landing_section_html', $html, $key, $term_id, $data );
	}

	/** Section headings may contain {category}, replaced with the page's H1. */
	private static function title( $title, $term_id ) {
		$title = str_replace( '%category%', EFB_Landing_Meta::CATEGORY_TOKEN, (string) $title );
		if ( false === strpos( $title, EFB_Landing_Meta::CATEGORY_TOKEN ) ) {
			return $title;
		}
		if ( efb_landing_is_archive() && (int) $term_id === get_queried_object_id() ) {
			$name = html_entity_decode( wp_strip_all_tags( woocommerce_page_title( false ) ), ENT_QUOTES, 'UTF-8' );
		} else {
			$term = get_term( (int) $term_id );
			$name = $term && ! is_wp_error( $term ) ? $term->name : '';
		}
		return trim( preg_replace( '/\s+/', ' ', str_replace( EFB_Landing_Meta::CATEGORY_TOKEN, $name, $title ) ) );
	}

	private static function format_html( $html ) {
		return do_shortcode( shortcode_unautop( wpautop( wptexturize( $html ) ) ) );
	}

	private static function faq( array $items ) {
		$html = '<div class="efbl-faq">';
		foreach ( $items as $item ) {
			$html .= '<details class="efbl-faq__item"><summary class="efbl-faq__q"><h3 class="efbl-faq__title"><span class="efbl-faq__icon" aria-hidden="true"></span>' . esc_html( $item['q'] ) . '</h3></summary>';
			$html .= '<div class="efbl-faq__a"><div class="efbl-faq__a-inner">' . self::format_html( $item['a'] ) . '</div></div></details>';
		}
		return $html . '</div>';
	}

	private static function comparison( array $table, $label ) {
		$html  = '<div class="efbl-table-wrap" role="region" tabindex="0" aria-label="' . esc_attr( $label ? $label : __( 'Comparison table', 'ecofridge-blocks' ) ) . '">';
		$html .= '<table class="efbl-table"><thead><tr>';
		foreach ( $table['head'] as $cell ) {
			$html .= '<th scope="col">' . self::cell( $cell ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $table['rows'] as $row ) {
			$html .= '<tr>';
			foreach ( $row as $i => $cell ) {
				$html .= 0 === $i ? '<th scope="row">' . self::cell( $cell ) . '</th>' : '<td>' . self::cell( $cell ) . '</td>';
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table></div>';
	}

	private static function cell( $value ) {
		$icons = array(
			'[yes]' => '<span class="efbl-yes" role="img" aria-label="' . esc_attr__( 'Yes', 'ecofridge-blocks' ) . '">✓</span>',
			'[no]'  => '<span class="efbl-no" role="img" aria-label="' . esc_attr__( 'No', 'ecofridge-blocks' ) . '">✕</span>',
		);
		return strtr( wp_kses_post( $value ), $icons );
	}

	private static function articles( array $ids ) {
		if ( ! $ids ) {
			return '';
		}
		$query = new WP_Query(
			array(
				'post_type'           => 'any',
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'post_status'         => 'publish',
				'posts_per_page'      => count( $ids ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( ! $query->have_posts() ) {
			return '';
		}

		$html = '<ul class="efbl-articles">';
		foreach ( $query->posts as $post ) {
			$html .= '<li class="efbl-article">';
			$html .= '<a class="efbl-article__link" href="' . esc_url( get_permalink( $post ) ) . '">';
			$html .= has_post_thumbnail( $post ) ? get_the_post_thumbnail( $post, 'medium_large', array( 'class' => 'efbl-article__img', 'loading' => 'lazy' ) ) : '<span class="efbl-article__img efbl-article__img--empty"></span>';
			$html .= '<span class="efbl-article__body">';
			$html .= '<time class="efbl-article__date" datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( '', $post ) ) . '</time>';
			$html .= '<span class="efbl-article__title">' . esc_html( get_the_title( $post ) ) . '</span>';
			$html .= '<span class="efbl-article__excerpt">' . esc_html( preg_replace( '/^[\s\x{00A0}]+/u', '', wp_trim_words( get_the_excerpt( $post ), 22 ) ) ) . '</span>';
			$html .= '</span></a></li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Cards for the other categories under the same parent (root categories get
	 * none); on a tag, the categories its products belong to.
	 */
	private static function related_categories( $term_id ) {
		if ( efb_landing_is_tag( $term_id ) ) {
			$siblings = efb_landing_tag_categories( $term_id );
		} else {
			$tree = efb_landing_tree();
			if ( empty( $tree[ $term_id ] ) ) {
				return '';
			}
			$siblings = array_values( array_diff( efb_landing_children( $tree[ $term_id ] ), array( $term_id ) ) );
		}
		$siblings = apply_filters( 'efb_landing_related_categories', $siblings, $term_id );

		$items = '';
		foreach ( $siblings as $id ) {
			$cat = get_term( (int) $id, 'product_cat' );
			if ( ! $cat || is_wp_error( $cat ) ) {
				continue;
			}
			// WooCommerce keeps a count that includes products in subcategories.
			$count = (int) get_term_meta( $cat->term_id, 'product_count_product_cat', true );
			$count = $count ? $count : (int) $cat->count;
			if ( ! $count ) {
				continue;
			}
			$url   = efb_term_image_url( $cat->term_id );
			$image = $url
				? '<img class="efbl-cat__img" src="' . esc_url( $url ) . '" alt="" loading="lazy">'
				: wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'efbl-cat__img' ) );
			$items .= '<li class="efbl-cat"><a class="efbl-cat__link" href="' . esc_url( get_term_link( $cat ) ) . '">' . $image
				. '<span class="efbl-cat__name">' . esc_html( efb_landing_label( $cat->term_id ) ) . '</span>'
				/* translators: %s: number of products */
				. '<span class="efbl-cat__count">' . esc_html( sprintf( _n( '%s product', '%s products', $count, 'ecofridge-blocks' ), number_format_i18n( $count ) ) ) . '</span>'
				. '</a></li>';
		}

		return '' === $items ? '' : '<ul class="efbl-cats">' . $items . '</ul>';
	}

	/**
	 * Every market page (children of /markets/): the site's own card carousel
	 * (as on the home page) when the pages have featured images, otherwise links
	 * as pills (no empty image boxes).
	 */
	private static function markets_carousel() {
		$parent = get_page_by_path( apply_filters( 'efb_landing_markets_parent', 'markets' ) );
		if ( ! $parent ) {
			return '';
		}
		$pages = get_pages(
			array(
				'parent'      => $parent->ID,
				'sort_column' => 'menu_order,post_title',
			)
		);
		// On a market page, the other markets only.
		$current = is_tax( 'market' ) && function_exists( 'efb_market_page' ) ? efb_market_page( get_queried_object() ) : null;
		if ( $current ) {
			$pages = array_values( array_filter( $pages, static fn( $p ) => $p->ID !== $current->ID ) );
		}
		if ( ! $pages ) {
			return '';
		}
		$with_images = array_filter( $pages, 'has_post_thumbnail' );
		if ( ! $with_images || ! function_exists( 'ef_render_category_carousel' ) ) {
			$chips = '';
			foreach ( $pages as $page ) {
				$chips .= '<li><a class="efbl-chip" href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a></li>';
			}
			return '<ul class="efbl-chips efbl-markets">' . $chips . '</ul>';
		}

		$cards = array();
		foreach ( $pages as $page ) { // every market; a page without an image gets the shop placeholder.
			$img     = (string) get_the_post_thumbnail_url( $page, 'woocommerce_single' );
			$cards[] = array(
				'title' => get_the_title( $page ),
				'link'  => get_permalink( $page ),
				'img'   => '' !== $img ? $img : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '' ),
			);
		}
		$GLOBALS['ef_cc_used'] = true; // the theme prints the carousel script in the footer.
		ob_start();
		ef_render_category_carousel( apply_filters( 'efb_landing_markets_cards', $cards ) );
		return '<div class="ef-cat-carousel-wrap efbl-markets-carousel">' . ob_get_clean() . '</div>';
	}

	/**
	 * Removes FAQPage JSON-LD scripts from HTML and returns their questions.
	 * Other JSON-LD is left in place, and so is anything that cannot be parsed.
	 *
	 * @return array{0:string,1:array} [ html without FAQPage scripts, Question objects ]
	 */
	private static function split_faq_schema( $html ) {
		$questions = array();
		if ( false === stripos( $html, 'FAQPage' ) ) {
			return array( $html, $questions );
		}

		$html = preg_replace_callback(
			'#<script[^>]*application/ld\+json[^>]*>(.*?)</script>#si',
			function ( $m ) use ( &$questions ) {
				$json = self::decode_json_ld( $m[1] );
				if ( ! is_array( $json ) || 'FAQPage' !== ( $json['@type'] ?? '' ) || empty( $json['mainEntity'] ) || ! is_array( $json['mainEntity'] ) ) {
					return $m[0];
				}
				foreach ( $json['mainEntity'] as $q ) {
					if ( is_array( $q ) && 'Question' === ( $q['@type'] ?? '' ) && ! empty( $q['name'] ) ) {
						$questions[] = $q;
					}
				}
				return '';
			},
			$html
		);

		return array( $html, $questions );
	}

	/** Hand-written JSON-LD often has raw line breaks inside strings (invalid JSON); spaces fix that. */
	private static function decode_json_ld( $raw ) {
		$raw = trim( $raw );
		foreach ( array( $raw, preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $raw ) ) as $candidate ) {
			$json = json_decode( $candidate, true );
			if ( is_array( $json ) ) {
				return $json;
			}
		}
		return null;
	}

	private static function question_key( $text ) {
		return strtolower( (string) preg_replace( '/[^\p{L}\p{N}]+/u', '', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	}

	public function print_faq_schema() {
		$data = EFB_Landing_Meta::get( get_queried_object_id() );
		$faq  = array();
		$seen = array();

		// Accordion questions first, then the ones from the embedded schema; same question once.
		foreach ( $data['faq'] as $item ) {
			$seen[ self::question_key( $item['q'] ) ] = true;
			$faq[] = array(
				'@type'          => 'Question',
				'name'           => $item['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $item['a'] ),
				),
			);
		}
		foreach ( self::$embedded_faq as $q ) {
			$key = self::question_key( $q['name'] );
			if ( '' !== $key && empty( $seen[ $key ] ) ) {
				$seen[ $key ] = true;
				$faq[]        = $q;
			}
		}
		if ( ! $faq ) {
			return;
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $faq,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
	}
}
