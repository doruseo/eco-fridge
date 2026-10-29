<?php
/**
 * Admin: Media → Image Optimizer (dashboard, bulk conversion, settings,
 * tools), the "Optimized" column and row action in the media list.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin screens.
 */
class O2IO_Admin {

	const SLUG = 'o2-image-optimizer';

	/** Hooks. */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_o2io_status', array( __CLASS__, 'ajax_status' ) );
		add_action( 'wp_ajax_o2io_control', array( __CLASS__, 'ajax_control' ) );
		add_action( 'wp_ajax_o2io_tick', array( __CLASS__, 'ajax_tick' ) );
		add_action( 'wp_ajax_o2io_one', array( __CLASS__, 'ajax_one' ) );
		add_action( 'admin_post_o2io_tool', array( __CLASS__, 'tool' ) );
		add_filter( 'manage_media_columns', array( __CLASS__, 'column' ) );
		add_action( 'manage_media_custom_column', array( __CLASS__, 'column_cell' ), 10, 2 );
		add_filter( 'media_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( O2IO_FILE ), array( __CLASS__, 'plugin_link' ) );
	}

	/** Menu entry under Media. */
	public static function menu() {
		add_media_page( 'Image Optimizer', 'Image Optimizer', 'manage_options', self::SLUG, array( __CLASS__, 'page' ) );
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function plugin_link( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'upload.php?page=' . self::SLUG ) ) . '">Settings</a>' );
		return $links;
	}

	/** Settings registration. */
	public static function register() {
		register_setting( 'o2io', O2IO_Settings::OPTION, array( 'sanitize_callback' => array( 'O2IO_Settings', 'sanitize' ) ) );
	}

	/**
	 * Admin CSS/JS on the plugin page and the media list.
	 *
	 * @param string $hook Screen hook.
	 */
	public static function assets( $hook ) {
		if ( 'media_page_' . self::SLUG !== $hook && 'upload.php' !== $hook ) { return; }
		wp_enqueue_style( 'o2io-admin', O2IO_URL . 'assets/admin.css', array(), O2IO_VERSION );
		wp_enqueue_script( 'o2io-admin', O2IO_URL . 'assets/admin.js', array(), O2IO_VERSION, true );
		wp_localize_script( 'o2io-admin', 'o2ioAdmin', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'o2io' ),
			'pause' => (int) O2IO_Settings::get( 'batch_pause' ),
		) );
	}

	/** The page. */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$tab  = sanitize_key( $_GET['tab'] ?? 'dashboard' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array( 'dashboard' => 'Dashboard', 'settings' => 'Settings', 'tools' => 'Tools', 'help' => 'How it works' );
		echo '<div class="wrap o2io-wrap"><h1>O2 Image Optimizer</h1>';
		if ( isset( $_GET['o2io_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( wp_unslash( $_GET['o2io_msg'] ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $key => $label ) {
			printf( '<a href="%s" class="nav-tab%s">%s</a>', esc_url( admin_url( 'upload.php?page=' . self::SLUG . '&tab=' . $key ) ), $tab === $key ? ' nav-tab-active' : '', esc_html( $label ) );
		}
		echo '</nav>';
		switch ( $tab ) {
			case 'settings':
				self::tab_settings();
				break;
			case 'tools':
				self::tab_tools();
				break;
			case 'help':
				self::tab_help();
				break;
			default:
				self::tab_dashboard();
		}
		echo '</div>';
	}

	/** Dashboard: server support, library totals, bulk conversion, log. */
	private static function tab_dashboard() {
		$st = O2IO_Attachments::stats();
		$q  = O2IO_Queue::state();
		$pct = $st['images'] ? round( 100 * ( $st['done'] + $st['partial'] + $st['error'] ) / $st['images'] ) : 0;
		echo '<div class="o2io-bulk" id="o2io-bulk" data-status="' . esc_attr( $q['status'] ) . '">';
		echo '<div class="o2io-grid">';

		echo '<section class="o2io-card"><h2>Server support</h2><table class="widefat striped"><tbody>';
		foreach ( array( 'avif' => 'AVIF', 'webp' => 'WebP' ) as $f => $label ) {
			$engine = O2IO_Converter::engine_for( $f );
			$on     = O2IO_Settings::get( $f );
			printf( '<tr><th>%s</th><td>%s</td><td>%s</td></tr>', esc_html( $label ),
				$engine ? '<span class="o2io-ok">Supported (' . esc_html( ucfirst( $engine ) ) . ')</span>' : '<span class="o2io-bad">Not supported on this server</span>',
				$on ? 'Enabled' : 'Disabled in Settings' );
		}
		echo '</tbody></table><p class="description">Delivery: <strong>' . esc_html( self::delivery_label() ) . '</strong>. Originals are never modified; converted copies sit next to them (photo.jpg → photo.jpg.avif, photo.jpg.webp).</p></section>';

		$saved = array_sum( $st['saved'] );
		echo '<section class="o2io-card"><h2>Media library</h2><div class="o2io-stats" id="o2io-stats">';
		self::stat( 'Images', $st['images'] );
		self::stat( 'Optimized', $st['done'] );
		self::stat( 'Not yet', $st['pending'], 'o2io-stat--pending' );
		self::stat( 'With errors', $st['partial'] + $st['error'], 'o2io-stat--error' );
		echo '</div><p>Space saved vs. originals: ';
		foreach ( $st['saved'] as $f => $b ) { echo '<strong>' . esc_html( strtoupper( $f ) ) . '</strong> ' . esc_html( size_format( $b, 1 ) ) . ' &nbsp; '; }
		echo $saved ? '' : '—';
		echo '</p></section>';

		// Big status panels, shown by admin.css according to #o2io-bulk[data-status]; right above Bulk conversion, across the full width of the grid.
		echo '<div class="o2io-running" role="status" aria-live="polite">'
			. '<div class="o2io-running__anim" aria-hidden="true"><span class="o2io-running__ring"></span><span class="o2io-running__icon">🖼️</span></div>'
			. '<div class="o2io-running__text"><p class="o2io-running__warn">⚠️ KEEP THIS WINDOW OPEN until the process finishes.</p>'
			. '<p class="o2io-running__title">Optimisation is running… <span id="o2io-running-pct">' . (int) $pct . '%</span></p>'
			. '<p class="o2io-running__note">Do not close this tab or leave this page. Closing it does not damage any image (originals are never changed), but the conversion then slows down and only carries on when the website has visitors – open this page again to speed it up.</p></div></div>'
			. '<div class="o2io-finished" role="status"><strong>✅ Optimisation finished.</strong> You can close this window.</div>'
			. '<div class="o2io-paused-note" role="status"><strong>⏸ Paused.</strong> Press "Resume" to carry on.</div>';

		echo '<section class="o2io-card o2io-card--wide"><h2>Bulk conversion</h2>';
		echo '<p>Converts the images in small batches (' . (int) O2IO_Settings::get( 'batch_size' ) . ' images per batch, ' . (int) O2IO_Settings::get( 'batch_pause' ) . ' s pause, at most ' . (int) O2IO_Queue::BUDGET . ' s of work per batch). Keep this page open while it runs: the page itself drives the batches, so it is fast and does not depend on visitors. New uploads are converted automatically.</p>';
		echo '<div class="o2io-progress"><div class="o2io-progress__bar" id="o2io-bar" style="width:' . (int) $pct . '%"></div></div>';
		echo '<p id="o2io-queue-text">' . esc_html( self::queue_text( $q ) ) . '</p>';
		echo '<p class="o2io-buttons">'
			. '<button type="button" class="button button-primary" data-o2io="start">Convert images not converted yet</button> '
			. '<button type="button" class="button" data-o2io="start_all" data-confirm="Re-create the AVIF/WebP copies of every image? Originals are not touched.">Re-convert all images</button> '
			. '<button type="button" class="button" data-o2io="pause">Pause</button> '
			. '<button type="button" class="button" data-o2io="resume">Resume</button> '
			. '<button type="button" class="button button-link-delete" data-o2io="stop">Stop</button></p>';
		echo '</section>';

		echo '<section class="o2io-card o2io-card--wide"><h2>Activity</h2><ul class="o2io-log" id="o2io-log">';
		foreach ( array_slice( O2IO_Queue::get_log(), 0, 15 ) as $l ) {
			echo '<li><time>' . esc_html( wp_date( 'j M H:i', $l[0] ) ) . '</time> ' . esc_html( $l[1] ) . '</li>';
		}
		echo '</ul></section></div></div>';
	}

	/**
	 * One stat box.
	 *
	 * @param string $label Label.
	 * @param int    $n     Number.
	 * @param string $cls   Extra class.
	 */
	private static function stat( $label, $n, $cls = '' ) {
		printf( '<div class="o2io-stat %s"><span>%s</span>%s</div>', esc_attr( $cls ), esc_html( number_format_i18n( $n ) ), esc_html( $label ) );
	}

	/**
	 * Human text for the queue state.
	 *
	 * @param array $q State.
	 * @return string
	 */
	private static function queue_text( $q ) {
		$labels = array( 'idle' => 'Not running.', 'running' => 'Running…', 'paused' => 'Paused.', 'finished' => 'Finished.' );
		$t      = $labels[ $q['status'] ] ?? '';
		if ( $q['started'] ) {
			$t .= sprintf( ' This run: %d images processed, %d with errors (started %s, last update %s).', $q['processed'], $q['errors'], wp_date( 'j M H:i', $q['started'] ), $q['updated'] ? wp_date( 'H:i:s', $q['updated'] ) : '—' );
		}
		return $t;
	}

	/** Delivery mode in words. */
	private static function delivery_label() {
		return array(
			'picture' => '<picture> elements (each browser picks AVIF, WebP or the original)',
			'rewrite' => 'server rewrite by Accept header',
			'both'    => '<picture> elements + server rewrite',
			'off'     => 'off (files are converted but not served)',
		)[ O2IO_Settings::get( 'delivery' ) ] ?? '';
	}

	/** Settings form. */
	private static function tab_settings() {
		$s = O2IO_Settings::all();
		$n = O2IO_Settings::OPTION;
		echo '<form method="post" action="options.php" class="o2io-form">';
		settings_fields( 'o2io' );

		echo '<h2>Formats</h2><table class="form-table" role="presentation">';
		self::checkbox( 'avif', 'AVIF', 'Smallest files; supported by current Chrome, Edge, Firefox, Safari 16.4+. Slower to create.', $s );
		self::number( 'avif_quality', 'AVIF quality', 1, 100, 'Default 62. AVIF looks good at lower numbers than JPEG.', $s );
		self::number( 'avif_speed', 'AVIF encoder speed', 0, 10, '0 = smallest files but very slow, 10 = fastest. 6 is a good balance on shared hosting.', $s );
		self::checkbox( 'webp', 'WebP', 'Supported by every current browser; the fallback for browsers without AVIF.', $s );
		self::number( 'webp_quality', 'WebP quality', 1, 100, 'Default 82.', $s );
		self::select( 'engine', 'Engine', array( 'auto' => 'Automatic (Imagick, then GD)', 'imagick' => 'Imagick', 'gd' => 'GD' ), '', $s );
		self::checkbox( 'strip_metadata', 'Strip metadata', 'Remove EXIF/XMP (camera data, GPS) from the converted copies; the colour profile is kept.', $s );
		self::checkbox( 'skip_larger', 'Keep only smaller files', 'Delete a converted copy that isn\'t smaller than the original (the original is served instead).', $s );
		self::number( 'min_saving', 'Minimum saving (%)', 0, 90, 'A copy must be at least this much smaller to be kept.', $s );
		echo '</table>';

		echo '<h2>Which images</h2><table class="form-table" role="presentation">';
		self::checkbox( 'convert_jpeg', 'JPEG', '', $s );
		self::checkbox( 'convert_png', 'PNG', 'Transparency is kept.', $s );
		self::checkbox( 'convert_gif', 'GIF', 'Only the first frame of an animated GIF would be kept – leave off for animations.', $s );
		self::checkbox( 'convert_sizes', 'All sizes', 'Convert every size WordPress generates (thumbnails, medium, large…), not only the main file.', $s );
		echo '</table>';

		echo '<h2>New uploads</h2><table class="form-table" role="presentation">';
		self::select( 'on_upload', 'Convert new uploads', array( 'background' => 'In the background (recommended)', 'immediate' => 'Immediately (upload waits)', 'off' => 'No – only with the bulk tool' ), '', $s );
		self::number( 'max_width', 'Large image threshold (px)', 0, 10000, 'WordPress makes a scaled copy of uploads wider/taller than this and uses it on the site (the original file is kept). 0 = WordPress default.', $s );
		echo '</table>';

		echo '<h2>Delivery</h2><table class="form-table" role="presentation">';
		self::select( 'delivery', 'Serve converted images', array(
			'picture' => '<picture> elements – recommended, works behind any CDN',
			'rewrite' => 'Server rewrite by Accept header (uploads/.htaccess)',
			'both'    => 'Both',
			'off'     => 'Off',
		), 'The rewrite mode keeps the .jpg/.png URLs and sends "Vary: Accept"; use it only if the server and any CDN honour Vary, or a browser without AVIF support could be served an AVIF.', $s );
		self::select( 'picture_scope', '<picture> on', array( 'page' => 'Every image of the page (theme, blocks, WooCommerce…)', 'content' => 'Post content and attachment images only' ), '', $s );
		self::checkbox( 'skip_lazy_data', 'Skip script-lazy-loaded images', 'Leave images with data-srcset / data-lazy-src (filled in later by a lazy-loading script) unchanged. Images whose src is a placeholder are always left alone.', $s );
		self::textarea( 'exclude_classes', 'Exclude images with class', 'One per line. Images with any of these classes are left as they are.', $s );
		self::textarea( 'exclude_paths', 'Exclude paths', 'One per line, e.g. 2024/05/ or logo-. Matched anywhere in the image path.', $s );
		self::checkbox( 'exclude_admin', 'Preview switch', 'Logged-in editors can add ?o2io_off=1 to a URL to see the page without optimised images.', $s );
		echo '</table>';

		echo '<h2>Background conversion</h2><table class="form-table" role="presentation">';
		self::number( 'batch_size', 'Images per batch', 1, 50, 'Each image includes all its sizes. Lower it if the host limits CPU.', $s );
		self::number( 'batch_pause', 'Pause between batches (s)', 0, 600, '', $s );
		echo '</table>';

		echo '<h2>Uninstall</h2><table class="form-table" role="presentation">';
		self::checkbox( 'delete_on_uninstall', 'Delete converted files', 'When the plugin is deleted, also delete every .avif/.webp copy it made (originals are never touched).', $s );
		echo '</table>';

		submit_button();
		echo '</form>';
	}

	/** Tools: single test, clean-up, .htaccess. */
	private static function tab_tools() {
		$action = esc_url( admin_url( 'admin-post.php' ) );
		echo '<div class="o2io-grid">';
		echo '<section class="o2io-card"><h2>Convert one image</h2><p>Convert (or re-convert) a single image and see the result.</p>';
		echo '<form method="post" action="' . $action . '">';
		wp_nonce_field( 'o2io_tool' );
		echo '<input type="hidden" name="action" value="o2io_tool"><input type="hidden" name="tool" value="one">';
		echo '<label>Attachment ID <input type="number" name="id" min="1" required class="small-text"></label> ';
		submit_button( 'Convert', 'secondary', 'submit', false );
		echo '</form>';
		$last = get_transient( 'o2io_tool_result' );
		if ( $last ) {
			echo '<pre class="o2io-pre">' . esc_html( $last ) . '</pre>';
		}
		echo '</section>';

		echo '<section class="o2io-card"><h2>Server rules</h2><p>Re-write the plugin\'s block in <code>uploads/.htaccess</code> (MIME types, and the rewrite rules if that delivery mode is on).</p>';
		echo '<form method="post" action="' . $action . '">';
		wp_nonce_field( 'o2io_tool' );
		echo '<input type="hidden" name="action" value="o2io_tool"><input type="hidden" name="tool" value="htaccess">';
		submit_button( 'Write .htaccess rules', 'secondary', 'submit', false );
		echo '</form></section>';

		echo '<section class="o2io-card"><h2>Delete converted files</h2><p>Remove every .avif/.webp copy made by this plugin and its records. Originals are not touched; the site falls back to them.</p>';
		echo '<form method="post" action="' . $action . '" onsubmit="return confirm(\'Delete all converted copies?\')">';
		wp_nonce_field( 'o2io_tool' );
		echo '<input type="hidden" name="action" value="o2io_tool"><input type="hidden" name="tool" value="cleanup">';
		submit_button( 'Delete converted files', 'delete', 'submit', false );
		echo '</form></section></div>';
	}

	/** Help. */
	private static function tab_help() {
		echo '<div class="o2io-card o2io-help">';
		echo '<h2>How it works</h2><ol>';
		echo '<li><strong>Conversion.</strong> For every image (and each of its sizes) the plugin writes an AVIF and a WebP copy next to the original: <code>photo.jpg.avif</code>, <code>photo.jpg.webp</code>. Originals are never modified. A copy that isn\'t smaller than the original is discarded.</li>';
		echo '<li><strong>Delivery.</strong> Each image on the page is wrapped in <code>&lt;picture&gt;</code> with an AVIF and a WebP source. The browser takes the first format it supports – AVIF in current browsers, WebP in older ones, the original JPEG/PNG in very old ones.</li>';
		echo '<li><strong>New uploads</strong> are converted automatically in the background a few seconds after upload.</li>';
		echo '<li><strong>Existing images:</strong> Dashboard → "Convert images not converted yet". It runs in small batches in the background, with pauses, and can be paused, resumed or stopped at any time.</li>';
		echo '<li><strong>WP-CLI:</strong> <code>wp o2io status</code>, <code>wp o2io convert --limit=50</code>, <code>wp o2io convert --all</code>, <code>wp o2io cleanup</code>.</li>';
		echo '<li><strong>Not covered:</strong> CSS background images and images from other domains.</li>';
		echo '</ol></div>';
	}

	/* —— form helpers —— */

	/**
	 * Checkbox row.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $help  Help.
	 * @param array  $s     Settings.
	 */
	private static function checkbox( $key, $label, $help, $s ) {
		printf( '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="%s[%s]" value="1"%s> %s</label></td></tr>',
			esc_html( $label ), esc_attr( O2IO_Settings::OPTION ), esc_attr( $key ), checked( ! empty( $s[ $key ] ), true, false ), esc_html( $help ) );
	}

	/**
	 * Number row.
	 *
	 * @param string $key  Key.
	 * @param string $label Label.
	 * @param int    $min  Min.
	 * @param int    $max  Max.
	 * @param string $help Help.
	 * @param array  $s    Settings.
	 */
	private static function number( $key, $label, $min, $max, $help, $s ) {
		printf( '<tr><th scope="row"><label for="o2io-%2$s">%1$s</label></th><td><input type="number" id="o2io-%2$s" name="%3$s[%2$s]" value="%4$d" min="%5$d" max="%6$d" class="small-text"> <span class="description">%7$s</span></td></tr>',
			esc_html( $label ), esc_attr( $key ), esc_attr( O2IO_Settings::OPTION ), (int) $s[ $key ], (int) $min, (int) $max, esc_html( $help ) );
	}

	/**
	 * Select row.
	 *
	 * @param string $key     Key.
	 * @param string $label   Label.
	 * @param array  $choices Choices.
	 * @param string $help    Help.
	 * @param array  $s       Settings.
	 */
	private static function select( $key, $label, $choices, $help, $s ) {
		printf( '<tr><th scope="row"><label for="o2io-%1$s">%2$s</label></th><td><select id="o2io-%1$s" name="%3$s[%1$s]">', esc_attr( $key ), esc_html( $label ), esc_attr( O2IO_Settings::OPTION ) );
		foreach ( $choices as $v => $l ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $s[ $key ], $v, false ), esc_html( $l ) );
		}
		echo '</select>' . ( $help ? ' <p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>';
	}

	/**
	 * Textarea row.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $help  Help.
	 * @param array  $s     Settings.
	 */
	private static function textarea( $key, $label, $help, $s ) {
		printf( '<tr><th scope="row"><label for="o2io-%1$s">%2$s</label></th><td><textarea id="o2io-%1$s" name="%3$s[%1$s]" rows="3" class="regular-text">%4$s</textarea><p class="description">%5$s</p></td></tr>',
			esc_attr( $key ), esc_html( $label ), esc_attr( O2IO_Settings::OPTION ), esc_textarea( $s[ $key ] ), esc_html( $help ) );
	}

	/* —— AJAX —— */

	/** Queue + library status (polled by the dashboard). */
	public static function ajax_status() {
		check_ajax_referer( 'o2io', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( null, 403 ); }
		$st = O2IO_Attachments::stats();
		$q  = O2IO_Queue::state();
		wp_send_json_success( array(
			'stats' => $st,
			'pct'   => $st['images'] ? round( 100 * ( $st['done'] + $st['partial'] + $st['error'] ) / $st['images'] ) : 0,
			'queue' => $q,
			'text'  => self::queue_text( $q ),
			'log'   => array_map( fn( $l ) => array( wp_date( 'j M H:i', $l[0] ), $l[1] ), array_slice( O2IO_Queue::get_log(), 0, 15 ) ),
		) );
	}

	/** Run the next due batch (the open dashboard drives the queue), then report the status. */
	public static function ajax_tick() {
		check_ajax_referer( 'o2io', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( null, 403 ); }
		O2IO_Queue::tick();
		self::ajax_status();
	}

	/** Start / pause / resume / stop. */
	public static function ajax_control() {
		check_ajax_referer( 'o2io', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( null, 403 ); }
		switch ( sanitize_key( $_POST['do'] ?? '' ) ) {
			case 'start':
				O2IO_Queue::start( 'missing' );
				break;
			case 'start_all':
				O2IO_Queue::start( 'all' );
				break;
			case 'pause':
				O2IO_Queue::pause();
				break;
			case 'resume':
				O2IO_Queue::resume();
				break;
			case 'stop':
				O2IO_Queue::stop();
				break;
		}
		self::ajax_status();
	}

	/** Convert one attachment from the media list. */
	public static function ajax_one() {
		check_ajax_referer( 'o2io', 'nonce' );
		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) { wp_send_json_error( null, 403 ); }
		O2IO_Attachments::process( $id, true );
		wp_send_json_success( array( 'html' => self::cell_html( $id ) ) );
	}

	/** Tools form handler. */
	public static function tool() {
		check_admin_referer( 'o2io_tool' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
		$msg = '';
		switch ( sanitize_key( $_POST['tool'] ?? '' ) ) {
			case 'one':
				$id = absint( $_POST['id'] ?? 0 );
				if ( $id && O2IO_Attachments::is_convertible( $id ) ) {
					$r     = O2IO_Attachments::process( $id, true );
					$lines = array( '#' . $id . ' ' . get_the_title( $id ) . ' — ' . $r['status'] );
					foreach ( $r['files'] as $name => $formats ) {
						foreach ( $formats as $f => $x ) {
							$lines[] = sprintf( '%-48s %-5s %-8s %9s → %s', $name, $f, $x[2], size_format( $x[1], 1 ), $x[0] ? size_format( $x[0], 1 ) : '—' );
						}
					}
					set_transient( 'o2io_tool_result', implode( "\n", $lines ), 10 * MINUTE_IN_SECONDS );
					$msg = 'Converted.';
				} else {
					$msg = 'Not a JPEG/PNG image attachment.';
				}
				break;
			case 'htaccess':
				O2IO_Delivery::write_htaccess();
				$msg = 'uploads/.htaccess updated.';
				break;
			case 'cleanup':
				O2IO_Queue::stop();
				$n = 0;
				foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => O2IO_Attachments::META ) ) as $id ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$n += O2IO_Attachments::reset( $id );
				}
				O2IO_Queue::log( "Clean-up: $n converted files deleted." );
				$msg = "$n converted files deleted.";
				break;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'tab' => 'tools', 'o2io_msg' => rawurlencode( $msg ) ), admin_url( 'upload.php' ) ) );
		exit;
	}

	/* —— Media list —— */

	/**
	 * "Optimized" column.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function column( $cols ) {
		$cols['o2io'] = 'Optimized';
		return $cols;
	}

	/**
	 * Column cell.
	 *
	 * @param string $col Column.
	 * @param int    $id  Attachment ID.
	 */
	public static function column_cell( $col, $id ) {
		if ( 'o2io' === $col ) {
			echo '<div class="o2io-cell" data-id="' . (int) $id . '">' . self::cell_html( $id ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped.
		}
	}

	/**
	 * What the cell shows.
	 *
	 * @param int $id Attachment ID.
	 * @return string
	 */
	private static function cell_html( $id ) {
		if ( ! O2IO_Attachments::is_convertible( $id ) ) { return '—'; }
		$r = O2IO_Attachments::record( $id );
		if ( ! $r ) { return '<span class="o2io-badge o2io-badge--pending">Not yet</span>'; }
		$out = '<span class="o2io-badge o2io-badge--' . esc_attr( $r['status'] ) . '">' . esc_html( ucfirst( $r['status'] ) ) . '</span>';
		foreach ( (array) $r['saved'] as $f => $b ) {
			$out .= '<br><small>' . esc_html( strtoupper( $f ) ) . ' −' . esc_html( size_format( $b, 1 ) ) . '</small>';
		}
		if ( ! empty( $r['errors'] ) ) {
			$out .= '<br><small class="o2io-err" title="' . esc_attr( implode( "\n", $r['errors'] ) ) . '">' . count( $r['errors'] ) . ' error(s)</small>';
		}
		return $out;
	}

	/**
	 * "Convert now" row action.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Attachment.
	 * @return array
	 */
	public static function row_action( $actions, $post ) {
		if ( O2IO_Attachments::is_convertible( $post->ID ) && current_user_can( 'edit_post', $post->ID ) ) {
			$actions['o2io'] = '<a href="#" class="o2io-one" data-id="' . (int) $post->ID . '">Convert AVIF/WebP</a>';
		}
		return $actions;
	}
}
