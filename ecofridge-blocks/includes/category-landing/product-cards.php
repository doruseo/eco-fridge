<?php
/**
 * Product carousel used by the "All products in this range" and "Spare parts" sections:
 * the whole product image (never cropped), name, price, and a quantity box with − / + next to
 * an Add to cart button that uses WooCommerce's own AJAX add-to-cart.
 *
 * The carousel is a CSS scroll-snap track with previous/next buttons (assets/landing.js),
 * so it needs no slider library.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

final class EFB_Landing_Cards {

	/**
	 * @param WC_Product[] $products
	 * @param string       $label    Accessible name of the carousel.
	 */
	public static function carousel( array $products, $label ) {
		$cards = '';
		foreach ( $products as $product ) {
			$cards .= self::card( $product );
		}
		if ( '' === $cards ) {
			return '';
		}

		// WooCommerce's AJAX add-to-cart handles the buttons and refreshes the mini-cart.
		if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}
		wp_enqueue_script( 'efb-landing' );

		return '<div class="efbl-carousel" data-efbl-carousel role="region" aria-roledescription="carousel" aria-label="' . esc_attr( $label ) . '">'
			. '<button type="button" class="efbl-carousel__nav efbl-carousel__nav--prev" data-efbl-dir="-1" aria-label="' . esc_attr__( 'Previous products', 'ecofridge-blocks' ) . '" hidden><span aria-hidden="true">&#8249;</span></button>'
			. '<ul class="efbl-carousel__track">' . $cards . '</ul>'
			. '<button type="button" class="efbl-carousel__nav efbl-carousel__nav--next" data-efbl-dir="1" aria-label="' . esc_attr__( 'Next products', 'ecofridge-blocks' ) . '" hidden><span aria-hidden="true">&#8250;</span></button>'
			. '</div>';
	}

	private static function card( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			return '';
		}

		$link  = $product->get_permalink();
		$name  = $product->get_name();
		$image = $product->get_image(
			'woocommerce_single', // Not cropped, unlike the square catalogue thumbnail.
			array(
				'class'   => 'efbl-card__img',
				'loading' => 'lazy',
				'alt'     => $name,
				'sizes'   => '(min-width: 1200px) 230px, (min-width: 768px) 30vw, 48vw',
			)
		);

		$html  = '<li class="efbl-card">';
		$html .= '<a class="efbl-card__link" href="' . esc_url( $link ) . '">';
		$html .= '<span class="efbl-card__media">' . $image . '</span>';
		$html .= '<span class="efbl-card__title">' . esc_html( $name ) . '</span>';
		$html .= '</a>';
		$html .= '<span class="efbl-card__price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
		$html .= self::buy( $product );
		return $html . '</li>';
	}

	/** Quantity − / + and Add to cart for simple products; a link to the product for the rest. */
	private static function buy( $product ) {
		$id = $product->get_id();

		if ( ! $product->is_in_stock() ) {
			// The theme labels every button "Add to cart", so say plainly that this one cannot be bought now.
			return '<div class="efbl-card__buy"><span class="efbl-card__stock">' . esc_html__( 'Out of stock', 'ecofridge-blocks' ) . '</span>'
				. '<a class="button efbl-card__add efbl-card__add--link" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html__( 'View product', 'ecofridge-blocks' ) . '</a></div>';
		}
		if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() ) {
			return '<div class="efbl-card__buy"><a class="button efbl-card__add efbl-card__add--link" href="' . esc_url( $product->get_permalink() ) . '">'
				. esc_html__( 'Choose options', 'ecofridge-blocks' ) . '</a></div>';
		}

		$max   = $product->get_max_purchase_quantity();
		$input = sprintf(
			'<input type="number" class="efbl-qty__input" value="1" min="1" step="1" %1$s inputmode="numeric" aria-label="%2$s">',
			$max > 0 ? 'max="' . (int) $max . '"' : '',
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Quantity of %s', 'ecofridge-blocks' ), $product->get_name() ) )
		);

		$html  = '<div class="efbl-card__buy">';
		$html .= '<div class="efbl-qty">';
		$html .= '<button type="button" class="efbl-qty__btn" data-efbl-step="-1" aria-label="' . esc_attr__( 'Decrease quantity', 'ecofridge-blocks' ) . '">&minus;</button>';
		$html .= $input;
		$html .= '<button type="button" class="efbl-qty__btn" data-efbl-step="1" aria-label="' . esc_attr__( 'Increase quantity', 'ecofridge-blocks' ) . '">+</button>';
		$html .= '</div>';
		// No "product_type_simple" class: the theme binds a self-retriggering click handler to it.
		$html .= sprintf(
			'<a href="%1$s" rel="nofollow" data-quantity="1" data-product_id="%2$d" data-product_sku="%3$s" class="button add_to_cart_button ajax_add_to_cart efbl-card__add">%4$s</a>',
			esc_url( $product->add_to_cart_url() ),
			$id,
			esc_attr( $product->get_sku() ),
			esc_html( $product->add_to_cart_text() )
		);
		return $html . '</div>';
	}
}
