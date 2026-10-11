<?php
/**
 * Toggle block: native <details>/<summary>, so it works without JavaScript and
 * is keyboard/screen-reader accessible out of the box.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( $content ) ) {
	return;
}

printf(
	'<details %1$s%2$s><summary class="efb-toggle__title">%3$s</summary><div class="efb-toggle__body">%4$s</div></details>',
	get_block_wrapper_attributes( array( 'class' => 'efb-toggle' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$attributes['open'] ? ' open' : '',
	wp_kses_post( $attributes['title'] ),
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks.
);
