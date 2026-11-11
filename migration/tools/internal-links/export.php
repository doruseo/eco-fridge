<?php
$out = array( 'posts' => array(), 'terms' => array(), 'pages' => array() );
foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $p ) {
	$out['posts'][] = array( 'id' => $p->ID, 'title' => $p->post_title, 'slug' => $p->post_name, 'url' => get_permalink( $p ), 'date' => $p->post_date, 'content' => $p->post_content, 'cats' => wp_get_post_categories( $p->ID, array( 'fields' => 'names' ) ), 'focus' => get_post_meta( $p->ID, 'rank_math_focus_keyword', true ) );
}
foreach ( array( 'product_cat', 'market', 'product_brand' ) as $tax ) {
	if ( ! taxonomy_exists( $tax ) ) continue;
	foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) ) as $t ) {
		$out['terms'][] = array( 'tax' => $tax, 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'parent' => $t->parent, 'count' => $t->count, 'url' => get_term_link( $t ) );
	}
}
foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $p ) {
	$out['pages'][] = array( 'id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink( $p ) );
}
echo wp_json_encode( $out );
