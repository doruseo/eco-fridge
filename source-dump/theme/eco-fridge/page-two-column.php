<?php
/*
Template Name: Two column
*/
?>

<?php get_header(); ?>

			<div id="content">
			
				<div id="inner-content" class="wrap cf">

					<div id="main" class="cf" role="main">
					
						<div class="col12 cf">

							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	
							<article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">
	
								<header class="article-header">
	
									<h1 class="page-title" itemprop="headline"><?php the_title(); ?></h1>
	
								</header>
	
								<section class="entry-content cf" itemprop="articleBody">
								
									<?php the_content(); ?>
									
								</section>
	
								<footer class="article-footer cf">
	
								</footer>
	
								<?php// comments_template(); ?>
	
							</article>
	
							<?php endwhile; else : ?>
	
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
	
							<?php endif; ?>
						
						</div>
						
						<div class="col6">
						
							<h2>Column 1 of 2</h2>
							
							<p>Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Aenean eu leo quam. Pellentesque ornare sem lacinia quam venenatis vestibulum. Etiam porta sem malesuada magna mollis euismod.</p>
						
						</div>
						
						<div class="col6">
						
							<h2>Column 2 of 2</h2>
							
							<p>Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Aenean eu leo quam. Pellentesque ornare sem lacinia quam venenatis vestibulum. Etiam porta sem malesuada magna mollis euismod.</p>
							
						</div>

					</div>

				</div>

			</div>

<?php get_footer(); ?>
