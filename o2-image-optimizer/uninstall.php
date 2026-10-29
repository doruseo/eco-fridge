<?php
/**
 * Uninstall: remove settings, records and the .htaccess block; the converted
 * copies only when "Delete converted files" is on. Originals are never touched.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

$o2io_settings = (array) get_option( 'o2io_settings', array() );

if ( ! empty( $o2io_settings['delete_on_uninstall'] ) ) {
	$o2io_ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_o2io' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	foreach ( $o2io_ids as $o2io_id ) {
		$o2io_main = get_attached_file( $o2io_id );
		if ( ! $o2io_main ) { continue; }
		$o2io_files = array( $o2io_main );
		$o2io_meta  = wp_get_attachment_metadata( $o2io_id );
		foreach ( (array) ( $o2io_meta['sizes'] ?? array() ) as $o2io_size ) {
			if ( ! empty( $o2io_size['file'] ) ) { $o2io_files[] = dirname( $o2io_main ) . '/' . $o2io_size['file']; }
		}
		foreach ( $o2io_files as $o2io_file ) {
			foreach ( array( 'avif', 'webp' ) as $o2io_f ) {
				if ( file_exists( $o2io_file . '.' . $o2io_f ) ) { wp_delete_file( $o2io_file . '.' . $o2io_f ); }
			}
		}
	}
}

delete_post_meta_by_key( '_o2io' );
foreach ( array( 'o2io_settings', 'o2io_queue', 'o2io_log' ) as $o2io_opt ) {
	delete_option( $o2io_opt );
}

if ( ! function_exists( 'insert_with_markers' ) ) {
	require_once ABSPATH . 'wp-admin/includes/misc.php';
}
$o2io_htaccess = trailingslashit( wp_get_upload_dir()['basedir'] ) . '.htaccess';
if ( file_exists( $o2io_htaccess ) && is_writable( $o2io_htaccess ) ) {
	insert_with_markers( $o2io_htaccess, 'O2 Image Optimizer', array() );
}
