<?php
/**
 * Contact form block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_fields  = ! empty( $attributes['fields'] ) ? $attributes['fields'] : efb_form_default_fields();
$efb_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
$efb_uid     = wp_unique_id( 'efb-form-' );

$efb_wrapper = get_block_wrapper_attributes( array( 'class' => 'efb-form-wrap' ) );
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<form class="efb-form" method="post" action="<?php echo esc_url( rest_url( 'efb/v1/form' ) ); ?>" novalidate>
		<?php foreach ( $efb_fields as $efb_field ) : ?>
			<?php
			$efb_name = sanitize_key( $efb_field['name'] ?? '' );
			if ( ! $efb_name ) {
				continue;
			}
			$efb_type  = in_array( $efb_field['type'] ?? 'text', array( 'text', 'email', 'tel', 'textarea', 'number', 'url' ), true ) ? $efb_field['type'] : 'text';
			$efb_req   = ! empty( $efb_field['required'] );
			$efb_id    = $efb_uid . '-' . $efb_name;
			$efb_auto  = array(
				'name'    => 'name',
				'email'   => 'email',
				'phone'   => 'tel',
				'company' => 'organization',
			);
			$efb_attrs = 'id="' . esc_attr( $efb_id ) . '" name="' . esc_attr( $efb_name ) . '"' . ( $efb_req ? ' required aria-required="true"' : '' ) . ( isset( $efb_auto[ $efb_name ] ) ? ' autocomplete="' . esc_attr( $efb_auto[ $efb_name ] ) . '"' : '' );
			?>
			<p class="efb-form__field efb-form__field--<?php echo esc_attr( $efb_type ); ?>">
				<label for="<?php echo esc_attr( $efb_id ); ?>"><?php echo esc_html( $efb_field['label'] ?? $efb_name ); ?><?php echo $efb_req ? ' <span class="efb-form__req" aria-hidden="true">*</span>' : ''; ?></label>
				<?php if ( 'textarea' === $efb_type ) : ?>
					<textarea <?php echo $efb_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> rows="6"></textarea>
				<?php else : ?>
					<input type="<?php echo esc_attr( $efb_type ); ?>" <?php echo $efb_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> />
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
		<p class="efb-form__hp" aria-hidden="true"><label>Leave this field empty <input type="text" name="_efb_hp" tabindex="-1" autocomplete="off" /></label></p>
		<input type="hidden" name="_efb_elapsed" value="0" />
		<input type="hidden" name="_efb_post" value="<?php echo esc_attr( $efb_post_id ); ?>" />
		<input type="hidden" name="_efb_form" value="<?php echo esc_attr( $attributes['formId'] ); ?>" />
		<p class="efb-form__actions"><button type="submit" class="efb-button efb-form__submit"><?php echo esc_html( $attributes['buttonText'] ); ?></button></p>
		<div class="efb-form__status" role="status" aria-live="polite"></div>
	</form>
</div>
