<?php
/**
 * Product tag copy — type-of-business tags (used by migration 44).
 * Format: slug => [ short, long, faq, articles ] (see migration/category-copy).
 *
 * @package EcoFridgeMigration
 */

return array(

	'hotel-buffet-display-fridges'    => array(
		'short'    => '<p>Display fridges for hotel breakfasts, buffets and conference catering – double-glazed countertop chillers, cake display towers, premium patisserie displays and glass counters that keep food chilled and presented at its best.</p>',
		'long'     => <<<'HTML'
<h2>Chilled presentation for hotels and buffets</h2>
<p>Hotel breakfasts, buffets, conference catering and event spaces need chilled displays that look premium, keep food at a safe temperature for the length of the service and fit into rooms that are reset every day. This collection brings together the displays that hotels, restaurants and caterers use front of house.</p>

<h2>Displays for buffet service</h2>
<ul>
<li><strong>Countertop chillers</strong> – fully double-glazed stainless steel displays (1000, 1200 and 1500 mm wide, 720 mm high) that stand on an existing counter or buffet table and hold 0 to +5 °C – ideal for yoghurts, fruit, cold meats, cheese and desserts.</li>
<li><strong>Cake display towers</strong> – mirrored stainless steel uprights with glass on four sides (about 700 mm) or a large 1200 mm glass display with rear sliding doors, at −1 to +7 °C.</li>
<li><strong>Premium patisserie displays</strong> – double-glazed models in mirrored stainless steel for desserts, pastries and cakes.</li>
<li><strong>Premium glass counter</strong> – a frameless UV bonded counter that can be built into a new or existing counter.</li>
<li><strong>Open flat glass cabinet</strong> – a low stainless steel grab and go display for self-service at breakfast or in a lobby café.</li>
</ul>

<h2>Practical points for hotels</h2>
<ul>
<li><strong>Weight and surfaces.</strong> Countertop chillers weigh about 90–120 kg before food is loaded, so check that the counter or buffet station can carry them.</li>
<li><strong>Mobility.</strong> The cake towers and the premium glass counter have castors, so they can be moved between rooms and for cleaning.</li>
<li><strong>Power.</strong> Check sockets at each position before an event; leave space around every unit for ventilation.</li>
<li><strong>Time limits.</strong> Set holding times for each dish in your food safety plan and record display temperatures during service.</li>
</ul>

<h2>Presentation</h2>
<p>Mirrored stainless steel and double-glazed glass reflect light and keep displays clear of condensation, so food looks fresh throughout the service. Group products by course, label allergens clearly and refill in small quantities rather than overfilling at the start.</p>
HTML,
		'faq'      => array(
			array( 'What is a buffet display fridge?', 'A chilled glass display used to present cold food at a buffet or breakfast service – usually a countertop chiller standing on a buffet table, or a cake display tower – keeping food at a safe temperature while guests serve themselves or are served.' ),
			array( 'Can a countertop chiller go on a buffet table?', 'Yes, if the table is level, deep enough (about 750 mm) and strong enough: the units weigh about 90–120 kg before food is loaded. They plug into a standard socket.' ),
			array( 'What temperature do buffet displays hold?', 'Our countertop chillers hold 0 to +5 °C and the cake display towers −1 to +7 °C.' ),
			array( 'Can the displays be moved between rooms?', 'The cake display towers and the premium glass counter have castors. Countertop chillers are lifted into position and stay on their counter.' ),
		),
		'articles' => array( 7426, 7388, 7357 ),
	),

	'ice-cream-freezer-tag'           => array(
		'short'    => '<p>Ice cream freezers for shops, cafés, kiosks and forecourts – sliding glass lid chest freezers and glass door display freezers that keep ice cream, lollies and frozen desserts at −18 to −24 °C.</p>',
		'long'     => <<<'HTML'
<h2>Freezers that sell ice cream</h2>
<p>Ice cream is one of the highest-margin impulse lines a shop or café can sell – as long as it is kept properly frozen and customers can see it. An ice cream freezer keeps products at −18 °C or colder, protects their texture from temperature swings and puts them where customers make the decision to buy.</p>

<h2>Chest or upright?</h2>
<ul>
<li><strong>Sliding glass lid chest freezer</strong> – the classic ice cream freezer: about 1510 mm wide and 850 mm high, about 500 litres in five baskets, curved sliding glass lids, LED lighting, a lock and castors. Cold air stays in the chest when the lid opens, so it holds temperature well in busy summer trading.</li>
<li><strong>Upright glass door display freezers</strong> – show tubs, multipacks and frozen desserts at eye level in a 600 mm or 1200 mm footprint (about 362 or 806 litres on the premium models), with triple-glazed self-closing doors on the high-spec models.</li>
</ul>

<h2>Keeping ice cream at its best</h2>
<ul>
<li>Store and display at −18 °C or colder and avoid partial thawing: repeated softening and refreezing creates ice crystals and ruins texture.</li>
<li>Never stack products above the load line of a chest freezer.</li>
<li>Keep the freezer out of direct sunlight and away from heat sources – especially in summer.</li>
<li>Defrost when ice builds up on the walls, and keep lids and doors closed between customers.</li>
</ul>

<h2>Selling more ice cream</h2>
<p>Position the freezer where customers queue or pass the till, give each brand or product type its own basket or shelf, and keep best-sellers in the most visible spots. Outside the summer season, use the freezer for frozen desserts and ready meals so it earns its place all year.</p>
HTML,
		'faq'      => array(
			array( 'What is an ice cream freezer?', 'A display freezer designed to keep ice cream and frozen desserts at −18 °C or colder while customers can see them – usually a chest freezer with sliding glass lids or an upright glass door freezer.' ),
			array( 'What temperature should an ice cream freezer be?', 'Ice cream should be stored and displayed at −18 °C or colder. Our ice cream freezers operate between −18 and −24 °C.' ),
			array( 'Chest freezer or upright for ice cream?', 'A chest freezer is the classic choice for lollies and single portions near the till; an upright glass door freezer shows tubs and multipacks at eye level in less floor space.' ),
			array( 'How much does the chest display freezer hold?', 'About 500 litres in five baskets, in a cabinet about 1510 × 700 × 850 mm.' ),
			array( 'How much does an ice cream freezer cost?', 'Our sliding glass lid chest freezer is about £595 + VAT; upright display freezers start at about £850 + VAT.' ),
		),
		'articles' => array( 7381, 7365, 7356 ),
	),
);
