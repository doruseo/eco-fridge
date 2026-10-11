<?php
/**
 * "[Category] Features" section: a grid of feature cards, each with an optional icon,
 * a short title and a sentence or two.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

final class EFB_Landing_Features {

	/**
	 * Icon key => [ label, SVG path data (24×24, stroked) ].
	 */
	public static function icons() {
		return array(
			'snowflake'   => array( __( 'Snowflake (cooling)', 'ecofridge-blocks' ), 'M12 2v20M2 12h20M4.9 4.9l14.2 14.2M19.1 4.9 4.9 19.1M9 3.5l3 2.5 3-2.5M9 20.5l3-2.5 3 2.5M3.5 9l2.5 3-2.5 3M20.5 9 18 12l2.5 3' ),
			'thermometer' => array( __( 'Thermometer (temperature)', 'ecofridge-blocks' ), 'M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z' ),
			'energy'      => array( __( 'Lightning bolt (energy)', 'ecofridge-blocks' ), 'M13 2 3 14h9l-1 8 10-12h-9l1-8z' ),
			'eco'         => array( __( 'Leaf (eco / R290)', 'ecofridge-blocks' ), 'M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10zM2 21c0-3 1.85-5.36 5.08-6' ),
			'light'       => array( __( 'Light bulb (LED lighting)', 'ecofridge-blocks' ), 'M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z' ),
			'display'     => array( __( 'Eye (visibility / display)', 'ecofridge-blocks' ), 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z' ),
			'shelves'     => array( __( 'Layers (shelves / capacity)', 'ecofridge-blocks' ), 'M12 2 2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5' ),
			'airflow'     => array( __( 'Wind (fan / airflow)', 'ecofridge-blocks' ), 'M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2' ),
			'defrost'     => array( __( 'Droplet (defrost / humidity)', 'ecofridge-blocks' ), 'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z' ),
			'size'        => array( __( 'Ruler (sizes / dimensions)', 'ecofridge-blocks' ), 'M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-4.6-4.6a1 1 0 0 1 0-1.4L15.3 2.7a1 1 0 0 1 1.4 0l4.6 4.6a1 1 0 0 1 0 1.4zM7.5 10.5l2 2M10.5 7.5l2 2M13.5 4.5l2 2M4.5 13.5l2 2' ),
			'quiet'       => array( __( 'Muted speaker (quiet)', 'ecofridge-blocks' ), 'M11 5 6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6' ),
			'lock'        => array( __( 'Lock (security)', 'ecofridge-blocks' ), 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4' ),
			'quality'     => array( __( 'Shield (quality / warranty)', 'ecofridge-blocks' ), 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 12l2 2 4-4' ),
			'service'     => array( __( 'Wrench (service / parts)', 'ecofridge-blocks' ), 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z' ),
			'delivery'    => array( __( 'Truck (delivery)', 'ecofridge-blocks' ), 'M1 3h15v13H1zM16 8h4l3 3v5h-7zM5.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM18.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z' ),
			'check'       => array( __( 'Tick (general)', 'ecofridge-blocks' ), 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3' ),
			'star'        => array( __( 'Star (premium)', 'ecofridge-blocks' ), 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' ),
		);
	}

	public static function svg( $icon ) {
		$icons = self::icons();
		if ( empty( $icons[ $icon ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . esc_attr( $icons[ $icon ][1] ) . '"/></svg>';
	}

	/**
	 * @param array $items Each: icon, title, text.
	 */
	public static function render( array $items ) {
		$html = '';
		foreach ( $items as $item ) {
			$icon  = self::svg( $item['icon'] ?? '' );
			$html .= '<li class="efbl-feature' . ( '' === $icon ? ' efbl-feature--no-icon' : '' ) . '">';
			$html .= '' !== $icon ? '<span class="efbl-feature__icon">' . $icon . '</span>' : '';
			$html .= '<h3 class="efbl-feature__title">' . esc_html( $item['title'] ) . '</h3>';
			if ( '' !== trim( (string) ( $item['text'] ?? '' ) ) ) {
				$html .= '<div class="efbl-feature__text">' . wpautop( wp_kses_post( $item['text'] ) ) . '</div>';
			}
			$html .= '</li>';
		}
		return '' === $html ? '' : '<ul class="efbl-features">' . $html . '</ul>';
	}
}
