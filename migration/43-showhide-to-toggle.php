<?php
/**
 * 43 — Legacy [showhide] shortcode → [efb_toggle].
 *
 * Two product short descriptions still used the old "Show/Hide" plugin's
 * shortcode (with typographic quotes), which no longer exists, so the raw
 * "[showhide type=…]" text showed on the page. It becomes the plugin's own
 * collapsible toggle, titled "Read more". Idempotent.
 *
 * @package EcoFridgeMigration
 */

$ids = get_posts(
	array(
		'post_type'   => 'product',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
		's'           => 'showhide',
	)
);
$done = 0;
foreach ( $ids as $id ) {
	$post = get_post( $id );
	foreach ( array( 'post_excerpt', 'post_content' ) as $field ) {
		$text = $post->$field;
		if ( false === strpos( $text, '[showhide' ) ) {
			continue;
		}
		$text = preg_replace( '~(?:<p>\s*)?\[showhide\b[^\]]*\](?:\s*</p>)?~i', '[efb_toggle title="Read more"]', $text );
		$text = preg_replace( '~(?:<p>\s*)?\[/showhide\](?:\s*</p>)?~i', '[/efb_toggle]', $text );
		wp_update_post( array( 'ID' => $id, $field => wp_slash( $text ) ) );
		$done++;
	}
}
WP_CLI::success( "Converted [showhide] in $done fields." );
