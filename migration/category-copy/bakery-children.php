<?php
/**
 * Copy for the child categories of Bakery Ovens & Refrigeration (migration 63):
 * Ovens, Grills, Oven Stands, Bakery Fridges, Bakery Freezers, Retarder Provers.
 * Figures come from each product's Features list and dimensions (2026-10-27).
 *
 * @package EcoFridgeMigration
 */

return array(

	'ovens' => array(
		'name'     => 'Commercial Ovens',
		'short'    => '<p>Stainless steel commercial convection ovens for cafés, bakeries and small kitchens – compact 13-amp models and large ovens with steam and grill functions, all thermostatically controlled from +50 to +300 °C.</p>',
		'long'     => <<<'HTML'
<h2>Convection ovens for professional kitchens</h2>
<p>Our commercial ovens are multi-purpose stainless steel convection ovens for bake-off, fresh baking and reheating in cafés, coffee shops, small restaurants and at events. Every model has a 120-minute timer with bell, a chamber light, double toughened glass that stays cool to the touch, good insulation on five sides and rounded cavity corners for easy cleaning.</p>

<h2>The range</h2>
<ul>
<li><strong><a href="/product/commercial-stainless-steel-oven/">Commercial stainless steel oven</a></strong> – 600 × 680 × 590 mm, four 400 × 300 mm trays, 3 kW on a 13-amp plug.</li>
<li><strong><a href="/product/commercial-stainless-steel-oven-and-grill-with-steamer/">Oven and grill with steamer</a></strong> – 600 × 630 × 575 mm, four 440 × 320 mm trays, steam function and a grill with its own thermostat, 2.6 kW on a 13-amp plug.</li>
<li><strong><a href="/product/commercial-stainless-steel-large-oven-with-steamer-2/">Large oven with grill and steamer</a></strong> – 860 × 750 × 590 mm, four 590 × 390 mm trays, 6 kW.</li>
<li><strong><a href="/product/commercial-stainless-steel-large-oven-with-steamer/">Large oven with steamer</a></strong> – 920 × 850 × 825 mm, eight 520 × 390 mm trays, 9 kW.</li>
</ul>
<p>Each oven comes with its aluminium baking trays. The 6 kW and 9 kW ovens must be installed by a qualified electrician.</p>

<h2>Choosing an oven</h2>
<p>Start with what you bake and how much. The compact ovens suit bake-off pastries and savouries at a café counter and plug into a normal socket; the large ovens take bigger trays for higher volumes. Choose a steam model for bread with a good crust and for reheating without drying food out. For a sturdy base with tray storage, see our <a href="/range/bakery-cabinets/oven-stands/">oven stands</a>; for panini and toasties, our <a href="/range/bakery-cabinets/grills/">grills</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a commercial convection oven?', 'A fan-assisted oven built for professional use: the fan circulates hot air so food cooks evenly on every tray and faster than in a conventional oven.' ),
			array( 'Which ovens plug into a 13-amp socket?', 'The commercial stainless steel oven (3 kW) and the oven and grill with steamer (2.6 kW). The large 6 kW and 9 kW ovens must be installed by a qualified electrician.' ),
			array( 'What temperature do the ovens reach?', 'They are thermostatically controlled from +50 to +300 °C.' ),
			array( 'Are baking trays included?', 'Yes – each oven comes with aluminium baking trays: four, or eight with the large oven with steamer.' ),
			array( 'What is the steam function for?', 'Steam helps bread develop a good crust and keeps pastries and reheated products moist.' ),
		),
		'articles' => array( 7366, 7427, 7444 ),
	),

	'grills' => array(
		'name'     => 'Commercial Grills',
		'short'    => '<p>Stainless steel commercial grills – a contact grill for panini, toasties and burgers, and convection ovens with a built-in grill, all heating up to +300 °C.</p>',
		'long'     => <<<'HTML'
<h2>Grilling for cafés and kitchens</h2>
<p>A commercial grill toasts, sears and finishes food quickly during busy service. Choose a dedicated contact grill for the counter, or a convection oven with a built-in grill that bakes, steams and grills in one footprint.</p>

<h2>The range</h2>
<ul>
<li><strong><a href="/product/commercial-stainless-steel-contact-grill/">Contact grill</a></strong> – 430 × 370 × 200 mm with a 340 × 235 mm cooking area, cast iron plates that hold their heat, 50 to 300 °C, 230 V, 21 kg. For panini, sandwiches and burgers made to order.</li>
<li><strong><a href="/product/commercial-stainless-steel-oven-and-grill-with-steamer/">Oven and grill with steamer</a></strong> – 600 × 630 × 575 mm, oven and grill each +50 to +300 °C, steam function, 2.6 kW on a 13-amp plug.</li>
<li><strong><a href="/product/commercial-stainless-steel-large-oven-with-steamer-2/">Large oven with grill and steamer</a></strong> – 860 × 750 × 590 mm, four 590 × 390 mm trays, 6 kW (installation by a qualified electrician).</li>
</ul>

<h2>Contact grill or oven with grill?</h2>
<p>A contact grill cooks from both sides at once, so it is the quickest way to serve toasted sandwiches and burgers to order. An oven with a grill function adds browning and gratinating to baking and steaming, which saves space in a small kitchen. See all our <a href="/range/bakery-cabinets/ovens/">commercial ovens</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a contact grill?', 'A grill with heated plates above and below the food, so both sides cook at once – used for panini, toasted sandwiches and burgers.' ),
			array( 'What temperature does the contact grill reach?', 'It heats from 50 to 300 °C. Its cast iron plates hold their heat, so it keeps up when several orders come in together.' ),
			array( 'Do the ovens with grill need special wiring?', 'The oven and grill with steamer (2.6 kW) uses a 13-amp plug. The large 6 kW oven with grill and steamer must be installed by a qualified electrician.' ),
			array( 'How big is the contact grill’s cooking area?', '340 × 235 mm, in a 430 × 370 × 200 mm unit that fits on a counter.' ),
		),
		'articles' => array( 7427, 7444, 7366 ),
	),

	'oven-stands' => array(
		'name'     => 'Oven Stands',
		'short'    => '<p>A heavy-duty stainless steel oven stand that gives a commercial oven a stable base, with tray runners on both sides for 600 × 600 mm trays and adjustable feet.</p>',
		'long'     => <<<'HTML'
<h2>A stable base with tray storage</h2>
<p>An oven stand raises a counter-top oven off the worktop and keeps baking trays, gastronorm pans and oven racks within reach beneath it. Our <a href="/product/commercial-stainless-steel-large-oven-stand/">large oven stand</a> is made of corrosion-resistant stainless steel, with an open frame that is easy to clean.</p>

<h2>Specification</h2>
<ul>
<li>850 × 700 × 690 mm (W × D × H).</li>
<li>Tray runners on both sides for 600 × 600 mm trays.</li>
<li>Strong, heavy-duty stainless steel frame for professional use.</li>
<li>Adjustable feet for stability on uneven floors.</li>
</ul>

<h2>Choosing a stand</h2>
<p>The stand is offered with our large ovens. Compare your oven’s footprint with the stand’s 850 × 700 mm top before ordering, or call <a href="tel:01280811411">01280 811411</a> and we will check it for you. Browse our <a href="/range/bakery-cabinets/ovens/">commercial ovens</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an oven stand for?', 'It gives a counter-top commercial oven a stable base at a working height and stores trays, pans and racks underneath.' ),
			array( 'What trays does it hold?', 'It has tray runners on both sides for 600 × 600 mm trays.' ),
			array( 'Will it stand level on an uneven floor?', 'Yes, it has adjustable feet.' ),
			array( 'Which ovens does it suit?', 'It is offered with our large ovens. Check your oven’s footprint against the 850 × 700 mm top, or call us on 01280 811411.' ),
		),
		'articles' => array( 7427, 7366, 7444 ),
	),

	'bakery-fridges' => array(
		'name'     => 'Bakery Fridges',
		'short'    => '<p>Upright stainless steel bakery fridges for ten 600 × 400 mm bakery trays, with forced-air cooling at +2 to +7 °C, designed to work in kitchens up to 43 °C.</p>',
		'long'     => <<<'HTML'
<h2>Chilled storage on bakery trays</h2>
<p>A bakery fridge stores dough, pastry, fillings and finished bakes on standard 600 × 400 mm bakery trays, so products go from the bench into the fridge and back without being moved onto other containers. Our single-door <a href="/product/stainless-steel-upright-bakery-fridge/">upright bakery fridge</a> holds ten trays in a 600 mm wide stainless steel cabinet.</p>

<h2>Key features</h2>
<ul>
<li>600 × 950 × 1950 mm (W × D × H), capacity of ten 600 × 400 mm trays.</li>
<li>+2 to +7 °C, with forced-air refrigeration for an even temperature throughout the cabinet.</li>
<li>Digital temperature display; controller with cabinet, evaporator and condenser probes.</li>
<li>Energy-saving auto defrost, with the defrost water evaporated by a hot gas pipe.</li>
<li>Evaporator fan stops when the door opens; compressor protected by an overheat cut-out with automatic reset.</li>
<li>Stainless steel exterior, full-height door handle, removable door gasket and fitted castors.</li>
<li>Designed to operate in ambient temperatures up to 43 °C.</li>
</ul>
<p>The matching <a href="/range/bakery-cabinets/bakery-freezers/">bakery freezer</a> and <a href="/range/bakery-cabinets/retarder-provers/">retarder prover</a> have the same 600 × 950 mm footprint. For general kitchen storage, see our <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/">upright storage fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a bakery fridge?', 'A refrigerator built around standard 600 × 400 mm bakery trays, for storing dough, pastry, fillings and finished bakes.' ),
			array( 'How many trays does it hold?', 'Ten 600 × 400 mm bakery trays.' ),
			array( 'What temperature does it keep?', '+2 to +7 °C, with forced-air refrigeration for an even temperature.' ),
			array( 'Will it cope with a hot bakery?', 'Yes, it is designed to operate in ambient temperatures up to 43 °C.' ),
		),
		'articles' => array( 7366, 7349, 7359 ),
	),

	'bakery-freezers' => array(
		'name'     => 'Bakery Freezers',
		'short'    => '<p>Upright stainless steel bakery freezers for ten 600 × 400 mm bakery trays, holding −18 to −24 °C, designed to work in kitchens up to 43 °C.</p>',
		'long'     => <<<'HTML'
<h2>Frozen storage on bakery trays</h2>
<p>A bakery freezer keeps frozen dough, par-baked bread and pastries on the same 600 × 400 mm trays you bake on, ready to go into the oven or the retarder prover. Our single-door <a href="/product/stainless-steel-upright-bakery-freezer/">upright bakery freezer</a> holds ten trays in a 600 mm wide stainless steel cabinet.</p>

<h2>Key features</h2>
<ul>
<li>600 × 950 × 1950 mm (W × D × H), capacity of ten 600 × 400 mm trays.</li>
<li>−18 to −24 °C.</li>
<li>Digital temperature display; controller with cabinet, evaporator and condenser probes.</li>
<li>Energy-saving auto defrost, with the defrost water evaporated by a hot gas pipe.</li>
<li>Evaporator fan stops when the door opens; compressor protected by an overheat cut-out with automatic reset.</li>
<li>Stainless steel exterior, full-height door handle, removable door gasket and fitted castors.</li>
<li>Designed to operate in ambient temperatures up to 43 °C.</li>
</ul>
<p>It is a storage freezer: it keeps products frozen rather than blast-freezing them. Pair it with the matching <a href="/range/bakery-cabinets/bakery-fridges/">bakery fridge</a> or <a href="/range/bakery-cabinets/retarder-provers/">retarder prover</a>, or see our <a href="/product-category/storage-freezer/upright-freezer-storage-freezer/">upright storage freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a bakery freezer?', 'A freezer built around standard 600 × 400 mm bakery trays, for keeping frozen dough, par-baked bread and pastries.' ),
			array( 'What temperature does it hold?', '−18 to −24 °C.' ),
			array( 'How many trays does it hold?', 'Ten 600 × 400 mm bakery trays.' ),
			array( 'Can it freeze fresh products quickly?', 'It is a storage freezer, designed to keep products frozen, not a blast freezer. Freeze products to your process’s requirements before storing them.' ),
		),
		'articles' => array( 7372, 7394, 7349 ),
	),

	'retarder-provers' => array(
		'name'     => 'Retarder Provers',
		'short'    => '<p>An upright stainless steel retarder prover for ten 600 × 400 mm trays: it holds dough chilled, then proves it with controlled temperature (0 to +40 °C) and humidity on a timer, ready to bake in the morning.</p>',
		'long'     => <<<'HTML'
<h2>Prove overnight, bake first thing</h2>
<p>A retarder prover is a fridge and a prover in one cabinet. In retard mode it keeps dough cold to slow fermentation; at the time you set, it switches to proving, warming the cabinet and controlling humidity so the dough is ready to bake when you arrive. You can shape dough at the end of the day and bake fresh the next morning without an early start.</p>

<h2>Key features</h2>
<ul>
<li>600 × 950 × 2000 mm (W × D × H), single door, ten 600 × 400 mm trays (trays not supplied).</li>
<li>Temperature range 0 to +40 °C, with humidity control.</li>
<li>Timer to change from retarding to proving automatically.</li>
<li>Forced-air system for uniform conditions throughout the cabinet.</li>
<li>Stainless steel exterior, full-height door handle, removable door gasket and fitted castors.</li>
<li>Designed to operate in ambient temperatures up to 43 °C.</li>
<li>Needs a water supply and an external drain (pipes included).</li>
</ul>
<p>The user manual is on the <a href="/product/stainless-steel-upright-bakery-retarder-prover/">product page</a>. For straightforward chilled or frozen storage, see our <a href="/range/bakery-cabinets/bakery-fridges/">bakery fridges</a> and <a href="/range/bakery-cabinets/bakery-freezers/">bakery freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a retarder prover?', 'A cabinet that first retards dough by keeping it cold, then proves it by raising the temperature and humidity at a set time, so it is ready to bake when you need it.' ),
			array( 'How does the timer help?', 'Set the cycle at the end of the day: the dough stays chilled overnight and is proved in time to bake the following morning.' ),
			array( 'What does it need to be installed?', 'A water supply and an external drain; the pipes are included.' ),
			array( 'Are trays included?', 'No. It holds ten 600 × 400 mm bakery trays, which are not supplied.' ),
		),
		'articles' => array( 7366, 7418, 7359 ),
	),
);
