<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

					<div id="main" class="col8 cf" role="main">

						<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

						<article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">

							<header class="article-header">

								<h1 class="page-title" itemprop="headline"><?php the_title(); ?></h1>

							</header>

							<section class="entry-content cf" itemprop="articleBody">
							
								<?php the_content(); ?>
								
								<ul class="postsIndex">	
								
									<?php $loop = new WP_Query( array( 'post_type' => 'testimonial', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'ASC' ) ); ?>
									
									<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
									
										<li>
										
										<a target="_blank" href="<?php echo(types_render_field( "pdf", array( "raw" => "true" ) )); ?> "><?php the_post_thumbnail('thumb-event-index', array('class' => 'eventImage')); ?></a>
																
										<h2><?php the_title(); ?></h2>
										
										<p class="date"><?php echo(types_render_field( "testimonial-quote", array( "raw" => "true" ) )); ?></p>
										
										<?php the_content(); ?>
										
										<p class="date"><?php echo(types_render_field( "testimonial-cite", array( "raw" => "true" ) )); ?></p>
										
										</li>
															
									<?php endwhile; ?>
								
								</ul><!-- listLinks -->
								
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

					<?php get_sidebar(); ?>

				</div>

			</div>

<?php get_footer(); ?>
