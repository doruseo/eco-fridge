<?php
/**
 * Cookie register and the cookie policy.
 *
 * The register (`efcc_cookies` option) holds what the scanner found and what
 * was added or edited by hand. The list shown to visitors merges it with the
 * cookies declared by the enabled services and the site's necessary cookies.
 *
 * Shortcodes:
 *   [efcc_cookie_table category="statistics"]  cookie tables (all categories by default)
 *   [efcc_cookie_policy]                        a complete UK cookie policy, tables included
 *   [efcc_settings_button label="…"]            button that opens the preferences
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Cookies.
 */
class EFCC_Cookies {

	const OPTION  = 'efcc_cookies';
	const UPDATED = 'efcc_cookies_updated';

	/** Hooks. */
	public static function init() {
		add_shortcode( 'efcc_cookie_table', array( __CLASS__, 'shortcode_table' ) );
		add_shortcode( 'efcc_cookie_policy', array( __CLASS__, 'shortcode_policy' ) );
		add_shortcode( 'efcc_settings_button', array( __CLASS__, 'shortcode_button' ) );
	}

	/**
	 * The stored register.
	 *
	 * @return array name => entry
	 */
	public static function register() {
		return (array) get_option( self::OPTION, array() );
	}

	/**
	 * Save the register and remember when it changed.
	 *
	 * @param array $reg Register.
	 */
	public static function save( $reg ) {
		ksort( $reg, SORT_NATURAL | SORT_FLAG_CASE );
		update_option( self::OPTION, $reg, false );
		update_option( self::UPDATED, time(), false );
	}

	/**
	 * Add cookies found by the scanner.
	 *
	 * @param string[] $names Cookie names.
	 * @param string   $url   Page they were seen on.
	 * @return int New cookies.
	 */
	public static function record_scan( $names, $url ) {
		$reg = self::register();
		$new = 0;
		foreach ( array_unique( $names ) as $name ) {
			$name = substr( preg_replace( '/[^\w.\-*]/', '', (string) $name ), 0, 120 );
			if ( '' === $name ) { continue; }
			if ( isset( $reg[ $name ] ) ) {
				$reg[ $name ]['last_seen'] = time();
				continue;
			}
			$k = EFCC_Library::match_cookie( $name );
			// Unknown cookies are listed under Necessary until someone classifies them.
			$reg[ $name ] = array(
				'name'       => $name,
				'category'   => $k['category'] ?? 'necessary',
				'provider'   => $k['provider'] ?? '',
				'purpose'    => $k['purpose'] ?? '',
				'duration'   => $k['duration'] ?? '',
				'service'    => $k['service'] ?? '',
				'source'     => 'scan',
				'known'      => $k ? 1 : 0,
				'first_seen' => time(),
				'last_seen'  => time(),
				'page'       => esc_url_raw( $url ),
			);
			$new++;
		}
		self::save( $reg );
		return $new;
	}

	/**
	 * Every cookie to declare, grouped by category.
	 *
	 * @return array category => list of entries
	 */
	public static function grouped() {
		$days = (int) EFCC_Settings::get( 'general', 'consent_days' );
		$list = array();

		// 1. Necessary cookies of the site itself (always there, even if a scan
		//    never saw them – e.g. logged-in cookies).
		foreach ( array( 'efcc_consent', 'wordpress_test_cookie', 'wordpress_logged_in_*', 'wp_woocommerce_session_*', 'woocommerce_cart_hash', 'woocommerce_items_in_cart' ) as $n ) {
			$k = EFCC_Library::match_cookie( $n );
			if ( $k ) { $k['name'] = $n; $list[ $n ] = $k; }
		}
		// 2. Cookies declared by the enabled services.
		foreach ( EFCC_Settings::services() as $id => $s ) {
			foreach ( $s['cookies'] as $n ) {
				$k            = EFCC_Library::match_cookie( $n );
				$list[ $n ] = array(
					'name'     => $n,
					'category' => $s['category'],
					'provider' => $k['provider'] ?? $s['provider'],
					'purpose'  => $k['purpose'] ?? $s['purpose'],
					'duration' => $k['duration'] ?? '',
					'service'  => $id,
				);
			}
		}
		// 3. The register (scan + manual), which wins: it may hold edits.
		foreach ( self::register() as $n => $c ) {
			if ( ! empty( $c['hidden'] ) ) { unset( $list[ $n ] ); continue; }
			// A scanned cookie covered by a declared pattern adds nothing new.
			if ( 'scan' === ( $c['source'] ?? '' ) && empty( $c['edited'] ) ) {
				foreach ( array_keys( $list ) as $pattern ) {
					if ( false !== strpos( $pattern, '*' ) && EFCC_Library::wildcard( $pattern, $n ) ) { continue 2; }
				}
			}
			$list[ $n ] = $c;
		}

		$out = array_fill_keys( array_keys( EFCC_Settings::categories() ), array() );
		foreach ( $list as $c ) {
			$cat = isset( $out[ $c['category'] ] ) ? $c['category'] : 'necessary';
			$c['duration'] = str_replace( '%days%', (string) $days, (string) ( $c['duration'] ?? '' ) );
			$out[ $cat ][] = $c;
		}
		foreach ( $out as $k => $rows ) {
			usort( $rows, static fn( $a, $b ) => strnatcasecmp( $a['name'], $b['name'] ) );
			$out[ $k ] = $rows;
		}
		return $out;
	}

	/**
	 * [efcc_cookie_table category="…"].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode_table( $atts ) {
		$atts = shortcode_atts( array( 'category' => '' ), $atts );
		$cats = EFCC_Settings::categories();
		$all  = self::grouped();
		$html = '';
		foreach ( $all as $cat => $rows ) {
			if ( '' !== $atts['category'] && $cat !== $atts['category'] ) { continue; }
			$html .= '<h3 class="efcc-table-title">' . esc_html( $cats[ $cat ]['label'] ?? ucfirst( $cat ) ) . '</h3>';
			$html .= '<p class="efcc-table-desc">' . esc_html( $cats[ $cat ]['description'] ?? '' ) . '</p>';
			if ( ! $rows ) {
				$html .= '<p class="efcc-table-empty"><em>We do not use cookies in this category.</em></p>';
				continue;
			}
			$html .= '<div class="efcc-table-wrap"><table class="efcc-cookie-table"><thead><tr><th scope="col">Cookie</th><th scope="col">Provider</th><th scope="col">Purpose</th><th scope="col">Duration</th></tr></thead><tbody>';
			foreach ( $rows as $c ) {
				$html .= '<tr><td><code>' . esc_html( $c['name'] ) . '</code></td><td>' . esc_html( $c['provider'] ?? '' ) . '</td><td>' . esc_html( $c['purpose'] ?? '' ) . '</td><td>' . esc_html( $c['duration'] ?? '' ) . '</td></tr>';
			}
			$html .= '</tbody></table></div>';
		}
		return '<div class="efcc-cookie-tables">' . $html . '</div>';
	}

	/**
	 * [efcc_settings_button label="…"].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode_button( $atts ) {
		$atts = shortcode_atts( array( 'label' => 'Change my cookie settings' ), $atts );
		return '<button type="button" class="efcc-open efcc-inline-button">' . esc_html( $atts['label'] ) . '</button>';
	}

	/**
	 * [efcc_cookie_policy] — a complete UK cookie policy (PECR / UK GDPR).
	 *
	 * @return string
	 */
	public static function shortcode_policy() {
		$g       = EFCC_Settings::get( 'general' );
		$company = $g['company'] ? $g['company'] : get_bloginfo( 'name' );
		$email   = $g['contact_email'] ? $g['contact_email'] : get_option( 'admin_email' );
		$privacy = (int) $g['privacy_page'] ? (int) $g['privacy_page'] : (int) get_option( 'wp_page_for_privacy_policy' );
		$updated = (int) get_option( self::UPDATED, 0 );
		$cats    = EFCC_Settings::categories();
		$site    = wp_parse_url( home_url(), PHP_URL_HOST );

		ob_start();
		?>
<div class="efcc-policy">
	<p><em>Last updated: <?php echo esc_html( wp_date( 'j F Y', $updated ? $updated : time() ) ); ?></em></p>

	<h2>About this policy</h2>
	<p>This policy explains how <?php echo esc_html( $company ); ?> (“we”, “us”) uses cookies and similar technologies on <?php echo esc_html( $site ); ?>, what they do, and how you can control them. It should be read together with our <?php echo $privacy ? '<a href="' . esc_url( get_permalink( $privacy ) ) . '">Privacy Policy</a>' : 'Privacy Policy'; ?>.</p>

	<h2>What cookies are</h2>
	<p>Cookies are small text files that a website stores on your device. Similar technologies, such as local storage and pixels, work in a similar way; in this policy “cookies” covers all of them. Some cookies are set by us (first-party cookies); others are set by services we use, such as Google (third-party cookies).</p>

	<h2>Our legal basis</h2>
	<p>Under the Privacy and Electronic Communications Regulations (PECR) and the UK GDPR, we only use cookies that are <strong>strictly necessary</strong> for the website to work without asking you. All other cookies are used <strong>only if you agree</strong>. Optional cookies are off until you choose, and you can reject them as easily as you accept them.</p>

	<h2>The types of cookies we use</h2>
	<ul>
		<?php foreach ( $cats as $c ) : ?>
		<li><strong><?php echo esc_html( $c['label'] ); ?>:</strong> <?php echo esc_html( $c['description'] ); ?></li>
		<?php endforeach; ?>
	</ul>

	<h2>The cookies we use</h2>
	<p>The tables below list the cookies used on this website, who sets them, what they are for and how long they last.</p>
	<?php echo self::shortcode_table( array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>

	<h2>Google Consent Mode</h2>
	<p>We use Google Consent Mode. Until you agree, Google tags on this website are told not to store analytics or advertising cookies and not to use your data for advertising. If you agree, they work normally; if you change your mind, they stop.</p>

	<h2>How to change your choices</h2>
	<p>You can change or withdraw your consent at any time using the cookie button in the bottom corner of every page, or here:</p>
	<p><?php echo self::shortcode_button( array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<p>You can also block or delete cookies in your browser settings. If you block necessary cookies, parts of the website (such as the basket and checkout) will not work. For more information about cookies and how to manage them, visit the Information Commissioner’s Office website: <a href="https://ico.org.uk/for-the-public/online/cookies/" rel="noopener" target="_blank">ico.org.uk</a>.</p>

	<h2>How long we keep your choices</h2>
	<p>We remember your choices for <?php echo (int) $g['consent_days']; ?> days, after which we ask again. We also ask again if we change the cookies we use in a way that affects your choices. To prove that we have your consent, we keep a record of your choices with an anonymous consent ID, the date and a shortened IP address<?php echo (int) $g['log_days'] ? ' for ' . (int) round( $g['log_days'] / 365 * 12 ) . ' months' : ''; ?>.</p>

	<h2>Contact us</h2>
	<p>If you have questions about this policy, please contact us at <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>. You also have the right to complain to the Information Commissioner’s Office (<a href="https://ico.org.uk/make-a-complaint/" rel="noopener" target="_blank">ico.org.uk/make-a-complaint</a>).</p>
</div>
		<?php
		return ob_get_clean();
	}
}
