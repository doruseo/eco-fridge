<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

						<div id="main" class="col12 cf" role="main">

							<h1 class="archive-title"><span><?php _e( '', 'bonestheme' ); ?></span> <?php single_cat_title(); ?></h1>
							
							<div class="tax-description">
								<?php $taxonomy = get_queried_object();
									echo  $taxonomy->description; 
								?>
							</div>
							
							<ul class="postsIndex products">

								<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
								
									<li>
												 
										 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
							
											<section class="entry-content">
											
												<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
												
												<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
												
												<?php if ( ((get_post_meta($post->ID, 'wpcf-from-price', TRUE))=='£POA') ) { ?>
													<div class="from-price"><?php echo (types_render_field("from-price", array( ))); ?></div>
												<?php } else { ?>
													<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
												<?php } ?>
							
											</section>
							
											<footer class="article-footer">
							
											</footer>
							
										</article>
									
									</li>
	
								<?php endwhile; ?>
							
							</ul>
							
							<?php wp_reset_postdata(); ?>
							
							<div><?php echo(types_render_termmeta( "range-content-2", array( "output" => "html" ) )); ?></div>

									<?php bones_page_navi(); ?>

							<?php else : ?>
							
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

						<?php// get_sidebar(); ?>

				</div>

			</div>

<?php get_footer(); ?>
