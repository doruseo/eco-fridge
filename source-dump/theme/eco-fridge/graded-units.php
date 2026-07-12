<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'range' => 'graded-units', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
				 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
	
					</section>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>

	</ul>

<!--<h2>Refurbished Units</h2>

<?php if ( get_term ('28')->count<1 ) { ?>
	
	<p class="no-stock">No refurbished units currently in stock. Please check back soon.</p>
	
<?php } else { ?>

	<p>Used cabinets with some scratches and/or dents:</p>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'condition' => 'refurbished', 'posts_per_page' => 21, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
				 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
	
					</section>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>

	</ul>
	
<?php } ?>

<h2>Discount Products</h2>

<?php if ( get_term ('29')->count<1 ) { ?>
	
	<p class="no-stock">No discount products currently in stock. Please check back soon.</p>
	
<?php } else { ?>

	<p style="font-size: 0.9em;">These cabinets are new with no damage:</p>
	
	
	
<?php } ?>-->

<!--<h2>Ex-Showroom Units</h2>

<?php if ( get_term ('31')->count<1 ) { ?>
	
	<p class="no-stock">No ex-showroom units currently in stock. Please check back soon.</p>
	
<?php } else { ?>

	<p>Like new ex-showroom condition with no or very slight damage:</p>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'graded', 'condition' => 'ex-showroom', 'posts_per_page' => 21, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
				 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
	
					</section>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>

	</ul>
	
<?php } ?>-->