<?php
/**
 * Category copy — Storage Cabinets tree (/range/storage-cabinets/…), used by
 * migration 39. Format: see commercial-fridge.php.
 *
 * @package EcoFridgeMigration
 */

return array(

	'storage-cabinets' => array(
		'name'     => 'Storage Cabinets',
		'short'    => '<p>Commercial storage fridges and freezers for kitchens, stockrooms, butchers and bakeries – upright, under counter, gastronorm and bakery cabinets in white or stainless steel.</p>',
		'long'     => <<<'HTML'
<h2>Storage refrigeration for professional kitchens</h2>
<p>Storage cabinets are the solid-door fridges and freezers that keep ingredients and stock safe behind the scenes. They are built for constant opening, heavy loads and warm kitchens, with strong shelving or gastronorm racks, digital controllers and easy-clean interiors.</p>

<h2>Upright storage</h2>
<ul>
<li><a href="/range/storage-cabinets/upright-storage-chiller/">Standard white upright storage chiller</a> and <a href="/range/storage-cabinets/standard-upright-storage-freezer/">freezer</a> – great-value single door cabinets.</li>
<li><a href="/range/storage-cabinets/upright-storage-chillers/">Premium heavy-duty upright chillers</a> and <a href="/range/storage-cabinets/upright-storage-freezers/">freezers</a> – single, large and double door models with auto defrost.</li>
<li><a href="/range/storage-cabinets/upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet/">Stainless steel gastronorm chillers and fish/meat cabinets</a> (−5 to +5 °C) and <a href="/range/storage-cabinets/standard-upright-stainless-steel-gastronorm-freezers/">gastronorm freezers</a>.</li>
<li><a href="/range/storage-cabinets/stainless-steel-upright-bakery-cabinets/">Bakery fridges, freezers and retarder provers</a> for tray-based bakery work.</li>
</ul>

<h2>Under counter and counter storage</h2>
<ul>
<li>Single door under counter fridges – <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-chiller/">standard</a> and <a href="/range/storage-cabinets/stainless-steel-under-counter-chiller/">premium</a>.</li>
<li>Single door under counter freezers – <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-freezer/">standard</a> and <a href="/range/storage-cabinets/premium-stainless-steel-under-counter-freezer/">premium</a>.</li>
<li>Two and three door stainless steel <a href="/range/storage-cabinets/stainless-steel-counter-chillers/">counter chillers</a> and <a href="/range/storage-cabinets/stainless-steel-counter-freezers/">counter freezers</a>.</li>
</ul>

<h2>Choosing storage cabinets</h2>
<p>Plan around your deliveries and menu: how much chilled and frozen stock you hold, where it is used, and how hot the kitchen gets. Stainless steel suits professional kitchens; white steel is cost-effective for stockrooms. Chillers run around +2 to +7 °C (−5 to +5 °C for meat and fish), freezers −18 to −24 °C.</p>
HTML,
		'faq'      => array(
			array( 'What are storage cabinets?', 'Storage cabinets are commercial fridges and freezers with solid doors, used to keep ingredients and stock chilled or frozen in kitchens, stockrooms and back-of-house areas.' ),
			array( 'Standard or premium storage cabinets?', 'Standard models are great-value white steel cabinets. Premium models add heavy-duty construction, auto defrost, larger capacities and stainless steel options.' ),
			array( 'What temperatures do they hold?', 'Storage chillers hold about +2 to +7 °C, meat and fish gastronorm chillers −5 to +5 °C, and freezers −18 to −24 °C.' ),
			array( 'What is a gastronorm cabinet?', 'A cabinet sized for gastronorm (GN) pans and trays used in professional kitchens, usually in stainless steel with ladder racks.' ),
			array( 'Are they suitable for hot kitchens?', 'Many models are designed for high ambient temperatures; each product’s specification says so. All need ventilation space.' ),
		),
		'articles' => array( 7431, 7369, 7362 ),
	),

	'upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet' => array(
		'name'     => 'Stainless Steel Gastronorm Chillers & Fish/Meat Cabinets',
		'short'    => '<p>Upright stainless steel gastronorm chillers and fish/meat storage cabinets – single and double door, −5 to +5 °C, forced-air cooling and castors for butchers and professional kitchens.</p>',
		'long'     => <<<'HTML'
<h2>Chilled storage built for meat and fish</h2>
<p>These upright stainless steel cabinets are designed for professional kitchens, butchers and fishmongers. They run colder than a standard fridge – −5 to +5 °C – and take gastronorm pans and trays, so produce moves straight from storage to the prep station.</p>

<h2>Key features</h2>
<ul>
<li>Forced-air refrigeration for an even temperature throughout the cabinet.</li>
<li>75 mm water-based polyurethane insulation.</li>
<li>18/10 stainless steel doors, full-height handles and removable ladder racks.</li>
<li>Magnetic door gaskets that are easy to replace; fitted castors.</li>
<li>Digital temperature display.</li>
</ul>

<h2>Models</h2>
<p>A large single door chiller about 740 mm wide, a double door chiller about 1440 mm wide (both about 900 mm deep and 2000 mm high), and a large fish/meat storage cabinet with manual defrost. For frozen storage, see the <a href="/range/storage-cabinets/standard-upright-stainless-steel-gastronorm-freezers/">gastronorm freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a gastronorm chiller?', 'It is a commercial fridge with racks sized for gastronorm kitchen pans, usually built in stainless steel for professional kitchens.' ),
			array( 'Why does it run down to −5 °C?', 'Holding meat and fish just above freezing keeps them fresher for longer than a standard +2 to +7 °C fridge.' ),
			array( 'What sizes are available?', 'About 740 × 900 × 2000 mm (single door) and 1440 × 900 × 2000 mm (double door).' ),
			array( 'Are the racks removable?', 'Yes, the 18/10 stainless steel ladder racks lift out for cleaning.' ),
		),
		'articles' => array( 7343, 7440, 7376 ),
	),

	'standard-upright-stainless-steel-gastronorm-freezers' => array(
		'name'     => 'Stainless Steel Gastronorm Freezers',
		'short'    => '<p>Upright stainless steel gastronorm freezers – large single door and double door models holding −18 to −24 °C, with forced-air cooling, ladder racks and castors.</p>',
		'long'     => <<<'HTML'
<h2>Professional frozen storage</h2>
<p>The stainless steel gastronorm freezers give professional kitchens and butchers large-capacity frozen storage that is tough, hygienic and organised around standard gastronorm pans.</p>

<h2>Key features</h2>
<ul>
<li>−18 to −24 °C with a digital temperature display.</li>
<li>Forced-air refrigeration and 75 mm polyurethane insulation.</li>
<li>18/10 stainless steel doors and removable ladder racks.</li>
<li>Magnetic, replaceable door gaskets; full-height handles; fitted castors.</li>
</ul>

<h2>Models</h2>
<p>Large single door (about 740 mm wide) and double door (about 1440 mm wide), both about 900 mm deep and 2000 mm high. Pair them with the matching <a href="/range/storage-cabinets/upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet/">gastronorm chillers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a gastronorm freezer?', 'A commercial freezer with racks sized for gastronorm pans, built in stainless steel for professional kitchens.' ),
			array( 'What sizes are available?', 'About 740 × 900 × 2000 mm (single door) and 1440 × 900 × 2000 mm (double door).' ),
			array( 'What temperature does it hold?', 'Between −18 and −24 °C.' ),
			array( 'Does it have castors?', 'Yes, castors are fitted for easy cleaning around the cabinet.' ),
		),
		'articles' => array( 7386, 7372, 7440 ),
	),

	'upright-storage-chiller' => array(
		'name'     => 'Standard Upright Storage Fridges',
		'short'    => '<p>A standard white upright storage fridge – about 600 mm wide, four heavy-duty shelves and +2 to +7 °C, a reliable, great-value fridge for kitchens and stockrooms.</p>',
		'long'     => <<<'HTML'
<h2>Reliable everyday chilled storage</h2>
<p>The standard white upright storage chiller is a solid-door commercial fridge for kitchens, cafés, stockrooms and staff areas. It holds a steady +2 to +7 °C and offers plenty of shelf space in a compact footprint.</p>

<h2>Key features</h2>
<ul>
<li>+2 to +7 °C with a digital temperature display.</li>
<li>Aluminium interior and four adjustable heavy-duty shelves.</li>
<li>Auto/manual defrost and a replaceable door gasket.</li>
<li>Adjustable feet for levelling; stainless steel drip tray.</li>
</ul>

<h2>Size</h2>
<p>About 600 × 700 × 1860 mm (W × D × H). Need more capacity or auto defrost? See the <a href="/range/storage-cabinets/upright-storage-chillers/">premium upright storage fridges</a>. Matching freezer: <a href="/range/storage-cabinets/standard-upright-storage-freezer/">standard upright storage freezer</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an upright storage fridge?', 'A tall solid-door commercial fridge used to keep ingredients and stock chilled in kitchens and stockrooms.' ),
			array( 'What are the dimensions?', 'About 600 mm wide, 700 mm deep and 1860 mm high.' ),
			array( 'What temperature does it hold?', 'Between +2 and +7 °C.' ),
			array( 'Is there a matching freezer?', 'Yes, the <a href="/range/storage-cabinets/standard-upright-storage-freezer/">standard upright storage freezer</a> has the same footprint.' ),
		),
		'articles' => array( 7431, 7414, 7393 ),
	),

	'standard-upright-storage-freezer' => array(
		'name'     => 'Standard Upright Storage Freezers',
		'short'    => '<p>A standard white upright storage freezer – about 600 mm wide, six static heavy-duty shelves and −18 to −24 °C for kitchens and stockrooms.</p>',
		'long'     => <<<'HTML'
<h2>Great-value frozen storage</h2>
<p>The standard white upright storage freezer gives cafés, takeaways and stockrooms dependable frozen storage at a sensible price. Six static shelves keep frozen stock organised and easy to rotate.</p>

<h2>Key features</h2>
<ul>
<li>−18 to −24 °C with a digital temperature display.</li>
<li>Aluminium interior and six static heavy-duty shelves.</li>
<li>Manual defrost and a replaceable door gasket.</li>
<li>Suitable for high ambient temperatures; adjustable feet.</li>
</ul>

<h2>Size</h2>
<p>About 600 × 700 × 1860 mm (W × D × H), matching the <a href="/range/storage-cabinets/upright-storage-chiller/">standard upright storage fridge</a>. For auto defrost and larger sizes, see the <a href="/range/storage-cabinets/upright-storage-freezers/">premium upright storage freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an upright storage freezer?', 'A tall solid-door commercial freezer used to store frozen stock and ingredients on shelves.' ),
			array( 'What are the dimensions?', 'About 600 mm wide, 700 mm deep and 1860 mm high.' ),
			array( 'Does it defrost automatically?', 'No, it uses manual defrost. The premium range has automatic defrost.' ),
			array( 'What temperature does it hold?', 'Between −18 and −24 °C.' ),
		),
		'articles' => array( 7386, 7394, 7372 ),
	),

	'upright-storage-chillers' => array(
		'name'     => 'Premium Upright Storage Fridges',
		'short'    => '<p>Premium heavy-duty upright storage fridges in white steel – single, large and double door models with five shelves, auto defrost and high-ambient performance.</p>',
		'long'     => <<<'HTML'
<h2>Heavy-duty chilled storage</h2>
<p>The premium upright storage fridges are built for busy kitchens and stockrooms: heavy-duty construction, five adjustable shelves, automatic defrost and a refrigeration system designed for high ambient temperatures.</p>

<h2>Key features</h2>
<ul>
<li>Aluminium interior for long life and five adjustable heavy-duty shelves.</li>
<li>Automatic defrost and digital temperature display.</li>
<li>Replaceable door gasket and stainless steel drip tray.</li>
<li>Castors or adjustable feet depending on the model.</li>
</ul>

<h2>Models</h2>
<ul>
<li>Single door – about 600 mm wide, 0 to +5 °C.</li>
<li>Large door – about 880 mm wide, +2 to +7 °C.</li>
<li>Double door – about 1200 mm wide, +2 to +7 °C.</li>
</ul>
<p>Matching freezers: <a href="/range/storage-cabinets/upright-storage-freezers/">premium upright storage freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What makes these storage fridges premium?', 'Heavy-duty construction, five shelves, automatic defrost and suitability for high ambient temperatures.' ),
			array( 'What sizes are available?', 'Single door about 600 mm, large door about 880 mm and double door about 1200 mm wide, all about 680 mm deep and 1960 mm high.' ),
			array( 'What temperature do they hold?', 'The single door model holds 0 to +5 °C; the large and double door models +2 to +7 °C.' ),
			array( 'Are matching freezers available?', 'Yes, see the <a href="/range/storage-cabinets/upright-storage-freezers/">premium upright storage freezers</a>.' ),
		),
		'articles' => array( 7431, 7410, 7417 ),
	),

	'upright-storage-freezers' => array(
		'name'     => 'Premium Upright Storage Freezers',
		'short'    => '<p>Premium heavy-duty upright storage freezers in white steel – single, large and double door models with five shelves, auto defrost and −18 to −24 °C.</p>',
		'long'     => <<<'HTML'
<h2>Heavy-duty frozen storage</h2>
<p>The premium upright storage freezers give busy kitchens and stockrooms large, reliable frozen capacity, with automatic defrost to save staff time and a refrigeration system designed for high ambient temperatures.</p>

<h2>Key features</h2>
<ul>
<li>−18 to −24 °C with a digital temperature display.</li>
<li>Aluminium interior and five adjustable heavy-duty shelves.</li>
<li>Automatic defrost, replaceable door gasket and stainless steel drip tray.</li>
<li>Castors on the single and double door models.</li>
</ul>

<h2>Models</h2>
<p>Single door (about 600 mm wide), large door (about 880 mm) and double door (about 1200 mm), all about 680 mm deep and 1900–1960 mm high. Matching chillers: <a href="/range/storage-cabinets/upright-storage-chillers/">premium upright storage fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What makes these freezers premium?', 'Heavy-duty construction, five adjustable shelves, automatic defrost and suitability for high ambient temperatures.' ),
			array( 'What sizes are available?', 'Single door about 600 mm, large door about 880 mm and double door about 1200 mm wide.' ),
			array( 'What temperature do they hold?', 'Between −18 and −24 °C.' ),
			array( 'Do they need defrosting?', 'No, these models defrost automatically.' ),
		),
		'articles' => array( 7386, 7412, 7394 ),
	),

	'standard-stainless-steel-under-counter-chiller' => array(
		'name'     => 'Standard Stainless Steel Under Counter Fridges',
		'short'    => '<p>A standard under counter fridge with a stainless steel door – about 600 × 600 × 860 mm, two adjustable shelves and +2 to +7 °C for bars, cafés and small kitchens.</p>',
		'long'     => <<<'HTML'
<h2>Compact chilled storage under the worktop</h2>
<p>The standard under counter chiller fits beneath a standard worktop, putting chilled ingredients or drinks right where staff work. Its stainless steel door is durable and easy to wipe clean.</p>

<h2>Key features</h2>
<ul>
<li>+2 to +7 °C with a digital temperature display.</li>
<li>Aluminium interior and two adjustable shelves.</li>
<li>Auto/manual defrost and a replaceable door gasket.</li>
<li>Adjustable feet for levelling; stainless steel drip tray.</li>
</ul>

<h2>Size</h2>
<p>About 600 × 600 × 860 mm (W × D × H). For a premium model suited to hot kitchens, see the <a href="/range/storage-cabinets/stainless-steel-under-counter-chiller/">premium stainless steel under counter fridge</a>; the matching freezer is the <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-freezer/">standard under counter freezer</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an under counter fridge?', 'A compact commercial fridge about 860 mm high that fits beneath a worktop.' ),
			array( 'What are the dimensions?', 'About 600 mm wide, 600 mm deep and 860 mm high.' ),
			array( 'What temperature does it hold?', 'Between +2 and +7 °C.' ),
			array( 'Is a matching freezer available?', 'Yes, the <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-freezer/">standard under counter freezer</a> shares the same size.' ),
		),
		'articles' => array( 7399, 7393, 7414 ),
	),

	'standard-stainless-steel-under-counter-freezer' => array(
		'name'     => 'Standard Stainless Steel Under Counter Freezers',
		'short'    => '<p>A standard under counter freezer with a stainless steel door – about 600 × 600 × 860 mm, two adjustable shelves and −18 to −24 °C.</p>',
		'long'     => <<<'HTML'
<h2>Frozen storage at the prep station</h2>
<p>The standard under counter freezer keeps frozen ingredients beneath the worktop – ideal for chips, ice cream, frozen fruit and portioned stock in cafés, bars and takeaways.</p>

<h2>Key features</h2>
<ul>
<li>−18 to −24 °C with a digital temperature display.</li>
<li>Aluminium interior and two adjustable shelves.</li>
<li>Auto/manual defrost and a replaceable door gasket.</li>
<li>Adjustable feet; stainless steel drip tray.</li>
</ul>

<h2>Size</h2>
<p>About 600 × 600 × 860 mm (W × D × H), matching the <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-chiller/">standard under counter fridge</a>. For a premium alternative, see the <a href="/range/storage-cabinets/premium-stainless-steel-under-counter-freezer/">premium under counter freezer</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an under counter freezer?', 'A compact commercial freezer that fits beneath a worktop.' ),
			array( 'What are the dimensions?', 'About 600 mm wide, 600 mm deep and 860 mm high.' ),
			array( 'What temperature does it hold?', 'Between −18 and −24 °C.' ),
			array( 'How do I keep it efficient?', 'Leave ventilation space, keep the door gasket clean and defrost when ice builds up.' ),
		),
		'articles' => array( 7372, 7394, 7375 ),
	),

	'stainless-steel-under-counter-chiller' => array(
		'name'     => 'Premium Stainless Steel Under Counter Fridges',
		'short'    => '<p>A premium stainless steel under counter fridge – about 600 × 600 × 850 mm, 0 to +5 °C, auto defrost and suitable for high ambient kitchens.</p>',
		'long'     => <<<'HTML'
<h2>Under counter chilling for busy kitchens</h2>
<p>The premium stainless steel under counter chiller is designed for hot, busy kitchens. It holds 0 to +5 °C – a little colder than our standard model – and defrosts automatically, so it keeps working through long services.</p>

<h2>Key features</h2>
<ul>
<li>0 to +5 °C with a digital temperature display.</li>
<li>Aluminium interior and two adjustable heavy-duty shelves.</li>
<li>Automatic defrost and replaceable door gasket.</li>
<li>Suitable for high ambient temperatures; stainless steel drip tray.</li>
</ul>

<h2>Size</h2>
<p>About 600 × 600 × 850 mm (W × D × H). Need more space under the worktop? See the two and three door <a href="/range/storage-cabinets/stainless-steel-counter-chillers/">stainless steel counter chillers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What makes this under counter fridge premium?', 'It holds a colder 0 to +5 °C, defrosts automatically and is designed for high ambient kitchens.' ),
			array( 'What are the dimensions?', 'About 600 mm wide, 600 mm deep and 850 mm high.' ),
			array( 'Is a matching freezer available?', 'Yes, the <a href="/range/storage-cabinets/premium-stainless-steel-under-counter-freezer/">premium under counter freezer</a>.' ),
			array( 'Can it go next to cooking equipment?', 'It is designed for high ambient temperatures, but still needs ventilation space and should not sit against direct heat.' ),
		),
		'articles' => array( 7369, 7399, 7410 ),
	),

	'premium-stainless-steel-under-counter-freezer' => array(
		'name'     => 'Premium Stainless Steel Under Counter Freezers',
		'short'    => '<p>A premium stainless steel under counter freezer – about 600 × 600 × 900 mm, −18 to −24 °C, auto defrost and suitable for high ambient kitchens.</p>',
		'long'     => <<<'HTML'
<h2>Hard-working frozen storage under the counter</h2>
<p>The premium under counter freezer keeps frozen ingredients beside the fryer or grill and is built for hot kitchens, with automatic defrost so ice never slows service down.</p>

<h2>Key features</h2>
<ul>
<li>−18 to −24 °C with a digital temperature display.</li>
<li>Aluminium interior and two adjustable heavy-duty shelves.</li>
<li>Automatic defrost and replaceable door gasket.</li>
<li>Suitable for high ambient temperatures; stainless steel drip tray.</li>
</ul>

<h2>Size</h2>
<p>About 600 × 600 × 900 mm (W × D × H). For more frozen capacity under the worktop, see the <a href="/range/storage-cabinets/stainless-steel-counter-freezers/">stainless steel counter freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What makes this under counter freezer premium?', 'Automatic defrost and a design suited to high ambient kitchens.' ),
			array( 'What are the dimensions?', 'About 600 mm wide, 600 mm deep and 900 mm high.' ),
			array( 'What temperature does it hold?', 'Between −18 and −24 °C.' ),
			array( 'Is a matching fridge available?', 'Yes, the <a href="/range/storage-cabinets/stainless-steel-under-counter-chiller/">premium under counter fridge</a>.' ),
		),
		'articles' => array( 7372, 7375, 7369 ),
	),

	'stainless-steel-counter-chillers' => array(
		'name'     => 'Stainless Steel Counter Fridges',
		'short'    => '<p>Stainless steel counter fridges – two and three door gastronorm counters in 304 stainless steel holding −5 to +5 °C, plus a single door under counter chiller.</p>',
		'long'     => <<<'HTML'
<h2>Refrigerated prep counters</h2>
<p>A counter fridge is a refrigerated cabinet with a full worktop on top – the heart of many professional kitchens. Ingredients are stored below and prepared on top, saving space and steps during service.</p>

<h2>Key features</h2>
<ul>
<li>304 grade stainless steel inside and out.</li>
<li>Gastronorm-friendly, with removable stainless steel ladder racks.</li>
<li>−5 to +5 °C on the two and three door counters, cold enough for meat, fish and dairy.</li>
<li>75 mm polyurethane insulation and magnetic, removable door gaskets.</li>
<li>Integrated door handles, fitted castors and door locks on the three door model.</li>
</ul>

<h2>Models</h2>
<p>Two door counter about 1480 × 700 × 880 mm, three door counter about 1955 × 700 × 850 mm, and a single door premium under counter chiller about 600 mm wide. Matching freezers: <a href="/range/storage-cabinets/stainless-steel-counter-freezers/">stainless steel counter freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a counter fridge?', 'A refrigerated cabinet with a worktop on top, used in professional kitchens for storage and preparation in one footprint.' ),
			array( 'What sizes are available?', 'Two door about 1480 mm wide and three door about 1955 mm wide, both about 700 mm deep; plus a 600 mm single door model.' ),
			array( 'What temperature do they hold?', 'The two and three door counters hold −5 to +5 °C.' ),
			array( 'Are they gastronorm compatible?', 'Yes, they are gastronorm-friendly with removable ladder racks.' ),
		),
		'articles' => array( 7369, 7399, 7377 ),
	),

	'stainless-steel-counter-freezers' => array(
		'name'     => 'Stainless Steel Counter Freezers',
		'short'    => '<p>Stainless steel counter freezers – two and three door gastronorm counters in 304 stainless steel holding −18 to −24 °C with a worktop on top.</p>',
		'long'     => <<<'HTML'
<h2>Frozen storage with a worktop</h2>
<p>A counter freezer combines frozen storage below with a stainless steel worktop above, so frozen ingredients are stored right where they are prepared and cooked.</p>

<h2>Key features</h2>
<ul>
<li>−18 to −24 °C with a digital temperature display.</li>
<li>304 grade stainless steel inside and out, gastronorm-friendly.</li>
<li>75 mm polyurethane insulation and magnetic, removable door gaskets.</li>
<li>Removable ladder racks, fitted castors and door locks on the three door model.</li>
</ul>

<h2>Models</h2>
<p>Two door about 1480 × 700 × 880 mm and three door about 1955 × 700 × 880 mm. Matching chillers: <a href="/range/storage-cabinets/stainless-steel-counter-chillers/">stainless steel counter fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a counter freezer?', 'A commercial freezer cabinet with a worktop on top, used for frozen storage at the prep station.' ),
			array( 'What sizes are available?', 'Two door about 1480 mm and three door about 1955 mm wide, both about 700 mm deep and 880 mm high.' ),
			array( 'What temperature do they hold?', 'Between −18 and −24 °C.' ),
			array( 'Do they lock?', 'The three door model has door locks.' ),
		),
		'articles' => array( 7372, 7369, 7386 ),
	),

	'stainless-steel-upright-bakery-cabinets' => array(
		'name'     => 'Bakery Fridges, Freezers & Retarder Provers',
		'short'    => '<p>Upright stainless steel bakery cabinets for 400 × 600 mm trays – a bakery fridge, a bakery freezer and a retarder prover with humidity control for professional baking.</p>',
		'long'     => <<<'HTML'
<h2>Refrigeration designed around the bakery tray</h2>
<p>Bakery cabinets are upright stainless steel fridges and freezers built to hold standard 400 × 600 mm bakery trays, so dough and bakes move straight from bench to cabinet. The range also includes a retarder prover that controls the rise of dough overnight.</p>

<h2>The range</h2>
<ul>
<li><strong>Bakery fridge</strong> – +2 to +7 °C, forced-air cooling and energy-saving auto defrost.</li>
<li><strong>Bakery freezer</strong> – −18 to −24 °C, hot-gas defrost water evaporation and a protected compressor.</li>
<li><strong>Retarder prover</strong> – 0 to 40 °C with humidity control and a timer, designed to work in ambient temperatures up to 43 °C; needs a water supply and external drain.</li>
</ul>
<p>All have stainless steel exteriors, removable door gaskets, full-height handles, digital displays and fitted castors. Trays are not supplied.</p>

<h2>What is a retarder prover?</h2>
<p>A retarder prover first holds dough cold to slow fermentation (retarding), then warms and humidifies it to prove it ready for baking – automatically, on a timer. Bakers prepare dough in the afternoon and bake fresh first thing. See also our <a href="/range/bakery-cabinets/">bakery ovens and refrigeration</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a bakery fridge?', 'A bakery fridge is an upright commercial fridge designed to hold standard bakery trays, used for dough, pastries and finished bakes.' ),
			array( 'What is a retarder prover?', 'A cabinet that chills dough to slow fermentation, then warms and humidifies it to prove it on a timer, so dough is ready to bake when you arrive.' ),
			array( 'What tray size do these cabinets take?', '400 × 600 mm bakery trays (trays are not supplied).' ),
			array( 'Does the retarder prover need plumbing?', 'Yes, it needs a water supply and an external drain for the humidity system.' ),
		),
		'articles' => array( 7366, 7395, 7418 ),
	),
);
