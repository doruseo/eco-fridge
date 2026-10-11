<?php
/**
 * Contact form handling for the efb/contact-form block (replaces WPForms/CF7).
 *
 * The browser only sends the post ID, the form ID and the field values. The form
 * configuration (fields, recipient, subject) is always read server-side from the
 * block saved in that post, so it cannot be tampered with.
 *
 * Spam protection: honeypot field, minimum fill time (measured client-side),
 * per-IP rate limit.
 * Every submission is stored as an `efb_entry` post (Form Entries in the admin)
 * so nothing is lost when email delivery fails.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'efb/v1',
			'/form',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'efb_form_submit',
			)
		);
	}
);

/**
 * Find a contact form block by its formId inside a post's blocks (recursive).
 *
 * @param array  $blocks  Parsed blocks.
 * @param string $form_id Form ID.
 * @return array|null Block attributes.
 */
function efb_find_form_block( $blocks, $form_id ) {
	foreach ( $blocks as $block ) {
		if ( 'efb/contact-form' === $block['blockName'] && isset( $block['attrs']['formId'] ) && $block['attrs']['formId'] === $form_id ) {
			return $block['attrs'];
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = efb_find_form_block( $block['innerBlocks'], $form_id );
			if ( $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * Default fields — 1:1 with the original "Contact us" form.
 *
 * @return array
 */
function efb_form_default_fields() {
	return array(
		array( 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
		array( 'name' => 'company', 'label' => 'Company', 'type' => 'text', 'required' => false ),
		array( 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true ),
		array( 'name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true ),
		array( 'name' => 'enquiry', 'label' => 'Enquiry', 'type' => 'textarea', 'required' => false ),
	);
}

/**
 * Handle a submission.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function efb_form_submit( WP_REST_Request $request ) {
	$params  = $request->get_params();
	$post_id = isset( $params['_efb_post'] ) ? absint( $params['_efb_post'] ) : 0;
	$form_id = isset( $params['_efb_form'] ) ? sanitize_key( $params['_efb_form'] ) : '';
	$post    = $post_id ? get_post( $post_id ) : null;

	if ( ! $post || 'publish' !== $post->post_status || ! $form_id ) {
		return new WP_Error( 'efb_form_invalid', __( 'This form is no longer available.', 'ecofridge-blocks' ), array( 'status' => 400 ) );
	}
	$attrs = efb_find_form_block( parse_blocks( $post->post_content ), $form_id );
	if ( ! $attrs ) {
		return new WP_Error( 'efb_form_invalid', __( 'This form is no longer available.', 'ecofridge-blocks' ), array( 'status' => 400 ) );
	}

	// Spam checks: honeypot, minimum fill time, rate limit.
	if ( ! empty( $params['_efb_hp'] ) ) {
		return rest_ensure_response( array( 'ok' => true, 'message' => $attrs['successMessage'] ?? '' ) );
	}
	// Seconds the visitor spent on the form, measured in the browser (no clock skew).
	// Missing = not submitted through the page script (typical of spam bots).
	$elapsed = isset( $params['_efb_elapsed'] ) ? (int) $params['_efb_elapsed'] : 0;
	if ( $elapsed < 3 ) {
		return new WP_Error( 'efb_form_fast', __( 'Please take a moment to complete the form and try again.', 'ecofridge-blocks' ), array( 'status' => 429 ) );
	}
	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate_key = 'efb_rl_' . md5( $ip );
	$count    = (int) get_transient( $rate_key );
	if ( $count >= 5 ) {
		return new WP_Error( 'efb_form_rate', __( 'Too many submissions. Please try again in a few minutes.', 'ecofridge-blocks' ), array( 'status' => 429 ) );
	}

	// Validate fields against the server-side configuration.
	$fields   = ! empty( $attrs['fields'] ) ? $attrs['fields'] : efb_form_default_fields();
	$values   = array();
	$errors   = array();
	$reply_to = '';
	foreach ( $fields as $field ) {
		$name  = sanitize_key( $field['name'] ?? '' );
		$label = $field['label'] ?? $name;
		$type  = $field['type'] ?? 'text';
		if ( ! $name ) {
			continue;
		}
		$raw   = isset( $params[ $name ] ) ? wp_unslash( $params[ $name ] ) : '';
		$value = 'textarea' === $type ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		if ( ! empty( $field['required'] ) && '' === $value ) {
			/* translators: %s: field label */
			$errors[ $name ] = sprintf( __( '%s is required.', 'ecofridge-blocks' ), $label );
		} elseif ( 'email' === $type && '' !== $value && ! is_email( $value ) ) {
			$errors[ $name ] = __( 'Please enter a valid email address.', 'ecofridge-blocks' );
		} elseif ( 'tel' === $type && '' !== $value && ! preg_match( '/^[0-9 +()\-]{6,20}$/', $value ) ) {
			$errors[ $name ] = __( 'Please enter a valid phone number.', 'ecofridge-blocks' );
		}
		if ( 'email' === $type && is_email( $value ) && ! $reply_to ) {
			$reply_to = $value;
		}
		$values[ $label ] = $value;
	}
	if ( $errors ) {
		return new WP_Error( 'efb_form_errors', __( 'Please correct the highlighted fields.', 'ecofridge-blocks' ), array( 'status' => 422, 'fields' => $errors ) );
	}

	set_transient( $rate_key, $count + 1, 10 * MINUTE_IN_SECONDS );

	// Store the entry.
	$first = reset( $values );
	$entry = wp_insert_post(
		array(
			'post_type'   => 'efb_entry',
			'post_status' => 'private',
			'post_title'  => sprintf( '%s — %s', $attrs['formName'] ?? __( 'Contact form', 'ecofridge-blocks' ), $first ? $first : __( '(no name)', 'ecofridge-blocks' ) ),
		)
	);
	if ( $entry && ! is_wp_error( $entry ) ) {
		update_post_meta( $entry, '_efb_values', $values );
		update_post_meta( $entry, '_efb_source', get_permalink( $post_id ) );
		update_post_meta( $entry, '_efb_ip', $ip );
	}

	// Email notification.
	$to      = ! empty( $attrs['recipient'] ) && is_email( $attrs['recipient'] ) ? $attrs['recipient'] : get_option( 'admin_email' );
	$subject = ! empty( $attrs['subject'] ) ? $attrs['subject'] : __( 'Enquiry from ECO-Fridge website contact form', 'ecofridge-blocks' );
	$body    = '';
	foreach ( $values as $label => $value ) {
		$body .= $label . ":\n" . ( '' !== $value ? $value : '-' ) . "\n\n";
	}
	$body   .= '--' . "\n" . sprintf( 'Sent from %s', get_permalink( $post_id ) );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( $reply_to ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	}
	$sent = wp_mail( $to, $subject, $body, $headers );
	if ( $entry && ! is_wp_error( $entry ) ) {
		update_post_meta( $entry, '_efb_mail_sent', $sent ? 'yes' : 'no' );
	}

	return rest_ensure_response(
		array(
			'ok'      => true,
			'message' => ! empty( $attrs['successMessage'] ) ? $attrs['successMessage'] : __( 'Thank you for your enquiry. We will be in touch shortly.', 'ecofridge-blocks' ),
		)
	);
}

/**
 * Admin: show the submitted values on the entry screen and in the list.
 */
add_action(
	'add_meta_boxes_efb_entry',
	static function () {
		add_meta_box(
			'efb-entry-values',
			__( 'Submitted values', 'ecofridge-blocks' ),
			static function ( $post ) {
				$values = (array) get_post_meta( $post->ID, '_efb_values', true );
				echo '<table class="widefat striped"><tbody>';
				foreach ( $values as $label => $value ) {
					echo '<tr><th style="width:160px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
				}
				echo '<tr><th>' . esc_html__( 'Page', 'ecofridge-blocks' ) . '</th><td>' . esc_html( get_post_meta( $post->ID, '_efb_source', true ) ) . '</td></tr>';
				echo '<tr><th>' . esc_html__( 'Email sent', 'ecofridge-blocks' ) . '</th><td>' . esc_html( get_post_meta( $post->ID, '_efb_mail_sent', true ) ) . '</td></tr>';
				echo '<tr><th>IP</th><td>' . esc_html( get_post_meta( $post->ID, '_efb_ip', true ) ) . '</td></tr>';
				echo '</tbody></table>';
			},
			'efb_entry',
			'normal',
			'high'
		);
	}
);

add_filter(
	'manage_efb_entry_posts_columns',
	static function ( $columns ) {
		return array(
			'cb'        => $columns['cb'],
			'title'     => __( 'Entry', 'ecofridge-blocks' ),
			'efb_email' => __( 'Email', 'ecofridge-blocks' ),
			'efb_mail'  => __( 'Email sent', 'ecofridge-blocks' ),
			'date'      => $columns['date'],
		);
	}
);

add_action(
	'manage_efb_entry_posts_custom_column',
	static function ( $column, $post_id ) {
		if ( 'efb_email' === $column ) {
			$values = (array) get_post_meta( $post_id, '_efb_values', true );
			foreach ( $values as $value ) {
				if ( is_email( $value ) ) {
					echo '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
					break;
				}
			}
		} elseif ( 'efb_mail' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_efb_mail_sent', true ) );
		}
	},
	10,
	2
);
