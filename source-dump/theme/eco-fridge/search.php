<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

					<div id="main" class="col8 cf" role="main">
					
						<header>
						
							<h1 class="archive-title"><span><?php _e( 'Search Results for:', 'bonestheme' ); ?></span> <?php echo esc_attr(get_search_query()); ?></h1>
							
						</header>
												
						<ul class="postsIndex">

						<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
						
							<li>
	
								<article id="post-<?php the_ID(); ?>" <?php post_class('cf'); ?> role="article">
		
									<header class="article-header">
		
										<h3 class="search-title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
		
									</header>
		
									<section class="entry-content">
											<?php the_excerpt( '<span class="read-more">' . __( 'Read more &raquo;', 'bonestheme' ) . '</span>' ); ?>
		
									</section>
		
									<footer class="article-footer">
									</footer> <!-- end article footer -->
		
								</article>
							
							</li>

						<?php endwhile; ?>
						
						</ul>

								<?php bones_page_navi(); ?>

							<?php else : ?>

									<article id="post-not-found" class="hentry cf">
										<header class="article-header">
											<h2><?php _e( 'Sorry, No Results.', 'bonestheme' ); ?></h2>
										</header>
										<section class="entry-content">
											<p><?php _e( 'Try your search again.', 'bonestheme' ); ?></p>
										</section>
									</article><?php get_search_form(); ?>

							<?php endif; ?>

						</div>

							<?php get_sidebar(); ?>

					</div>

			</div>

<?php get_footer(); ?>
