<?php
/**
 * 67 — EF Image Optimizer → O2 Image Optimizer.
 *
 * The image optimizer became the general plugin "O2 Image Optimizer"
 * (folder o2-image-optimizer, prefix o2io). This carries over what the old
 * plugin stored, so nothing has to be converted again:
 *  - options efio_settings / efio_queue / efio_log → o2io_* (the queue is
 *    left paused if it was running, so it is resumed from the dashboard);
 *  - attachment records: post meta _efio → _o2io;
 *  - the old uploads/.htaccess block "EF Image Optimizer" is removed (the new
 *    plugin writes its own on activation);
 *  - pending background jobs of the old plugin are removed.
 * The converted files themselves (photo.jpg.avif / .webp) keep their names.
 *
 * Run BEFORE activating o2-image-optimizer, with ef-image-optimizer
 * deactivated: wp eval-file migration/67-image-optimizer-to-o2.php
 * Safe to run again (existing o2io options are never overwritten).
 *
 * @package EcoFridgeMigration
 */

global $wpdb;

foreach ( array( 'settings', 'queue', 'log' ) as $name ) {
	$old = get_option( 'efio_' . $name, null );
	if ( null === $old ) {
		continue;
	}
	if ( null !== get_option( 'o2io_' . $name, null ) ) {
		WP_CLI::log( "o2io_$name already exists: kept." );
		continue;
	}
	if ( 'queue' === $name && is_array( $old ) && 'running' === ( $old['status'] ?? '' ) ) {
		$old['status'] = 'paused';
	}
	if ( 'settings' === $name && is_array( $old ) && isset( $old['exclude_classes'] ) ) {
		$old['exclude_classes'] = str_replace( 'no-efio', 'no-o2io', (string) $old['exclude_classes'] );
	}
	update_option( 'o2io_' . $name, $old, false );
	WP_CLI::log( "efio_$name → o2io_$name" );
}

$moved = $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = '_o2io' WHERE meta_key = '_efio'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
WP_CLI::log( "Attachment records moved: $moved" );

if ( ! function_exists( 'insert_with_markers' ) ) {
	require_once ABSPATH . 'wp-admin/includes/misc.php';
}
$htaccess = trailingslashit( wp_get_upload_dir()['basedir'] ) . '.htaccess';
if ( file_exists( $htaccess ) && is_writable( $htaccess ) && false !== strpos( (string) file_get_contents( $htaccess ), 'BEGIN EF Image Optimizer' ) ) {
	insert_with_markers( $htaccess, 'EF Image Optimizer', array() );
	// insert_with_markers() leaves empty BEGIN/END lines: drop them.
	$text = (string) file_get_contents( $htaccess );
	$text = preg_replace( '/\R?# BEGIN EF Image Optimizer.*?# END EF Image Optimizer\R?/s', "\n", $text );
	file_put_contents( $htaccess, $text );
	WP_CLI::log( 'Old uploads/.htaccess block removed.' );
}

wp_clear_scheduled_hook( 'efio_process_batch' );
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'efio_process_batch', array(), 'efio' );
	as_unschedule_all_actions( 'efio_process_one', array(), 'efio' );
}
delete_transient( 'efio_batch_lock' );
foreach ( array( 'settings', 'queue', 'log' ) as $name ) {
	if ( null !== get_option( 'o2io_' . $name, null ) ) {
		delete_option( 'efio_' . $name );
	}
}
WP_CLI::success( 'EF Image Optimizer data moved to O2 Image Optimizer.' );
