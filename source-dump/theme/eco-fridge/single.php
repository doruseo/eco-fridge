<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

					<div id="main" class="col12 cf" role="main">
					
						<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
							
							<article id="post-<?php the_ID(); ?>" <?php post_class('cf'); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">
							
								<header class="article-header">
								
									<div class="back-to-index">
									
										<a href="/category/blog/">Back to Blog index</a>	
									
									</div>
								
									<h1 class="entry-title single-title" itemprop="headline"><?php the_title(); ?></h1>
								
								</header> <?php // end article header ?>
								
								<section class="entry-content cf" itemprop="articleBody">
								
									<div class="blog-image-wrapper"><a href="<?php the_permalink() ?>"><?php the_post_thumbnail('thumb-blog'); ?></a></div>
									
									<?php the_content(); ?>
									
								</section> <?php // end article section ?>
								
								<footer class="article-footer">
									
									<?php the_tags( '<p class="tags"><span class="tags-title">' . __( 'Tags:', 'bonestheme' ) . '</span> ', ', ', '</p>' ); ?>
								
								</footer> <?php // end article footer ?>
							
							</article> <?php // end article ?>
						
						<?php endwhile; ?>
						
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

					<?php get_sidebar(); ?>

				</div>

			</div>

<?php get_footer(); ?>
