<?php
/**
 * Blog: /blog/ (posts page), its topic categories (/blog/<slug>/), post tags
 * and single posts.
 *
 *  - templates: home.php, category.php, tag.php → template-parts/blog-archive.php;
 *    single-post.php for articles
 *  - helpers used by those templates: cards, topic buttons, reading time,
 *    table of contents, related posts, structured data
 *  - styles: assets/blog.css, loaded on blog screens only
 *  - "SEO heading (H1)" field on the category edit screen
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Articles per blog page in the grid (3 columns); page 1 also shows the latest one on its own above it. */
const EF_BLOG_PER_PAGE = 12;

/**
 * Whether the current screen is part of the blog.
 */
function ef_blog_is_blog() {
	return is_home() || is_category() || is_tag() || is_singular( 'post' );
}

add_action( 'wp_enqueue_scripts', function () {
	// Also pages with the blog cards / help box blocks (or the [ef_help_cta] shortcode).
	$post = is_singular() ? get_post() : null;
	if ( ! ef_blog_is_blog() && ! is_front_page() && ! ( function_exists( 'ef_page_has_theme_blocks' ) && ef_page_has_theme_blocks() ) && ! ( $post && has_shortcode( $post->post_content, 'ef_help_cta' ) ) ) { return; }
	$file = get_stylesheet_directory() . '/assets/blog.css';
	wp_enqueue_style( 'ef-blog', get_stylesheet_directory_uri() . '/assets/blog.css', array( 'eco-fridge-child' ), (string) filemtime( $file ) );
}, 20 );

add_filter( 'body_class', function ( $classes ) {
	if ( ef_blog_is_blog() ) {
		$classes[] = is_singular( 'post' ) ? 'ef-blog-single' : 'ef-blog-archive';
	}
	return $classes;
} );

/** Whether a query is a blog archive (index, topic or tag). */
function ef_blog_is_archive_query( $query ) {
	return ! is_admin() && $query->is_main_query() && ( $query->is_home() || $query->is_category() || $query->is_tag() );
}

/**
 * Page 1: the latest article + a full grid (1 + 12); later pages: 12, starting
 * after the extra article on page 1. Set on the SQL limit itself: the
 * posts_per_page query var is reset to the Reading setting after pre_get_posts.
 */
add_filter( 'post_limits', function ( $limits, $query ) {
	if ( ! ef_blog_is_archive_query( $query ) ) { return $limits; }
	$paged = max( 1, (int) $query->get( 'paged' ) );
	return 1 === $paged
		? 'LIMIT 0, ' . ( EF_BLOG_PER_PAGE + 1 )
		: 'LIMIT ' . ( 1 + ( $paged - 1 ) * EF_BLOG_PER_PAGE ) . ', ' . EF_BLOG_PER_PAGE;
}, 99, 2 );

/** Page count to match (max_num_pages = found_posts / posts_per_page). */
add_filter( 'found_posts', function ( $found, $query ) {
	if ( ! ef_blog_is_archive_query( $query ) ) { return $found; }
	$pages = (int) ceil( max( 0, $found - 1 ) / EF_BLOG_PER_PAGE );
	return $found ? max( 1, $pages ) * max( 1, (int) $query->get( 'posts_per_page' ) ) : 0;
}, 10, 2 );

/** No sidebar anywhere on the blog. */
add_filter( 'astra_page_layout', function ( $layout ) {
	return ef_blog_is_blog() ? 'no-sidebar' : $layout;
}, 20 );

/**
 * Topic categories (children of "Blog"), in their button order.
 *
 * @return WP_Term[]
 */
function ef_blog_topics() {
	return function_exists( 'efb_blog_categories' ) ? efb_blog_categories() : array();
}

/**
 * A post's topic category.
 *
 * @param int|WP_Post $post Post.
 * @return WP_Term|null
 */
function ef_blog_topic( $post ) {
	$ids = wp_list_pluck( ef_blog_topics(), 'term_id' );
	foreach ( get_the_category( $post ) as $cat ) {
		if ( in_array( $cat->term_id, $ids, true ) ) {
			return $cat;
		}
	}
	return null;
}

/**
 * Minutes to read a post (220 words a minute).
 *
 * @param int|WP_Post $post Post.
 * @return int
 */
function ef_blog_reading_time( $post ) {
	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post ) ) ) );
	return max( 1, (int) round( $words / 220 ) );
}

/**
 * A short plain-text summary of a post.
 *
 * @param int|WP_Post $post  Post.
 * @param int         $words Maximum words.
 * @return string
 */
function ef_blog_summary( $post, $words = 24 ) {
	$post = get_post( $post );
	$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( excerpt_remove_blocks( $post->post_content ) );
	return wp_trim_words( wp_strip_all_tags( $text ), $words, '…' );
}

/**
 * The H1 of a blog archive and the intro under it.
 *
 * @return array{eyebrow:string, title:string, intro:string}
 */
function ef_blog_heading() {
	if ( is_category() ) {
		$term = get_queried_object();
		$h1   = (string) get_term_meta( $term->term_id, 'ef_h1', true );
		return array(
			'eyebrow' => 'ECO-Fridge Blog',
			'title'   => $h1 ? $h1 : $term->name,
			'intro'   => $term->description,
		);
	}
	if ( is_tag() ) {
		$term = get_queried_object();
		return array(
			'eyebrow' => 'ECO-Fridge Blog',
			'title'   => sprintf( 'Articles about %s', $term->name ),
			'intro'   => $term->description ? $term->description : sprintf( 'Guides, tips and advice from ECO-Fridge about %s.', strtolower( $term->name ) ),
		);
	}
	return array(
		'eyebrow' => 'ECO-Fridge Blog',
		'title'   => 'Commercial Refrigeration Blog',
		'intro'   => 'Practical advice from the ECO-Fridge team on choosing, running and getting the most from commercial fridges, freezers and display cabinets – written for cafés, restaurants, shops and caterers across the UK.',
	);
}

add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_home() || is_category() ) {
		$heading        = ef_blog_heading();
		$parts['title'] = $heading['title'];
		if ( is_paged() ) {
			$parts['page'] = sprintf( 'Page %d', max( 2, (int) get_query_var( 'paged' ) ) );
		}
	}
	return $parts;
}, 20 );

/**
 * Meta description of the blog index, topic pages and articles: the topic's
 * introduction or the article's summary (printed by the theme without an SEO
 * plugin; with Rank Math it fills in where no description was written).
 *
 * @return string
 */
function ef_blog_meta_description() {
	if ( is_home() || is_category() ) {
		$heading = ef_blog_heading();
		$desc    = $heading['intro'];
	} elseif ( is_singular( 'post' ) ) {
		$desc = ef_blog_summary( get_queried_object(), 30 );
	} else {
		return '';
	}
	return $desc ? wp_html_excerpt( wp_strip_all_tags( $desc ), 158, '…' ) : '';
}

add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) { return; }
	$desc = ef_blog_meta_description();
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
}, 2 );

/**
 * Topic buttons: "All articles" + every topic, the current one highlighted.
 *
 * @return string
 */
function ef_blog_topic_nav() {
	$current = is_category() ? get_queried_object_id() : 0;
	$page    = get_option( 'page_for_posts' );
	$total   = (int) wp_count_posts()->publish;
	$items   = '<li><a class="ef-pill' . ( is_home() ? ' is-active' : '' ) . '" href="' . esc_url( $page ? get_permalink( $page ) : home_url( '/blog/' ) ) . '"' . ( is_home() ? ' aria-current="page"' : '' ) . '>All articles <span class="ef-pill__count">' . $total . '</span></a></li>';
	foreach ( ef_blog_topics() as $topic ) {
		if ( ! $topic->count ) { continue; }
		$active = $current === $topic->term_id;
		$items .= '<li><a class="ef-pill' . ( $active ? ' is-active' : '' ) . '" href="' . esc_url( get_term_link( $topic ) ) . '"' . ( $active ? ' aria-current="page"' : '' ) . '>'
			. esc_html( $topic->name ) . ' <span class="ef-pill__count">' . (int) $topic->count . '</span></a></li>';
	}
	return '<nav class="ef-blog-topics" aria-label="Blog categories"><ul>' . $items . '</ul></nav>';
}

/**
 * Placeholder for posts without a featured image.
 */
function ef_blog_placeholder() {
	return '<span class="ef-card__placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" width="44" height="44"><path fill="currentColor" d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v6h10V4H7zm0 8v8h10v-8H7zm2-6h2v2H9V6zm0 8h2v3H9v-3z"/></svg></span>';
}

/**
 * An article card.
 *
 * @param WP_Post $post    Post.
 * @param string  $variant '' or 'feature' (the latest article: wide, image on the left).
 * @param string  $tag     Heading tag of the title.
 * @return string
 */
function ef_blog_card( $post, $variant = '', $tag = 'h2' ) {
	$link  = get_permalink( $post );
	$title = get_the_title( $post );
	$topic = ef_blog_topic( $post );
	$wide  = 'feature' === $variant;
	$size  = $wide ? 'large' : 'medium_large';
	$sizes = $wide ? '(max-width: 900px) 100vw, 680px' : '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 380px';
	$img   = has_post_thumbnail( $post )
		? get_the_post_thumbnail( $post, $size, array( 'loading' => $wide ? 'eager' : 'lazy', 'sizes' => $sizes, 'alt' => '' ) )
		: ef_blog_placeholder();

	$html  = '<article class="ef-card' . ( $variant ? ' ef-card--' . esc_attr( $variant ) : '' ) . '">';
	$html .= '<div class="ef-card__media">' . $img . '</div>';
	$html .= '<div class="ef-card__body">';
	if ( $topic ) {
		$html .= '<a class="ef-chip" href="' . esc_url( get_term_link( $topic ) ) . '">' . esc_html( $topic->name ) . '</a>';
	}
	$html .= '<' . $tag . ' class="ef-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></' . $tag . '>';
	$html .= '<p class="ef-card__excerpt">' . esc_html( ef_blog_summary( $post, $wide ? 48 : 22 ) ) . '</p>';
	$html .= '<div class="ef-card__meta"><time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( 'j M Y', $post ) ) . '</time>'
		. '<span aria-hidden="true">·</span><span>' . (int) ef_blog_reading_time( $post ) . ' min read</span></div>';
	if ( $wide ) {
		$html .= '<span class="ef-card__more" aria-hidden="true">Read article <span>→</span></span>';
	}
	$html .= '</div></article>';
	return $html;
}

/**
 * Pagination of a blog archive.
 *
 * @return string
 */
function ef_blog_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'list',
			'mid_size'  => 1,
			'prev_text' => '<span aria-hidden="true">←</span> Newer',
			'next_text' => 'Older <span aria-hidden="true">→</span>',
		)
	);
	return $links ? '<nav class="ef-blog-pagination" aria-label="Blog pages">' . $links . '</nav>' : '';
}

/**
 * Call-to-action band at the end of blog pages.
 *
 * @return string
 */
function ef_blog_cta() {
	return '<aside class="ef-blog-cta">'
		. '<div class="ef-blog-cta__text"><p class="ef-blog-cta__title">Need help choosing the right fridge?</p>'
		. '<p>Our team has over 30 years’ experience in commercial refrigeration. Tell us about your business and we’ll recommend the right equipment for your space and budget.</p></div>'
		. '<div class="ef-blog-cta__actions"><a class="ef-btn ef-btn--light" href="' . esc_url( home_url( '/contact-us/' ) ) . '">Contact us</a></div>'
		. '</aside>';
}

/**
 * The post content with anchors on its H2s, and the table of contents built from them.
 *
 * @return array{html:string, toc:array<int,array{id:string,text:string}>}
 */
function ef_blog_content_with_toc() {
	$html = apply_filters( 'the_content', get_the_content() );
	$html = str_replace( ']]>', ']]&gt;', $html );
	$toc  = array();
	$used = array();
	$html = preg_replace_callback(
		'~<h2(\s[^>]*)?>(.*?)</h2>~is',
		static function ( $m ) use ( &$toc, &$used ) {
			$attrs = (string) $m[1];
			$text  = trim( html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $text ) {
				return $m[0];
			}
			if ( preg_match( '~\sid=["\']([^"\']+)~', $attrs, $id ) ) {
				$anchor = $id[1];
			} else {
				$anchor = sanitize_title( $text );
				$base   = $anchor ? $anchor : 'section';
				$n      = 2;
				while ( isset( $used[ $anchor ] ) || '' === $anchor ) {
					$anchor = $base . '-' . $n++;
				}
				$attrs .= ' id="' . esc_attr( $anchor ) . '"';
			}
			$used[ $anchor ] = true;
			$toc[]           = array( 'id' => $anchor, 'text' => $text );
			return '<h2' . $attrs . '>' . $m[2] . '</h2>';
		},
		$html
	);
	return array( 'html' => $html, 'toc' => $toc );
}

/**
 * Latest posts on the same topic.
 *
 * @param WP_Post $post  Post.
 * @param int     $count Number of posts.
 * @return WP_Post[]
 */
function ef_blog_related( $post, $count = 3 ) {
	$topic = ef_blog_topic( $post );
	$args  = array(
		'numberposts'         => $count,
		'post__not_in'        => array( $post->ID ),
		'ignore_sticky_posts' => true,
	);
	if ( $topic ) {
		$args['category__in'] = array( $topic->term_id );
	}
	$related = get_posts( $args );
	if ( count( $related ) < $count ) {
		$related = array_merge(
			$related,
			get_posts(
				array(
					'numberposts'  => $count - count( $related ),
					'post__not_in' => array_merge( array( $post->ID ), wp_list_pluck( $related, 'ID' ) ),
				)
			)
		);
	}
	return $related;
}

/**
 * Share links for a post.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function ef_blog_share( $post ) {
	$url   = rawurlencode( get_permalink( $post ) );
	$title = rawurlencode( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );
	$links = array(
		'LinkedIn' => array( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url, 'M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM9 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C20.6 8.65 21 11.2 21 14.5V21h-4v-5.8c0-1.4-.03-3.2-1.95-3.2-1.95 0-2.25 1.5-2.25 3.1V21H9z' ),
		'Facebook' => array( 'https://www.facebook.com/sharer/sharer.php?u=' . $url, 'M14 8h3V4h-3c-2.8 0-4.5 1.7-4.5 4.5V11H7v4h2.5v7h4v-7H17l.5-4h-4V8.8c0-.5.3-.8.5-.8z' ),
		'X'        => array( 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title, 'M17.8 3H21l-7 8 8.2 10H16l-5-6.2L5.4 21H2.2l7.5-8.6L1.8 3h6.4l4.5 5.7zm-1.1 16h1.8L7.4 4.9H5.5z' ),
		'Email'    => array( 'mailto:?subject=' . $title . '&body=' . $url, 'M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7.2L4 7.3V17h16V7.3zM5.2 7l6.8 4.2L18.8 7z' ),
	);
	$html = '<div class="ef-share"><span class="ef-share__label">Share</span>';
	foreach ( $links as $name => list( $href, $path ) ) {
		$html .= '<a class="ef-share__btn" href="' . esc_url( $href ) . '"' . ( 'Email' === $name ? '' : ' target="_blank" rel="noopener"' ) . ' aria-label="' . esc_attr( 'Share on ' . $name ) . '">'
			. '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="' . esc_attr( $path ) . '"/></svg></a>';
	}
	return $html . '</div>';
}

/**
 * Structured data for a post: BlogPosting + BreadcrumbList.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function ef_blog_schema( $post ) {
	$topic = ef_blog_topic( $post );
	$blog  = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/blog/' );
	$logo  = get_theme_mod( 'custom_logo' ) ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '';
	$org   = array( '@type' => 'Organization', 'name' => 'ECO-Fridge Ltd', 'url' => home_url( '/' ) );
	if ( $logo ) {
		$org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo );
	}
	$article = array(
		'@type'            => 'BlogPosting',
		'headline'         => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
		'description'      => ef_blog_summary( $post, 30 ),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'mainEntityOfPage' => get_permalink( $post ),
		'author'           => $org,
		'publisher'        => $org,
		'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
	);
	if ( has_post_thumbnail( $post ) ) {
		$article['image'] = get_the_post_thumbnail_url( $post, 'large' );
	}
	if ( $topic ) {
		$article['articleSection'] = $topic->name;
	}
	$crumbs = array( array( 'Home', home_url( '/' ) ), array( 'Blog', $blog ) );
	if ( $topic ) {
		$crumbs[] = array( $topic->name, get_term_link( $topic ) );
	}
	$crumbs[] = array( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ), get_permalink( $post ) );
	$list     = array();
	foreach ( $crumbs as $i => list( $name, $url ) ) {
		$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $url );
	}
	$graph = array(
		'@context' => 'https://schema.org',
		'@graph'   => array( $article, array( '@type' => 'BreadcrumbList', 'itemListElement' => $list ) ),
	);
	return '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}

/** [ef_help_cta] — the "Need help choosing the right fridge?" box, anywhere. */
add_shortcode( 'ef_help_cta', 'ef_blog_cta' );

/**
 * [ef_home_blog count="8"] — the latest articles as a carousel (home page),
 * using the blog cards and the category carousel's arrows and script.
 */
add_shortcode( 'ef_home_blog', function ( $atts ) {
	$atts  = shortcode_atts( array( 'count' => 8 ), $atts, 'ef_home_blog' );
	$posts = get_posts( array( 'numberposts' => max( 3, min( 12, (int) $atts['count'] ) ), 'ignore_sticky_posts' => true ) );
	if ( ! $posts ) { return ''; }
	$GLOBALS['ef_cc_used'] = true; // the footer prints the carousel script
	$blog  = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/blog/' );
	$html  = '<div class="ef-cat-carousel ef-blog-carousel">';
	$html .= '<button type="button" class="ef-cc-arrow ef-cc-prev" aria-label="Previous articles">&lsaquo;</button>';
	$html .= '<div class="ef-cc-track" role="list">';
	foreach ( $posts as $post ) {
		$html .= '<div class="ef-cc-slide" role="listitem">' . ef_blog_card( $post, '', 'h3' ) . '</div>';
	}
	$html .= '</div><button type="button" class="ef-cc-arrow ef-cc-next" aria-label="More articles">&rsaquo;</button></div>';
	$html .= '<p class="ef-blog-carousel__all"><a class="ef-btn" href="' . esc_url( $blog ) . '">View all articles</a></p>';
	return $html;
} );

/**
 * "SEO heading (H1)" on the blog topic category edit screen.
 */
add_action( 'category_edit_form_fields', function ( $term ) {
	wp_nonce_field( 'ef_blog_h1', 'ef_blog_h1_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ef_h1"><?php esc_html_e( 'SEO heading (H1)', 'eco-fridge-child' ); ?></label></th>
		<td>
			<input type="text" id="ef_h1" name="ef_h1" value="<?php echo esc_attr( (string) get_term_meta( $term->term_id, 'ef_h1', true ) ); ?>">
			<p class="description"><?php esc_html_e( 'The heading of the category page on the blog. Leave empty to use the name. The description is shown under it.', 'eco-fridge-child' ); ?></p>
		</td>
	</tr>
	<?php
} );
add_action( 'edited_category', function ( $term_id ) {
	if ( ! isset( $_POST['ef_blog_h1_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ef_blog_h1_nonce'] ) ), 'ef_blog_h1' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) || ! isset( $_POST['ef_h1'] ) ) { return; }
	update_term_meta( $term_id, 'ef_h1', sanitize_text_field( wp_unslash( $_POST['ef_h1'] ) ) );
} );

/**
 * "Also read" boxes in articles (group block .ef-also-read, migration 61): each
 * link becomes a card with the article's image, topic and current title. Links
 * that are not articles (pages, categories) keep their own text and a placeholder.
 */
add_filter( 'render_block_core/group', function ( $html, $block ) {
	if ( false === strpos( (string) ( $block['attrs']['className'] ?? '' ), 'ef-also-read' ) ) {
		return $html;
	}
	if ( ! preg_match_all( '~<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>~is', $html, $links, PREG_SET_ORDER ) ) {
		return $html;
	}

	$cards = '';
	foreach ( $links as $link ) {
		$url   = html_entity_decode( $link[1] );
		$title = wp_strip_all_tags( $link[2] );
		$topic = null;
		$img   = '';
		$id    = url_to_postid( 0 === strpos( $url, '/' ) ? home_url( $url ) : $url );
		if ( $id ) {
			$url   = get_permalink( $id );
			$title = get_the_title( $id );
			$topic = 'post' === get_post_type( $id ) ? ef_blog_topic( $id ) : null;
			if ( has_post_thumbnail( $id ) ) {
				$img = get_the_post_thumbnail( $id, 'thumbnail', array( 'loading' => 'lazy', 'alt' => '' ) );
			}
		}
		$cards .= '<li><a class="ef-also-read__card" href="' . esc_url( $url ) . '">'
			. '<span class="ef-also-read__img">' . ( $img ? $img : ef_blog_placeholder() ) . '</span>'
			. '<span class="ef-also-read__text">'
			. ( $topic ? '<span class="ef-also-read__topic">' . esc_html( $topic->name ) . '</span>' : '' )
			. '<span class="ef-also-read__title">' . esc_html( $title ) . '</span></span>'
			. '<span class="ef-also-read__go" aria-hidden="true"></span>'
			. '</a></li>';
	}

	$single = 1 === count( $links ) ? ' ef-also-read--single' : '';
	return '<aside class="ef-also-read' . $single . '" aria-label="Also read">'
		. '<p class="ef-also-read__label"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5a.5.5 0 0 0 0 1H20v2H6.5A2.5 2.5 0 0 1 4 19.5v-15zM6.5 4a.5.5 0 0 0-.5.5v12.55c.16-.03.33-.05.5-.05H18V4H6.5z"/></svg>Also read</p>'
		. '<ul class="ef-also-read__list">' . $cards . '</ul>'
		. '</aside>';
}, 10, 2 );
