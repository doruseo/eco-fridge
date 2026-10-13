<?php
/**
 * Blog posts page (/blog/) – see template-parts/blog-archive.php.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
get_template_part( 'template-parts/blog-archive' );
get_footer();
