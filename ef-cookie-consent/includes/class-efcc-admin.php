<?php
/**
 * Admin: Cookie Consent menu with its tabs, saving, scanner endpoint, consent
 * log list and CSV export, import / export of the settings.
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin screens.
 */
class EFCC_Admin {

	const SLUG = 'ef-cookie-consent';

	/** Hooks. */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_efcc_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_efcc_tool', array( __CLASS__, 'tool' ) );
		add_action( 'admin_post_efcc_export_log', array( __CLASS__, 'export_log' ) );
		add_action( 'wp_ajax_efcc_scan_save', array( __CLASS__, 'ajax_scan_save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( EFCC_FILE ), array( __CLASS__, 'plugin_link' ) );
	}

	/** Menu. */
	public static function menu() {
		add_menu_page( 'Cookie Consent', 'Cookie Consent', 'manage_options', self::SLUG, array( __CLASS__, 'page' ), 'dashicons-shield', 81 );
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function plugin_link( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">Settings</a>' );
		return $links;
	}

	/**
	 * Admin assets on our page.
	 *
	 * @param string $hook Screen hook.
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) { return; }
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'efcc-admin', EFCC_URL . 'assets/admin.css', array(), EFCC_VERSION );
		wp_enqueue_script( 'efcc-admin', EFCC_URL . 'assets/admin.js', array( 'wp-color-picker' ), EFCC_VERSION, true );
		wp_localize_script( 'efcc-admin', 'efccAdmin', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'efcc_scan' ),
		) );
	}

	/** Tab list. */
	private static function tabs() {
		return array(
			'dashboard'  => 'Dashboard',
			'banner'     => 'Banner',
			'categories' => 'Categories',
			'services'   => 'Services',
			'cookies'    => 'Cookies & scan',
			'gcm'        => 'Google Consent Mode',
			'blocking'   => 'Blocking',
			'log'        => 'Consent log',
			'general'    => 'Policy & general',
			'tools'      => 'Tools',
		);
	}

	/** The page. */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$tab = sanitize_key( $_GET['tab'] ?? 'dashboard' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( self::tabs()[ $tab ] ) ) { $tab = 'dashboard'; }
		echo '<div class="wrap efcc-wrap"><h1>EF Cookie Consent</h1>';
		if ( isset( $_GET['efcc_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['efcc_msg'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		echo '<nav class="nav-tab-wrapper">';
		foreach ( self::tabs() as $k => $label ) {
			printf( '<a class="nav-tab%s" href="%s">%s</a>', $tab === $k ? ' nav-tab-active' : '', esc_url( self::url( $k ) ), esc_html( $label ) );
		}
		echo '</nav><div class="efcc-tab">';
		call_user_func( array( __CLASS__, 'tab_' . $tab ) );
		echo '</div></div>';
	}

	/**
	 * URL of a tab.
	 *
	 * @param string $tab Tab.
	 * @param array  $args Extra args.
	 * @return string
	 */
	private static function url( $tab, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG, 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Opening of a settings form.
	 *
	 * @param string $tab Tab.
	 */
	private static function form_open( $tab ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="efcc-form">';
		wp_nonce_field( 'efcc_save' );
		echo '<input type="hidden" name="action" value="efcc_save"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	}

	/* —— field helpers (name = efcc[section][key]) —— */

	private static function name( $section, $key ) { return 'efcc[' . $section . '][' . $key . ']'; }

	private static function row_text( $section, $key, $label, $help = '', $type = 'text', $class = 'regular-text' ) {
		$v = EFCC_Settings::get( $section, $key );
		printf( '<tr><th scope="row"><label for="efcc-%1$s-%2$s">%3$s</label></th><td><input type="%4$s" id="efcc-%1$s-%2$s" name="%5$s" value="%6$s" class="%7$s">%8$s</td></tr>',
			esc_attr( $section ), esc_attr( $key ), esc_html( $label ), esc_attr( $type ), esc_attr( self::name( $section, $key ) ), esc_attr( (string) $v ), esc_attr( $class ),
			$help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : '' );
	}

	private static function row_textarea( $section, $key, $label, $help = '', $rows = 3 ) {
		$v = EFCC_Settings::get( $section, $key );
		printf( '<tr><th scope="row"><label for="efcc-%1$s-%2$s">%3$s</label></th><td><textarea id="efcc-%1$s-%2$s" name="%4$s" rows="%5$d" class="large-text">%6$s</textarea>%7$s</td></tr>',
			esc_attr( $section ), esc_attr( $key ), esc_html( $label ), esc_attr( self::name( $section, $key ) ), (int) $rows, esc_textarea( (string) $v ),
			$help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : '' );
	}

	private static function row_check( $section, $key, $label, $text, $help = '' ) {
		$v = EFCC_Settings::get( $section, $key );
		printf( '<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="%2$s" value="1"%3$s> %4$s</label>%5$s</td></tr>',
			esc_html( $label ), esc_attr( self::name( $section, $key ) ), checked( ! empty( $v ), true, false ), esc_html( $text ),
			$help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : '' );
	}

	private static function row_select( $section, $key, $label, $choices, $help = '' ) {
		$v = (string) EFCC_Settings::get( $section, $key );
		printf( '<tr><th scope="row"><label for="efcc-%1$s-%2$s">%3$s</label></th><td><select id="efcc-%1$s-%2$s" name="%4$s">', esc_attr( $section ), esc_attr( $key ), esc_html( $label ), esc_attr( self::name( $section, $key ) ) );
		foreach ( $choices as $k => $l ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $v, (string) $k, false ), esc_html( $l ) );
		}
		echo '</select>' . ( $help ? '<p class="description">' . wp_kses_post( $help ) . '</p>' : '' ) . '</td></tr>';
	}

	private static function row_color( $section, $key, $label ) {
		$v = EFCC_Settings::get( $section, $key );
		printf( '<tr><th scope="row">%1$s</th><td><input type="text" name="%2$s" value="%3$s" class="efcc-color" data-default-color="%3$s"></td></tr>',
			esc_html( $label ), esc_attr( self::name( $section, $key ) ), esc_attr( (string) $v ) );
	}

	private static function pages_choices() {
		$out = array( 0 => '— Automatic —' );
		foreach ( get_pages( array( 'post_status' => 'publish' ) ) as $p ) { $out[ $p->ID ] = $p->post_title; }
		return $out;
	}

	/* —— tabs —— */

	/** Dashboard: checklist, stats, quick links. */
	private static function tab_dashboard() {
		$g      = EFCC_Settings::get( 'general' );
		$gcm    = EFCC_Settings::get( 'gcm' );
		$policy = EFCC_Settings::policy_page();
		$scan   = (int) EFCC_Settings::get( 'scan', 'last_scan' );
		$st     = EFCC_Log::stats( 30 );
		$total  = array_sum( $st );
		$checks = array(
			array( (bool) $g['enabled'], 'Banner enabled', 'banner' ),
			array( $policy > 0, $policy ? 'Cookie policy page: ' . get_the_title( $policy ) : 'Choose the cookie policy page', 'general' ),
			array( $policy && false !== strpos( (string) get_post_field( 'post_content', $policy ), '[efcc_cookie' ), 'Cookie table on the policy page ([efcc_cookie_table] or [efcc_cookie_policy])', 'general' ),
			array( $scan > 0, $scan ? 'Last cookie scan: ' . wp_date( 'j M Y H:i', $scan ) : 'Run a cookie scan', 'cookies' ),
			array( (bool) $gcm['enabled'], 'Google Consent Mode v2 on', 'gcm' ),
			array( 'plugin' !== $gcm['gtm_load'] || '' !== $gcm['gtm_id'], 'plugin' === $gcm['gtm_load'] ? ( $gcm['gtm_id'] ? 'GTM container ' . $gcm['gtm_id'] . ' loaded by the plugin' : 'Enter your GTM container ID' ) : 'GTM loaded elsewhere / not used', 'gcm' ),
			array( (bool) $g['log'], 'Consent log on (proof of consent)', 'general' ),
			array( 'equal' === EFCC_Settings::get( 'appearance', 'button_style' ), 'Buttons with equal prominence (ICO recommends "Reject all" as prominent as "Accept all")', 'banner', true ),
		);
		echo '<div class="efcc-grid"><section class="efcc-card"><h2>Checklist</h2><ul class="efcc-checks">';
		foreach ( $checks as $c ) {
			$cls = $c[0] ? 'ok' : ( empty( $c[3] ) ? 'todo' : 'warn' );
			echo '<li class="efcc-check efcc-check--' . esc_attr( $cls ) . '"><a href="' . esc_url( self::url( $c[2] ) ) . '">' . esc_html( $c[1] ) . '</a></li>';
		}
		echo '</ul></section>';
		echo '<section class="efcc-card"><h2>Choices in the last 30 days</h2>';
		if ( $total ) {
			foreach ( array( 'accept' => 'Accepted all', 'reject' => 'Rejected all', 'custom' => 'Chose their own', 'gpc' => 'Global Privacy Control' ) as $k => $l ) {
				$pct = round( 100 * $st[ $k ] / $total );
				echo '<div class="efcc-bar"><span>' . esc_html( $l ) . '</span><div><i style="width:' . (int) $pct . '%"></i></div><b>' . (int) $st[ $k ] . ' (' . (int) $pct . '%)</b></div>';
			}
		} else {
			echo '<p>No choices recorded yet.</p>';
		}
		echo '<p><a class="button" href="' . esc_url( add_query_arg( 'efcc_preview', '1', home_url( '/' ) ) ) . '" target="_blank">Preview the banner on the site</a></p></section></div>';
	}

	/** Banner texts + appearance. */
	private static function tab_banner() {
		self::form_open( 'banner' );
		echo '<h2>Appearance</h2><table class="form-table" role="presentation">';
		self::row_select( 'appearance', 'layout', 'Layout', array( 'bar' => 'Bar at the bottom', 'box-left' => 'Box, bottom left', 'box-right' => 'Box, bottom right', 'center' => 'Box in the centre' ) );
		self::row_select( 'appearance', 'button_style', 'Buttons', array(
			'accent' => '"Accept all" filled, "Reject all" and "Manage options" outlined',
			'equal'  => 'Equal prominence – all three filled (recommended by the ICO)',
		), 'The ICO expects rejecting to be as easy as accepting and has criticised banners that make "Accept" stand out. "Equal prominence" is the safest choice under PECR. "Reject all" is always the middle button.' );
		self::row_check( 'appearance', 'uppercase', 'Button text', 'Capital letters on every button (applied to all of them, so none stands out)' );
		self::row_color( 'appearance', 'primary', 'Main colour (buttons, links, switches)' );
		self::row_color( 'appearance', 'primary_text', 'Text on the main colour' );
		self::row_color( 'appearance', 'background', 'Background' );
		self::row_color( 'appearance', 'text', 'Text colour' );
		self::row_text( 'appearance', 'radius', 'Corner radius (px)', '', 'number', 'small-text' );
		self::row_text( 'appearance', 'font_size', 'Font size (px)', '', 'number', 'small-text' );
		self::row_text( 'appearance', 'max_width', 'Content width of the bar (px)', '', 'number', 'small-text' );
		self::row_check( 'appearance', 'shadow', 'Shadow', 'Drop shadow under the banner' );
		self::row_text( 'appearance', 'z_index', 'Stacking order (z-index)', 'Raise it if a sticky header covers the banner.', 'number', 'small-text' );
		self::row_select( 'appearance', 'icon_style', 'Cookie button', array( 'colorful' => 'Colourful cookie', 'brand' => 'Cookie in the main colour', 'mono' => 'Single colour' ) );
		echo '</table><h2>Texts</h2><table class="form-table" role="presentation">';
		self::row_text( 'texts', 'title', 'Title', 'Leave empty for no title.' );
		self::row_textarea( 'texts', 'message', 'Message', '{policy} becomes the link to the cookie policy.' );
		self::row_text( 'texts', 'policy_link', 'Policy link text' );
		self::row_text( 'texts', 'accept', '"Accept" button' );
		self::row_text( 'texts', 'reject', '"Reject" button' );
		self::row_text( 'texts', 'manage', '"Manage" button' );
		self::row_text( 'texts', 'save', '"Save" button (preferences)' );
		self::row_text( 'texts', 'modal_title', 'Preferences title' );
		self::row_textarea( 'texts', 'modal_intro', 'Preferences introduction' );
		self::row_text( 'texts', 'always_active', '"Always active" label' );
		self::row_text( 'texts', 'services_label', '"Services" heading' );
		self::row_text( 'texts', 'cookies_label', '"Cookies" heading' );
		self::row_text( 'texts', 'floating_label', 'Cookie button label (screen readers, tooltip)' );
		self::row_textarea( 'texts', 'placeholder', 'Blocked content message', '{service} and {category} are replaced.' );
		self::row_text( 'texts', 'placeholder_button', 'Blocked content button' );
		self::row_text( 'texts', 'consent_id', '"Consent ID" label' );
		echo '</table>';
		submit_button();
		echo '</form>';
	}

	/** Categories. */
	private static function tab_categories() {
		self::form_open( 'categories' );
		echo '<p>Necessary cookies are always on. Hide a category that the website does not use.</p><table class="widefat efcc-table"><thead><tr><th>Category</th><th>Name shown</th><th>Description</th><th>Shown</th></tr></thead><tbody>';
		foreach ( EFCC_Settings::get( 'categories' ) as $k => $c ) {
			printf( '<tr><td><code>%1$s</code></td><td><input type="text" name="efcc[categories][%1$s][label]" value="%2$s" class="regular-text"></td><td><textarea name="efcc[categories][%1$s][description]" rows="2" class="large-text">%3$s</textarea></td><td>%4$s</td></tr>',
				esc_attr( $k ), esc_attr( $c['label'] ), esc_textarea( $c['description'] ),
				'necessary' === $k ? 'Always' : '<input type="checkbox" name="efcc[categories][' . esc_attr( $k ) . '][show]" value="1"' . checked( ! empty( $c['show'] ), true, false ) . '>' );
		}
		echo '</tbody></table>';
		submit_button();
		echo '</form>';
	}

	/** Services: presets and custom ones. */
	private static function tab_services() {
		$saved   = (array) EFCC_Settings::get( 'services' );
		$presets = EFCC_Library::services();
		self::form_open( 'services' );
		echo '<p>Each enabled service is listed in the preferences with its own switch, its cookies are listed in the cookie policy, and its scripts / iframes are blocked until its category is allowed. <strong>Patterns</strong>: one per line – text found in the script URL, inline script or iframe URL of the service.</p>';
		echo '<table class="widefat efcc-table efcc-services-table"><thead><tr><th>On</th><th>Service</th><th>Category</th><th>Provider / privacy policy</th><th>Purpose</th><th>Patterns</th><th>Cookies</th><th></th></tr></thead><tbody id="efcc-services-body">';
		foreach ( $saved as $id => $s ) { self::service_row( $id, $s ); }
		echo '</tbody></table>';
		echo '<p class="efcc-add"><select id="efcc-preset"><option value="">Add a preset…</option>';
		foreach ( $presets as $id => $p ) {
			if ( isset( $saved[ $id ] ) ) { continue; }
			echo '<option value="' . esc_attr( $id ) . '" data-preset="' . esc_attr( wp_json_encode( array_merge( $p, array( 'patterns' => implode( "\n", $p['patterns'] ), 'cookies' => implode( "\n", $p['cookies'] ), 'enabled' => 1 ) ) ) ) . '">' . esc_html( $p['name'] ) . '</option>';
		}
		echo '</select> <button type="button" class="button" id="efcc-add-preset">Add</button> <button type="button" class="button" id="efcc-add-custom">Add a custom service</button></p>';
		echo '<template id="efcc-service-template">';
		self::service_row( '__ID__', array( 'enabled' => 1, 'name' => '', 'category' => 'marketing', 'provider' => '', 'purpose' => '', 'privacy' => '', 'patterns' => '', 'cookies' => '' ) );
		echo '</template>';
		submit_button();
		echo '</form>';
	}

	/**
	 * One editable service row.
	 *
	 * @param string $id ID.
	 * @param array  $s  Service.
	 */
	private static function service_row( $id, $s ) {
		$n = 'efcc[services][' . $id . ']';
		echo '<tr class="efcc-service-row">';
		echo '<td><input type="checkbox" name="' . esc_attr( $n ) . '[enabled]" value="1"' . checked( ! empty( $s['enabled'] ), true, false ) . '></td>';
		echo '<td><input type="text" name="' . esc_attr( $n ) . '[name]" value="' . esc_attr( $s['name'] ) . '" placeholder="Name" required><br><code class="efcc-id">' . esc_html( $id ) . '</code></td>';
		echo '<td><select name="' . esc_attr( $n ) . '[category]">';
		foreach ( EFCC_Library::category_keys() as $k ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $s['category'], $k, false ) . '>' . esc_html( ucfirst( $k ) ) . '</option>';
		}
		echo '</select></td>';
		echo '<td><input type="text" name="' . esc_attr( $n ) . '[provider]" value="' . esc_attr( $s['provider'] ) . '" placeholder="Provider"><input type="url" name="' . esc_attr( $n ) . '[privacy]" value="' . esc_attr( $s['privacy'] ) . '" placeholder="https://… privacy policy"></td>';
		echo '<td><textarea name="' . esc_attr( $n ) . '[purpose]" rows="3">' . esc_textarea( $s['purpose'] ) . '</textarea></td>';
		echo '<td><textarea name="' . esc_attr( $n ) . '[patterns]" rows="3" class="code">' . esc_textarea( $s['patterns'] ) . '</textarea></td>';
		echo '<td><textarea name="' . esc_attr( $n ) . '[cookies]" rows="3" class="code">' . esc_textarea( $s['cookies'] ) . '</textarea></td>';
		echo '<td><button type="button" class="button-link-delete efcc-remove" aria-label="Remove">✕</button></td>';
		echo '</tr>';
	}

	/** Cookies: scanner + register. */
	private static function tab_cookies() {
		$reg  = EFCC_Cookies::register();
		$urls = self::scan_urls();
		echo '<div class="efcc-card"><h2>Cookie scan</h2><p>Opens these pages in hidden frames with every category allowed (nothing is recorded in the consent log), waits for their tags to load and lists every cookie and storage key they set. Known cookies are described automatically; new ones are added under <em>Necessary</em> until you classify them below.</p>';
		echo '<ul class="efcc-scan-urls">';
		foreach ( $urls as $u ) { echo '<li><code>' . esc_html( wp_make_link_relative( $u ) ) . '</code></li>'; }
		echo '</ul><p><button type="button" class="button button-primary" id="efcc-scan" data-urls="' . esc_attr( wp_json_encode( $urls ) ) . '">Scan the website</button> <span id="efcc-scan-status"></span></p><div id="efcc-scan-frames" aria-hidden="true"></div>';
		self::form_open( 'scan' );
		echo '<table class="form-table" role="presentation">';
		self::row_textarea( 'scan', 'urls', 'Extra pages to scan', 'One URL per line, e.g. a page with a video or a form.' );
		echo '</table><input type="hidden" name="efcc[scan][last_scan]" value="' . (int) EFCC_Settings::get( 'scan', 'last_scan' ) . '">';
		submit_button( 'Save pages', 'secondary' );
		echo '</form></div>';

		echo '<div class="efcc-card"><h2>Cookie register</h2><p>Found by the scan or added by hand. The cookie policy also lists the cookies of the enabled services and the site\'s necessary cookies automatically. Edit a row to describe or re-classify it; tick <em>Hide</em> to leave it out of the policy.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'efcc_tool' );
		echo '<input type="hidden" name="action" value="efcc_tool"><input type="hidden" name="tool" value="cookies">';
		echo '<table class="widefat efcc-table"><thead><tr><th>Cookie</th><th>Category</th><th>Provider</th><th>Purpose</th><th>Duration</th><th>Source</th><th>Hide</th><th>Delete</th></tr></thead><tbody>';
		if ( ! $reg ) { echo '<tr><td colspan="8">No cookies yet – run a scan.</td></tr>'; }
		foreach ( $reg as $name => $c ) {
			$n = 'cookies[' . $name . ']';
			echo '<tr><td><code>' . esc_html( $name ) . '</code>' . ( empty( $c['known'] ) && 'scan' === ( $c['source'] ?? '' ) && empty( $c['edited'] ) ? ' <span class="efcc-new">new – classify</span>' : '' ) . '</td>';
			echo '<td><select name="' . esc_attr( $n ) . '[category]">';
			foreach ( EFCC_Library::category_keys() as $k ) { echo '<option value="' . esc_attr( $k ) . '"' . selected( $c['category'], $k, false ) . '>' . esc_html( ucfirst( $k ) ) . '</option>'; }
			echo '</select></td>';
			echo '<td><input type="text" name="' . esc_attr( $n ) . '[provider]" value="' . esc_attr( $c['provider'] ?? '' ) . '"></td>';
			echo '<td><input type="text" name="' . esc_attr( $n ) . '[purpose]" value="' . esc_attr( $c['purpose'] ?? '' ) . '" class="regular-text"></td>';
			echo '<td><input type="text" name="' . esc_attr( $n ) . '[duration]" value="' . esc_attr( $c['duration'] ?? '' ) . '" class="small-text"></td>';
			echo '<td>' . esc_html( $c['source'] ?? '' ) . ( ! empty( $c['last_seen'] ) ? '<br><small>' . esc_html( wp_date( 'j M Y', $c['last_seen'] ) ) . '</small>' : '' ) . '</td>';
			echo '<td><input type="checkbox" name="' . esc_attr( $n ) . '[hidden]" value="1"' . checked( ! empty( $c['hidden'] ), true, false ) . '></td>';
			echo '<td><input type="checkbox" name="' . esc_attr( $n ) . '[delete]" value="1"></td></tr>';
		}
		echo '</tbody><tfoot><tr><td><input type="text" name="new[name]" placeholder="Add a cookie name (* = any)"></td><td><select name="new[category]">';
		foreach ( EFCC_Library::category_keys() as $k ) { echo '<option value="' . esc_attr( $k ) . '">' . esc_html( ucfirst( $k ) ) . '</option>'; }
		echo '</select></td><td><input type="text" name="new[provider]" placeholder="Provider"></td><td><input type="text" name="new[purpose]" placeholder="Purpose" class="regular-text"></td><td><input type="text" name="new[duration]" placeholder="Duration" class="small-text"></td><td colspan="3"></td></tr></tfoot></table>';
		submit_button( 'Save the register' );
		echo '</form></div>';
	}

	/**
	 * Pages the scanner opens.
	 *
	 * @return string[]
	 */
	private static function scan_urls() {
		$urls = array( home_url( '/' ) );
		foreach ( array( 'shop', 'cart', 'contact-us' ) as $slug ) {
			$p = get_page_by_path( $slug );
			if ( $p ) { $urls[] = get_permalink( $p ); }
		}
		$cat = get_terms( array( 'taxonomy' => 'product_cat', 'number' => 1, 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true ) );
		if ( $cat && ! is_wp_error( $cat ) ) { $urls[] = get_term_link( $cat[0] ); }
		foreach ( array( 'product', 'post', 'video' ) as $type ) {
			$p = get_posts( array( 'post_type' => $type, 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) );
			if ( $p ) { $urls[] = get_permalink( $p[0] ); }
		}
		$policy = EFCC_Settings::policy_page();
		if ( $policy ) { $urls[] = get_permalink( $policy ); }
		foreach ( EFCC_Settings::lines( (string) EFCC_Settings::get( 'scan', 'urls' ) ) as $u ) {
			if ( 0 === strpos( $u, home_url() ) ) { $urls[] = $u; }
		}
		return array_values( array_unique( array_filter( array_map( 'strval', $urls ) ) ) );
	}

	/** Google Consent Mode + GTM guide. */
	private static function tab_gcm() {
		$id = EFCC_Settings::get( 'gcm', 'gtm_id' );
		self::form_open( 'gcm' );
		echo '<table class="form-table" role="presentation">';
		self::row_check( 'gcm', 'enabled', 'Consent Mode v2', 'Send consent defaults and updates to Google tags (required for Google Ads / GA4 audiences in the UK and EEA)' );
		self::row_text( 'gcm', 'gtm_id', 'GTM container ID', 'e.g. GTM-ABC1234' );
		self::row_select( 'gcm', 'gtm_load', 'Load GTM', array( 'plugin' => 'By this plugin (recommended: right after the consent defaults)', 'external' => 'By another plugin or the theme (make sure it loads after this plugin\'s <head> script)', 'none' => 'Not used' ) );
		self::row_select( 'gcm', 'mode', 'Mode', array(
			'advanced' => 'Advanced – GTM always loads; Google tags send cookieless pings until consent',
			'basic'    => 'Basic – GTM loads only after Statistics or Marketing consent',
		), 'Advanced lets Google model conversions for visitors who decline; Basic sends nothing to Google before consent.' );
		self::row_text( 'gcm', 'wait', 'wait_for_update (ms)', 'How long tags wait for an update before using the defaults.', 'number', 'small-text' );
		self::row_check( 'gcm', 'redaction', 'ads_data_redaction', 'Redact ad click identifiers while ad_storage is denied' );
		self::row_check( 'gcm', 'passthrough', 'url_passthrough', 'Pass ad click information through URLs while cookies are denied' );
		self::row_select( 'gcm', 'functionality', 'functionality_storage', array( 'preferences' => 'Follows the Preferences category', 'granted' => 'Always granted (necessary functionality)' ) );
		echo '</table>';
		submit_button();
		echo '</form>';

		$map = EFCC_Frontend::gcm_map();
		echo '<div class="efcc-card efcc-guide"><h2>Signals sent</h2><table class="widefat striped"><thead><tr><th>Consent type</th><th>Default</th><th>Granted when</th></tr></thead><tbody>';
		foreach ( $map as $type => $cat ) {
			echo '<tr><td><code>' . esc_html( $type ) . '</code></td><td>' . ( 'necessary' === $cat ? 'granted' : 'denied' ) . '</td><td>' . esc_html( 'necessary' === $cat ? 'Always' : ucfirst( $cat ) . ' allowed' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p>dataLayer events: <code>efcc_consent_ready</code> (every page, with the stored choice), <code>efcc_consent_update</code> (when a choice is made or applied; variable <code>efcc_consent</code> holds <code>preferences</code>, <code>statistics</code>, <code>marketing</code>, <code>services</code>, <code>method</code>), and <code>efcc_statistics_granted</code> / <code>efcc_marketing_granted</code> / <code>efcc_preferences_granted</code>.</p>';
		echo '<h2>Setting up Google Tag Manager</h2><ol>';
		echo '<li>In GTM → <strong>Admin → Container settings</strong>, tick <em>Enable consent overview</em>.</li>';
		echo '<li><strong>Google tags</strong> (Google tag / GA4, Google Ads conversion & remarketing, Floodlight) have built-in consent checks: leave them on their usual triggers (<em>Initialization – All Pages</em> / <em>All Pages</em>). In the consent overview set them to <em>No additional consent required</em> – Consent Mode decides what they store.</li>';
		echo '<li><strong>Other tags</strong> (Meta Pixel, LinkedIn, TikTok, Clarity, Hotjar…): open the tag → <em>Advanced settings → Consent settings</em> → <em>Require additional consent for tag to fire</em> and add <code>ad_storage</code> (marketing tags) or <code>analytics_storage</code> (analytics tags). Fire them on <em>All Pages</em> plus a <em>Custom Event</em> trigger <code>efcc_consent_update</code>, so they also run right after a visitor accepts.</li>';
		echo '<li>Optional variable: <em>Data Layer Variable</em> <code>efcc_consent.marketing</code> (or <code>.statistics</code>) for trigger conditions.</li>';
		echo '<li>Do not add another consent template / CMP in GTM: this plugin sets the defaults before GTM loads.</li>';
		echo '<li>Check it in GTM <strong>Preview</strong>: the Consent tab shows <em>Default</em> = denied and <em>Update</em> = granted after you accept.</li>';
		echo '</ol>';
		if ( ! $id ) { echo '<p class="efcc-warn">No container ID yet: enter it above, or set "Load GTM" to "By another plugin".</p>'; }
		echo '</div>';
	}

	/** Blocking. */
	private static function tab_blocking() {
		self::form_open( 'blocking' );
		echo '<table class="form-table" role="presentation">';
		self::row_check( 'blocking', 'auto_block', 'Scripts', 'Block the scripts of enabled services (matched by their patterns) until their category is allowed' );
		self::row_check( 'blocking', 'iframes', 'Embeds', 'Replace YouTube, Vimeo, Google Maps… iframes with a placeholder until their category is allowed' );
		self::row_check( 'blocking', 'woo_attribution', 'WooCommerce order attribution', 'Set the sbjs_* source-tracking cookies only after Statistics consent' );
		self::row_textarea( 'blocking', 'exclude', 'Never block', 'One per line: text in a script or iframe that must never be blocked.' );
		echo '</table><p class="description">Scripts can also be blocked by hand: <code>&lt;script type="text/plain" data-efcc-category="marketing"&gt;…&lt;/script&gt;</code> runs only after Marketing consent (add <code>data-efcc-service="id"</code> to tie it to a service).</p>';
		submit_button();
		echo '</form>';
	}

	/** Consent log list. */
	private static function tab_log() {
		$page   = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$res    = EFCC_Log::query( $page, 50, $search );
		echo '<form method="get" class="efcc-log-search"><input type="hidden" name="page" value="' . esc_attr( self::SLUG ) . '"><input type="hidden" name="tab" value="log"><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Consent ID"> <button class="button">Search</button> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=efcc_export_log' ), 'efcc_export' ) ) . '">Export CSV</a></form>';
		echo '<p>' . (int) $res['total'] . ' records. Kept for ' . (int) EFCC_Settings::get( 'general', 'log_days' ) . ' days; IP addresses are truncated.</p>';
		echo '<table class="widefat striped"><thead><tr><th>Date (UTC)</th><th>Consent ID</th><th>Choice</th><th>Categories</th><th>Services off</th><th>Version</th><th>IP</th><th>Page</th></tr></thead><tbody>';
		foreach ( $res['rows'] as $r ) {
			$cats = json_decode( $r['categories'], true ) ?: array();
			$svc  = json_decode( $r['services'], true ) ?: array();
			$on   = array_keys( array_filter( $cats ) );
			$off  = array_keys( array_filter( $svc, static fn( $v ) => ! $v ) );
			echo '<tr><td>' . esc_html( $r['created'] ) . '</td><td><code>' . esc_html( $r['consent_id'] ) . '</code></td><td>' . esc_html( $r['method'] ) . '</td><td>' . esc_html( $on ? implode( ', ', $on ) : 'necessary only' ) . '</td><td>' . esc_html( implode( ', ', $off ) ) . '</td><td>' . (int) $r['version'] . '</td><td>' . esc_html( $r['ip'] ) . '</td><td>' . esc_html( wp_make_link_relative( $r['url'] ) ) . '</td></tr>';
		}
		if ( ! $res['rows'] ) { echo '<tr><td colspan="8">No records.</td></tr>'; }
		echo '</tbody></table>';
		$pages = (int) ceil( $res['total'] / 50 );
		if ( $pages > 1 ) {
			echo '<p class="efcc-pages">' . paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $pages ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/** Policy & general. */
	private static function tab_general() {
		self::form_open( 'general' );
		echo '<h2>General</h2><table class="form-table" role="presentation">';
		self::row_check( 'general', 'enabled', 'Banner', 'Show the cookie banner and apply consent' );
		self::row_text( 'general', 'consent_days', 'Remember choices for (days)', 'The ICO has no fixed limit; 6–12 months is common. We then ask again.', 'number', 'small-text' );
		self::row_text( 'general', 'version', 'Consent version', 'Raise it (or use Tools → "Ask everyone again") after adding cookies that need new consent: visitors are asked again.', 'number', 'small-text' );
		self::row_check( 'general', 'gpc', 'Global Privacy Control', 'Treat a browser\'s GPC signal as "Reject all" (no banner for those visitors)' );
		self::row_check( 'general', 'reload_on_revoke', 'Withdrawal', 'Reload the page after consent is withdrawn, so scripts already running stop' );
		self::row_check( 'general', 'show_floating', 'Cookie button', 'Show the cookie button after a choice is made (lets visitors change their mind at any time)' );
		self::row_select( 'general', 'floating_position', 'Cookie button position', array( 'left' => 'Bottom left', 'right' => 'Bottom right' ) );
		self::row_text( 'general', 'hide_banner_on', 'Hide the banner on pages', 'Page IDs, comma-separated (e.g. a landing page in an app). The cookie button still shows.' );
		self::row_check( 'general', 'analytics_exemption', 'UK analytics exemption', 'Treat first-party analytics as allowed without consent', 'The Data (Use and Access) Act 2025 adds a PECR exemption for some analytics cookies (clear information and an easy way to object required). Turn on only once that exemption is in force and your policy says so; Google Analytics is not first-party analytics for this purpose. Leave off if unsure.' );
		echo '</table><h2>Consent log</h2><table class="form-table" role="presentation">';
		self::row_check( 'general', 'log', 'Record choices', 'Keep proof of consent (consent ID, date, choice, truncated IP)' );
		self::row_text( 'general', 'log_days', 'Keep records for (days)', '', 'number', 'small-text' );
		echo '</table><h2>Cookie policy</h2><table class="form-table" role="presentation">';
		self::row_select( 'general', 'policy_page', 'Cookie policy page', self::pages_choices(), 'Linked from the banner. Put <code>[efcc_cookie_table]</code> (tables only) or <code>[efcc_cookie_policy]</code> (a complete UK policy) on it; <code>[efcc_settings_button]</code> adds a "change my settings" button. Links to <code>#efcc-settings</code> also open the preferences.' );
		self::row_select( 'general', 'privacy_page', 'Privacy policy page', self::pages_choices() );
		self::row_text( 'general', 'company', 'Company name (policy)', 'Defaults to the site title.' );
		self::row_text( 'general', 'contact_email', 'Contact email (policy)', 'Defaults to the admin email.', 'email' );
		echo '</table>';
		submit_button();
		echo '</form>';
	}

	/** Tools. */
	private static function tab_tools() {
		$action = esc_url( admin_url( 'admin-post.php' ) );
		echo '<div class="efcc-grid">';
		echo '<section class="efcc-card"><h2>Ask everyone again</h2><p>Raises the consent version: every visitor sees the banner again. Use it after adding cookies or services that need new consent.</p><form method="post" action="' . $action . '">';
		wp_nonce_field( 'efcc_tool' );
		echo '<input type="hidden" name="action" value="efcc_tool"><input type="hidden" name="tool" value="renew">';
		submit_button( 'Ask everyone again (version ' . ( (int) EFCC_Settings::get( 'general', 'version' ) + 1 ) . ')', 'secondary', 'submit', false );
		echo '</form></section>';
		echo '<section class="efcc-card"><h2>Export settings</h2><p>All settings and the cookie register as JSON.</p><textarea class="large-text code" rows="6" readonly onclick="this.select()">' . esc_textarea( wp_json_encode( array( 'settings' => get_option( EFCC_Settings::OPTION, array() ), 'cookies' => EFCC_Cookies::register() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</textarea></section>';
		echo '<section class="efcc-card"><h2>Import settings</h2><form method="post" action="' . $action . '">';
		wp_nonce_field( 'efcc_tool' );
		echo '<input type="hidden" name="action" value="efcc_tool"><input type="hidden" name="tool" value="import"><textarea name="json" class="large-text code" rows="6" placeholder="Paste exported JSON"></textarea>';
		submit_button( 'Import', 'secondary', 'submit', false );
		echo '</form></section>';
		echo '<section class="efcc-card"><h2>Reset</h2><p>Back to the default settings (the consent log and cookie register are kept).</p><form method="post" action="' . $action . '" onsubmit="return confirm(\'Reset all settings?\')">';
		wp_nonce_field( 'efcc_tool' );
		echo '<input type="hidden" name="action" value="efcc_tool"><input type="hidden" name="tool" value="reset">';
		submit_button( 'Reset settings', 'delete', 'submit', false );
		echo '</form></section></div>';
	}

	/* —— handlers —— */

	/** Save a settings tab. */
	public static function save() {
		check_admin_referer( 'efcc_save' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
		$tab  = sanitize_key( $_POST['tab'] ?? '' );
		$data = isset( $_POST['efcc'] ) ? (array) wp_unslash( $_POST['efcc'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per field.
		$map  = array(
			'banner'     => array( 'appearance', 'texts' ),
			'categories' => array( 'categories' ),
			'services'   => array( 'services' ),
			'gcm'        => array( 'gcm' ),
			'blocking'   => array( 'blocking' ),
			'general'    => array( 'general' ),
			'scan'       => array( 'scan' ),
		);
		foreach ( $map[ $tab ] ?? array() as $section ) {
			EFCC_Settings::save_section( $section, $data[ $section ] ?? array() );
		}
		wp_safe_redirect( self::url( 'scan' === $tab ? 'cookies' : $tab, array( 'efcc_msg' => rawurlencode( 'Settings saved.' ) ) ) );
		exit;
	}

	/** Tools and the cookie register. */
	public static function tool() {
		check_admin_referer( 'efcc_tool' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
		$tool = sanitize_key( $_POST['tool'] ?? '' );
		$tab  = 'tools';
		$msg  = '';
		switch ( $tool ) {
			case 'renew':
				$all                       = (array) get_option( EFCC_Settings::OPTION, array() );
				$all['general']            = wp_parse_args( (array) ( $all['general'] ?? array() ), EFCC_Settings::defaults()['general'] );
				$all['general']['version'] = (int) $all['general']['version'] + 1;
				update_option( EFCC_Settings::OPTION, $all );
				$msg = 'Consent version raised to ' . $all['general']['version'] . ': visitors will be asked again.';
				break;
			case 'import':
				$json = json_decode( wp_unslash( $_POST['json'] ?? '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded then sanitised.
				if ( is_array( $json ) && isset( $json['settings'] ) ) {
					EFCC_Settings::import( (array) $json['settings'] );
					if ( isset( $json['cookies'] ) && is_array( $json['cookies'] ) ) { self::save_register( $json['cookies'], array() ); }
					$msg = 'Settings imported.';
				} else {
					$msg = 'That is not an export of this plugin.';
				}
				break;
			case 'reset':
				delete_option( EFCC_Settings::OPTION );
				$msg = 'Settings reset.';
				break;
			case 'cookies':
				$rows = isset( $_POST['cookies'] ) ? (array) wp_unslash( $_POST['cookies'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$new  = isset( $_POST['new'] ) ? (array) wp_unslash( $_POST['new'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				self::save_register( $rows, $new, true );
				$tab = 'cookies';
				$msg = 'Cookie register saved.';
				break;
		}
		wp_safe_redirect( self::url( $tab, array( 'efcc_msg' => rawurlencode( $msg ) ) ) );
		exit;
	}

	/**
	 * Save edits to the register (or an imported one).
	 *
	 * @param array $rows  name => fields.
	 * @param array $new   New row.
	 * @param bool  $merge Edit the existing register (true) or replace it.
	 */
	private static function save_register( $rows, $new, $merge = false ) {
		$reg = $merge ? EFCC_Cookies::register() : array();
		foreach ( $rows as $name => $f ) {
			$name = substr( preg_replace( '/[^\w.\-*]/', '', (string) $name ), 0, 120 );
			if ( '' === $name ) { continue; }
			if ( ! empty( $f['delete'] ) ) { unset( $reg[ $name ] ); continue; }
			$old          = $reg[ $name ] ?? array( 'name' => $name, 'source' => 'manual', 'first_seen' => time() );
			$cat          = sanitize_key( $f['category'] ?? 'necessary' );
			$reg[ $name ] = array_merge( $old, array(
				'category' => in_array( $cat, EFCC_Library::category_keys(), true ) ? $cat : 'necessary',
				'provider' => sanitize_text_field( $f['provider'] ?? '' ),
				'purpose'  => sanitize_text_field( $f['purpose'] ?? '' ),
				'duration' => sanitize_text_field( $f['duration'] ?? '' ),
				'hidden'   => empty( $f['hidden'] ) ? 0 : 1,
				'edited'   => $merge ? 1 : ( $old['edited'] ?? 0 ),
			) );
		}
		$n = substr( preg_replace( '/[^\w.\-*]/', '', (string) ( $new['name'] ?? '' ) ), 0, 120 );
		if ( '' !== $n ) {
			$cat       = sanitize_key( $new['category'] ?? 'necessary' );
			$reg[ $n ] = array(
				'name'       => $n,
				'category'   => in_array( $cat, EFCC_Library::category_keys(), true ) ? $cat : 'necessary',
				'provider'   => sanitize_text_field( $new['provider'] ?? '' ),
				'purpose'    => sanitize_text_field( $new['purpose'] ?? '' ),
				'duration'   => sanitize_text_field( $new['duration'] ?? '' ),
				'source'     => 'manual',
				'edited'     => 1,
				'first_seen' => time(),
			);
		}
		EFCC_Cookies::save( $reg );
	}

	/** Scanner frame results. */
	public static function ajax_scan_save() {
		check_ajax_referer( 'efcc_scan', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( null, 403 ); }
		$names = array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['names'] ?? array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$url   = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$new   = EFCC_Cookies::record_scan( $names, $url );
		if ( ! empty( $_POST['done'] ) ) {
			$all                      = (array) get_option( EFCC_Settings::OPTION, array() );
			$all['scan']              = wp_parse_args( (array) ( $all['scan'] ?? array() ), EFCC_Settings::defaults()['scan'] );
			$all['scan']['last_scan'] = time();
			update_option( EFCC_Settings::OPTION, $all );
		}
		wp_send_json_success( array( 'new' => $new ) );
	}

	/** Consent log as CSV. */
	public static function export_log() {
		check_admin_referer( 'efcc_export' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consent-log-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'created_utc', 'consent_id', 'method', 'categories', 'services', 'version', 'ip', 'user_agent', 'url' ) );
		$page = 1;
		do {
			$res = EFCC_Log::query( $page++, 500 );
			foreach ( $res['rows'] as $r ) {
				fputcsv( $out, array( $r['created'], $r['consent_id'], $r['method'], $r['categories'], $r['services'], $r['version'], $r['ip'], $r['user_agent'], $r['url'] ) );
			}
		} while ( count( $res['rows'] ) === 500 );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
