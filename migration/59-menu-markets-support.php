<?php
/**
 * 59 — Header menus.
 *
 *  - Landing Pages Menu (row 1): Markets and Support move out from under
 *    Contact Us to the top level. New order:
 *    Home | Shop | Markets (4 market pages) | Blog | About Us | Support | Contact Us.
 *  - Commercial Menu (row 2): "Commercial Fridge" → "Commercial Fridges",
 *    "Commercial Freezer" → "Commercial Freezers".
 *
 * Items are found by their label, so the migration also works on a copy of the
 * database with other IDs. Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

/**
 * A menu's items, keyed by label.
 *
 * @param string $name Menu name.
 * @return WP_Post[]
 */
function efm59_items( $name ) {
	$menu  = wp_get_nav_menu_object( $name );
	$items = $menu ? wp_get_nav_menu_items( $menu->term_id ) : array();
	$out   = array();
	foreach ( (array) $items as $it ) {
		$out[ html_entity_decode( $it->title ) ] = $it;
	}
	return $out;
}

// Row 1: top-level order; Markets keeps its children right after it.
$landing = efm59_items( 'Landing Pages Menu' );
$order   = array( 'Home', 'Shop', 'Markets', 'Blog', 'About Us', 'Support', 'Contact Us' );
$pos     = 1;
foreach ( $order as $label ) {
	if ( empty( $landing[ $label ] ) ) {
		echo "MISSING: $label\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		continue;
	}
	$it = $landing[ $label ];
	update_post_meta( $it->ID, '_menu_item_menu_item_parent', '0' );
	wp_update_post( array( 'ID' => $it->ID, 'menu_order' => $pos++ ) );
	echo $pos - 1, ' ', $label, "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( 'Markets' === $label ) {
		foreach ( $landing as $child ) {
			if ( (int) $child->menu_item_parent === (int) $it->ID ) {
				wp_update_post( array( 'ID' => $child->ID, 'menu_order' => $pos++ ) );
				echo '   ', $pos - 1, ' ', html_entity_decode( $child->title ), "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}
}

// Row 2: plural labels.
$commercial = efm59_items( 'Commercial Menu' );
foreach ( array( 'Commercial Fridge' => 'Commercial Fridges', 'Commercial Freezer' => 'Commercial Freezers' ) as $old => $new ) {
	if ( ! empty( $commercial[ $old ] ) ) {
		wp_update_post( array( 'ID' => $commercial[ $old ]->ID, 'post_title' => $new ) );
		echo "$old -> $new\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( ! empty( $commercial[ $new ] ) ) {
		echo "$new already set\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
