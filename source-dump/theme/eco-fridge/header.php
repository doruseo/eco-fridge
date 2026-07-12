<!doctype html>

<!--[if lt IE 7]><html <?php language_attributes(); ?> class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if (IE 7)&!(IEMobile)]><html <?php language_attributes(); ?> class="no-js lt-ie9 lt-ie8"><![endif]-->
<!--[if (IE 8)&!(IEMobile)]><html <?php language_attributes(); ?> class="no-js lt-ie9"><![endif]-->
<!--[if gt IE 8]><!--> <html <?php language_attributes(); ?> class="no-js"><!--<![endif]-->

	<head>
		<meta charset="utf-8">

		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-NFMC9Z3');</script>
<!-- End Google Tag Manager -->->

		<title><?php wp_title('|', true, 'right'); ?></title>

		<meta name="HandheldFriendly" content="True">
		<meta name="MobileOptimized" content="320">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

		<link rel="apple-touch-icon" href="<?php echo get_template_directory_uri(); ?>/library/images/apple-icon-touch.png">
		<link rel="icon" href="<?php echo get_template_directory_uri(); ?>/favicon.png">
		<!--[if IE]>
			<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/favicon.ico">
		<![endif]-->
		<meta name="msapplication-TileColor" content="#f01d4f">
		<meta name="msapplication-TileImage" content="<?php echo get_template_directory_uri(); ?>/library/images/win8-tile-icon.png">
		
		<meta name="msvalidate.01" content="755F2F1CE69A56DFAD96A1D9C0595F02" />

		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
		
		<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/library/js/respond.min.js"></script>
		
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
		
		<link rel="stylesheet" id="font-awesome-stylesheet-css" href="<?php echo get_template_directory_uri(); ?>/library/fonts/font-awesome-4.6-2.3/css/font-awesome.min.css" type="text/css" media="all">
		

		<?php wp_head(); ?>

	</head>

	<body <?php body_class(); ?>>
	
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NFMC9Z3"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

		<div id="container">

			<header class="header" role="banner">

				<div id="inner-header" class="wrap cf">
				
					<!--<div class="contacts contacts-visible">
						<span class="phone">Contact Us On 01280 811411</span></span>
					</div>-->

					<div class="logoWrapper">
					
					<a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><img src="<?php bloginfo('template_url'); ?>/library/images/eco-fridge-logo.png" class="logo" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"></a>
					
					<a href="#search-panel" class="btn-search-panel">SEARCH</a>
					
					<a href="#menu-navmain" class="btn-menu"><span>≡</span> MENU</a>
					
					</div>

					<nav role="navigation">
					
						<?php wp_nav_menu(array(
    					'container_class' => 'nav-main-container cf',
    					'menu_class' => 'nav top-nav cf',
    					'theme_location' => 'nav-main',
    					)); ?>

					</nav>
					
					<div id="search-panel" class="cf">
						
						<form role="search" method="get" id="searchform" action="<?php echo home_url( '/' ); ?>">
						<input name="s" id="s" type="text" value="Enter keyword" onfocus="if (this.value == 'Enter keyword') {this.value = '';}" onblur="if (this.value == '') {this.value = 'Enter keyword';}" class="searchField">
					    <input name="Submit" type="submit" value="Go" align="top" class="btn-go">
					    </form>
					</div>
					
					<div id="search-box">
						<form role="search" method="get" id="searchform" action="<?php echo home_url( '/' ); ?>">
						    <div>
						    	<input type="text" value="" name="s" id="s" class="keyword" />
						        <input type="submit" id="searchsubmit" value="Search" class="submit" />
						    </div>
						</form>
					</div>
					
				</div>

			</header>
			
			<?php if ( is_front_page() ) { ?>
			
				<div class="home-slider">
																					
					<div class="home-slider-wrapper"><?php echo do_shortcode( '[sliderpro id="1"]' ); ?>
					
				</div>
					
			<?php } else { ?>
			
				<div class="banner">
				
					<div class="inner-banner wrap cf">
					
						Call us today -&nbsp;  <div class="phone"><a href="tel:01280811411">01280 811411</a></div>
						
						<img src="<?php bloginfo('template_url'); ?>/library/images/products.png">
					
					</div>
				
				</div>
				
			<?php } ?>