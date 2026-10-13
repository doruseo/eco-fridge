<?php
/**
 * Help box block — a green box with a title, a short text and two buttons
 * (styles: the theme's .ef-blog-cta).
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_buttons = '';
foreach ( array( array( 'primary', 'ef-btn ef-btn--light' ), array( 'secondary', 'ef-btn ef-btn--ghost' ) ) as $efb_b ) {
	$efb_text = trim( (string) $attributes[ $efb_b[0] . 'Text' ] );
	$efb_url  = trim( (string) $attributes[ $efb_b[0] . 'Url' ] );
	if ( '' !== $efb_text && '' !== $efb_url ) {
		$efb_buttons .= '<a class="' . esc_attr( $efb_b[1] ) . '" href="' . esc_url( $efb_url, array( 'http', 'https', 'tel', 'mailto' ) ) . '">' . esc_html( $efb_text ) . '</a>';
	}
}
?>
<aside <?php echo get_block_wrapper_attributes( array( 'class' => 'ef-blog-cta' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="ef-blog-cta__text">
		<?php if ( '' !== trim( (string) $attributes['title'] ) ) : ?>
			<p class="ef-blog-cta__title"><?php echo esc_html( $attributes['title'] ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $attributes['text'] ) ) : ?>
			<p><?php echo esc_html( $attributes['text'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $efb_buttons ) : ?>
		<div class="ef-blog-cta__actions"><?php echo $efb_buttons; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php endif; ?>
</aside>
