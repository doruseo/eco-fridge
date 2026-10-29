<?php
/**
 * Library: the consent categories, service presets (with the patterns that
 * identify their scripts and iframes) and a database of well-known cookies
 * (name or pattern, provider, purpose, duration, category) used to describe
 * what the scanner finds.
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Static reference data.
 */
class EFCC_Library {

	/**
	 * Category keys in display order.
	 *
	 * @return string[]
	 */
	public static function category_keys() {
		return array( 'necessary', 'preferences', 'statistics', 'marketing' );
	}

	/**
	 * Service presets, keyed by ID.
	 *
	 * patterns: substrings that identify the service's <script src>, inline
	 * script code or <iframe src> (for automatic blocking).
	 *
	 * @return array
	 */
	public static function services() {
		return array(
			'google-analytics'  => array(
				'name'     => 'Google Analytics',
				'category' => 'statistics',
				'provider' => 'Google Ireland Ltd',
				'purpose'  => 'Measures how visitors use the website (pages visited, time on site, traffic sources) so we can improve it.',
				'privacy'  => 'https://policies.google.com/privacy',
				'patterns' => array( 'google-analytics.com/analytics.js', "gtag('config', 'G-", 'gtag("config", "G-', 'googletagmanager.com/gtag/js?id=G-', 'googletagmanager.com/gtag/js?id=UA-' ),
				'cookies'  => array( '_ga', '_ga_*', '_gid', '_gat*' ),
			),
			'google-ads'        => array(
				'name'     => 'Google Ads',
				'category' => 'marketing',
				'provider' => 'Google Ireland Ltd',
				'purpose'  => 'Measures the results of our Google advertising and shows relevant adverts.',
				'privacy'  => 'https://policies.google.com/privacy',
				'patterns' => array( 'googleadservices.com', 'googletagmanager.com/gtag/js?id=AW-', "gtag('config', 'AW-", 'gtag("config", "AW-' ),
				'cookies'  => array( '_gcl_au', '_gcl_aw', '_gcl_dc', '_gcl_gb' ),
			),
			'google-tag-manager' => array(
				'name'     => 'Google Tag Manager',
				'category' => 'necessary',
				'provider' => 'Google Ireland Ltd',
				'purpose'  => 'Loads the website\'s tags. It sets no cookies itself; each tag it loads follows your consent choices (Google Consent Mode).',
				'privacy'  => 'https://policies.google.com/privacy',
				'patterns' => array(),
				'cookies'  => array(),
			),
			'facebook-pixel'    => array(
				'name'     => 'Meta (Facebook) Pixel',
				'category' => 'marketing',
				'provider' => 'Meta Platforms Ireland Ltd',
				'purpose'  => 'Measures the results of advertising on Facebook and Instagram and builds audiences for adverts.',
				'privacy'  => 'https://www.facebook.com/privacy/policy/',
				'patterns' => array( 'connect.facebook.net', 'fbq(' ),
				'cookies'  => array( '_fbp', '_fbc', 'fr' ),
			),
			'microsoft-clarity' => array(
				'name'     => 'Microsoft Clarity',
				'category' => 'statistics',
				'provider' => 'Microsoft Corporation',
				'purpose'  => 'Records how visitors interact with pages (clicks, scrolling) as heatmaps and session recordings.',
				'privacy'  => 'https://privacy.microsoft.com/privacystatement',
				'patterns' => array( 'clarity.ms/tag', 'www.clarity.ms' ),
				'cookies'  => array( '_clck', '_clsk', 'CLID', 'MUID' ),
			),
			'microsoft-ads'     => array(
				'name'     => 'Microsoft Advertising',
				'category' => 'marketing',
				'provider' => 'Microsoft Corporation',
				'purpose'  => 'Measures the results of advertising on Bing and Microsoft sites.',
				'privacy'  => 'https://privacy.microsoft.com/privacystatement',
				'patterns' => array( 'bat.bing.com' ),
				'cookies'  => array( '_uetsid', '_uetvid', 'MUID' ),
			),
			'hotjar'            => array(
				'name'     => 'Hotjar',
				'category' => 'statistics',
				'provider' => 'Hotjar Ltd',
				'purpose'  => 'Heatmaps, recordings and surveys showing how visitors use the website.',
				'privacy'  => 'https://www.hotjar.com/legal/policies/privacy/',
				'patterns' => array( 'static.hotjar.com', 'hotjar.com/c/hotjar' ),
				'cookies'  => array( '_hjSessionUser_*', '_hjSession_*', '_hjAbsoluteSessionInProgress', '_hjFirstSeen', '_hjIncludedInSessionSample_*' ),
			),
			'linkedin'          => array(
				'name'     => 'LinkedIn Insight Tag',
				'category' => 'marketing',
				'provider' => 'LinkedIn Ireland Unlimited Company',
				'purpose'  => 'Measures the results of LinkedIn advertising and builds audiences.',
				'privacy'  => 'https://www.linkedin.com/legal/privacy-policy',
				'patterns' => array( 'snap.licdn.com', '_linkedin_partner_id' ),
				'cookies'  => array( 'li_fat_id', 'li_sugr', 'bcookie', 'lidc', 'UserMatchHistory', 'AnalyticsSyncHistory' ),
			),
			'tiktok'            => array(
				'name'     => 'TikTok Pixel',
				'category' => 'marketing',
				'provider' => 'TikTok Technology Ltd',
				'purpose'  => 'Measures the results of TikTok advertising.',
				'privacy'  => 'https://www.tiktok.com/legal/privacy-policy-eea',
				'patterns' => array( 'analytics.tiktok.com' ),
				'cookies'  => array( '_ttp', '_tt_enable_cookie' ),
			),
			'matomo'            => array(
				'name'     => 'Matomo',
				'category' => 'statistics',
				'provider' => 'This website (self-hosted)',
				'purpose'  => 'Measures how visitors use the website.',
				'privacy'  => '',
				'patterns' => array( 'matomo.js', 'piwik.js', '_paq.push' ),
				'cookies'  => array( '_pk_id.*', '_pk_ses.*', '_pk_ref.*' ),
			),
			'youtube'           => array(
				'name'     => 'YouTube',
				'category' => 'marketing',
				'provider' => 'Google Ireland Ltd',
				'purpose'  => 'Plays embedded videos. YouTube may set cookies to count views and personalise adverts.',
				'privacy'  => 'https://policies.google.com/privacy',
				'patterns' => array( 'youtube.com/embed', 'youtube-nocookie.com/embed', 'youtube.com/iframe_api' ),
				'cookies'  => array( 'YSC', 'VISITOR_INFO1_LIVE', 'VISITOR_PRIVACY_METADATA' ),
			),
			'vimeo'             => array(
				'name'     => 'Vimeo',
				'category' => 'statistics',
				'provider' => 'Vimeo Inc.',
				'purpose'  => 'Plays embedded videos and counts views.',
				'privacy'  => 'https://vimeo.com/privacy',
				'patterns' => array( 'player.vimeo.com' ),
				'cookies'  => array( 'vuid' ),
			),
			'google-maps'       => array(
				'name'     => 'Google Maps',
				'category' => 'marketing',
				'provider' => 'Google Ireland Ltd',
				'purpose'  => 'Shows embedded maps. Google may set cookies to remember preferences and personalise adverts.',
				'privacy'  => 'https://policies.google.com/privacy',
				'patterns' => array( 'google.com/maps/embed', 'maps.google.com/maps', 'maps.googleapis.com' ),
				'cookies'  => array( 'NID' ),
			),
			'woocommerce-attribution' => array(
				'name'     => 'WooCommerce order attribution',
				'category' => 'statistics',
				'provider' => 'This website',
				'purpose'  => 'Records how a visitor found the website (search, advert, link) so we can see which channels lead to orders.',
				'privacy'  => '',
				'patterns' => array(),
				'cookies'  => array( 'sbjs_*' ),
			),
		);
	}

	/**
	 * Well-known cookies: name or pattern (* = any characters) → description.
	 *
	 * @return array[]
	 */
	public static function known_cookies() {
		$c = array(
			// This plugin.
			array( 'efcc_consent', 'necessary', 'This website', 'Stores your cookie choices.', '%days% days', '' ),
			// WordPress.
			array( 'wordpress_test_cookie', 'necessary', 'This website (WordPress)', 'Checks that the browser accepts cookies.', 'Session', '' ),
			array( 'wordpress_logged_in_*', 'necessary', 'This website (WordPress)', 'Keeps a logged-in user signed in.', 'Session or 14 days', '' ),
			array( 'wordpress_sec_*', 'necessary', 'This website (WordPress)', 'Secures the session of a logged-in user.', 'Session or 14 days', '' ),
			array( 'wp-settings-*', 'necessary', 'This website (WordPress)', 'Remembers a logged-in user\'s dashboard settings.', '1 year', '' ),
			array( 'wp-settings-time-*', 'necessary', 'This website (WordPress)', 'Remembers a logged-in user\'s dashboard settings.', '1 year', '' ),
			array( 'wp_lang', 'necessary', 'This website (WordPress)', 'Remembers the language chosen on the login screen.', 'Session', '' ),
			// WooCommerce.
			array( 'woocommerce_cart_hash', 'necessary', 'This website (WooCommerce)', 'Helps WooCommerce know when the basket changes.', 'Session', '' ),
			array( 'woocommerce_items_in_cart', 'necessary', 'This website (WooCommerce)', 'Helps WooCommerce know when the basket changes.', 'Session', '' ),
			array( 'wp_woocommerce_session_*', 'necessary', 'This website (WooCommerce)', 'Keeps your basket between pages.', '2 days', '' ),
			array( 'woocommerce_recently_viewed', 'necessary', 'This website (WooCommerce)', 'Shows recently viewed products.', 'Session', '' ),
			array( 'store_notice*', 'necessary', 'This website (WooCommerce)', 'Remembers that the store notice was dismissed.', 'Session', '' ),
			array( 'wc_cart_created', 'necessary', 'This website (WooCommerce)', 'Records when the basket was created.', 'Session', '' ),
			array( 'tk_ai', 'statistics', 'WooCommerce', 'Anonymous identifier for WooCommerce usage statistics (logged-in users).', 'Session', '' ),
			array( 'sbjs_migrations', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: technical data for the source tracker.', 'Session', 'woocommerce-attribution' ),
			array( 'sbjs_current_add', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: landing page and time of the current visit.', 'Session', 'woocommerce-attribution' ),
			array( 'sbjs_first_add', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: landing page and time of the first visit.', 'Session', 'woocommerce-attribution' ),
			array( 'sbjs_current', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: traffic source of the current visit.', 'Session', 'woocommerce-attribution' ),
			array( 'sbjs_first', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: traffic source of the first visit.', 'Session', 'woocommerce-attribution' ),
			array( 'sbjs_udata', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: browser and device type.', 'Session', 'woocommerce-attribution' ),
			array( 'sbjs_session', 'statistics', 'This website (WooCommerce order attribution)', 'Order attribution: number of pages viewed in this visit.', '30 minutes', 'woocommerce-attribution' ),
			// This site (ECO-Fridge Blocks).
			array( 'efb_*', 'necessary', 'This website', 'Technical cookie of the website (forms, basket reminders).', 'Session', '' ),
			// Caching / hosting.
			array( '_lscache_vary', 'necessary', 'This website (LiteSpeed)', 'Lets the server cache serve the right version of a page.', 'Session', '' ),
			array( 'PHPSESSID', 'necessary', 'This website', 'Keeps a server session between pages.', 'Session', '' ),
			// Google.
			array( '_ga', 'statistics', 'Google Analytics', 'Distinguishes visitors.', '2 years', 'google-analytics' ),
			array( '_ga_*', 'statistics', 'Google Analytics', 'Keeps the state of a visit.', '2 years', 'google-analytics' ),
			array( '_gid', 'statistics', 'Google Analytics', 'Distinguishes visitors.', '24 hours', 'google-analytics' ),
			array( '_gat*', 'statistics', 'Google Analytics', 'Limits the request rate.', '1 minute', 'google-analytics' ),
			array( '_gcl_au', 'marketing', 'Google Ads', 'Measures ad conversions.', '90 days', 'google-ads' ),
			array( '_gcl_aw', 'marketing', 'Google Ads', 'Stores the ad click that brought you here.', '90 days', 'google-ads' ),
			array( '_gcl_dc', 'marketing', 'Google Ads', 'Stores ad click information.', '90 days', 'google-ads' ),
			array( '_gcl_gb', 'marketing', 'Google Ads', 'Stores ad click information.', '90 days', 'google-ads' ),
			array( 'NID', 'marketing', 'Google', 'Remembers preferences and personalises Google adverts (Google Maps, embeds).', '6 months', 'google-maps' ),
			// Meta.
			array( '_fbp', 'marketing', 'Meta', 'Identifies the browser for advert measurement.', '90 days', 'facebook-pixel' ),
			array( '_fbc', 'marketing', 'Meta', 'Stores the last advert click.', '90 days', 'facebook-pixel' ),
			array( 'fr', 'marketing', 'Meta', 'Advert delivery and measurement.', '90 days', 'facebook-pixel' ),
			// Microsoft.
			array( '_clck', 'statistics', 'Microsoft Clarity', 'Keeps the Clarity user ID.', '1 year', 'microsoft-clarity' ),
			array( '_clsk', 'statistics', 'Microsoft Clarity', 'Joins page views into one session recording.', '1 day', 'microsoft-clarity' ),
			array( 'CLID', 'statistics', 'Microsoft Clarity', 'Identifies the first visit.', '1 year', 'microsoft-clarity' ),
			array( 'MUID', 'marketing', 'Microsoft', 'Identifies the browser across Microsoft sites.', '1 year', 'microsoft-ads' ),
			array( '_uetsid', 'marketing', 'Microsoft Advertising', 'Measures ad conversions.', '1 day', 'microsoft-ads' ),
			array( '_uetvid', 'marketing', 'Microsoft Advertising', 'Measures ad conversions.', '13 months', 'microsoft-ads' ),
			// Hotjar.
			array( '_hjSessionUser_*', 'statistics', 'Hotjar', 'Keeps the Hotjar user ID.', '1 year', 'hotjar' ),
			array( '_hjSession_*', 'statistics', 'Hotjar', 'Keeps the current session.', '30 minutes', 'hotjar' ),
			array( '_hj*', 'statistics', 'Hotjar', 'Hotjar analytics.', 'Session to 1 year', 'hotjar' ),
			// LinkedIn, TikTok.
			array( 'li_fat_id', 'marketing', 'LinkedIn', 'Measures LinkedIn ad conversions.', '30 days', 'linkedin' ),
			array( 'li_sugr', 'marketing', 'LinkedIn', 'Browser identifier for LinkedIn ads.', '90 days', 'linkedin' ),
			array( 'bcookie', 'marketing', 'LinkedIn', 'Browser identifier.', '1 year', 'linkedin' ),
			array( 'lidc', 'marketing', 'LinkedIn', 'Routing for LinkedIn services.', '1 day', 'linkedin' ),
			array( '_ttp', 'marketing', 'TikTok', 'Measures TikTok ad conversions.', '13 months', 'tiktok' ),
			// Matomo.
			array( '_pk_id.*', 'statistics', 'Matomo', 'Distinguishes visitors.', '13 months', 'matomo' ),
			array( '_pk_ses.*', 'statistics', 'Matomo', 'Keeps the current visit.', '30 minutes', 'matomo' ),
			array( '_pk_ref.*', 'statistics', 'Matomo', 'Stores how the visitor arrived.', '6 months', 'matomo' ),
			// YouTube, Vimeo.
			array( 'YSC', 'marketing', 'YouTube', 'Counts video views.', 'Session', 'youtube' ),
			array( 'VISITOR_INFO1_LIVE', 'marketing', 'YouTube', 'Estimates bandwidth and personalises adverts.', '6 months', 'youtube' ),
			array( 'VISITOR_PRIVACY_METADATA', 'marketing', 'YouTube', 'Stores the visitor\'s consent state for YouTube.', '6 months', 'youtube' ),
			array( 'vuid', 'statistics', 'Vimeo', 'Counts video views.', '2 years', 'vimeo' ),
		);
		return array_map( static function ( $r ) {
			return array( 'name' => $r[0], 'category' => $r[1], 'provider' => $r[2], 'purpose' => $r[3], 'duration' => $r[4], 'service' => $r[5] );
		}, $c );
	}

	/**
	 * The known entry for a cookie name, or null.
	 *
	 * @param string $name Cookie name.
	 * @return array|null
	 */
	public static function match_cookie( $name ) {
		// Exact names first, then patterns (most specific = longest first).
		$known = self::known_cookies();
		foreach ( $known as $k ) {
			if ( false === strpos( $k['name'], '*' ) && $k['name'] === $name ) { return $k; }
		}
		usort( $known, static fn( $a, $b ) => strlen( $b['name'] ) <=> strlen( $a['name'] ) );
		foreach ( $known as $k ) {
			if ( false !== strpos( $k['name'], '*' ) && self::wildcard( $k['name'], $name ) ) { return $k; }
		}
		return null;
	}

	/**
	 * Whether a name matches a pattern with * wildcards.
	 *
	 * @param string $pattern Pattern.
	 * @param string $name    Name.
	 * @return bool
	 */
	public static function wildcard( $pattern, $name ) {
		$re = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';
		return (bool) preg_match( $re, $name );
	}
}
