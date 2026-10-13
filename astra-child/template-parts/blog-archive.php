<?php
/**
 * Blog archive: /blog/, its topic categories and post tags.
 *
 * Hero (H1 + intro) → topic buttons → the latest article on its own (first
 * page: image on the left, summary on the right) → article grid →
 * pagination → call to action.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$ef_heading = ef_blog_heading();
$ef_posts   = $GLOBALS['wp_query']->posts;
$ef_first   = ! is_paged() && count( $ef_posts ) >= 2;
?>
<div id="primary" class="content-area primary ef-blog">
	<main id="main" class="site-main">

		<?php if ( is_home() ) : // the blog index: just the heading, then the topic buttons ?>
		<header class="ef-blog-hero ef-blog-hero--plain">
			<h1 class="ef-blog-hero__title"><?php echo esc_html( $ef_heading['title'] ); ?></h1>
		</header>
		<?php else : ?>
		<header class="ef-blog-hero">
			<p class="ef-blog-hero__eyebrow"><?php echo esc_html( $ef_heading['eyebrow'] ); ?></p>
			<h1 class="ef-blog-hero__title"><?php echo esc_html( $ef_heading['title'] ); ?></h1>
			<?php if ( $ef_heading['intro'] ) : ?>
				<p class="ef-blog-hero__intro"><?php echo esc_html( wp_strip_all_tags( $ef_heading['intro'] ) ); ?></p>
			<?php endif; ?>
		</header>
		<?php endif; ?>

		<?php echo ef_blog_topic_nav(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>

		<?php if ( $ef_posts ) : ?>
			<?php if ( $ef_first ) : ?>
				<section class="ef-blog-feature" aria-label="Latest article">
					<?php echo ef_blog_card( $ef_posts[0], 'feature' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</section>
			<?php endif; ?>

			<?php $ef_rest = $ef_first ? array_slice( $ef_posts, 1 ) : $ef_posts; ?>
			<?php if ( $ef_rest ) : ?>
				<?php if ( $ef_first ) : ?>
					<h2 class="ef-blog-section-title"><?php echo is_home() ? 'More articles' : 'More articles in this category'; ?></h2>
				<?php endif; ?>
				<div class="ef-blog-grid">
					<?php
					foreach ( $ef_rest as $ef_post ) {
						echo ef_blog_card( $ef_post, '', $ef_first ? 'h3' : 'h2' ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
			<?php endif; ?>

			<?php echo ef_blog_pagination(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>
			<p class="ef-blog-empty">There are no articles here yet.</p>
		<?php endif; ?>

		<?php echo ef_blog_cta(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	</main>
</div>
