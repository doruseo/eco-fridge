<?php
/**
 * Spec table block — cells may contain simple inline HTML (links, strong).
 * Columns are sortable (assets/table.js) and the table stacks on mobile using
 * the header text as cell labels.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_head = array_values( (array) $attributes['head'] );
$efb_rows = array_values( array_filter( (array) $attributes['rows'], 'is_array' ) );
if ( ! $efb_head && ! $efb_rows ) {
	return;
}
$efb_allowed = array(
	'a'      => array(
		'href'   => true,
		'target' => true,
		'rel'    => true,
		'title'  => true,
	),
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
	'br'     => array(),
	'span'   => array( 'class' => true ),
	'sup'    => array(),
	'sub'    => array(),
);

$efb_wrapper = get_block_wrapper_attributes( array( 'class' => 'efb-table' . ( $attributes['sortable'] ? ' is-sortable' : '' ) ) );
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<table>
		<?php if ( '' !== $attributes['caption'] ) : ?>
			<caption><?php echo esc_html( $attributes['caption'] ); ?></caption>
		<?php endif; ?>
		<?php if ( $efb_head ) : ?>
			<thead>
				<tr>
					<?php foreach ( $efb_head as $efb_th ) : ?>
						<th scope="col"><?php echo wp_kses( (string) $efb_th, $efb_allowed ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
		<?php endif; ?>
		<tbody>
			<?php foreach ( $efb_rows as $efb_row ) : ?>
				<tr>
					<?php foreach ( array_values( $efb_row ) as $efb_c => $efb_cell ) : ?>
						<td data-label="<?php echo esc_attr( wp_strip_all_tags( (string) ( $efb_head[ $efb_c ] ?? '' ) ) ); ?>"><?php echo wp_kses( (string) $efb_cell, $efb_allowed ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
