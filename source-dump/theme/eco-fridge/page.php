<?php get_header(); ?>

			<div id="content">
			
				<div id="inner-content" class="wrap cf">

					<div id="main" class="col12 cf" role="main">

						<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

						<article id="post-<?php the_ID(); ?>" <?php post_class( 'cf' ); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">

							<header class="article-header">

								<h1 class="page-title" itemprop="headline"><?php the_title(); ?></h1>

							</header>

							<section class="entry-content cf" itemprop="articleBody">															
								
								<?php if ( is_page ( array ( 'contact-us' ) ) ) { ?>
									
									<div class="content-1">
										<?php the_content(); ?>
									</div>
								
									<div class="content-2"><?php echo(types_render_field( "content-2", array( "output" => "html" ) )); ?></div>
								
								
								<?php } elseif ( is_front_page () ) { ?>
								
									<?php the_content(); ?>
									
									<p class="mobile-only" style="margin: 0;text-align: center;"><strong>Some of our clients</strong></p>
									<p class="mobile-only" style="padding-top: 0.15em;"><?php echo do_shortcode("[show-logos orderby='none' category='0' activeurl='new' style='normal' interface='hcarousel' tooltip='false' description='false' limit='0' filter='false' carousel='ticker,4000,true,false,35000,25,true,false,true,1,0,1' class='mobile-only']"); ?> </p>
								
								<?php } else { ?>
								
									<?php the_content(); ?>
								
								<?php } ?>
								
								<?php if ( is_page( 'support' ) ) { ?>
								
									<div class="brochures-wrapper">
									
										<h2>ECO-Fridge Brochures</h2>
											
											<p>Click the links below to download our brochures in PDF format&hellip;</p>
										
											<ul class="brochures-index cf">
											
											<?php $loop = new WP_Query( array( 'post_type' => 'brochure', 'posts_per_page' => 100, 'order' => 'ASC' ) ); ?>
											
											<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
											
												<li>
													 
													 <a href="<?php echo(types_render_field( "brochure-link", array( "raw" => "true" ) )); ?>" target="_blank" class="brochure-thumbnail"><?php the_post_thumbnail('thumb-brochure'); ?></a>
													 
													 <a href="<?php echo(types_render_field( "brochure-link", array( "raw" => "true" ) )); ?>" target="_blank" class="brochure-title"><h3><?php the_title(); ?></h3></a>
												
												</li>
												
											<?php endwhile; ?>
											
											</ul>
									
									</div>
									
									<?php wp_reset_postdata(); ?>
								
									<h2>ECO-Fridge Videos</h2>
								
									<ul class="videos-index">
									
									<?php $loop = new WP_Query( array( 'post_type' => 'video', 'posts_per_page' => 21, 'order' => 'ASC' ) ); ?>
									
									<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
									
										<li class="cf"> 
										
											<div class="video-wrapper"><div class="embed-container"><iframe src="https://www.youtube.com/embed/<?php echo types_render_field( "youtube-id-number", array() ); ?>" frameborder="0" allowfullscreen></iframe></div></div>
									
											 <h3><?php the_title(); ?></h3>
											 
											 <?php the_content(); ?>
										
										</li>
										
									<?php endwhile; ?>
									
									</ul>
									
								<?php } elseif ( is_page( 'graded-units' ) ) { ?>
								
									<?php get_template_part('graded-units'); ?>
									
								<?php } ?>
								
								<?php wp_reset_postdata(); ?>
								
								<?php get_template_part('markets'); ?>
								
								<?php wp_reset_postdata(); ?>
									
								<?php if ( is_front_page () ) { ?>
								
								<p>Whatever your business needs, ECO-Fridge has the commercial refrigeration solution for you; our range includes patisseries, upright displays, freezers, multidecks and storage cabinets. The display fridges we supply are built to the highest standards and are perfect for any coffee shop environment, school cafeteria or delis. Our high-performance multideck chillers offer an excellent refrigeration solution for all types catering and retail operations. The Frost-Tech brand offers something for every business application, ensuring top quality, reliability, and energy efficiency.</p>
								<p><strong>Choose the perfect display fridge for your business</strong></p>
								<p>ECO-Fridge commercial display fridges combine robust construction with an attractive display to showcase food and drink produce. Our glass door display fridges are the perfect solution for shops, supermarkets or cafes.</p>
								<p>Coming in a wide range of sizes and styles – from large, triple or double door models to smaller, single door models and even compact countertop units, with hinged or sliding doors and over or under mounted refrigeration systems - it's quick and easy to choose your ideal glass door refrigerated cabinet. Of course, consumers will have access to different sizes, in terms of width, height and depth.</p>
								<p>With so many options available finding the size and capacity to suit your demands is simple. A choice of finishes from black steel to 304 stainless steel, can also be found ensuring that any style of business is complemented and enhanced.</p>
								<p>Having been supplying businesses for over 30 years, we are able to give you professional advice as well as equip you with the most advanced, reliable commercial refrigeration equipment that will help your company grow in sells and function to the best of its ability.</p>
								<p>As our company name suggests, we are committed to supplying market leading commercial refrigeration solutions which are both energy efficient and sustainable – helping your business or company to reduce your costs both financially and to the environment. Our display fridges and multideck chiller units are all manufactured using quality, modern and environmentally sound European components and our refrigerant development programme enables us to offer the market the ‘cleanest’ refrigeration. This also helps to ensure that your commercial fridges and display units spend more time chilling than they do being repaired.</p>
								<p>All of our commercial refrigeration display units are thoroughly tested to the highest standards so you can buy with confidence, knowing that your display fridge is fit for purpose month after month, year after year. As part of our dedicated service, we will deliver, assemble and install your unit, ready to be stocked with your products. Such is our reputation for quality and reliability, we are proud to be the ongoing suppliers of commercial refrigeration units and display fridges to leading names such as Cafe Nero, Costa Coffee, Coffee1, Wenzels and Gail's Bakery.</p>
								<p>At ECO-Fridge ltd UK, we stock the most practical and efficient models of glass door refrigeration on the market, ensuring that your drinks, food and beverages are held at optimum freshness and ready to serve.</p>
								<p>Glass door chiller units are simply perfect for storing and cooling drinks and other produce, attractively presenting what's available whilst maintaining optimum chilled conditions and all within a stylish frame. Call us or place your order online now.</p>
								<p>When stock needs to be kept chilled, a commercial display fridge ensures that it’s not only kept at the perfect serving temperature - it also attractively merchandises to maximise sales. Whether it’s being used in a hotel bar, sandwich shop, cafe or convenience store, there’s always a great display refrigerator option to best merchandise food or drinks.</p>
								<p>ECO-Fridge have a fantastic range of top-quality commercial refrigeration display fridge cabinets to suit a whole host of chilled catering and food service applications. We have many specific categories to choose from:</p>
								<p><strong>Patisserie display fridge</strong> cabinets are ideal for restaurants, dining hall canteens, cake and patisserie counters. Our top range of cake fridges, all have full glass surrounds to view the products from any angle. Our outstanding patisserie display chillers come in any elegant finishes from coated steel to 304 stainless steel so whether you’re planning on using as a sandwich, cake or patisserie display fridge our high-quality multideck chiller perfect match at the lowest cost on the UK’s market to suit.</p>
								<p><strong>Standard multideck display fridge</strong> category is the home of the ubiquitous glass door merchandiser a staple of cafes, shops and convenience stores up and down the country. Multideck fridge units offer a great way to present a wide range of products in perfectly chilled conditions. The size of the cold multideck fridge display you choose is very important. The smaller one has no more then 600mm longer, and the biggest model in our offer has 1800mm length.</p>
									<p><strong>Premium multideck chiller</strong> cabinets have a lot of amazing features like removable bottom plates, auto defrost, glass side panels, premium LED lightning, night blinds, shelf risers, and stainless steel finishes. Generally temperature ranges fall anywhere between -2˚C to 10˚C providing perfect conditions for the majority of refrigerated produces such as dairy, soft drinks, cooked meats, sandwiches, fruits and vegetables.</p>
									<p><strong>Grab and Go fridge</strong> displays are designed to house and display various food or beverage products in an elegant and easily accessible manner, all perfectly cooled and ready for serve. Our multideck fridge units help you to maximise the sales of "grab and go" foods such as sandwiches, wraps or boxed salads. You have so many options to choose and buy from the Standard to the Premium Grab and Go display fridge category, so just click on the links and buy.</p>
									<p><strong>Upright display chiller</strong> is a part of the multideck fridge category with open chiller features. These upright display chiller cabinets have different sizes from the smaller one glass door chiller perfect suitable in coffee shops, hospital cafeterias, convenience stores, supermarkets and schools to the simple and large 2 doors display fridge units perfect for outlets where the demand for chilled drinks is high, such as gyms, sports clubs and swimming pools.</p>
									<p><strong>Back Bar display fridge</strong> cabinets with integrated top mounted light are a great way to attractively display and merchandise your chilled drinks. These glass door chiller units are available in a variety of shapes and sizes to suit your business. From low height single door versions for small pubs to large capacity double doors bottle wells for festivals and sporting arenas, there's plenty of choice and buy.</p>
									<p><strong>Sliding glass freezer</strong> category offer quick and easy access to frozen products like ice cream, meat and other. Our ice cream freezer has an effective waste management solution. This system allows for effortless hygiene regimes as you dispose the excess frost build-up. Our glass lid chest freezer units are the perfect solution for food outlets or stores wanting ultimate storage capacity with the enticing appeal. So, choose your best Sliding Glass Chest Freezer and buy online.</p>
									<p><strong>Storage Freezer</strong> cabinets are ideal for any catering environment where optimum freezing and ample food storage are essential. Our stainless steel freezers are fully functioning in the heat of a busy kitchen, operating up to a maximum temperature of 43ºC. From hotels and restaurants to school and hospital cafeterias, these eco-friendly storage freezers, like the <strong>Upright Gastronorm</strong> freezer, and <strong>Under Counter freezer</strong> are ideal for any commercial kitchen where food safety is of the utmost importance. And if you need an ultra-low temperature storage cabinet, ECO Fridge UK has the perfect solution for you: the <strong>Commercial Extra Low Temperature Chest Freezer</strong>, with available range of sizes from 130 litres to 500 litres capacity, temperatures of -45°C to -86°C, depending on the model you choose.</p>
									<p><strong>Upright Cake Display Fridge</strong> is an elegant stainless steel chiller, with a great system of LED light to illuminate contents and present the stock and 4 castors included. In this way, our high-quality open chillers are matching perfectly with your patisserie or cake shop at the lowest cost on the UK’s market to suit.</p>
									<p><strong>Double glazed counter top chiller</strong> category is offering the best way to entice your customers with cakes, fruits and other patisserie products. Our top-of-the-range stainless steel counter top chiller is ideal for restaurants, cake or coffee shops in need of a stylish chilled display which can be on top of your new or existing counter.</p>
									<p><strong>Storage Chiller</strong> cabinets are our top-of-the-range in stainless steel catering unit, ideal for absolutely any catering environment with a large volume capacity, as well as working excellent under the very hot temperature of a busy kitchen. This type of commercial refrigeration equipment, like the <strong>Upright Gastronorm chiller</strong>, and <strong>Under Counter chiller</strong> is designed to operate to 43ºC ambient, and is suitable for meat.</p>
									<p>Manufactured by Frost-Tech in the perfect conditions and the highest quality components, you are guaranteed quality and performance, just browse our broad selection and contact one of our advisers for free expert, impartial advice.</p>
									<p>We understand that the commercial refrigeration is vital to any catering business. That’s why our vast range of multideck display fridge, multideck chiller and multideck fridge units have been designed to offer a solution for any type of business need.</p>
									<p><a href="https://www.eco-fridge.co.uk/contact-us/">Contact our specialists</a> to obtain the perfect recommendation from commercial display fridge and multideck chiller categories that suit your budget and needs. No other company knows commercial refrigeration like us, so don’t hesitate to contact our sale team on <strong>01280 811411</strong>.</p>
									
								<?php } ?>
								
							</section>

							<footer class="article-footer cf">

							</footer>

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

				</div>

			</div>

<?php get_footer(); ?>
