<?php if ( is_front_page () ) { ?>
	
	<h2>Display Cabinets</h2>
	
	<ul class="postsIndex products cf">
	
		<?php 
			$term_id = 2;
			$taxonomy_name = 'range';
			//Set parent term.  
			$terms = get_terms( $taxonomy_name, array( 'parent' => $term_id, 'orderby' => 'slug', 'hide_empty' => false ) );
			foreach ( $terms as $term ) {
			$meta_image = get_wp_term_image($term->term_id);
				echo '<li><a href="' . get_term_link( $term ) . '"><img src="' . $meta_image . '"></a>';
			    echo '<h2><a href="' . get_term_link( $term ) . '">' . $term->name . '</a></li>';   
			}
		?>
	
		<!--<?php
			$term_id = 2;
			$taxonomy_name = 'range';
			$term_children = get_term_children( $term_id, $taxonomy_name );
			$terms = get_terms( array( 'taxonomy' => 'range', 'orderby' => 'slug' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$count = count( $terms );
				$i = 0;
			?>
			<?php foreach ( $term_children as $child ) {
					$term = get_term_by( 'id', $child, $taxonomy_name ) ; ?>
				<li>
					<?php 
						if (function_exists('get_wp_term_image')) {
							$meta_image = get_wp_term_image($term->term_id);
							echo '<a href="' . get_term_link( $child, $taxonomy_name ) . '"><img src="' . $meta_image . '"></a>';
					} ?>
					<h2><a href="<?php echo get_term_link($term); ?>">
						<?php echo $term->name; ?>
					</a></h2>
				</li>
			<?php } ?>
		<?php } ?>-->
	
	</ul>
		
	<h2>Storage Cabinets</h2>
	
	<ul class="postsIndex products cf">
	
		<?php 
			$term_id = 3;
			$taxonomy_name = 'range';
			//Set parent term.  
			$terms = get_terms( $taxonomy_name, array( 'parent' => $term_id, 'orderby' => 'slug', 'hide_empty' => false ) );
			foreach ( $terms as $term ) {
			$meta_image = get_wp_term_image($term->term_id);
				echo '<li><a href="' . get_term_link( $term ) . '"><img src="' . $meta_image . '"></a>';
			    echo '<h2><a href="' . get_term_link( $term ) . '">' . $term->name . '</a></li>';   
			}
		?>
	
		<!--<?php
			$args = array( 'hide_empty' => true );
			$term_id = 3;
			$taxonomy_name = 'range';
			$term_children = get_term_children( $term_id, $taxonomy_name );
			$terms = get_terms( 'range', $args );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$count = count( $terms );
				$i = 0;
			?>
			<?php foreach ( $term_children as $child ) {
					$term = get_term_by( 'id', $child, $taxonomy_name ) ; ?>
				<li>
					<?php 
						if (function_exists('get_wp_term_image')) {
							$meta_image = get_wp_term_image($term->term_id);
							echo '<a href="' . get_term_link( $child, $taxonomy_name ) . '"><img src="' . $meta_image . '"></a>';
					} ?>
					<h2><a href="<?php echo get_term_link($term); ?>">
						<?php echo $term->name; ?>
					</a></h2>
				</li>
			<?php } ?>
		<?php } ?>-->
	
	</ul>
		
	<h2>Graded Units</h2>
	
	<ul class="postsIndex products cf">
		<li>
			<a href="/graded-units/"><img src="/wp-content/uploads/Graded-Units-72dpi.jpg"></a>
			<h2><a href="/graded-units/">Refurbished &amp; End of Line Cabinets</a></h2>
		</li>
	</ul>
	
	
<?php } elseif ( is_page ('coffee-shops')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'coffee-shops', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
				 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } elseif ( is_page ('sports-clubs')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'sports-clubs', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } elseif ( is_page ('display-freezers')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'display-freezers', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } elseif ( is_page ('restaurants')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'restaurants', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } elseif ( is_page ('airports')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'airports', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } elseif ( is_page ('hotels')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'hotels', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>
	
<?php } elseif ( is_page ('forecourts')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'forecourts', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } elseif ( is_page ('convenience-stores')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'convenience-stores', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>
	
<?php } elseif ( is_page ('delis')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'delis', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>
		
<?php } elseif ( is_page ('delis-and-butchers')) { ?>
	
	<ul class="postsIndex products cf">
	
		<?php $loop = new WP_Query( array( 'post_type' => 'product', 'market' => 'delis-and-butchers', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
		
		<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
		
			<li>
						 
				 <article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article">
	
					<section class="entry-content">
					
						<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('thumb-product-index'); ?></a>
						
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						
						<div class="from-price">From <?php echo (types_render_field("from-price", array( ))); ?></div>
	
					</section>
	
					<footer class="article-footer">
	
					</footer>
	
				</article>
			
			</li>
			
		<?php endwhile; ?>
	
	</ul>
	
	<?php wp_reset_postdata(); ?>
	
	<div><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>

<?php } ?>