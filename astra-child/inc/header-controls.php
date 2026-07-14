<?php
/**
 * Customize controls for the green bar (row 2, custom): cart icon + sticky.
 *
 * Row 2 (icon-mega) is rendered by custom code (not by the native Header Builder,
 * which cannot reproduce icon-mega). However:
 *  - The CART in the green bar is the NATIVE Astra cart → its icon = the Astra
 *    option `woo-header-cart-icon`; we expose it here so you can change it.
 *  - STICKY: a toggle for the green bar (the custom header stuck to the top on scroll).
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'ef_greenbar', array(
		'title'    => __( 'Green Category Bar (row 2)', 'eco-fridge-child' ),
		'priority' => 32,
	) );

	/* 1) Cart icon — uses the native Astra option `woo-header-cart-icon`,
	 *    which the native cart in the green bar displays. */
	if ( ! $wp_customize->get_setting( 'astra-settings[woo-header-cart-icon]' ) ) {
		$wp_customize->add_setting( 'astra-settings[woo-header-cart-icon]', array(
			'type'              => 'option',
			'default'           => 'bag',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_key',
		) );
	}
	$wp_customize->add_control( 'ef_cart_icon_ctrl', array(
		'type'        => 'select',
		'section'     => 'ef_greenbar',
		'settings'    => 'astra-settings[woo-header-cart-icon]',
		'label'       => __( 'Cart icon', 'eco-fridge-child' ),
		'description' => __( 'The native Astra cart icon shown in the green bar.', 'eco-fridge-child' ),
		'choices'     => array(
			'default' => __( 'Default', 'eco-fridge-child' ),
			'bag'     => __( 'Bag', 'eco-fridge-child' ),
			'basket'  => __( 'Basket', 'eco-fridge-child' ),
			'cart'    => __( 'Cart', 'eco-fridge-child' ),
		),
	) );

	/* 2) Sticky for the green bar (the custom header). */
	$wp_customize->add_setting( 'ef_greenbar_sticky', array(
		'type'              => 'theme_mod',
		'default'           => true,
		'transport'         => 'refresh',
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'ef_greenbar_sticky', array(
		'type'        => 'checkbox',
		'section'     => 'ef_greenbar',
		'label'       => __( 'Sticky top header', 'eco-fridge-child' ),
		'description' => __( 'When checked, the header (including the green bar) stays stuck to the top on scroll. Unchecked = scrolls with the page.', 'eco-fridge-child' ),
	) );
} );

/** true if the header (green bar) is set to sticky. */
function ef_header_is_sticky() {
	return (bool) get_theme_mod( 'ef_greenbar_sticky', true );
}
