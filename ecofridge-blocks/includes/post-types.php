<?php
/**
 * Brochure and Video post types (same URLs as the original site:
 * /brochure/, /brochure/<slug>/, /video/, /video/<slug>/) plus the form entries
 * post type used by the contact form block.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the post types and their meta.
 */
function efb_register_post_types() {
	register_post_type(
		'brochure',
		array(
			'labels'        => array(
				'name'          => __( 'Brochures', 'ecofridge-blocks' ),
				'singular_name' => __( 'Brochure', 'ecofridge-blocks' ),
				'add_new_item'  => __( 'Add New Brochure', 'ecofridge-blocks' ),
				'edit_item'     => __( 'Edit Brochure', 'ecofridge-blocks' ),
			),
			'public'        => true,
			'has_archive'   => 'brochure',
			'rewrite'       => array(
				'slug'       => 'brochure',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-media-document',
			'menu_position' => 21,
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
		)
	);

	register_post_type(
		'video',
		array(
			'labels'        => array(
				'name'          => __( 'Videos', 'ecofridge-blocks' ),
				'singular_name' => __( 'Video', 'ecofridge-blocks' ),
				'add_new_item'  => __( 'Add New Video', 'ecofridge-blocks' ),
				'edit_item'     => __( 'Edit Video', 'ecofridge-blocks' ),
			),
			'public'        => true,
			'has_archive'   => 'video',
			'rewrite'       => array(
				'slug'       => 'video',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-video-alt3',
			'menu_position' => 22,
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
		)
	);

	register_post_type(
		'efb_entry',
		array(
			'labels'          => array(
				'name'          => __( 'Form Entries', 'ecofridge-blocks' ),
				'singular_name' => __( 'Form Entry', 'ecofridge-blocks' ),
				'edit_item'     => __( 'View Entry', 'ecofridge-blocks' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'supports'        => array( 'title' ),
		)
	);

	$auth = static function () {
		return current_user_can( 'edit_posts' );
	};
	register_post_meta(
		'brochure',
		'_efb_file_url',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $auth,
		)
	);
	register_post_meta(
		'video',
		'_efb_youtube_id',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $auth,
		)
	);
}
add_action( 'init', 'efb_register_post_types' );

/**
 * Editor sidebar panel for the brochure file / YouTube ID.
 */
add_action(
	'enqueue_block_editor_assets',
	static function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, array( 'brochure', 'video' ), true ) ) {
			return;
		}
		wp_enqueue_script(
			'efb-media-meta',
			EFB_URL . 'assets/media-meta-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-block-editor' ),
			EFB_VERSION,
			true
		);
	}
);

/**
 * Extract a YouTube ID from either a bare ID or any YouTube URL.
 *
 * @param string $value ID or URL.
 * @return string
 */
function efb_youtube_id( $value ) {
	$value = trim( (string) $value );
	if ( preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $value, $m ) ) {
		return $m[1];
	}
	return preg_match( '~^[A-Za-z0-9_-]{11}$~', $value ) ? $value : '';
}

/**
 * Render the brochure download / video player on the single views.
 */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( array( 'brochure', 'video' ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		if ( 'video' === get_post_type( $post_id ) ) {
			$id = efb_youtube_id( get_post_meta( $post_id, '_efb_youtube_id', true ) );
			if ( $id ) {
				$player  = '<div class="efb-video-embed"><iframe src="' . esc_url( 'https://www.youtube-nocookie.com/embed/' . $id ) . '" title="' . esc_attr( get_the_title( $post_id ) ) . '" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
				$content = $player . $content;
			}
			return $content;
		}
		$file  = get_post_meta( $post_id, '_efb_file_url', true );
		$cover = get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'efb-brochure-cover' ) );
		$out   = '<div class="efb-brochure-single">';
		if ( $cover ) {
			$out .= $file ? '<a href="' . esc_url( $file ) . '" target="_blank" rel="noopener">' . $cover . '</a>' : $cover;
		}
		if ( $file ) {
			$out .= '<p><a class="efb-button" href="' . esc_url( $file ) . '" target="_blank" rel="noopener">' . esc_html__( 'Download brochure (PDF)', 'ecofridge-blocks' ) . '</a></p>';
		}
		$out .= '</div>';
		return $out . $content;
	}
);
