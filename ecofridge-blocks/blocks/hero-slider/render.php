<?php
/**
 * Hero slider block.
 *
 * Slide shape: { eyebrow, title, text, buttonText, buttonUrl, imageId, imageUrl, imageAlt }.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_slides = array_values( array_filter( (array) $attributes['slides'], 'is_array' ) );
if ( ! $efb_slides ) {
	return;
}
$efb_count = count( $efb_slides );

$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'efb-hero',
		'data-autoplay'        => $attributes['autoplay'] ? (int) $attributes['interval'] : 0,
		'style'                => '--efb-hero-min:' . (int) $attributes['minHeight'] . 'px',
		'aria-roledescription' => 'carousel',
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<div class="efb-hero__track">
		<?php foreach ( $efb_slides as $efb_i => $efb_s ) : ?>
			<div class="efb-hero__slide<?php echo 0 === $efb_i ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $efb_i + 1 ) . ' / ' . $efb_count ); ?>"<?php echo 0 === $efb_i ? '' : ' aria-hidden="true"'; ?>>
				<div class="efb-hero__content">
					<?php if ( ! empty( $efb_s['eyebrow'] ) ) : ?>
						<p class="efb-hero__eyebrow"><?php echo esc_html( $efb_s['eyebrow'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $efb_s['title'] ) ) : ?>
						<<?php echo 0 === $efb_i ? 'h1' : 'h2'; ?> class="efb-hero__title"><?php echo esc_html( $efb_s['title'] ); ?></<?php echo 0 === $efb_i ? 'h1' : 'h2'; ?>>
					<?php endif; ?>
					<?php if ( ! empty( $efb_s['text'] ) ) : ?>
						<p class="efb-hero__text"><?php echo esc_html( $efb_s['text'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $efb_s['buttonText'] ) && ! empty( $efb_s['buttonUrl'] ) ) : ?>
						<a class="efb-button efb-hero__button" href="<?php echo esc_url( $efb_s['buttonUrl'] ); ?>"<?php echo $efb_i ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $efb_s['buttonText'] ); ?></a>
					<?php endif; ?>
				</div>
				<div class="efb-hero__media">
					<?php
					echo efb_image( // phpcs:ignore WordPress.Security.EscapeOutput
						$efb_s['imageId'] ?? 0,
						$efb_s['imageUrl'] ?? '',
						$efb_s['imageAlt'] ?? ( $efb_s['title'] ?? '' ),
						'large',
						0 === $efb_i ? array( 'loading' => 'eager', 'fetchpriority' => 'high' ) : array()
					);
					?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $efb_count > 1 ) : ?>
		<button type="button" class="efb-hero__arrow efb-hero__prev" aria-label="<?php esc_attr_e( 'Previous slide', 'ecofridge-blocks' ); ?>">&#8249;</button>
		<button type="button" class="efb-hero__arrow efb-hero__next" aria-label="<?php esc_attr_e( 'Next slide', 'ecofridge-blocks' ); ?>">&#8250;</button>
		<div class="efb-hero__dots">
			<?php for ( $efb_d = 0; $efb_d < $efb_count; $efb_d++ ) : ?>
				<button type="button" class="efb-hero__dot<?php echo 0 === $efb_d ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'ecofridge-blocks' ), $efb_d + 1 ) ); ?>"></button>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
</div>
