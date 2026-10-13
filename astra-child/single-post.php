<?php
/**
 * Blog article.
 *
 * Breadcrumbs → topic → H1 → meta (author, date, reading time) → featured
 * image → content with a sticky table of contents and help card beside it →
 * tags, share links, author box → related articles → previous / next →
 * call to action. Structured data: BlogPosting + BreadcrumbList.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<div id="primary" class="content-area primary ef-post-page">
	<main id="main" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		$ef_post    = get_post();
		$ef_topic   = ef_blog_topic( $ef_post );
		$ef_blog    = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/blog/' );
		$ef_content = ef_blog_content_with_toc();
		$ef_toc     = count( $ef_content['toc'] ) >= 3 ? $ef_content['toc'] : array();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'ef-post' ); ?>>

			<header class="ef-post-hero">
				<nav class="ef-post-crumbs" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span aria-hidden="true">›</span>
					<a href="<?php echo esc_url( $ef_blog ); ?>">Blog</a>
					<?php if ( $ef_topic ) : ?>
						<span aria-hidden="true">›</span><a href="<?php echo esc_url( get_term_link( $ef_topic ) ); ?>"><?php echo esc_html( $ef_topic->name ); ?></a>
					<?php endif; ?>
				</nav>
				<?php if ( $ef_topic ) : ?>
					<a class="ef-chip" href="<?php echo esc_url( get_term_link( $ef_topic ) ); ?>"><?php echo esc_html( $ef_topic->name ); ?></a>
				<?php endif; ?>
				<h1 class="ef-post-hero__title"><?php the_title(); ?></h1>
				<div class="ef-post-meta">
					<span class="ef-post-meta__author">
						<span class="ef-post-meta__avatar" aria-hidden="true">EF</span>
						By <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">ECO-Fridge</a>
					</span>
					<span class="ef-post-meta__item">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm-2 8h14v10H5z"/></svg>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
					</span>
					<span class="ef-post-meta__item">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm1 3v5.4l3.6 2.1-1 1.7L11 13.5V7z"/></svg>
						<?php echo (int) ef_blog_reading_time( $ef_post ); ?> min read
					</span>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="ef-post-cover">
					<?php the_post_thumbnail( 'full', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1240px) 100vw, 1200px' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="ef-post-layout<?php echo $ef_toc ? '' : ' ef-post-layout--no-toc'; ?>">
				<aside class="ef-post-aside">
					<?php if ( $ef_toc ) : ?>
						<details class="ef-toc" open>
							<summary>In this article</summary>
							<ol>
								<?php foreach ( $ef_toc as $ef_item ) : ?>
									<li><a href="#<?php echo esc_attr( $ef_item['id'] ); ?>"><?php echo esc_html( $ef_item['text'] ); ?></a></li>
								<?php endforeach; ?>
							</ol>
						</details>
					<?php endif; ?>
					<div class="ef-help-card">
						<p class="ef-help-card__title">Need expert advice?</p>
						<p>Talk to our team about the right fridge or freezer for your business.</p>
						<a class="ef-btn" href="tel:01280811411">Call 01280 811411</a>
						<a class="ef-help-card__link" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">Browse the shop →</a>
					</div>
				</aside>

				<div class="ef-post-body entry-content">
					<?php echo $ef_content['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output. ?>
					<?php
					wp_link_pages(
						array(
							'before' => '<nav class="ef-post-pages">',
							'after'  => '</nav>',
						)
					);
					?>

					<footer class="ef-post-footer">
						<?php
						$ef_tags = get_the_tags();
						if ( $ef_tags ) :
							?>
							<div class="ef-post-tags">
								<span class="ef-post-tags__label">Topics</span>
								<?php foreach ( $ef_tags as $ef_tag ) : ?>
									<a class="ef-tag" href="<?php echo esc_url( get_tag_link( $ef_tag ) ); ?>"><?php echo esc_html( $ef_tag->name ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php echo ef_blog_share( $ef_post ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<div class="ef-author-box">
							<span class="ef-author-box__avatar" aria-hidden="true">EF</span>
							<div>
								<p class="ef-author-box__name">ECO-Fridge Ltd</p>
								<p>Commercial refrigeration specialists with over 30 years’ experience, supplying display and storage fridges and freezers to cafés, restaurants, shops and caterers across the UK.</p>
							</div>
						</div>
					</footer>
				</div>
			</div>
		</article>

		<?php
		$ef_related = ef_blog_related( $ef_post );
		if ( $ef_related ) :
			?>
			<section class="ef-related" aria-labelledby="ef-related-title">
				<h2 id="ef-related-title" class="ef-blog-section-title"><?php echo $ef_topic ? esc_html( 'More in ' . $ef_topic->name ) : 'More articles'; ?></h2>
				<div class="ef-blog-grid">
					<?php
					foreach ( $ef_related as $ef_item ) {
						echo ef_blog_card( $ef_item, '', 'h3' ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$ef_prev = get_previous_post();
		$ef_next = get_next_post();
		if ( $ef_prev || $ef_next ) :
			?>
			<nav class="ef-post-nav" aria-label="More articles">
				<?php if ( $ef_prev ) : ?>
					<a class="ef-post-nav__link ef-post-nav__link--prev" href="<?php echo esc_url( get_permalink( $ef_prev ) ); ?>"><span>← Previous article</span><?php echo esc_html( get_the_title( $ef_prev ) ); ?></a>
				<?php endif; ?>
				<?php if ( $ef_next ) : ?>
					<a class="ef-post-nav__link ef-post-nav__link--next" href="<?php echo esc_url( get_permalink( $ef_next ) ); ?>"><span>Next article →</span><?php echo esc_html( get_the_title( $ef_next ) ); ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>

		<?php echo ef_blog_cta(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		if ( ! defined( 'RANK_MATH_VERSION' ) ) { // Rank Math prints its own BlogPosting + BreadcrumbList.
			echo ef_blog_schema( $ef_post ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	endwhile;
	?>
	</main>
</div>
<script>
// Table of contents: collapsed on small screens, open beside the article on
// large ones, with the section being read highlighted.
(function () {
	var toc = document.querySelector('.ef-toc');
	if (!toc) { return; }
	if (window.matchMedia('(max-width: 1023px)').matches) { toc.removeAttribute('open'); }
	var heads = Array.prototype.slice.call(document.querySelectorAll('.ef-post-body h2[id]'));
	var links = {};
	toc.querySelectorAll('a').forEach(function (a) { links[a.hash.slice(1)] = a; });
	var ticking = false;
	function update() {
		ticking = false;
		var current = null;
		heads.forEach(function (h) { if (h.getBoundingClientRect().top < 230) { current = h; } });
		toc.querySelectorAll('a.is-current').forEach(function (a) { a.classList.remove('is-current'); });
		if (current && links[current.id]) { links[current.id].classList.add('is-current'); }
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
	}, { passive: true });
	update();
	// Jump below the sticky header (the theme's own smooth scroll ignores it).
	toc.addEventListener('click', function (e) {
		var a = e.target.closest('a[href^="#"]');
		var target = a && document.getElementById(a.hash.slice(1));
		if (!target) { return; }
		e.preventDefault();
		e.stopPropagation();
		var header = document.getElementById('ef-header');
		var offset = (header ? header.getBoundingClientRect().bottom : 0) + 24;
		window.scrollTo({ top: window.pageYOffset + target.getBoundingClientRect().top - Math.max(offset, 24), behavior: 'smooth' });
		history.replaceState(null, '', a.hash);
	}, true);
})();
</script>
<?php
get_footer();
