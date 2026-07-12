<?php get_header(); ?>

			<div id="content">
			
				<div id="inner-content" class="wrap cf">

					<div id="main" class="col12 cf" role="main">
					
						<header class="article-header">
						
							<div class="back-to-index products">

								<a href="/graded-units/">Back to Graded Units index</a>
								
							</div>
						
							<h1 class="entry-title single-title" itemprop="headline"><?php the_title(); ?></h1>
						
						</header> <?php // end article header ?>
						
						<section class="product-wrapper entry-content cf" itemprop="articleBody">
						
							<div class="product-image"><?php the_post_thumbnail('thumb-product'); ?></div>
							
							<div class="product-description-wrapper">
							
								<div class="product-description"><?php the_content(); ?></div>
								
								<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-original-price', TRUE))=='')) { ?>
								
									<p class="original-price">
										<span class="caption">Price</span> <span class="price" style="text-decoration: line-through;"><?php echo(types_render_field( "graded-unit-original-price", array( "raw" => "true" ) )); ?></span>
									</p>
								
								<?php } ?>
								
								<p class="graded-price">
									<span class="caption">
									<?php if ( has_term ( 'refurbished' , 'condition' ) ) { ?>
										
										Graded Price*
										
									<?php } elseif ( has_term ( 'end-of-line' , 'condition' ) ) { ?>
									
										End of Line Price*
										
									<?php } elseif ( has_term ( 'ex-showroom' , 'condition' ) ) { ?>
									
										Ex-Showroom Price*
										
									<?php } else { ?>
									
										Clearance Price*
										
									<?php } ?>
									</span> <span class="price"><?php echo(types_render_field( "graded-unit-price", array( "raw" => "true" ) )); ?></span>
								</p>
								
								<p><strong>Please call 01280 811411 to order this cabinet</strong></p>
								
								<div class="graded-unit-spec-table-wrapper">
								
									<table class="graded-unit-spec-table cf">
									
										<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-model', TRUE))=='')) { ?>
										 
											<tr>
												<td>Model</td>
												<td><?php echo(types_render_field( "graded-unit-model", array( "raw" => "true" ) )); ?></td>
											</tr>
											
										<?php } ?>
										
										<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-damage', TRUE))=='')) { ?>
										 
											<tr>
												<td>Damage</td>
												<td><?php echo(types_render_field( "graded-unit-damage", array( "raw" => "true" ) )); ?></td>
											</tr>
											
										<?php } ?>
										
										<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-dimensions', TRUE))=='')) { ?>
										 
											<tr>
												<td>Dimensions</td>
												<td><?php echo(types_render_field( "graded-unit-dimensions", array( "raw" => "true" ) )); ?></td>
											</tr>
											
										<?php } ?>
										
										<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-shelves', TRUE))=='')) { ?>
										 
											<tr>
												<td>Shelves</td>
												<td><?php echo(types_render_field( "graded-unit-shelves", array( "raw" => "true" ) )); ?></td>
											</tr>
											
										<?php } ?>
										
										<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-temperature', TRUE))=='')) { ?>
										 
											<tr>
												<td>Temperature</td>
												<td><?php echo(types_render_field( "graded-unit-temperature", array( "raw" => "true" ) )); ?></td>
											</tr>
											
										<?php } ?>
										
										<?php if (!((get_post_meta($post->ID, 'wpcf-graded-unit-weight', TRUE))=='')) { ?>
										 
											<tr>
												<td>Weight</td>
												<td><?php echo(types_render_field( "graded-unit-weight", array( "raw" => "true" ) )); ?></td>
											</tr>
											
										<?php } ?>
										
									</table>
								
								</div>
								
								<p class="notes">
									
									<?php if ( has_term ( 'refurbished' , 'condition' ) ) { ?>
										
										* Graded Price
										
									<?php } elseif ( has_term ( 'end-of-line' , 'condition' ) ) { ?>
									
										* End of Line Price
										
									<?php } elseif ( has_term ( 'ex-showroom' , 'condition' ) ) { ?>
									
										* Ex-Showroom Price
										
									<?php } else { ?>
									
										* Price
										
									<?php } ?>
									 = Delivered, Unpacked, Installed, 1 Weeks Parts & Labour Warranty. Some units have castors fitted, please ask for details. Deliveries above York, Leeds, Blackpool &amp; outside UK mainland will encur an extra charge.
								</p>
							
							</div>
							
						</section> <?php // end article section ?>

					</div>

				</div>

			</div>

<?php get_footer(); ?>
