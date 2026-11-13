<?php
// Apply the internal-link changes; skip any post edited since the export.
global $wpdb;
$changes = json_decode( file_get_contents( '/tmp/ef-il/changes.json' ), true );
$done = 0; $skipped = array();
foreach ( $changes as $id => $c ) {
	$id  = (int) $id;
	$old = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'post'", $id ) );
	if ( null === $old || md5( $old ) !== $c['old_md5'] ) { $skipped[] = $id; continue; }
	if ( ! metadata_exists( 'post', $id, '_ef_content_before_internal_links' ) ) {
		add_post_meta( $id, '_ef_content_before_internal_links', wp_slash( $old ), true );
	}
	$wpdb->update( $wpdb->posts, array( 'post_content' => $c['new'] ), array( 'ID' => $id ) );
	clean_post_cache( $id );
	$done++;
}
echo "updated $done, skipped " . count( $skipped ) . ' ' . implode( ',', $skipped ) . "\n";
