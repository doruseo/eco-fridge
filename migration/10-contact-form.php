<?php
/**
 * Pasul 10: formular de contact (WPForms Lite) — recreează CF7 „Contact us" (542).
 * Rulare: cp migration/10-contact-form.php source-dump/_run.php
 *         npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * Câmpuri 1:1 cu originalul: Name*, Company, Email*, Phone*, Enquiry.
 * Notificare -> sales@eco-fridge.co.uk, subiect + Reply-To ca în CF7.
 * Inserează formularul pe pagina contact-us (bloc shortcode), idempotent.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! post_type_exists( 'wpforms' ) ) {
	WP_CLI::error( 'WPForms nu e activ.' );
}

wp_set_current_user( 1 );

// —— structura formularului (schema WPForms) ——
$fields = array(
	'1' => array( 'id' => '1', 'type' => 'name',     'label' => 'Name',          'format' => 'simple', 'required' => '1', 'size' => 'large' ),
	'2' => array( 'id' => '2', 'type' => 'text',     'label' => 'Company',                             'size' => 'large' ),
	'3' => array( 'id' => '3', 'type' => 'email',    'label' => 'Email address',  'required' => '1',    'size' => 'large' ),
	'4' => array( 'id' => '4', 'type' => 'text',     'label' => 'Phone number',   'required' => '1',    'size' => 'large' ),
	'5' => array( 'id' => '5', 'type' => 'textarea', 'label' => 'Enquiry',                              'size' => 'large' ),
);

$settings = array(
	'form_title'             => 'Contact ECO-Fridge',
	'form_desc'              => '',
	'submit_text'            => 'Send',
	'submit_text_processing' => 'Sending&hellip;',
	'antispam_v3'            => '1',
	'notification_enable'    => '1',
	'notifications'          => array(
		'1' => array(
			'notification_name' => 'Default Notification',
			'email'             => 'sales@eco-fridge.co.uk',
			'subject'           => 'Enquiry from ECO-Fridge website contact form',
			'sender_name'       => '{field_id="1"}',
			'sender_address'    => '{admin_email}',
			'replyto'           => '{field_id="3"}',
			'message'           => '{all_fields}',
		),
	),
	'confirmations'          => array(
		'1' => array(
			'type'           => 'message',
			'message'        => '<p>Thank you for your enquiry. Our team will be in touch shortly.</p>',
			'message_scroll' => '1',
		),
	),
);

// —— creare / update form (idempotent după meta marker) ——
$existing = get_posts( array(
	'post_type'   => 'wpforms',
	'post_status' => 'any',
	'numberposts' => 1,
	'fields'      => 'ids',
	'meta_key'    => '_ef_contact_form',
	'meta_value'  => '1',
) );

$postarr = array(
	'post_type'   => 'wpforms',
	'post_status' => 'publish',
	'post_title'  => 'Contact ECO-Fridge',
	'post_author' => 1,
	'post_excerpt'=> 'Contact form',
);

if ( $existing ) {
	$form_id = $existing[0];
	$postarr['ID'] = $form_id;
	wp_update_post( $postarr );
} else {
	$form_id = wp_insert_post( $postarr, true );
	if ( is_wp_error( $form_id ) ) { WP_CLI::error( 'Form: ' . $form_id->get_error_message() ); }
}

$form_data = array(
	'id'       => (string) $form_id,
	'field_id' => 6,
	'fields'   => $fields,
	'settings' => $settings,
	'meta'     => array( 'template' => '' ),
);

// WPForms citește form_data din post_content (JSON). post_title trebuie să fie titlul formularului.
wp_update_post( array(
	'ID'           => $form_id,
	'post_content' => wp_slash( wp_json_encode( $form_data ) ),
) );
update_post_meta( $form_id, '_ef_contact_form', '1' );

WP_CLI::log( "WPForms form ID = $form_id" );

// —— inserare pe pagina contact-us (bloc shortcode), idempotent ——
$page = get_page_by_path( 'contact-us' );
if ( ! $page ) {
	WP_CLI::warning( 'Pagina contact-us lipsește — formularul nu a fost inserat.' );
} else {
	$content = $page->post_content;
	if ( false === strpos( $content, 'ef-contact-form' ) && false === strpos( $content, '[wpforms' ) ) {
		$form_block = "\n\n" . '<!-- wp:uagb/container {"block_id":"efcontact","contentWidth":"full_width","innerContentWidth":"alignwide","directionDesktop":"column","topPaddingDesktop":10,"bottomPaddingDesktop":45,"leftPaddingDesktop":20,"rightPaddingDesktop":20} -->'
			. '<div class="wp-block-uagb-container uagb-block-efcontact alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap">'
			. '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading ef-section-title">Send us an enquiry</h2><!-- /wp:heading -->'
			. '<!-- wp:shortcode -->' . "\n" . '<div class="ef-contact-form">[wpforms id="' . $form_id . '" title="false" description="false"]</div>' . "\n" . '<!-- /wp:shortcode -->'
			. '</div></div><!-- /wp:uagb/container -->';
		wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content . $form_block ) );
		WP_CLI::log( 'Formular inserat pe contact-us (page ' . $page->ID . ').' );
	} else {
		WP_CLI::log( 'contact-us conține deja un formular — sărit.' );
	}
}

WP_CLI::success( 'Formular de contact WPForms finalizat.' );
