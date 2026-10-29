<?php
/**
 * Front end: Consent Mode defaults and GTM in <head>, the banner, the
 * preferences dialog and the cookie button, blocking of scripts and iframes
 * of services that need consent, and WooCommerce order attribution.
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Visitor-facing parts.
 */
class EFCC_Frontend {

	const COOKIE = 'efcc_consent';

	/** Hooks. */
	public static function init() {
		if ( ! EFCC_Settings::get( 'general', 'enabled' ) ) { return; }
		add_action( 'wp_head', array( __CLASS__, 'head' ), -1000 );
		add_action( 'wp_footer', array( __CLASS__, 'footer' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 1 );
		if ( EFCC_Settings::get( 'blocking', 'woo_attribution' ) ) {
			// sbjs_* cookies only after consent: the banner script turns tracking on.
			add_filter( 'wc_order_attribution_allow_tracking', '__return_false' );
		}
	}

	/** Whether this is a normal front-end page view. */
	private static function applies() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_embed() ) { return false; }
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return false; }
		return true;
	}

	/** Scan mode: an administrator's scanner frame (everything allowed, nothing logged). */
	private static function is_scan() {
		return isset( $_GET['efcc_scan'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Consent Mode signal map: category → Google consent types.
	 *
	 * @return array
	 */
	public static function gcm_map() {
		$func = 'granted' === EFCC_Settings::get( 'gcm', 'functionality' ) ? 'necessary' : 'preferences';
		return array(
			'ad_storage'              => 'marketing',
			'ad_user_data'            => 'marketing',
			'ad_personalization'      => 'marketing',
			'analytics_storage'       => EFCC_Settings::get( 'general', 'analytics_exemption' ) ? 'necessary' : 'statistics',
			'functionality_storage'   => $func,
			'personalization_storage' => 'preferences',
			'security_storage'        => 'necessary',
		);
	}

	/**
	 * Settings the browser script needs.
	 *
	 * @return array
	 */
	private static function config() {
		$g        = EFCC_Settings::get( 'general' );
		$services = array();
		foreach ( EFCC_Settings::services() as $id => $s ) {
			$services[ $id ] = array( 'name' => $s['name'], 'category' => $s['category'], 'cookies' => $s['cookies'] );
		}
		$policy = EFCC_Settings::policy_page();
		return array(
			'cookie'      => self::COOKIE,
			'version'     => (int) $g['version'],
			'days'        => (int) $g['consent_days'],
			'categories'  => array_keys( EFCC_Settings::categories() ),
			'services'    => $services,
			'gcm'         => (bool) EFCC_Settings::get( 'gcm', 'enabled' ),
			'gcmMap'      => self::gcm_map(),
			'gtm'         => self::gtm_snippet_needed() ? EFCC_Settings::get( 'gcm', 'gtm_id' ) : '',
			'gtmMode'     => EFCC_Settings::get( 'gcm', 'mode' ),
			'log'         => (bool) $g['log'],
			'logUrl'      => esc_url_raw( rest_url( 'efcc/v1/consent' ) ),
			'gpc'         => (bool) $g['gpc'],
			'reload'      => (bool) $g['reload_on_revoke'],
			'wooAttr'     => (bool) EFCC_Settings::get( 'blocking', 'woo_attribution' ),
			'scan'        => self::is_scan(),
			'preview'     => isset( $_GET['efcc_preview'] ) && current_user_can( 'manage_options' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'hide'        => self::banner_hidden_here(),
			'policyUrl'   => $policy ? get_permalink( $policy ) : '',
			'domain'      => wp_parse_url( home_url(), PHP_URL_HOST ),
			'texts'       => array(
				'placeholder' => EFCC_Settings::get( 'texts', 'placeholder' ),
				'button'      => EFCC_Settings::get( 'texts', 'placeholder_button' ),
			),
			'catLabels'   => wp_list_pluck( EFCC_Settings::categories(), 'label' ),
		);
	}

	/** Whether the plugin itself loads the GTM container. */
	private static function gtm_snippet_needed() {
		return 'plugin' === EFCC_Settings::get( 'gcm', 'gtm_load' ) && '' !== (string) EFCC_Settings::get( 'gcm', 'gtm_id' );
	}

	/** Pages listed under "Hide the banner on" (the policy page keeps it). */
	private static function banner_hidden_here() {
		$ids = array_map( 'intval', EFCC_Settings::lines( str_replace( ',', "\n", (string) EFCC_Settings::get( 'general', 'hide_banner_on' ) ) ) );
		return $ids && is_singular() && in_array( (int) get_queried_object_id(), $ids, true );
	}

	/**
	 * <head>, first thing: Consent Mode defaults, the stored choice applied
	 * at once, then GTM (advanced mode) — so no tag fires before it knows.
	 */
	public static function head() {
		if ( ! self::applies() ) { return; }
		$cfg  = self::config();
		$gcm  = EFCC_Settings::get( 'gcm' );
		$def  = array();
		foreach ( $cfg['gcmMap'] as $type => $cat ) {
			$def[ $type ] = 'necessary' === $cat ? 'granted' : 'denied';
		}
		if ( $gcm['wait'] ) { $def['wait_for_update'] = (int) $gcm['wait']; }
		?>
<script id="efcc-config">window.efccConfig=<?php echo wp_json_encode( $cfg ); ?>;</script>
<script id="efcc-consent-mode">
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
<?php if ( $cfg['gcm'] ) : ?>
gtag('consent','default',<?php echo wp_json_encode( $def ); ?>);
<?php if ( $gcm['redaction'] ) : ?>gtag('set','ads_data_redaction',true);
<?php endif; ?>
<?php if ( $gcm['passthrough'] ) : ?>gtag('set','url_passthrough',true);
<?php endif; ?>
<?php endif; ?>
(function(c){
	// The stored choice, applied before any tag loads.
	var m=document.cookie.match(new RegExp('(?:^|; )'+c.cookie+'=([^;]*)')),s=null;
	try{s=m?JSON.parse(decodeURIComponent(m[1])):null;}catch(e){s=null;}
	if(s&&s.v!==c.version){s=null;}
	if(c.scan){s={cats:{preferences:1,statistics:1,marketing:1},svc:{}};}
	window.efccStored=s;
	var granted=function(cat){return cat==='necessary'||!!(s&&s.cats&&s.cats[cat]);};
	if(c.gcm&&s){var u={};for(var t in c.gcmMap){u[t]=granted(c.gcmMap[t])?'granted':'denied';}gtag('consent','update',u);}
	dataLayer.push({event:'efcc_consent_ready',efcc_consent:{preferences:granted('preferences'),statistics:granted('statistics'),marketing:granted('marketing'),given:!!s}});
	if(c.gtm&&(c.gtmMode==='advanced'||granted('statistics')||granted('marketing'))){
		(function(w,d,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName('script')[0],j=d.createElement('script');j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i;f.parentNode.insertBefore(j,f);window.efccGtmLoaded=true;})(window,document,'dataLayer',c.gtm);
	}
})(window.efccConfig);
</script>
		<?php
	}

	/** Banner styles and script. */
	public static function assets() {
		if ( ! self::applies() ) { return; }
		wp_enqueue_style( 'efcc', EFCC_URL . 'assets/efcc.css', array(), EFCC_VERSION );
		wp_add_inline_style( 'efcc', self::css_vars() );
		wp_enqueue_script( 'efcc', EFCC_URL . 'assets/efcc.js', array(), EFCC_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}

	/**
	 * CSS variables from the Appearance settings.
	 *
	 * @return string
	 */
	private static function css_vars() {
		$a = EFCC_Settings::get( 'appearance' );
		return sprintf(
			':root{--efcc-primary:%s;--efcc-primary-text:%s;--efcc-bg:%s;--efcc-text:%s;--efcc-radius:%dpx;--efcc-font:%dpx;--efcc-max:%dpx;--efcc-z:%d;--efcc-shadow:%s}',
			esc_attr( $a['primary'] ), esc_attr( $a['primary_text'] ), esc_attr( $a['background'] ), esc_attr( $a['text'] ),
			(int) $a['radius'], (int) $a['font_size'], (int) $a['max_width'], (int) $a['z_index'],
			$a['shadow'] ? '0 10px 40px rgba(0,0,0,.18)' : 'none'
		);
	}

	/** Banner, preferences dialog and cookie button. */
	public static function footer() {
		if ( ! self::applies() ) { return; }
		$t    = EFCC_Settings::get( 'texts' );
		$a    = EFCC_Settings::get( 'appearance' );
		$g    = EFCC_Settings::get( 'general' );
		$cats = EFCC_Settings::categories();
		$svcs = EFCC_Settings::services();
		$by   = array();
		foreach ( $svcs as $id => $s ) { $by[ $s['category'] ][ $id ] = $s; }
		$cookies = EFCC_Cookies::grouped();
		$policy  = EFCC_Settings::policy_page();
		$link    = $policy ? '<a href="' . esc_url( get_permalink( $policy ) ) . '">' . esc_html( $t['policy_link'] ) . '</a>' : esc_html( $t['policy_link'] );
		$message = str_replace( '{policy}', $link, esc_html( $t['message'] ) );
		$classes = 'efcc-banner efcc-banner--' . sanitize_html_class( $a['layout'] ) . ' efcc-buttons--' . sanitize_html_class( $a['button_style'] ) . ( ! empty( $a['uppercase'] ) ? ' efcc-buttons--upper' : '' );
		?>
<div id="efcc-banner" class="<?php echo esc_attr( $classes ); ?>" role="region" aria-label="<?php echo esc_attr( $t['title'] ); ?>" hidden>
	<div class="efcc-banner__inner">
		<div class="efcc-banner__text">
			<?php if ( '' !== trim( $t['title'] ) ) : ?><p class="efcc-banner__title" id="efcc-banner-title"><?php echo esc_html( $t['title'] ); ?></p><?php endif; ?>
			<p class="efcc-banner__message"><?php echo wp_kses( $message, array( 'a' => array( 'href' => array() ) ) ); ?></p>
		</div>
		<div class="efcc-banner__buttons">
			<!-- "Reject all" stays in the middle. -->
			<button type="button" class="efcc-btn efcc-btn--secondary" data-efcc="manage"><?php echo esc_html( $t['manage'] ); ?></button>
			<button type="button" class="efcc-btn efcc-btn--secondary" data-efcc="reject"><?php echo esc_html( $t['reject'] ); ?></button>
			<button type="button" class="efcc-btn efcc-btn--primary" data-efcc="accept"><?php echo esc_html( $t['accept'] ); ?></button>
		</div>
	</div>
</div>

<div id="efcc-modal" class="efcc-modal efcc-buttons--<?php echo esc_attr( $a['button_style'] ); ?><?php echo ! empty( $a['uppercase'] ) ? ' efcc-buttons--upper' : ''; ?>" hidden>
	<div class="efcc-modal__backdrop" data-efcc="close"></div>
	<div class="efcc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="efcc-modal-title" tabindex="-1">
		<div class="efcc-modal__head">
			<h2 id="efcc-modal-title"><?php echo esc_html( $t['modal_title'] ); ?></h2>
			<button type="button" class="efcc-modal__close" data-efcc="close" aria-label="Close">&times;</button>
		</div>
		<div class="efcc-modal__body">
			<p><?php echo esc_html( $t['modal_intro'] ); ?><?php echo $policy ? ' ' . wp_kses( $link, array( 'a' => array( 'href' => array() ) ) ) . '.' : ''; ?></p>
			<?php foreach ( $cats as $key => $c ) :
				$locked = 'necessary' === $key;
				$list   = $by[ $key ] ?? array();
				$cl     = $cookies[ $key ] ?? array();
				?>
			<section class="efcc-cat" data-category="<?php echo esc_attr( $key ); ?>">
				<div class="efcc-cat__head">
					<button type="button" class="efcc-cat__toggle" aria-expanded="false" aria-controls="efcc-cat-<?php echo esc_attr( $key ); ?>">
						<span class="efcc-chevron" aria-hidden="true"></span><?php echo esc_html( $c['label'] ); ?>
					</button>
					<?php if ( $locked ) : ?>
					<span class="efcc-always"><?php echo esc_html( $t['always_active'] ); ?></span>
					<?php else : ?>
					<label class="efcc-switch"><input type="checkbox" data-efcc-cat="<?php echo esc_attr( $key ); ?>"><span class="efcc-switch__ui" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html( $c['label'] ); ?></span></label>
					<?php endif; ?>
				</div>
				<div class="efcc-cat__body" id="efcc-cat-<?php echo esc_attr( $key ); ?>" hidden>
					<p><?php echo esc_html( $c['description'] ); ?></p>
					<?php if ( $list ) : ?>
					<p class="efcc-sub"><?php echo esc_html( $t['services_label'] ); ?></p>
					<ul class="efcc-services">
						<?php foreach ( $list as $id => $s ) : ?>
						<li>
							<div class="efcc-service">
								<span><strong><?php echo esc_html( $s['name'] ); ?></strong><?php echo $s['provider'] ? ' <small>· ' . esc_html( $s['provider'] ) . '</small>' : ''; ?></span>
								<?php if ( ! $locked ) : ?>
								<label class="efcc-switch efcc-switch--small"><input type="checkbox" data-efcc-svc="<?php echo esc_attr( $id ); ?>" data-efcc-svc-cat="<?php echo esc_attr( $key ); ?>"><span class="efcc-switch__ui" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html( $s['name'] ); ?></span></label>
								<?php endif; ?>
							</div>
							<?php if ( $s['purpose'] ) : ?><p class="efcc-service__purpose"><?php echo esc_html( $s['purpose'] ); ?><?php echo $s['privacy'] ? ' <a href="' . esc_url( $s['privacy'] ) . '" target="_blank" rel="noopener">Privacy policy</a>' : ''; ?></p><?php endif; ?>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
					<?php if ( $cl ) : ?>
					<details class="efcc-cookies"><summary><?php echo esc_html( $t['cookies_label'] ); ?> (<?php echo count( $cl ); ?>)</summary>
						<table><thead><tr><th>Cookie</th><th>Purpose</th><th>Duration</th></tr></thead><tbody>
						<?php foreach ( $cl as $ck ) : ?>
							<tr><td><code><?php echo esc_html( $ck['name'] ); ?></code></td><td><?php echo esc_html( $ck['purpose'] ?? '' ); ?></td><td><?php echo esc_html( $ck['duration'] ?? '' ); ?></td></tr>
						<?php endforeach; ?>
						</tbody></table>
					</details>
					<?php endif; ?>
				</div>
			</section>
			<?php endforeach; ?>
			<p class="efcc-consent-id" hidden><?php echo esc_html( $t['consent_id'] ); ?>: <code data-efcc-id></code></p>
		</div>
		<div class="efcc-modal__foot">
			<!-- "Reject all" stays in the middle. -->
			<button type="button" class="efcc-btn efcc-btn--secondary" data-efcc="save"><?php echo esc_html( $t['save'] ); ?></button>
			<button type="button" class="efcc-btn efcc-btn--secondary" data-efcc="reject"><?php echo esc_html( $t['reject'] ); ?></button>
			<button type="button" class="efcc-btn efcc-btn--primary" data-efcc="accept"><?php echo esc_html( $t['accept'] ); ?></button>
		</div>
	</div>
</div>

<?php if ( $g['show_floating'] ) : ?>
<button type="button" id="efcc-float" class="efcc-float efcc-float--<?php echo esc_attr( $g['floating_position'] ); ?> efcc-float--<?php echo esc_attr( $a['icon_style'] ); ?>" data-efcc="manage" aria-label="<?php echo esc_attr( $t['floating_label'] ); ?>" title="<?php echo esc_attr( $t['floating_label'] ); ?>" hidden>
	<?php echo self::cookie_svg( $a['icon_style'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
</button>
<?php endif; ?>
		<?php
	}

	/**
	 * The cookie icon.
	 *
	 * @param string $style colorful | brand | mono.
	 * @return string
	 */
	public static function cookie_svg( $style ) {
		$fill  = array( 'colorful' => '#f0a94a', 'brand' => 'var(--efcc-primary)', 'mono' => 'currentColor' )[ $style ] ?? '#f0a94a';
		$edge  = 'colorful' === $style ? '#c97f22' : 'rgba(0,0,0,.15)';
		$chips = array(
			array( 11, 13, 2.3, 'colorful' === $style ? '#6b3a17' : '#fff' ),
			array( 20, 10, 1.9, 'colorful' === $style ? '#e5484d' : '#fff' ),
			array( 22, 20, 2.2, 'colorful' === $style ? '#6b3a17' : '#fff' ),
			array( 13, 22, 1.8, 'colorful' === $style ? '#2f9e44' : '#fff' ),
			array( 17, 16, 1.6, 'colorful' === $style ? '#3b82f6' : '#fff' ),
			array( 8, 18, 1.3, 'colorful' === $style ? '#f2c94c' : '#fff' ),
		);
		$svg = '<svg viewBox="0 0 32 32" width="36" height="36" aria-hidden="true" focusable="false">'
			. '<path d="M16 2.5a13.5 13.5 0 1 0 13.4 15.1 4 4 0 0 1-4.6-4.3 4 4 0 0 1-4.3-4.7A4 4 0 0 1 16.6 2.5z" fill="' . $fill . '" stroke="' . $edge . '" stroke-width="1.2"/>';
		foreach ( $chips as $c ) {
			$svg .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="' . $c[2] . '" fill="' . $c[3] . '"/>';
		}
		return $svg . '</svg>';
	}

	/* —— Blocking of scripts and iframes —— */

	/** Buffer the page for blocking. */
	public static function start_buffer() {
		if ( self::applies() && ( EFCC_Settings::get( 'blocking', 'auto_block' ) || EFCC_Settings::get( 'blocking', 'iframes' ) ) ) {
			ob_start( array( __CLASS__, 'block' ) );
		}
	}

	/**
	 * Services that need consent, with their patterns.
	 *
	 * @return array[]
	 */
	private static function blockable() {
		$out = array();
		foreach ( EFCC_Settings::services() as $id => $s ) {
			if ( 'necessary' !== $s['category'] && $s['patterns'] ) { $out[ $id ] = $s; }
		}
		return $out;
	}

	/**
	 * The service a piece of code or URL belongs to.
	 *
	 * @param string $haystack Code or URL.
	 * @return array|null
	 */
	private static function match( $haystack ) {
		foreach ( self::blockable() as $s ) {
			foreach ( $s['patterns'] as $p ) {
				if ( '' !== $p && false !== stripos( $haystack, $p ) ) { return $s; }
			}
		}
		return null;
	}

	/**
	 * Rewrite scripts / iframes of services that need consent.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public static function block( $html ) {
		if ( '' === $html || false === stripos( $html, '<html' ) || ! self::blockable() ) { return $html; }
		$exclude = EFCC_Settings::lines( (string) EFCC_Settings::get( 'blocking', 'exclude' ) );

		if ( EFCC_Settings::get( 'blocking', 'auto_block' ) ) {
			$html = preg_replace_callback( '#<script\b([^>]*)>(.*?)</script>#is', static function ( $m ) use ( $exclude ) {
				$attrs = $m[1];
				$code  = $m[2];
				if ( preg_match( '/\bid=["\']efcc-/i', $attrs ) || false !== stripos( $attrs, 'data-efcc' ) ) { return $m[0]; }
				if ( preg_match( '/\btype=["\'](application\/(ld\+)?json|text\/template|text\/plain)/i', $attrs ) ) { return $m[0]; }
				foreach ( $exclude as $x ) {
					if ( false !== stripos( $attrs . $code, $x ) ) { return $m[0]; }
				}
				$s = self::match( $attrs . ' ' . $code );
				if ( ! $s ) { return $m[0]; }
				$type  = preg_match( '/\btype=["\']([^"\']+)["\']/i', $attrs, $t ) ? $t[1] : 'text/javascript';
				$attrs = preg_replace( '/\s*\btype=["\'][^"\']*["\']/i', '', $attrs );
				return '<script type="text/plain" data-efcc-type="' . esc_attr( $type ) . '" data-efcc-category="' . esc_attr( $s['category'] ) . '" data-efcc-service="' . esc_attr( $s['id'] ) . '"' . $attrs . '>' . $code . '</script>';
			}, $html );
		}

		if ( EFCC_Settings::get( 'blocking', 'iframes' ) ) {
			$html = preg_replace_callback( '#<iframe\b([^>]*)\bsrc=(["\'])([^"\']+)\2([^>]*)>#i', static function ( $m ) use ( $exclude ) {
				$src = $m[3];
				foreach ( $exclude as $x ) {
					if ( false !== stripos( $src, $x ) ) { return $m[0]; }
				}
				$s = self::match( $src );
				if ( ! $s ) { return $m[0]; }
				$cat  = EFCC_Settings::categories()[ $s['category'] ]['label'] ?? $s['category'];
				$text = str_replace( array( '{service}', '{category}' ), array( $s['name'], strtolower( $cat ) ), (string) EFCC_Settings::get( 'texts', 'placeholder' ) );
				return '<span class="efcc-placeholder" data-efcc-service="' . esc_attr( $s['id'] ) . '" data-efcc-category="' . esc_attr( $s['category'] ) . '">'
					. '<span class="efcc-placeholder__text">' . esc_html( $text ) . '</span>'
					. '<button type="button" class="efcc-btn efcc-btn--primary" data-efcc-allow="' . esc_attr( $s['category'] ) . '" data-efcc-allow-svc="' . esc_attr( $s['id'] ) . '">' . esc_html( EFCC_Settings::get( 'texts', 'placeholder_button' ) ) . '</button>'
					. '</span><iframe' . $m[1] . ' data-efcc-src="' . esc_attr( $src ) . '" data-efcc-category="' . esc_attr( $s['category'] ) . '" data-efcc-service="' . esc_attr( $s['id'] ) . '" src="about:blank" hidden' . $m[4] . '>';
			}, $html );
		}
		return $html;
	}
}
