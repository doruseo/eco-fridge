<?php
/**
 * Controale Customize pentru bara verde (rândul 2, custom): iconița coșului + sticky.
 *
 * Rândul 2 (icon-mega) e randat de cod custom (nu de Header Builder-ul nativ, care nu
 * poate reproduce icon-mega). Totuși:
 *  - COȘUL din bara verde e coșul NATIV Astra → iconița lui = opțiunea Astra
 *    `woo-header-cart-icon`; o expunem aici ca s-o poți schimba.
 *  - STICKY: toggle pentru bara verde (header-ul custom lipit sus la scroll).
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'ef_greenbar', array(
		'title'    => __( 'Green Category Bar (row 2)', 'eco-fridge-child' ),
		'priority' => 32,
	) );

	/* 1) Iconița coșului — folosește opțiunea nativă Astra `woo-header-cart-icon`,
	 *    pe care o afișează coșul nativ din bara verde. */
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
		'description' => __( 'Iconița coșului nativ Astra din bara verde.', 'eco-fridge-child' ),
		'choices'     => array(
			'default' => __( 'Default', 'eco-fridge-child' ),
			'bag'     => __( 'Bag', 'eco-fridge-child' ),
			'basket'  => __( 'Basket', 'eco-fridge-child' ),
			'cart'    => __( 'Cart', 'eco-fridge-child' ),
		),
	) );

	/* 2) Sticky pentru bara verde (header-ul custom). */
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
		'description' => __( 'Când e bifat, header-ul (inclusiv bara verde) rămâne lipit sus la scroll. Debifat = derulează cu pagina.', 'eco-fridge-child' ),
	) );
} );

/** true dacă header-ul (bara verde) e setat sticky. */
function ef_header_is_sticky() {
	return (bool) get_theme_mod( 'ef_greenbar_sticky', true );
}
