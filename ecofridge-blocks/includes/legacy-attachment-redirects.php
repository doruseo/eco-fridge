<?php
/**
 * Redirects the original site's /?attachment_id=N pages, whose IDs changed
 * when the media library was re-imported, to where they belong now
 * (includes/legacy-attachment-ids.php). Only requests that would otherwise
 * be a 404 are touched, so a current attachment ID keeps working as usual.
 *
 * @package EcoFridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() || ! isset( $_GET['attachment_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$old = absint( wp_unslash( $_GET['attachment_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $old ) {
			return;
		}
		$map = (array) require EFB_DIR . 'includes/legacy-attachment-ids.php';
		if ( isset( $map[ $old ] ) ) {
			wp_safe_redirect( home_url( $map[ $old ] ), 301 );
			exit;
		}
	},
	4
);
