<?php
/**
 * Product search block.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_wrapper = get_block_wrapper_attributes( array( 'class' => 'efb-search-block' ) );
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	echo efb_render_search_form( // phpcs:ignore WordPress.Security.EscapeOutput
		array(
			'placeholder' => $attributes['placeholder'],
			'button'      => $attributes['buttonText'],
			'showButton'  => (bool) $attributes['showButton'],
		)
	);
	?>
</div>
