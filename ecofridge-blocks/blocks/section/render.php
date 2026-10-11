<?php
/**
 * Section block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_tag   = in_array( $attributes['tagName'], array( 'section', 'div', 'aside', 'header', 'footer', 'article' ), true ) ? $attributes['tagName'] : 'section';
$efb_width = in_array( $attributes['contentWidth'], array( 'boxed', 'narrow', 'full' ), true ) ? $attributes['contentWidth'] : 'boxed';
$efb_bg    = $attributes['bgImageId'] ? wp_get_attachment_image_url( (int) $attributes['bgImageId'], 'full' ) : $attributes['bgImageUrl'];

$efb_style = efb_style_vars(
	array(
		'--efb-bg'       => $attributes['bgColor'] ? sanitize_text_field( $attributes['bgColor'] ) : '',
		'--efb-color'    => $attributes['textColor'] ? sanitize_text_field( $attributes['textColor'] ) : '',
		'--efb-pt'       => (int) $attributes['paddingTop'] . 'px',
		'--efb-pb'       => (int) $attributes['paddingBottom'] . 'px',
		'--efb-pt-m'     => (int) $attributes['paddingTopMobile'] . 'px',
		'--efb-pb-m'     => (int) $attributes['paddingBottomMobile'] . 'px',
		'--efb-max'      => 'boxed' === $efb_width ? (int) $attributes['maxWidth'] . 'px' : '',
		'--efb-bg-image' => $efb_bg ? 'url("' . esc_url_raw( $efb_bg ) . '")' : '',
		'--efb-overlay'  => $attributes['overlay'] ? ( (int) $attributes['overlay'] / 100 ) : '',
		'text-align'     => $attributes['textAlign'] ? sanitize_key( $attributes['textAlign'] ) : '',
	)
);

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-section efb-section--' . $efb_width . ( $efb_bg ? ' has-bg-image' : '' ) . ( $attributes['overlay'] ? ' has-overlay' : '' ),
		'style' => $efb_style,
	)
);

printf(
	'<%1$s %2$s><div class="efb-section__inner">%3$s</div></%1$s>',
	tag_escape( $efb_tag ),
	$efb_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by core.
	$content // phpcs:ignore WordPress.Security.EscapeOutput -- inner blocks.
);
