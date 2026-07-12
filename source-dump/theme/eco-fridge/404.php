<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

					<div id="main" class="col8" role="main">

						<article id="post-not-found" class="hentry cf">

							<header class="article-header">

								<h1><?php _e( 'Not Found', 'bonestheme' ); ?></h1>

							</header>

							<section class="entry-content">

								<p><?php _e( 'Apologies, but the page you requested could not be found. Perhaps searching will help...', 'bonestheme' ); ?></p>

							</section>

							<section class="search">

									<p><?php get_search_form(); ?></p>

							</section>

						</article>

					</div>

				</div>

			</div>

<?php get_footer(); ?>
