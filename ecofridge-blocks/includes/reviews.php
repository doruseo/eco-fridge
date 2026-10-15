<?php
/**
 * Product reviews from real buyers, without customer accounts.
 *
 *  - Customers buy as guests, so WooCommerce's "verified owners only" setting
 *    would show the review form to nobody. Instead, a few days after an order
 *    is completed the "Review request" email (WooCommerce → Settings → Emails)
 *    sends the buyer one signed link per product. The link opens the product
 *    page with the review form, name and email filled in from the order.
 *  - The server accepts a product review only with a valid link: its signature
 *    ties it to the order, the product and the order's email address. One
 *    review per product per order. Direct posts to wp-comments-post.php by bots
 *    have no link and are refused, so there is no public form to spam.
 *  - No links at all: a review whose text or name contains a web address is
 *    refused with a message asking to remove it; the website field is dropped.
 *  - Every review waits for manual approval (Comments → Reviews / Products →
 *    Reviews) and carries the "verified owner" label.
 *  - Staff can send the email at once from the order screen (Order actions →
 *    "Send review request"), e.g. for phone orders.
 *  - Automatic emails go out on the live site only (wp_get_environment_type()
 *    "production"), and only for orders completed from now on: the imported
 *    old orders on local and staging copies never email real customers.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

const EFB_RV_LINK_DAYS = 180; // How long a review link stays valid after the order is completed.

/**
 * Signature of a review link.
 *
 * @param int    $order_id   Order ID.
 * @param int    $product_id Product ID (the parent product for variations).
 * @param string $email      Order billing email.
 * @return string
 */
function efb_rv_sign( $order_id, $product_id, $email ) {
	return substr( hash_hmac( 'sha256', (int) $order_id . '|' . (int) $product_id . '|' . strtolower( trim( $email ) ), wp_salt( 'auth' ) . 'efb-review' ), 0, 32 );
}

/**
 * Review link for one product of an order.
 *
 * @param WC_Order $order      Order.
 * @param int      $product_id Product ID.
 * @return string
 */
function efb_rv_link( $order, $product_id ) {
	$token = $order->get_id() . '.' . (int) $product_id . '.' . efb_rv_sign( $order->get_id(), $product_id, $order->get_billing_email() );
	return add_query_arg( 'efb_rv', $token, get_permalink( $product_id ) ) . '#review_form_wrapper';
}

/**
 * The order behind a review token, if the token is valid for that product.
 *
 * @param string $token      "order.product.signature".
 * @param int    $product_id Product the review is for.
 * @return WC_Order|null
 */
function efb_rv_order( $token, $product_id ) {
	if ( ! preg_match( '/^(\d+)\.(\d+)\.([a-f0-9]{32})$/', (string) $token, $m ) || (int) $m[2] !== (int) $product_id ) {
		return null;
	}
	$order = wc_get_order( (int) $m[1] );
	if ( ! $order instanceof WC_Order || ! hash_equals( efb_rv_sign( $order->get_id(), $product_id, $order->get_billing_email() ), $m[3] ) ) {
		return null;
	}
	if ( ! $order->has_status( wc_get_is_paid_statuses() ) ) {
		return null; // refunded / cancelled since.
	}
	$done = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_created();
	if ( $done && $done->getTimestamp() < time() - EFB_RV_LINK_DAYS * DAY_IN_SECONDS ) {
		return null;
	}
	foreach ( $order->get_items() as $item ) {
		if ( (int) $item->get_product_id() === (int) $product_id ) {
			return $order;
		}
	}
	return null;
}

/**
 * Whether the order already has a review for the product.
 *
 * @param int $order_id   Order ID.
 * @param int $product_id Product ID.
 * @return bool
 */
function efb_rv_reviewed( $order_id, $product_id ) {
	return (bool) get_comments(
		array(
			'post_id'    => (int) $product_id,
			'type'       => 'review',
			'status'     => 'all',
			'count'      => true,
			'meta_key'   => '_efb_review_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => (int) $order_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
}

/**
 * Whether a text contains a web address (or a link in HTML / BBCode).
 *
 * @param string $text Text.
 * @return bool
 */
function efb_rv_has_link( $text ) {
	// A lower-case ending right after the dot ("cheap-fridges.com") or spelled out
	// ("spam [dot] com"); sentences like "e.g. in summer" or "Ltd. Co." pass.
	$tlds = 'com|net|org|info|biz|io|co\.uk|uk|eu|de|fr|nl|ru|cn|ly|xyz|top|shop|site|online|store|app|link|click|live|pro|vip';
	return (bool) preg_match( '~https?:|www\.|<\s*a\b|href\s*=|\[url~i', (string) $text )
		|| (bool) preg_match( '~\b[A-Za-z0-9-]{2,}(?:\.|\s*[(\[]\s*dot\s*[)\]]\s*)(?:' . $tlds . ')\b~', (string) $text );
}

/**
 * The valid review token of the current request for the product, if any.
 *
 * @param int $product_id Product ID.
 * @return array{token:string,order:WC_Order}|null
 */
function efb_rv_request( $product_id ) {
	static $cache = array();
	if ( ! array_key_exists( $product_id, $cache ) ) {
		$token = isset( $_REQUEST['efb_rv'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['efb_rv'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed link from an email.
		$order = $token ? efb_rv_order( $token, $product_id ) : null;
		$cache[ $product_id ] = $order ? array( 'token' => $token, 'order' => $order ) : null;
	}
	return $cache[ $product_id ];
}

/* ---------------------------------------------------------------------------
 * Product page: the form appears for a valid review link.
 * ------------------------------------------------------------------------- */

// "Verified owners only" stays on; for the buyer who came from the email, the
// review counts as allowed – on the product page (WooCommerce's template) and
// when the form is sent (WooCommerce refuses guests in pre_comment_on_post).
add_filter(
	'pre_option_woocommerce_review_rating_verification_required',
	static function ( $value ) {
		if ( is_singular( 'product' ) ) {
			$product_id = get_queried_object_id();
		} elseif ( isset( $_POST['comment_post_ID'], $_POST['efb_rv'] ) && 'wp-comments-post.php' === basename( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- signed review link.
			$product_id = absint( $_POST['comment_post_ID'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			return $value;
		}
		$rv = efb_rv_request( $product_id );
		if ( ! $rv ) {
			return $value;
		}
		// Sending: a second review gets its own message (preprocess_comment below).
		return ! is_singular( 'product' ) || ! efb_rv_reviewed( $rv['order']->get_id(), $product_id ) ? 'no' : $value;
	}
);

// A review-link page is personal (name and email filled in): never cached.
add_action(
	'template_redirect',
	static function () {
		if ( is_singular( 'product' ) && isset( $_GET['efb_rv'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			do_action( 'litespeed_control_set_nocache', 'efb review link' );
			nocache_headers();
		}
	},
	1
);

add_filter(
	'woocommerce_product_review_comment_form_args',
	static function ( $args ) {
		$rv = efb_rv_request( get_queried_object_id() );
		if ( ! $rv ) {
			return $args;
		}
		$order = $rv['order'];
		$name  = trim( $order->get_billing_first_name() . ' ' . mb_substr( $order->get_billing_last_name(), 0, 1 ) );
		if ( isset( $args['fields']['author'] ) ) {
			$args['fields']['author'] = preg_replace( '/(<input[^>]*name="author"[^>]*value=")[^"]*"/', '$1' . esc_attr( $name ) . '"', $args['fields']['author'] );
		}
		if ( isset( $args['fields']['email'] ) ) {
			$args['fields']['email'] = preg_replace( '/(<input[^>]*name="email"[^>]*value=")[^"]*"/', '$1' . esc_attr( $order->get_billing_email() ) . '" readonly', $args['fields']['email'] );
		}
		unset( $args['fields']['url'] );
		$args['comment_notes_after'] = '<input type="hidden" name="efb_rv" value="' . esc_attr( $rv['token'] ) . '">'
			. '<p class="efb-review-note">' . esc_html__( 'Your name appears as first name and initial. Please do not include links or web addresses. Every review is checked by our team before it is published.', 'ecofridge-blocks' ) . '</p>';
		return $args;
	}
);

// Everyone else sees how reviews work instead of "only logged in customers".
add_filter(
	'gettext_woocommerce',
	static function ( $translation, $text ) {
		if ( 'Only logged in customers who have purchased this product may leave a review.' === $text ) {
			return __( 'Bought this product from us? After delivery we email you a link to review it.', 'ecofridge-blocks' );
		}
		return $translation;
	},
	10,
	2
);

/* ---------------------------------------------------------------------------
 * Submitting: only with a valid link, no links in the text, manual approval.
 * ------------------------------------------------------------------------- */

/** Order of the review being saved (for the comment meta). */
$GLOBALS['efb_rv_pending_order'] = 0;

add_filter(
	'preprocess_comment',
	static function ( $data ) {
		$post_id = (int) ( $data['comment_post_ID'] ?? 0 );
		if ( 'product' !== get_post_type( $post_id ) || current_user_can( 'moderate_comments' ) ) {
			return $data; // staff replies to reviews.
		}
		$rv = efb_rv_request( $post_id );
		if ( ! $rv ) {
			wp_die( esc_html__( 'Reviews can only be left from the link in the email we send after delivery.', 'ecofridge-blocks' ), esc_html__( 'Review not accepted', 'ecofridge-blocks' ), array( 'response' => 403 ) );
		}
		if ( efb_rv_reviewed( $rv['order']->get_id(), $post_id ) ) {
			wp_die( esc_html__( 'Thank you – we already have your review for this product.', 'ecofridge-blocks' ), esc_html__( 'Review already received', 'ecofridge-blocks' ), array( 'response' => 409 ) );
		}
		if ( efb_rv_has_link( $data['comment_content'] ?? '' ) || efb_rv_has_link( $data['comment_author'] ?? '' ) ) {
			wp_die(
				esc_html__( 'Please remove the link or web address from your review and send it again.', 'ecofridge-blocks' ),
				esc_html__( 'Links are not allowed', 'ecofridge-blocks' ),
				array(
					'response'  => 400,
					'back_link' => true,
				)
			);
		}
		// The order's address, whatever the form sent: WooCommerce then labels the
		// review "verified owner".
		$data['comment_author_email'] = $rv['order']->get_billing_email();
		$data['comment_author_url']   = '';
		$data['comment_type']         = 'review';
		$GLOBALS['efb_rv_pending_order'] = $rv['order']->get_id();
		return $data;
	},
	5
);

// Every review waits for approval (spam stays spam).
add_filter(
	'pre_comment_approved',
	static function ( $approved, $data ) {
		if ( 'spam' === $approved || 'trash' === $approved || is_wp_error( $approved ) ) {
			return $approved;
		}
		if ( 'product' === get_post_type( (int) $data['comment_post_ID'] ) && ! current_user_can( 'moderate_comments' ) ) {
			return 0;
		}
		return $approved;
	},
	100,
	2
);

add_action(
	'comment_post',
	static function ( $comment_id ) {
		if ( $GLOBALS['efb_rv_pending_order'] ) {
			update_comment_meta( $comment_id, '_efb_review_order', (int) $GLOBALS['efb_rv_pending_order'] );
			update_comment_meta( $comment_id, 'verified', 1 );
			$GLOBALS['efb_rv_pending_order'] = 0;
		}
	},
	20
);

// After sending: back to the product with a thank-you note.
add_filter(
	'comment_post_redirect',
	static function ( $location, $comment ) {
		if ( 'review' === $comment->comment_type && get_comment_meta( $comment->comment_ID, '_efb_review_order', true ) ) {
			return add_query_arg( 'efb_rv_thanks', '1', get_permalink( $comment->comment_post_ID ) ) . '#reviews';
		}
		return $location;
	},
	10,
	2
);

add_action(
	'woocommerce_before_single_product',
	static function () {
		if ( isset( $_GET['efb_rv_thanks'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wc_print_notice( __( 'Thank you for your review! It will appear on the page once our team has checked it.', 'ecofridge-blocks' ), 'success' );
		}
	}
);

/* ---------------------------------------------------------------------------
 * The email.
 * ------------------------------------------------------------------------- */

add_filter(
	'woocommerce_email_classes',
	static function ( $emails ) {
		require_once EFB_DIR . 'includes/class-efb-email-review-request.php';
		$emails['EFB_Email_Review_Request'] = new EFB_Email_Review_Request();
		return $emails;
	}
);

/**
 * Whether an address has asked not to receive review requests.
 *
 * @param string $email Email address.
 * @return bool
 */
function efb_rv_opted_out( $email ) {
	return in_array( md5( strtolower( trim( $email ) ) ), (array) get_option( 'efb_rv_optout', array() ), true );
}

/**
 * Link that stops review requests for the order's address.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function efb_rv_optout_link( $order ) {
	return add_query_arg( 'efb_rv_stop', $order->get_id() . '.' . efb_rv_sign( $order->get_id(), 0, $order->get_billing_email() ), home_url( '/' ) );
}

add_action(
	'template_redirect',
	static function () {
		$stop = isset( $_GET['efb_rv_stop'] ) ? sanitize_text_field( wp_unslash( $_GET['efb_rv_stop'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed link from an email.
		if ( '' === $stop ) {
			return;
		}
		if ( preg_match( '/^(\d+)\.([a-f0-9]{32})$/', $stop, $m ) ) {
			$order = wc_get_order( (int) $m[1] );
			if ( $order instanceof WC_Order && hash_equals( efb_rv_sign( $order->get_id(), 0, $order->get_billing_email() ), $m[2] ) ) {
				$optout   = (array) get_option( 'efb_rv_optout', array() );
				$optout[] = md5( strtolower( trim( $order->get_billing_email() ) ) );
				update_option( 'efb_rv_optout', array_values( array_unique( $optout ) ), false );
				wp_die( esc_html__( 'Done – we will not ask you for reviews again.', 'ecofridge-blocks' ), esc_html__( 'Unsubscribed', 'ecofridge-blocks' ), array( 'response' => 200 ) );
			}
		}
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
);

/**
 * Sends the review request for an order (once).
 *
 * @param int  $order_id Order ID.
 * @param bool $manual   Sent by staff from the order screen (sends again).
 * @return bool Sent.
 */
function efb_rv_send( $order_id, $manual = false ) {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order || ! $order->has_status( wc_get_is_paid_statuses() ) || ! is_email( $order->get_billing_email() ) ) {
		return false;
	}
	if ( ! $manual && ( $order->get_meta( '_efb_review_request_sent' ) || efb_rv_opted_out( $order->get_billing_email() ) ) ) {
		return false;
	}
	$email = WC()->mailer()->get_emails()['EFB_Email_Review_Request'] ?? null;
	if ( ! $email || ( ! $manual && ! $email->is_enabled() ) || ! $email->trigger( $order ) ) {
		return false;
	}
	$order->update_meta_data( '_efb_review_request_sent', time() );
	$order->save();
	$order->add_order_note( __( 'Review request email sent to the customer.', 'ecofridge-blocks' ) );
	return true;
}
add_action( 'efb_rv_send', 'efb_rv_send' );

// Order completed → email after the delay (live site only).
add_action(
	'woocommerce_order_status_completed',
	static function ( $order_id ) {
		if ( 'production' !== wp_get_environment_type() || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_meta( '_efb_review_request_sent' ) || as_has_scheduled_action( 'efb_rv_send', array( (int) $order_id ), 'ecofridge' ) ) {
			return;
		}
		$email = WC()->mailer()->get_emails()['EFB_Email_Review_Request'] ?? null;
		$days  = $email ? max( 0, (float) $email->get_option( 'delay_days', 7 ) ) : 7;
		as_schedule_single_action( time() + (int) round( $days * DAY_IN_SECONDS ), 'efb_rv_send', array( (int) $order_id ), 'ecofridge' );
	}
);

// Order screen: "Send review request".
add_filter(
	'woocommerce_order_actions',
	static function ( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order || $order->has_status( wc_get_is_paid_statuses() ) ) {
			$actions['efb_send_review_request'] = __( 'Send review request', 'ecofridge-blocks' );
		}
		return $actions;
	},
	10,
	2
);
add_action(
	'woocommerce_order_action_efb_send_review_request',
	static function ( $order ) {
		efb_rv_send( $order->get_id(), true );
	}
);

// The order a review came from, in the reviews list.
add_filter(
	'comment_text',
	static function ( $text, $comment = null ) {
		if ( is_admin() && $comment && current_user_can( 'edit_shop_orders' ) ) {
			$order = wc_get_order( (int) get_comment_meta( $comment->comment_ID, '_efb_review_order', true ) );
			if ( $order ) {
				$text .= '<p><small>' . esc_html__( 'Verified buyer – order', 'ecofridge-blocks' ) . ' <a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a></small></p>';
			}
		}
		return $text;
	},
	10,
	2
);
