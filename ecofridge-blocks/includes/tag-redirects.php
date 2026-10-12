<?php
/**
 * Legacy product tag URLs → their category (301).
 *
 * The original site's product tags were synonyms of the product categories
 * ("multideck-fridge-tag", "multideck-chiller-tag", "multideck-tag"…), so each
 * tag page competed with the category for the same search. The tags were
 * replaced by intent tags that no category covers (migration 42); the old tag
 * URLs keep working and pass their authority to the matching category.
 *
 * @package EcoFridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Old product tag slug → product category slug.
 *
 * @return array<string,string>
 */
function efb_legacy_tag_map() {
	return array(
		'back-bar-fridge-tag'            => 'back-bar-display-chillers',
		'bar-fridge-tag'                 => 'bar-fridge',
		'beer-chiller-tag'               => 'beer-fridge',
		'beer-cooler-tag'                => 'beer-fridge',
		'beer-fridge-tag'                => 'beer-fridge',
		'bottle-chiller-tag'             => 'commercial-drinks-fridge',
		'bottle-cooler'                  => 'commercial-drinks-fridge',
		'bottle-fridge-tag'              => 'commercial-drinks-fridge',
		'cake-display-fridge-tag'        => 'patisserie-fridge',
		'cake-display-heater-tag'        => 'heated-display-cabinets',
		'cake-fridge'                    => 'patisserie-fridge',
		'cake-heater-tag'                => 'heated-display-cabinets',
		'chest-display-freezer-tag'      => 'chest-freezer',
		'commercial-display-fridge-tag'  => 'commercial-display-fridge',
		'commercial-drinks-fridge-tag'   => 'commercial-drinks-fridge',
		'commercial-fridge-tag'          => 'commercial-fridge',
		'commercial-refrigeration-tag'   => 'commercial-fridge',
		'counter-chiller-tag'            => 'stainless-steel-counter-chillers',
		'counter-display-fridge-tag'     => 'counter-fridge',
		'counter-fridge-tag'             => 'counter-fridge',
		'counter-heater'                 => 'heated-display-cabinets',
		'display-freezer-tag'            => 'display-freezer',
		'display-fridge-tag'             => 'commercial-display-fridge',
		'display-heater-tag'             => 'heated-display-cabinets',
		'drinks-fridge-tag'              => 'commercial-drinks-fridge',
		'gastronorm-chiller-tag'         => 'gastronorm-fridge',
		'gastronorm-freezer-tag'         => 'gastronorm-freezer',
		'gastronorm-fridge-tag'          => 'gastronorm-fridge',
		'glass-door-fridge-tag'          => 'upright-fridge',
		'glass-front-fridge-tag'         => 'glass-front-fridge',
		'grab-and-go-display-fridge-tag' => 'grab-and-go',
		'grab-and-go-fridge-tag'         => 'grab-and-go',
		'grab-and-go-tag'                => 'grab-and-go',
		'heated-display-tag'             => 'heated-display-cabinets',
		'meat-chiller-tag'               => 'gastronorm-fridge',
		'meat-freezer-tag'               => 'gastronorm-freezer',
		'meat-fridge-tag'                => 'gastronorm-fridge',
		'meat-storage-chiller-tag'       => 'gastronorm-fridge',
		'meat-storage-freezer-tag'       => 'gastronorm-freezer',
		'meat-storage-fridge-tag'        => 'gastronorm-fridge',
		'multideck-chiller-tag'          => 'multideck-fridge',
		'multideck-display-fridge-tag'   => 'multideck-fridge',
		'multideck-fridge-tag'           => 'multideck-fridge',
		'multideck-tag'                  => 'multideck-fridge',
		'patisserie-display-fridge-tag'  => 'patisserie-fridge',
		'patisserie-display-tag'         => 'patisserie-fridge',
		'patisserie-fridge-tag'          => 'patisserie-fridge',
		'patisserie-heater-tag'          => 'heated-display-cabinets',
		'serve-over-cabinet-tag'         => 'serve-over',
		'serve-over-display-fridge-tag'  => 'serve-over',
		'serve-over-display-tag'         => 'serve-over',
		'serve-over-fridge-tag'          => 'serve-over',
		'serve-over-tag'                 => 'serve-over',
		'sliding-door-freezer-tag'       => 'chest-freezer',
		'storage-chiller-tag'            => 'storage-fridge',
		'storage-freezer-tag'            => 'storage-freezer',
		'storage-fridge-tag'             => 'storage-fridge',
		'top-counter-display-tag'        => 'top-counter',
		'top-counter-tag'                => 'top-counter',
		'under-counter-chiller-tag'      => 'under-counter-fridge',
		'under-counter-freezer-tag'      => 'under-counter-freezer',
		'under-counter-fridge-tag'       => 'under-counter-fridge',
		'upright-cake-display-tag'       => 'upright-cake-displays',
		'upright-cake-fridge-tag'        => 'upright-cake-displays',
		'upright-chiller-tag'            => 'upright-fridge',
		'upright-display-chiller-tag'    => 'upright-fridge',
		'upright-display-freezer'        => 'upright-freezer',
		'upright-display-fridge-tag'     => 'upright-fridge',
		'upright-freezer'                => 'upright-freezer',
		'upright-fridge-tag'             => 'upright-fridge',
		'upright-heater-tag'             => 'heated-display-cabinets',
		'upright-meat-freezer-tag'       => 'gastronorm-freezer',
		'wine-cooler-tag'                => 'wine-fridge',
		'wine-fridge-tag'                => 'wine-fridge',
	);
}

add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() ) {
			return; // A live tag with this slug wins.
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( ! preg_match( '~^/product-tag/([^/]+)/?(?:page/\d+/?)?$~', $path, $m ) ) {
			return;
		}
		$map = efb_legacy_tag_map();
		if ( ! isset( $map[ $m[1] ] ) ) {
			return;
		}
		$term = get_term_by( 'slug', $map[ $m[1] ], 'product_cat' );
		$link = $term ? get_term_link( $term ) : '';
		if ( $link && ! is_wp_error( $link ) ) {
			wp_safe_redirect( $link, 301 );
			exit;
		}
	},
	4
);
