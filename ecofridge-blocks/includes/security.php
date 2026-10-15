<?php
/**
 * Site hardening.
 *
 *  - XML-RPC and pingbacks off (password guessing and DDoS amplification);
 *    nothing on the site uses them.
 *  - Sign-in: after 5 failed attempts from one IP address, sign-in is blocked
 *    for that address for 15 minutes (wp-login.php and My Account alike); the
 *    error never says whether the username or the password was wrong.
 *  - Two-step sign-in (2FA, "Two Factor" plugin) is required for every staff
 *    account; until it is set up, the dashboard only opens the profile.
 *  - Usernames are not exposed: no /?author=N lookup, no author archives or
 *    author links, and /wp-json/wp/v2/users only for staff.
 *  - No WordPress version in the page source.
 *  - Security headers: no MIME sniffing, no framing by other sites, a strict
 *    referrer policy, no camera / microphone / location access, and HSTS on
 *    the live HTTPS site.
 *  - Checkout: at most 5 order attempts a minute per visitor (card testing).
 *  - Application passwords off (no integration uses them).
 *  - Theme and plugin file editors off, even if wp-config.php forgets it.
 *  - PHP files can never run from the uploads folder.
 *  - Comment / review spam: a hidden honeypot field; bots that fill it go
 *    straight to spam.
 *
 * Server-side measures (SSL, firewall, backups, updates, SPF/DKIM/DMARC) are in
 * the launch checklist; this file covers what WordPress itself can enforce.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

const EFB_LOGIN_MAX_FAILS = 5;
const EFB_LOGIN_LOCK_MIN  = 15;

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/* ---------------------------------------------------------------------------
 * XML-RPC and pingbacks.
 * ------------------------------------------------------------------------- */
// xmlrpc.php still answers system.multicall with XML-RPC "disabled", so the
// whole endpoint is closed.
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit;
}
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter( 'pings_open', '__return_false', 99 );
add_filter(
	'wp_headers',
	static function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
remove_action( 'wp_head', 'rsd_link' );

/* ---------------------------------------------------------------------------
 * No version number in the page source and feeds.
 * ------------------------------------------------------------------------- */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* ---------------------------------------------------------------------------
 * Application passwords: not used by anything on this site.
 * ------------------------------------------------------------------------- */
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/* ---------------------------------------------------------------------------
 * Usernames are not exposed.
 * ------------------------------------------------------------------------- */

// /?author=1 would redirect to /author/<slug>/, and the author archive itself
// shows the slug; both go to the homepage (staff can still preview them).
add_action(
	'template_redirect',
	static function () {
		if ( ( isset( $_GET['author'] ) || is_author() ) && ! current_user_can( 'edit_posts' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);

// Author links in the theme and in Rank Math's schema would print the slug.
add_filter(
	'author_link',
	static function ( $link ) {
		return is_admin() ? $link : home_url( '/' );
	},
	99
);

// The REST user list is only for staff (the block editor needs it); signed-in
// shop customers do not get it either.
add_filter(
	'rest_endpoints',
	static function ( $endpoints ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}
);

// oEmbed responses carry the author's display name and profile link.
add_filter(
	'oembed_response_data',
	static function ( $data ) {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}
);

/* ---------------------------------------------------------------------------
 * Sign-in: limited attempts per IP address, one generic error message.
 * ------------------------------------------------------------------------- */

/**
 * The visitor's IP address (the server's own value; forwarded headers can be faked).
 *
 * @return string
 */
function efb_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Transient key of the failed sign-in counter for an IP address.
 *
 * @param string $ip IP address.
 * @return string
 */
function efb_login_key( $ip ) {
	return 'efb_login_' . md5( $ip );
}

add_filter(
	'authenticate',
	static function ( $user ) {
		$fails = (int) get_transient( efb_login_key( efb_client_ip() ) );
		if ( $fails >= EFB_LOGIN_MAX_FAILS ) {
			return new WP_Error(
				'efb_login_locked',
				/* translators: %d: minutes */
				sprintf( __( 'Too many failed sign-in attempts. Please try again in %d minutes.', 'ecofridge-blocks' ), EFB_LOGIN_LOCK_MIN )
			);
		}
		// One message for a wrong username, email or password.
		if ( is_wp_error( $user ) && array_intersect( $user->get_error_codes(), array( 'invalid_username', 'invalid_email', 'incorrect_password' ) ) ) {
			return new WP_Error( 'efb_login_failed', __( 'The username or password is incorrect.', 'ecofridge-blocks' ) );
		}
		return $user;
	},
	99
);

add_action(
	'wp_login_failed',
	static function () {
		$key = efb_login_key( efb_client_ip() );
		set_transient( $key, (int) get_transient( $key ) + 1, EFB_LOGIN_LOCK_MIN * MINUTE_IN_SECONDS );
	}
);

add_action(
	'wp_login',
	static function () {
		delete_transient( efb_login_key( efb_client_ip() ) );
	}
);

/* ---------------------------------------------------------------------------
 * Two-step sign-in (2FA) for staff.
 *
 * The codes come from the "Two Factor" plugin (wordpress.org/plugins/two-factor).
 * Every account that can edit content or run the shop must turn it on: until it
 * does, every admin screen sends it to its profile to set up an authenticator
 * app and the backup codes. Shop customers are not affected.
 * ------------------------------------------------------------------------- */

/**
 * Whether the user has to sign in with a second step but has not set one up yet.
 *
 * @param WP_User $user User.
 * @return bool
 */
function efb_2fa_missing( $user ) {
	if ( ! class_exists( 'Two_Factor_Core' ) || ! $user || ! $user->exists() ) {
		return false;
	}
	$staff = user_can( $user, 'edit_posts' ) || user_can( $user, 'manage_woocommerce' );
	return $staff && ! Two_Factor_Core::is_user_using_two_factor( $user->ID );
}

add_action(
	'admin_init',
	static function () {
		global $pagenow;
		if ( wp_doing_ajax() || in_array( $pagenow, array( 'profile.php', 'admin-post.php' ), true ) ) {
			return;
		}
		if ( efb_2fa_missing( wp_get_current_user() ) ) {
			wp_safe_redirect( admin_url( 'profile.php?efb_2fa=required#two-factor-options' ) );
			exit;
		}
	}
);

add_action(
	'admin_notices',
	static function () {
		if ( 'profile' !== get_current_screen()->id || ! efb_2fa_missing( wp_get_current_user() ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Two-step sign-in is required for this account.', 'ecofridge-blocks' ) . '</strong> '
			. esc_html__( 'Under "Two-Factor Options" below, turn on "Authenticator App", scan the QR code with Google Authenticator, Microsoft Authenticator or 1Password, enter the code, then generate and keep the backup codes. Save the profile; the rest of the dashboard opens afterwards.', 'ecofridge-blocks' )
			. '</p></div>';
	}
);

/* ---------------------------------------------------------------------------
 * Checkout: at most 5 order attempts a minute from one visitor (WooCommerce
 * Store API rate limiting). Stops card-testing bots, which try stolen card
 * numbers one after another, and bots placing fake bank-transfer orders.
 * ------------------------------------------------------------------------- */
add_filter(
	'woocommerce_store_api_rate_limit_options',
	static function ( $options ) {
		// Placing the order only: the cart makes many Store API calls of its own.
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
		$post  = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
		if ( $post && preg_match( '#/wc/store(?:/v\d+)?/checkout#', $route ) ) {
			$options['enabled']       = true;
			$options['proxy_support'] = false; // REMOTE_ADDR only: forwarded headers can be faked.
			$options['limit']         = 5;
			$options['seconds']       = 60;
		}
		return $options;
	},
	20
);

/* ---------------------------------------------------------------------------
 * Security headers.
 * ------------------------------------------------------------------------- */

/**
 * Sends the security headers.
 */
function efb_security_headers() {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );
	if ( is_ssl() && 'production' === wp_get_environment_type() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}
add_action( 'send_headers', 'efb_security_headers' );
add_action( 'login_init', 'efb_security_headers' );
add_action( 'admin_init', 'efb_security_headers' );

/* ---------------------------------------------------------------------------
 * No PHP from the uploads folder (an uploaded script could never run).
 * ------------------------------------------------------------------------- */
add_action(
	'admin_init',
	static function () {
		$uploads = wp_get_upload_dir();
		$file    = trailingslashit( $uploads['basedir'] ) . '.htaccess';
		$rules   = "# BEGIN ECO-Fridge security\n<FilesMatch \"\\.(?i:php\\d?|phtml|phar|pht|cgi|pl|py|sh)$\">\n\tRequire all denied\n</FilesMatch>\n# END ECO-Fridge security\n";
		$current = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false !== strpos( $current, '# BEGIN ECO-Fridge security' ) || ! wp_is_writable( $uploads['basedir'] ) ) {
			return;
		}
		file_put_contents( $file, $rules . ( '' !== $current ? "\n" . $current : '' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
);

/* ---------------------------------------------------------------------------
 * Comment / review spam: honeypot field.
 * ------------------------------------------------------------------------- */
add_filter(
	'comment_form_default_fields',
	static function ( $fields ) {
		$fields['efb_hp'] = '<p class="efb-hp" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Leave this field empty <input type="text" name="efb_hp" value="" tabindex="-1" autocomplete="off"></label></p>';
		return $fields;
	}
);

add_filter(
	'pre_comment_approved',
	static function ( $approved ) {
		if ( ! is_user_logged_in() && ! empty( $_POST['efb_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public comment form.
			return 'spam';
		}
		return $approved;
	},
	99
);
