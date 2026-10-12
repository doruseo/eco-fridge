<?php
/**
 * Extended category copy — Storage Cabinets tree (used by migration 39).
 * Format: see more/commercial-fridge.php.
 *
 * @package EcoFridgeMigration
 */

return array(

	'storage-cabinets' => array(
		'long' => <<<'HTML'
<h2>Storage cabinets by type of business</h2>
<ul>
<li><strong>Restaurants and hotel kitchens</strong> – stainless steel two and three door counters at each section, with upright gastronorm chillers and freezers for bulk stock.</li>
<li><strong>Cafés, takeaways and pubs</strong> – a single door under counter fridge and freezer behind the counter, backed by a white upright fridge and freezer in the stockroom.</li>
<li><strong>Butchers and fishmongers</strong> – gastronorm fish/meat cabinets holding −5 to +5 °C, with gastronorm freezers for frozen lines.</li>
<li><strong>Bakeries</strong> – bakery fridges, freezers and a retarder prover for 400 × 600 mm trays.</li>
<li><strong>Schools, care homes and staff canteens</strong> – heavy-duty uprights with auto defrost and large double door capacity.</li>
</ul>

<h2>Capacity guide</h2>
<ul>
<li>Single door under counter fridge or freezer: about 125 litres.</li>
<li>Standard white upright freezer: about 400 litres.</li>
<li>Premium single door upright fridge or freezer: about 362 litres; large door fridge about 569 litres; double door about 806 litres.</li>
<li>Stainless steel gastronorm cabinets: single door about 740 mm and double door about 1440 mm wide, 900 mm deep for full-size pans.</li>
<li>Bakery cabinets: ten 400 × 600 mm trays.</li>
</ul>

<h2>Standard or premium?</h2>
<p>Standard cabinets are great-value white steel or stainless door models for lighter use and stockrooms. Premium cabinets add heavy-duty construction, automatic defrost, more shelves and – on the stainless steel counters and bakery cabinets – a design rated for room temperatures up to 43 °C. In a busy, hot kitchen the premium models keep their temperature with less effort and need less staff attention.</p>

<h2>Price overview</h2>
<p>Under counter fridges and freezers start from about £661 + VAT, standard white uprights from about £933 + VAT, premium heavy-duty uprights cost about £1,175 to £2,125 + VAT, stainless steel counters about £1,358 to £1,733 + VAT, gastronorm cabinets about £1,190 to £1,990 + VAT and bakery cabinets about £1,395 to £2,600 + VAT.</p>

<h2>Compliance and daily checks</h2>
<p>Keep chilled food at 8 °C or below (most kitchens aim for 1–5 °C) and frozen food at −18 °C or colder. Record cabinet temperatures daily, label stored food with dates, keep raw products below ready-to-eat food and clean seals and shelves on a regular schedule. Every storage cabinet we sell has a digital temperature display, which makes those checks quick.</p>
HTML,
		'faq'  => array(
			array( 'How much do storage cabinets cost?', 'From about £661 + VAT for an under counter fridge or freezer to about £2,600 + VAT for the bakery retarder prover. See the comparison table for the starting price of each range.' ),
			array( 'Which storage cabinets suit a hot kitchen?', 'The stainless steel two and three door counters and the bakery cabinets are designed for room temperatures up to 43 °C. Premium heavy-duty uprights are also designed for high ambient temperatures.' ),
			array( 'How do I choose the right capacity?', 'Size for the stock you hold between deliveries plus a buffer. Under counter cabinets hold about 125 litres, single door uprights about 362–400 litres and double door uprights about 806 litres.' ),
		),
	),

	'upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet' => array(
		'long' => <<<'HTML'
<h2>Models and prices</h2>
<ul>
<li><strong>Large upright gastronorm chiller (ECO-GN700M)</strong> – about 740 × 900 × 2000 mm, four shelves, about 140 kg, about £1,190 + VAT.</li>
<li><strong>Large upright fish/meat storage cabinet</strong> – single door with manual defrost, optimised for fish and meat, about £1,190 + VAT.</li>
<li><strong>Upright double door chiller (ECO-GN1440M)</strong> – about 1440 × 900 × 2000 mm, eight shelves, about 195 kg, about £1,790 + VAT.</li>
</ul>

<h2>Who uses them</h2>
<p>Butchers hold prepared cuts, joints and mince between the cutting room and the counter; fishmongers keep fresh fish close to 0 °C; restaurants, hotels and contract caterers store portioned proteins in gastronorm pans ready for service. The −5 to +5 °C range lets you run these cabinets colder than a general-purpose fridge.</p>

<h2>Hygiene routine</h2>
<ul>
<li>Keep raw meat and fish covered and below any cooked or ready-to-eat products.</li>
<li>Wipe spills immediately and remove the ladder racks for washing every week.</li>
<li>Clean the magnetic door gaskets and check that they seal along their full length.</li>
<li>Record the cabinet temperature at least daily.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'How much does a gastronorm chiller cost?', 'About £1,190 + VAT for the large single door chiller or the fish/meat cabinet, and about £1,790 + VAT for the double door chiller.' ),
			array( 'How many shelves are included?', 'Four on the single door chiller and eight on the double door chiller.' ),
			array( 'How heavy are they?', 'About 140 kg for the single door and 195 kg for the double door chiller.' ),
		),
	),

	'standard-upright-stainless-steel-gastronorm-freezers' => array(
		'long' => <<<'HTML'
<h2>Models and prices</h2>
<ul>
<li><strong>Large single door gastronorm freezer (ECO-GN700L)</strong> – about 740 × 900 × 2000 mm, four shelves, about 150 kg, about £1,696 + VAT.</li>
<li><strong>Double door gastronorm freezer (ECO-GN1440L)</strong> – about 1440 × 900 × 2000 mm, about 200 kg, about £1,990 + VAT.</li>
</ul>

<h2>Who uses them</h2>
<p>Catering kitchens, schools, care homes and contract caterers use gastronorm freezers for batch-cooked dishes, frozen proteins and desserts stored in standard pans, so food moves from freezer to oven without repacking. Butchers use them for frozen lines such as burgers, sausages and portioned meat.</p>

<h2>A matching pair</h2>
<p>The freezers share the footprint and finish of the <a href="/range/storage-cabinets/upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet/">gastronorm chillers</a>, so a chiller and freezer side by side form a tidy, uniform storage wall. Plan for the weight (150–200 kg each) and the 2000 mm height when checking the route in.</p>
HTML,
		'faq'  => array(
			array( 'How much does a gastronorm freezer cost?', 'About £1,696 + VAT for the large single door model and about £1,990 + VAT for the double door model.' ),
			array( 'How heavy are they?', 'About 150 kg (single door) and 200 kg (double door).' ),
		),
	),

	'upright-storage-chiller' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The ECO-USC60 is about 600 × 700 × 1860 mm, weighs about 117 kg and has four heavy-duty shelves. At about £933 + VAT it is our most affordable upright storage fridge.</p>

<h2>Who it suits</h2>
<p>It is a dependable choice for cafés, takeaways, small restaurants, stockrooms, staff rooms and offices – anywhere that needs full-height chilled storage without heavy kitchen use. In a hot, busy professional kitchen, the <a href="/range/storage-cabinets/upright-storage-chillers/">premium heavy-duty uprights</a> with automatic defrost are the better fit.</p>

<h2>Organising the shelves</h2>
<p>Keep ready-to-eat food on the top shelves, dairy and prepared ingredients in the middle and raw products on the bottom shelf in covered containers. Leave space between items so air can circulate, and keep a few centimetres clear at the back of the cabinet.</p>
HTML,
		'faq'  => array(
			array( 'How much does the standard upright storage fridge cost?', 'About £933 + VAT.' ),
			array( 'How heavy is it?', 'About 117 kg.' ),
		),
	),

	'standard-upright-storage-freezer' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The ECO-USF60 is about 600 × 700 × 1860 mm and holds about 400 litres on six static shelves. It weighs about 117 kg and costs about £1,014 + VAT.</p>

<h2>Who it suits</h2>
<p>A great-value frozen store for cafés, takeaways, pubs and stockrooms, and a natural partner to the <a href="/range/storage-cabinets/upright-storage-chiller/">standard upright storage fridge</a> of the same size. Six static shelves keep frozen stock organised and easy to rotate.</p>

<h2>Manual defrost routine</h2>
<p>Defrost when ice builds up on the cabinet walls: move stock to another freezer or cool boxes, switch off, let the ice melt and collect the water, dry the interior, then switch on and wait for −18 °C before reloading. Defrosting regularly keeps the freezer efficient and protects the shelves’ capacity.</p>
HTML,
		'faq'  => array(
			array( 'How much does the standard upright storage freezer cost?', 'About £1,014 + VAT.' ),
			array( 'How much does it hold?', 'About 400 litres on six static shelves.' ),
		),
	),

	'upright-storage-chillers' => array(
		'long' => <<<'HTML'
<h2>Models, capacity and prices</h2>
<ul>
<li><strong>Single door (USC60)</strong> – about 600 × 680 × 1960 mm, about 362 litres, 0 to +5 °C, about 117 kg, about £1,175 + VAT.</li>
<li><strong>Large door (USC88)</strong> – about 880 × 680 × 1960 mm, about 569 litres, about 136 kg, about £1,505 + VAT.</li>
<li><strong>Double door (USC120)</strong> – about 1200 × 680 × 1960 mm, about 806 litres, about 162 kg, about £1,881 + VAT.</li>
</ul>

<h2>Built for busy kitchens</h2>
<p>These heavy-duty uprights are designed for kitchens and stockrooms where the door is opened constantly and the room is warm. Automatic defrost keeps the evaporator clear without staff involvement, five adjustable shelves make it easy to organise stock, and the large door model gives about half as much space again as the single door in only 880 mm of wall.</p>

<h2>Choosing the size</h2>
<p>Single door models suit cafés and small kitchens; the large door model suits restaurants with a broader menu; the double door model suits schools, care homes, hotels and canteens that receive larger deliveries.</p>
HTML,
		'faq'  => array(
			array( 'How much does a premium upright storage fridge cost?', 'About £1,175 + VAT (single door), £1,505 + VAT (large door) and £1,881 + VAT (double door).' ),
			array( 'How much do they hold?', 'About 362 litres (single door), 569 litres (large door) and 806 litres (double door).' ),
		),
	),

	'upright-storage-freezers' => array(
		'long' => <<<'HTML'
<h2>Models, capacity and prices</h2>
<ul>
<li><strong>Single door (USF60)</strong> – about 600 × 680 × 1960 mm, about 362 litres, about 124 kg, about £1,290 + VAT.</li>
<li><strong>Large door (USF88)</strong> – about 880 × 680 × 1960 mm, about 148 kg, about £1,650 + VAT.</li>
<li><strong>Double door (USF120)</strong> – about 1200 × 680 × 1900 mm, about 806 litres, about 175 kg, about £2,125 + VAT.</li>
</ul>

<h2>Why auto defrost matters</h2>
<p>On a freezer that is opened all day, ice builds up quickly. Automatic defrost clears it on a regular cycle, so the freezer holds −18 to −24 °C without staff having to empty it, and capacity is not lost to ice. That makes the premium models the better choice for restaurants, schools, care homes and other busy kitchens.</p>

<h2>Matching fridges</h2>
<p>Each freezer has a matching <a href="/range/storage-cabinets/upright-storage-chillers/">premium upright storage fridge</a> in the same width, so a fridge and freezer side by side form a uniform storage wall.</p>
HTML,
		'faq'  => array(
			array( 'How much does a premium upright storage freezer cost?', 'About £1,290 + VAT (single door), £1,650 + VAT (large door) and £2,125 + VAT (double door).' ),
			array( 'How much do they hold?', 'About 362 litres for the single door and about 806 litres for the double door model.' ),
		),
	),

	'standard-stainless-steel-under-counter-chiller' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The ECO-SUC61 is about 600 × 600 × 860 mm, holds about 125 litres on two shelves and weighs about 55 kg. It costs about £661 + VAT, one of the most affordable fridges in our range.</p>

<h2>Where it fits</h2>
<ul>
<li><strong>Coffee shops</strong> – milk and dairy alternatives under the coffee machine.</li>
<li><strong>Bars</strong> – garnishes, mixers and dairy for cocktails.</li>
<li><strong>Small kitchens and kiosks</strong> – a section’s ingredients for one service.</li>
<li><strong>Offices and staff rooms</strong> – a durable alternative to a domestic fridge.</li>
</ul>

<h2>Installation</h2>
<p>Check the height under the worktop – the unit is about 860 mm high on its adjustable feet – and leave the ventilation space given in the manual. For hot kitchens, the <a href="/range/storage-cabinets/stainless-steel-under-counter-chiller/">premium under counter fridge</a> with automatic defrost is the better choice.</p>
HTML,
		'faq'  => array(
			array( 'How much does the standard under counter fridge cost?', 'About £661 + VAT.' ),
			array( 'How much does it hold?', 'About 125 litres on two shelves.' ),
		),
	),

	'standard-stainless-steel-under-counter-freezer' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The ECO-SUF61 is about 600 × 600 × 860 mm, holds about 125 litres on two shelves and weighs about 55 kg. It costs about £661 + VAT.</p>

<h2>Where it fits</h2>
<ul>
<li><strong>Takeaways and pubs</strong> – chips, frozen sides and desserts next to the fryer.</li>
<li><strong>Cafés and ice cream bars</strong> – ice cream, frozen fruit for smoothies and frozen bakes.</li>
<li><strong>Bars</strong> – ice and frozen garnishes close to service.</li>
</ul>

<h2>Pair it with the matching fridge</h2>
<p>The <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-chiller/">standard under counter fridge</a> shares the same footprint and stainless steel door, so the two sit neatly side by side under a 1200 mm worktop – chilled and frozen ingredients at the same station.</p>
HTML,
		'faq'  => array(
			array( 'How much does the standard under counter freezer cost?', 'About £661 + VAT.' ),
			array( 'How much does it hold?', 'About 125 litres on two shelves.' ),
		),
	),

	'stainless-steel-under-counter-chiller' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The SUC61 is about 600 × 600 × 850 mm, holds about 125 litres and weighs about 55 kg. It costs about £825 + VAT.</p>

<h2>Why choose the premium model?</h2>
<p>It runs colder – 0 to +5 °C rather than +2 to +7 °C – which gives a better margin for dairy, cooked meats and prepared ingredients, and it defrosts automatically, so it keeps working through long services in a hot kitchen. The heavy-duty construction is designed for the busiest outlets, where the door is opened constantly.</p>

<h2>Who it suits</h2>
<p>Restaurant and pub kitchens, busy coffee shops, sandwich bars and hotel kitchens that need dependable chilled storage at the point of preparation. Pair it with the <a href="/range/storage-cabinets/premium-stainless-steel-under-counter-freezer/">premium under counter freezer</a> for frozen ingredients at the same station.</p>
HTML,
		'faq'  => array(
			array( 'How much does the premium under counter fridge cost?', 'About £825 + VAT.' ),
			array( 'How much does it hold?', 'About 125 litres.' ),
		),
	),

	'premium-stainless-steel-under-counter-freezer' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The SUF61 is about 600 × 600 × 900 mm, finished in stainless steel, holds about 125 litres and weighs about 55 kg. It costs about £825 + VAT.</p>

<h2>Why choose the premium model?</h2>
<p>Automatic defrost keeps the freezer free of ice build-up without emptying it, and the heavy-duty construction suits the busiest kitchens. It is a reliable choice beside fryers and grills, where frozen chips, sides and proteins are used continuously through service.</p>

<h2>Need more capacity?</h2>
<p>If one under counter freezer is not enough, the <a href="/range/storage-cabinets/stainless-steel-counter-freezers/">two and three door stainless steel counter freezers</a> give several times the frozen space under a continuous worktop, and are rated for room temperatures up to 43 °C.</p>
HTML,
		'faq'  => array(
			array( 'How much does the premium under counter freezer cost?', 'About £825 + VAT.' ),
			array( 'Does it need defrosting?', 'No, it defrosts automatically.' ),
		),
	),

	'stainless-steel-counter-chillers' => array(
		'long' => <<<'HTML'
<h2>Models and prices</h2>
<ul>
<li><strong>Eco Premium two door counter chiller (EP12M)</strong> – about 1480 × 700 × 880 mm, about 200 kg, about £1,358 + VAT.</li>
<li><strong>Eco Premium three door counter chiller (EP13M)</strong> – about 1955 × 700 × 850 mm, about 252 kg, with door locks, about £1,706 + VAT.</li>
<li><strong>Premium single door under counter chiller (SUC61)</strong> – about 600 mm wide, about £825 + VAT.</li>
</ul>

<h2>Designed for 43 °C kitchens</h2>
<p>The Eco Premium counters are designed to operate in room temperatures up to 43 °C and are suitable for meat, so they keep proteins and dairy at −5 to +5 °C right beside the grill or range. The worktop on top turns refrigerated storage into a preparation bench, saving both floor space and steps.</p>

<h2>Planning a kitchen line</h2>
<p>Use a counter at each section – a two door counter at the pass or cold section, a three door counter on the main cooking line – and keep the ingredients each chef needs for service in gastronorm pans below. A matching <a href="/range/storage-cabinets/stainless-steel-counter-freezers/">counter freezer</a> completes the line for frozen items.</p>
HTML,
		'faq'  => array(
			array( 'How much does a stainless steel counter fridge cost?', 'About £1,358 + VAT (two door) and £1,706 + VAT (three door).' ),
			array( 'Can it work next to the grill?', 'Yes. The Eco Premium counters are designed for room temperatures up to 43 °C, with ventilation space around them.' ),
			array( 'How heavy are the counters?', 'About 200 kg (two door) and 252 kg (three door).' ),
		),
	),

	'stainless-steel-counter-freezers' => array(
		'long' => <<<'HTML'
<h2>Models and prices</h2>
<ul>
<li><strong>Eco Premium two door counter freezer (EP12L)</strong> – about 1480 × 700 × 880 mm, about 200 kg, about £1,366 + VAT.</li>
<li><strong>Eco Premium three door counter freezer (EP13L)</strong> – about 1955 × 700 × 880 mm, about 252 kg, with door locks, about £1,733 + VAT.</li>
</ul>

<h2>Designed for 43 °C kitchens</h2>
<p>Freezers next to cooking equipment work hard. These counters are designed to operate in room temperatures up to 43 °C, so they hold −18 to −24 °C beside fryers and grills, while the worktop adds preparation space above.</p>

<h2>Who uses them</h2>
<p>Restaurants, pubs, takeaways and hotel kitchens use counter freezers on the cooking line for chips, frozen sides, proteins and desserts. Combined with a matching <a href="/range/storage-cabinets/stainless-steel-counter-chillers/">counter chiller</a>, they form a complete refrigerated cooking line with one continuous worktop height.</p>
HTML,
		'faq'  => array(
			array( 'How much does a stainless steel counter freezer cost?', 'About £1,366 + VAT (two door) and £1,733 + VAT (three door).' ),
			array( 'How heavy are the counter freezers?', 'About 200 kg (two door) and 252 kg (three door).' ),
		),
	),

	'stainless-steel-upright-bakery-cabinets' => array(
		'long' => <<<'HTML'
<h2>Models and prices</h2>
<ul>
<li><strong>Stainless steel upright bakery fridge</strong> – ten 400 × 600 mm trays, +2 to +7 °C, about £1,395 + VAT.</li>
<li><strong>Stainless steel upright bakery freezer</strong> – ten 400 × 600 mm trays, −18 to −24 °C, about £1,490 + VAT.</li>
<li><strong>Stainless steel upright retarder prover (BK600RP)</strong> – ten 400 × 600 mm trays, humidity control and timer, about £2,600 + VAT.</li>
</ul>
<p>All three are designed to operate in room temperatures up to 43 °C – important beside bakery ovens.</p>

<h2>How bakeries use them</h2>
<p>The bakery fridge holds pastry, laminated dough and finished bakes on trays straight from the bench. The freezer stores frozen dough pieces and bake-off products. The retarder prover is the time-saver: dough prepared in the afternoon is held cold overnight and then proved automatically on the timer, so it is ready to bake when the first shift arrives.</p>

<h2>Installation of the retarder prover</h2>
<p>The retarder prover needs a water supply and an external drain for its humidity system; the connecting pipes are included. Plan the position with your plumber before delivery and leave ventilation space around the cabinet.</p>
HTML,
		'faq'  => array(
			array( 'How much do bakery cabinets cost?', 'About £1,395 + VAT for the bakery fridge, £1,490 + VAT for the bakery freezer and £2,600 + VAT for the retarder prover.' ),
			array( 'How many trays do they hold?', 'Ten 400 × 600 mm bakery trays each.' ),
			array( 'Can they work next to ovens?', 'Yes, they are designed for room temperatures up to 43 °C, with ventilation space around them.' ),
		),
	),
);
