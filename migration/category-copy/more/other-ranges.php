<?php
/**
 * Extended category copy — Bakery, Medical, Low Temperature, Used and the
 * legacy /ranges/ categories (used by migration 39).
 * Format: see more/commercial-fridge.php.
 *
 * @package EcoFridgeMigration
 */

return array(

	'bakery-cabinets' => array(
		'long' => <<<'HTML'
<h2>Oven price and capacity guide</h2>
<ul>
<li><strong>Stainless steel oven</strong> – four 400 × 300 mm trays, 13-amp plug, about £399 + VAT.</li>
<li><strong>Oven and grill with steamer</strong> – four 440 × 320 mm trays, 13-amp plug, about £580 + VAT.</li>
<li><strong>Large oven with grill and steamer</strong> – four 590 × 390 mm trays, about £899 + VAT (installed by a qualified electrician).</li>
<li><strong>Large oven with steamer</strong> – eight 520 × 390 mm trays, about £1,200 + VAT (installed by a qualified electrician).</li>
<li><strong>Heavy-duty oven stand</strong> – shelf runners for trays and GN pans, adjustable feet, about £295 + VAT.</li>
<li><strong>Contact grill</strong> – cast iron plates heating from 50 to 300 °C, about £295 + VAT.</li>
</ul>

<h2>Equipping a bakery or café kitchen</h2>
<p>Plan the kitchen around the products you bake and the volume at peak times. A café baking off croissants, cookies and scones through the day is well served by a compact convection oven on a 13-amp plug and a contact grill for paninis. A bakery producing bread and pastries needs larger tray capacity, steam for crust and shine, and refrigeration that fits the same trays: <a href="/range/storage-cabinets/stainless-steel-upright-bakery-cabinets/">bakery fridges, freezers and a retarder prover</a> for ten 400 × 600 mm trays each.</p>
<p>The retarder prover changes how a bakery works: dough is made in the afternoon, held cold overnight and proved automatically on the timer, so the first bake goes in as soon as staff arrive. It needs a water supply and an external drain.</p>

<h2>Power and installation</h2>
<p>The compact oven (3 kW) and the oven and grill with steamer (2.6 kW) run from a standard 13-amp socket, which makes them easy to add to an existing café. The large 6 kW and 9 kW ovens must be wired in by a qualified electrician, so check your supply before ordering. Leave space around ovens for ventilation, and keep refrigeration away from their heat – the bakery cabinets are rated for room temperatures up to 43 °C.</p>

<h2>Selling what you bake</h2>
<p>Complete the bakery with displays from the same supplier: <a href="/product-category/commercial-display-fridge/patisserie-fridge/">patisserie fridges</a> for cream cakes and desserts, <a href="/range/display-cabinets/heated-display-cabinets/">heated pastry displays</a> for pasties and sausage rolls, and <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go fridges</a> for sandwiches.</p>
HTML,
		'faq'  => array(
			array( 'How much does a commercial oven cost?', 'From about £399 + VAT for the compact stainless steel oven to about £1,200 + VAT for the large oven with steamer.' ),
			array( 'Which oven is best for a café?', 'A compact convection oven or the oven and grill with steamer – both run from a 13-amp plug and suit bake-off pastries, cookies and reheating.' ),
			array( 'Do the bakery fridges take the same trays as the ovens?', 'The bakery fridges, freezer and retarder prover take 400 × 600 mm bakery trays. Oven tray sizes vary by model – see each product page.' ),
		),
	),

	'medical' => array(
		'long' => <<<'HTML'
<h2>Who uses medical refrigeration</h2>
<ul>
<li><strong>Pharmacies and GP surgeries</strong> – medicines and vaccines that must be kept between +2 and +8 °C.</li>
<li><strong>Dental and veterinary practices</strong> – temperature-sensitive medicines and materials.</li>
<li><strong>Care homes</strong> – residents’ refrigerated medicines kept separately from food.</li>
<li><strong>Laboratories and research</strong> – reagents and samples; for frozen storage see our <a href="/range/low-temperature-chest-freezers/">low temperature freezers</a>.</li>
</ul>

<h2>Temperature monitoring and records</h2>
<p>Medicines and vaccines are usually stored between +2 and +8 °C, and most organisations must record the current, minimum and maximum temperature at least once every working day. A fridge with two independent refrigeration systems and two controllers, like our upright medical fridge, adds resilience, but monitoring is still essential: keep a calibrated thermometer or data logger in the cabinet, and know what to do if the temperature goes out of range.</p>

<h2>Good practice</h2>
<ul>
<li>Use the fridge only for medicines and samples – never for food or drink.</li>
<li>Do not overfill: leave space between items and the walls so air can circulate.</li>
<li>Keep the fridge out of direct sunlight and away from heat sources, and protect the power supply from being switched off by accident.</li>
<li>Check what your regulator or the product manufacturer requires for your storage, and contact us on <a href="tel:01280811411">01280 811411</a> to discuss your specification.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'What temperature does the medical fridge hold?', 'Our upright medical storage chiller holds 2 to 8 °C, stabilised by a high-quality temperature sensor.' ),
			array( 'How much does a medical fridge cost?', 'Our upright medical storage chiller is about £950 + VAT.' ),
			array( 'Do I need to record the fridge temperature?', 'Most organisations storing medicines must record the current, minimum and maximum temperatures every working day. Follow the requirements that apply to your service.' ),
		),
	),

	'upright-medical-fridge' => array(
		'long' => <<<'HTML'
<h2>Specification and price</h2>
<p>The medical white upright storage chiller holds 2 to 8 °C, with a high-quality temperature sensor keeping the internal temperature stable and even. It costs about £950 + VAT.</p>

<h2>Why redundancy matters</h2>
<p>Medicines, vaccines and samples can be costly – and sometimes impossible – to replace. With two independent refrigeration systems, a fault in one does not leave the cabinet without cooling, giving staff time to move stock or arrange a repair. Two digital controllers make it easy to see that both systems are working.</p>

<h2>Setting up the fridge</h2>
<ul>
<li>Position it on a level floor away from heat sources, with space for ventilation; lock the castors once in place.</li>
<li>Let it stabilise at temperature before loading any stock.</li>
<li>Store items in their original packaging on the shelves, not against the walls or the door.</li>
<li>Add an independent thermometer or data logger and record readings daily.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'How much does the upright medical fridge cost?', 'About £950 + VAT.' ),
			array( 'What temperature range does it hold?', '2 to 8 °C.' ),
		),
	),

	'low-temperature-chest-freezers' => array(
		'long' => <<<'HTML'
<h2>Models, sizes and prices</h2>
<ul>
<li><strong>Low temperature chest freezer −45</strong> – about 1350 × 750 × 910 mm, about £700 + VAT.</li>
<li><strong>Low temperature upright freezer −45</strong> – internal space about 480 × 520 × 1410 mm, about £1,495 + VAT.</li>
<li><strong>Low temperature chest freezer −65</strong> – about 1270 × 870 × 900 mm, about £1,695 + VAT.</li>
<li><strong>Ultra low temperature chest freezer −86</strong> – about 1270 × 870 × 900 mm, about £2,600 + VAT.</li>
</ul>

<h2>Who uses low temperature freezers</h2>
<ul>
<li><strong>Laboratories, universities and research</strong> – biological samples, reagents and research materials.</li>
<li><strong>Healthcare</strong> – products that must be stored well below standard freezer temperatures.</li>
<li><strong>Seafood and sushi</strong> – very low temperatures help preserve the colour and quality of fish such as tuna.</li>
<li><strong>Industry</strong> – materials and testing that require deep-frozen conditions.</li>
</ul>

<h2>Installing and running an ultra-low freezer</h2>
<p>Low temperature freezers work hardest when the room is warm. Keep them in a ventilated room below about 25 °C, away from heat sources and direct sun, with space around the cabinet. Let the freezer reach its set temperature before loading, keep the lid closed as much as possible, and keep an inventory so the lid is open for the shortest time. Plan a back-up for critical samples in case of a power cut.</p>
HTML,
		'faq'  => array(
			array( 'How much does a low temperature freezer cost?', 'About £700 + VAT for the −45 °C chest freezer, £1,495 + VAT for the −45 °C upright, £1,695 + VAT for the −65 °C chest freezer and £2,600 + VAT for the −86 °C ultra low temperature chest freezer.' ),
			array( 'Which low temperature freezer do I need?', 'Choose by the storage temperature your products require: −45 °C, −65 °C or the −86 °C ultra low temperature model.' ),
			array( 'How big is the −86 °C freezer?', 'About 1270 mm wide, 870 mm deep and 900 mm high.' ),
		),
	),

	'graded-units' => array(
		'long' => <<<'HTML'
<h2>How much can you save?</h2>
<p>Graded and ex-exhibition cabinets are priced well below the equivalent new models. For example, an ex-exhibition double door stainless steel gastronorm chiller is about £950 + VAT against about £1,790 + VAT new, and a used stainless steel two door counter about £850 + VAT against about £1,358 + VAT for a new two door counter chiller. Current used and ex-exhibition stock ranges from about £850 to £1,825 + VAT.</p>

<h2>What is typically available</h2>
<ul>
<li>Slimline multidecks in silver and stainless steel, including a 750 mm deep R290 stainless steel multideck.</li>
<li>Patisserie display fridges, including stainless steel double-glazed models.</li>
<li>Grab and go and serve over displays.</li>
<li>Stainless steel two door counters, a dual temperature uni-drawer counter and gastronorm chillers.</li>
</ul>
<p>Each unit is one of a kind, so the range changes as units sell and new stock arrives. Check the product page for the condition and specification of each unit.</p>

<h2>Buying used refrigeration well</h2>
<ul>
<li>Read the condition notes on the product page and ask us if anything is unclear.</li>
<li>Check the dimensions and the delivery route – used units are the same size and weight as new ones.</li>
<li>Compare the price with the new equivalent, including any optional warranty offered on the product page.</li>
<li>Act quickly on popular models – multidecks and grab and go fridges tend to sell first.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'How much cheaper are used cabinets?', 'It depends on the unit. For example, an ex-exhibition double door gastronorm chiller is about £950 + VAT against about £1,790 + VAT new.' ),
			array( 'Can I reserve a used unit?', 'Each unit is one of a kind and sold on a first-come basis. Call 01280 811411 to check availability.' ),
			array( 'What is an R290 multideck?', 'R290 (propane) is a natural refrigerant with a very low global warming potential, used in modern energy-efficient cabinets.' ),
		),
	),

	'deli-displays' => array(
		'long' => <<<'HTML'
<h2>Building a deli layout</h2>
<p>A successful deli combines served and self-service displays. Customers who want loose products – sliced meats, cheese by weight, olives and salads – queue at a <a href="/range/display-cabinets/serve-over-display/">serve over counter</a>, while those in a hurry pick packaged sandwiches, salads and drinks from a <a href="/range/display-cabinets/silver-steel-multideck-display-chillers/">multideck</a> or <a href="/range/display-cabinets/standard-grab-and-go-display-fridges/">grab and go fridge</a>. Cakes and desserts sit in a <a href="/range/display-cabinets/patisserie-display-fridges/">patisserie fridge</a> near the till, and a <a href="/range/display-cabinets/glass-lid-chest-freezer/">chest freezer</a> adds ice cream and frozen lines.</p>

<h2>Products and their display</h2>
<ul>
<li><strong>Cooked meats, charcuterie and cheese</strong> – serve over counters set at the colder end of their 0 to +12 °C range.</li>
<li><strong>Salads, quiches and prepared dishes</strong> – double glazed counters at 0 to +5 °C.</li>
<li><strong>Cakes and pastries</strong> – patisserie fridges at +3 to +7 °C or +3 to +12 °C.</li>
<li><strong>Packaged sandwiches, dairy and drinks</strong> – multidecks at −1 to +7 °C.</li>
</ul>

<h2>Food safety in a deli</h2>
<p>Keep raw and ready-to-eat products apart, in separate cabinets or clearly separated areas with their own utensils and boards. Hold chilled food at 8 °C or below, check display temperatures at opening and closing, and label products with allergen information. Remove opened products at the end of their shelf life and clean the display decks daily.</p>

<h2>Deli display price guide</h2>
<p>Deli displays start from about £595 + VAT for a chest display freezer, about £1,150 + VAT for a 1000 mm serve over, about £1,245 + VAT for a patisserie fridge and about £1,299 + VAT for a multideck. The premium UV bonded glass counter, built into a new or existing counter, is about £3,700 + VAT.</p>
HTML,
		'faq'  => array(
			array( 'How much does a deli display cost?', 'From about £1,150 + VAT for a 1000 mm serve over counter and £1,515 + VAT for a double glazed counter, up to about £3,700 + VAT for the premium UV bonded counter.' ),
			array( 'How long should a deli counter be?', 'It depends on your range: many delis start with 1.5–2 metres of serve over and add self-service cabinets for packaged lines. Our serve overs come in 1000, 1200, 1500 and 2000 mm widths that can be combined.' ),
			array( 'Should cheese and cooked meats share a counter?', 'They can share a counter at the right temperature, with separate trays, utensils and clear separation, following your food safety plan.' ),
		),
	),

	'upright-display-cabinets' => array(
		'long' => <<<'HTML'
<h2>Capacity and price guide</h2>
<ul>
<li>Half-height back bar chillers – from about £650 + VAT.</li>
<li>Single door glass door chillers – about 307–362 litres, from about £795 + VAT.</li>
<li>Double door glass door chillers – about 569–806 litres, from about £998 + VAT.</li>
<li>Upright display freezers – about 362 litres (single door) and 806 litres (double door), from about £850 + VAT.</li>
<li>Upright cake display – about 700 mm wide with glass on four sides, about £1,590 + VAT.</li>
</ul>

<h2>Planning a wall of upright cabinets</h2>
<p>Upright cabinets turn wall space into display space. Line chillers and freezers up along a wall at the same height for a tidy run, group drinks in one zone and frozen food in another, and keep the run within sight of the till. Allow room in front for hinged doors to open fully, or choose sliding doors where the aisle is narrow, and leave ventilation space above and behind each cabinet.</p>

<h2>Glass doors and running costs</h2>
<p>Closed glass doors keep the cold in, so upright cabinets are among the most economical ways to display chilled and frozen products. Self-closing, double or triple-glazed doors on the premium models cut condensation and heat gain further, which is why they suit the busiest stores.</p>
HTML,
		'faq'  => array(
			array( 'How much does an upright display cabinet cost?', 'From about £650 + VAT for a half-height chiller and £795 + VAT for a single door glass door chiller, up to about £1,900 + VAT for a double door display freezer.' ),
			array( 'Can chillers and freezers stand side by side?', 'Yes. Our upright chillers and freezers share the same height of about 1960 mm, so they line up neatly. Leave the ventilation space given in each manual.' ),
		),
	),

	'heated-display' => array(
		'long' => <<<'HTML'
<h2>The business case for hot food to go</h2>
<p>Hot food to go is one of the most profitable lines for bakeries, cafés, forecourts and convenience stores: pasties, sausage rolls, pies and breakfast items are made from low-cost ingredients and sell at a good margin, often as an add-on to a coffee or a meal deal. A heated display makes those products visible and ready to buy, which is what turns them into impulse purchases.</p>

<h2>Choosing a heated display</h2>
<ul>
<li><strong>Hot multideck</strong> – a 600 mm wide self-service tower with four hot plate shelves plus base, about £1,680 + VAT. Ideal where floor space is limited.</li>
<li><strong>Hot pastry displays</strong> – 900 and 1000 mm wide, double-glazed, loaded and served from the back through sliding rear doors, about £1,995–£1,999 + VAT.</li>
<li><strong>Heated grab and go open display</strong> – self-service on two fixed shelves for wrapped products, about £1,998 + VAT.</li>
</ul>

<h2>Running a hot display safely</h2>
<p>Switch the display on in good time so it is at temperature before loading. Load products piping hot from the oven, keep them at 63 °C or above, and label trays with the time they went on display so staff know when to remove them. Clean the shelves and glass daily once the cabinet has cooled, and empty any crumbs from the base.</p>
HTML,
		'faq'  => array(
			array( 'How much does a heated display cost?', 'About £1,680 + VAT for the hot multideck and about £1,995–£1,999 + VAT for the hot pastry displays and the heated grab and go.' ),
			array( 'Which heated display suits a small shop?', 'The 600 mm hot multideck gives four hot shelves plus base in a small footprint and runs from a 13-amp plug.' ),
			array( 'How should I clean a heated display?', 'Switch it off and let it cool, then wipe the shelves and glass with a suitable non-abrasive cleaner and remove crumbs from the base. Clean daily.' ),
		),
	),
);
