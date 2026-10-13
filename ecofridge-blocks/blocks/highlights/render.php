<?php
/**
 * Highlights block — a row of boxes, each with an icon, a bold lead and a
 * short text (e.g. "Free delivery to UK mainland addresses").
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_icons = efb_highlight_icons();
$efb_items = '';
foreach ( (array) $attributes['items'] as $efb_item ) {
	$efb_strong = trim( (string) ( $efb_item['strong'] ?? '' ) );
	$efb_text   = (string) ( $efb_item['text'] ?? '' );
	if ( '' === $efb_strong && '' === trim( $efb_text ) ) {
		continue;
	}
	$efb_icon   = $efb_icons[ $efb_item['icon'] ?? '' ] ?? $efb_icons['check'];
	// A text starting with punctuation (", shown excluding VAT") follows the lead directly.
	$efb_sep    = preg_match( '~^[,.;:]~', $efb_text ) ? '' : ' ';
	$efb_items .= '<li><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="' . esc_attr( $efb_icon['path'] ) . '"/></svg><span>'
		. ( '' !== $efb_strong ? '<strong>' . esc_html( $efb_strong ) . '</strong>' . $efb_sep : '' ) . esc_html( $efb_text ) . '</span></li>';
}
if ( '' === $efb_items ) {
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'ef-home-trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php echo $efb_items; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</ul>
