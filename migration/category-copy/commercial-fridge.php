<?php
/**
 * Category copy — Commercial Fridge tree (used by migration 39).
 *
 * Per category: name (H1), short (1–2 sentences, under the H1), long (HTML
 * below the products), faq ([question, answer] — the first defines the
 * category), articles (blog post IDs for "Buying guides & articles").
 * Text uses typographic apostrophes (’) so the PHP strings stay single-quoted.
 *
 * @package EcoFridgeMigration
 */

return array(

	'commercial-fridge' => array(
		'name'     => 'Commercial Fridges',
		'short'    => '<p>Commercial fridges for shops, cafés, restaurants and kitchens: display fridges that sell and storage fridges that keep stock safe, built for constant use and supplied at trade prices with free UK mainland delivery.</p>',
		'long'     => <<<'HTML'
<h2>Commercial fridges built for business</h2>
<p>A commercial fridge is a refrigerator designed for the demands of trade use: doors opened all day, busy kitchens and warm shop floors, and food that has to stay within a safe temperature band. ECO-Fridge supplies two families of commercial refrigeration, both sold direct at trade prices (ex VAT) and delivered free to UK mainland addresses.</p>

<h3>Display fridges – refrigeration that sells</h3>
<p><a href="/product-category/commercial-display-fridge/">Display fridges</a> put chilled products in front of customers. Choose an <a href="/product-category/commercial-display-fridge/multideck-fridge/">open multideck</a> for self-service food and drinks, a <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go fridge</a> for counters and cafés, a <a href="/product-category/commercial-display-fridge/patisserie-fridge/">patisserie fridge</a> for cakes and desserts, a <a href="/product-category/commercial-display-fridge/serve-over/">serve over counter</a> for delis and butchers, or a <a href="/product-category/commercial-display-fridge/upright-fridge/">glass door fridge</a> for bottled and canned drinks.</p>

<h3>Storage fridges – back-of-house reliability</h3>
<p><a href="/product-category/storage-fridge/">Storage fridges</a> keep ingredients and stock at a steady temperature away from the customer. The range covers <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/">upright storage fridges</a> for walls and stockrooms, <a href="/product-category/storage-fridge/under-counter-fridge/">under counter fridges</a> that turn worktops into chilled storage, and stainless steel <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/">gastronorm and meat fridges</a> for professional kitchens and butchers.</p>

<h2>How to choose the right commercial fridge</h2>
<ul>
<li><strong>Front or back of house?</strong> If customers should see or pick the product, you need a display fridge; if the fridge only holds stock, a solid-door storage fridge is more efficient.</li>
<li><strong>What you chill.</strong> Most display cabinets hold around −1 to +7 °C, patisserie fridges run slightly warmer to protect cream and glazes, and gastronorm chillers for meat and fish go down to −5 °C. The comparison table on each range shows the exact figures.</li>
<li><strong>Space and access.</strong> Measure the width, depth and height of the space, the doors and the route in. Widths in this range run from compact 600 mm units to long multidecks and serve overs.</li>
<li><strong>Finish.</strong> Standard ranges come in silver, black or white steel; premium ranges use high-grade 304 stainless steel with a mirrored finish for front-of-house impact.</li>
</ul>

<h2>Buying from ECO-Fridge</h2>
<p>ECO-Fridge UK Ltd is based in Buckingham and has supplied commercial refrigeration to independent shops and well-known brands for years. Prices are trade prices shown excluding VAT, delivery to UK mainland is free (some areas carry a surcharge – see the terms), and many models can be ordered with removal and disposal of your old unit or an optional 12-month parts warranty. Need advice on sizing? Call <a href="tel:01280811411">01280 811411</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a commercial fridge?', 'A commercial fridge is a refrigerator built for business use rather than the home. It has a stronger refrigeration system, a digital controller and a cabinet designed to recover quickly after the door is opened, so food stays at a safe temperature through a full trading day.' ),
			array( 'What is the difference between a commercial fridge and a domestic fridge?', 'Domestic fridges are designed for a few door openings a day in a cool kitchen. Commercial fridges are built for constant use, higher room temperatures and larger loads, with heavier shelving, fan-assisted cooling and easy-clean finishes. Using a domestic fridge in a business can also make food-safety compliance harder.' ),
			array( 'Do I need a display fridge or a storage fridge?', 'Choose a <a href="/product-category/commercial-display-fridge/">display fridge</a> when customers should see or help themselves to the products – drinks, sandwiches, cakes, deli items. Choose a <a href="/product-category/storage-fridge/">storage fridge</a> for ingredients and back-up stock in kitchens and stockrooms, where solid doors keep the cold in more efficiently.' ),
			array( 'What temperature should a commercial fridge run at?', 'Chilled food is normally kept at 8 °C or below in the UK, and most businesses aim for 1–5 °C. Our display cabinets typically run between −1 and +7 °C, patisserie fridges a little warmer to protect delicate products, and meat and fish gastronorm chillers down to −5 °C.' ),
			array( 'Are your prices shown with or without VAT?', 'All prices are trade prices shown excluding VAT. VAT is added at checkout, and delivery to UK mainland addresses is free (deliveries above York, Leeds and Blackpool or outside the mainland carry an extra charge).' ),
			array( 'Can you take away my old fridge?', 'Yes. On models that offer it, add Removal &amp; Disposal of your old equal-size unit when you order and we will take it away on delivery, so you do not need to arrange WEEE disposal yourself.' ),
		),
		'articles' => array( 7385, 7443, 7438 ),
	),

	'commercial-display-fridge' => array(
		'name'     => 'Display Fridges',
		'short'    => '<p>Commercial display fridges that keep food and drinks cold while putting them on show – multidecks, grab and go, patisserie, serve over and glass door fridges for every kind of shop and café.</p>',
		'long'     => <<<'HTML'
<h2>Display fridges that turn cold stock into sales</h2>
<p>A display fridge does two jobs at once: it keeps products at a safe chilled temperature and it presents them so customers want to buy. Bright LED lighting, large glass areas and a low front all help shoppers see what is on offer, while a digital controller holds the cabinet in its temperature band through busy trading hours.</p>

<h2>Types of display fridge</h2>
<ul>
<li><a href="/product-category/commercial-display-fridge/multideck-fridge/"><strong>Open multideck fridges</strong></a> – self-service shelving for convenience stores, supermarkets and food-to-go, with night blinds or shutters to save energy after hours.</li>
<li><a href="/product-category/commercial-display-fridge/grab-and-go/"><strong>Grab and go fridges</strong></a> – compact open-front cabinets for sandwiches, salads and drinks next to the till.</li>
<li><a href="/product-category/commercial-display-fridge/patisserie-fridge/"><strong>Patisserie fridges</strong></a> – closed glass cabinets that protect cakes and desserts while showing them off.</li>
<li><a href="/product-category/commercial-display-fridge/serve-over/"><strong>Serve over counters</strong></a> – staff-served deli and butchery counters with chilled storage underneath.</li>
<li><a href="/product-category/commercial-display-fridge/upright-fridge/"><strong>Glass door fridges</strong></a> and <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/"><strong>drinks fridges</strong></a> – upright cabinets for bottles and cans.</li>
<li><a href="/product-category/commercial-display-fridge/counter-fridge/"><strong>Counter fridges</strong></a> and <a href="/product-category/commercial-display-fridge/counter-fridge/top-counter/"><strong>countertop fridges</strong></a> – double-glazed displays at counter height or on top of an existing worktop.</li>
</ul>

<h2>Choosing a display fridge</h2>
<p>Start with how customers buy. Self-service suits open multidecks and grab and go units; products that need protecting or serving suit closed patisserie cabinets and serve overs. Then match the size to your floor space – widths run from 600 mm units for small cafés up to long multidecks and 2-metre serve overs – and choose a finish: silver, black or white steel in the standard ranges, or mirrored 304 stainless steel in the premium ranges.</p>
<p>Think about running hours too. Models with integral night blinds or lockable shutters reduce energy use overnight, and slide-out condensing systems make routine cleaning quicker, which keeps the fridge efficient for longer.</p>

<h2>Trade prices and free delivery</h2>
<p>All display fridges are supplied direct by ECO-Fridge at trade prices excluding VAT, with free delivery to UK mainland addresses. Compare the ranges in the table below, or call our team on <a href="tel:01280811411">01280 811411</a> for help choosing.</p>
HTML,
		'faq'      => array(
			array( 'What is a display fridge?', 'A display fridge is a commercial refrigerator with glass or an open front, designed to keep chilled products cold while customers can see them. It combines refrigeration with lighting and shelving that present the products for sale.' ),
			array( 'Which display fridge is best for a convenience store?', 'Open <a href="/product-category/commercial-display-fridge/multideck-fridge/">multideck fridges</a> are the usual choice: customers help themselves from several shelves and the low front maximises what they can see. Add a <a href="/product-category/commercial-display-fridge/upright-fridge/">glass door fridge</a> for drinks if you want lower running costs on bottled lines.' ),
			array( 'What is the difference between open and closed display fridges?', 'Open fridges such as multidecks and grab and go units allow self-service and encourage impulse buys, but use more energy, so most have night blinds or shutters. Closed fridges such as patisserie cabinets and glass door fridges keep the cold in better and protect delicate products.' ),
			array( 'What temperature does a display fridge run at?', 'Most of our chilled display cabinets operate between −1 and +7 °C. Patisserie fridges run slightly warmer (around +3 to +12 °C on some models) to keep cream and glazes in good condition, and serve over counters around 0 to +12 °C.' ),
			array( 'Do display fridges come with castors?', 'Some premium models have castors fitted as standard, and on many others castors can be added as an option when you order. Standard models usually stand on adjustable feet for levelling.' ),
		),
		'articles' => array( 7402, 7360, 7423 ),
	),

	'multideck-fridge' => array(
		'name'     => 'Multideck Fridges',
		'short'    => '<p>Open multideck fridges for self-service retail: four tiers of shelving, a low front and a night blind or shutter, in silver, black and mirrored stainless steel from compact 600 mm to long 1800 mm runs.</p>',
		'long'     => <<<'HTML'
<h2>Open fridges for shops and food-to-go</h2>
<p>A multideck fridge – often simply called an open fridge for shops – is an upright, open-fronted refrigerated cabinet with several tiers of shelving. Customers take products straight from the shelf, which makes multidecks the backbone of convenience stores, forecourts, supermarkets, canteens and food-to-go counters.</p>

<h2>What our multidecks have in common</h2>
<ul>
<li>Four tiers of adjustable shelving plus a base deck, with clear acrylic shelf-edge risers to keep products in place.</li>
<li>A low front and glass end panels so shoppers can see the whole range from the aisle.</li>
<li>Full-length top-mounted lighting and a digital temperature controller, holding −1 to +7 °C on the chilled models.</li>
<li>An integral night blind or a lockable shutter that closes the front out of hours to save energy.</li>
<li>Slide-out condensing systems and stainless steel drip trays for straightforward cleaning.</li>
</ul>

<h2>Which multideck range?</h2>
<p>The <a href="/range/display-cabinets/silver-steel-multideck-display-chillers/">standard silver steel slimline multidecks</a> and <a href="/range/display-cabinets/black-steel-slimline-multideck-display-fridge/">black steel slimline multidecks</a> are 600 mm deep, so they fit tight shop layouts, and come in widths from 600 to 1800 mm. The <a href="/range/display-cabinets/premium-mirrored-stainless-steel-multidecks/">premium mirrored stainless steel multidecks</a> add a high-grade 304 stainless finish for a more upmarket look, while the <a href="/range/display-cabinets/premium-mirrored-stainless-steel-shutter-multidecks/">shutter multidecks</a> replace the blind with a lockable shutter for extra security overnight. For hot and cold food in one footprint, see the <a href="/range/display-cabinets/dual-temperature-hot-and-cold-premium-mirrored-stainless-steel-multideck/">dual temperature multideck</a>.</p>

<h2>Sizing tips</h2>
<p>Allow space above and around the cabinet for air to circulate, and keep it away from direct sunlight, heaters and draughts from doors – open fridges work hardest in warm, moving air. Multidecks are rated for a maximum room temperature of around 25 °C, so choose a cool spot on the shop floor and use the night blind every evening.</p>
HTML,
		'faq'      => array(
			array( 'What is a multideck fridge?', 'A multideck fridge is an upright, open-fronted refrigerated display with several shelves (“decks”). Cold air curtains across the open front keep products chilled while customers help themselves, which makes multidecks ideal for self-service retail.' ),
			array( 'What sizes do your multidecks come in?', 'Our slimline multidecks are about 600 mm deep and around 1950–2000 mm high, in widths from 600 mm up to 1800 mm. The shutter multideck range is 750 mm deep. See the comparison table for the exact size of each model.' ),
			array( 'What is a night blind for?', 'The night blind is pulled down over the open front when the shop closes. It traps the cold air inside the cabinet, so the fridge uses noticeably less energy overnight and the products stay at temperature until the morning.' ),
			array( 'Night blind or shutter – which should I choose?', 'A blind is lighter and quick to use every day. A lockable shutter does the same energy-saving job but also secures the products overnight, which suits forecourts, shared spaces and stores with high-value lines.' ),
			array( 'What temperature does a multideck hold?', 'Our chilled multidecks are designed to run between −1 and +7 °C, which suits dairy, sandwiches, ready meals and drinks. They should be installed where the room stays below about 25 °C.' ),
			array( 'Can a multideck go in a small shop?', 'Yes. The 600 mm wide slimline models are designed for small shops, cafés and kiosks, and the shallow 600 mm depth keeps aisles clear. Measure the doorways too, so the cabinet can be delivered to its final position.' ),
		),
		'articles' => array( 7407, 7364, 7363 ),
	),

	'grab-and-go' => array(
		'name'     => 'Grab and Go Fridges',
		'short'    => '<p>Open-front grab and go fridges for sandwiches, salads and drinks – compact counter-height displays in silver, black or mirrored stainless steel that keep impulse buys within reach of the till.</p>',
		'long'     => <<<'HTML'
<h2>Self-service chilled display for cafés and counters</h2>
<p>A grab and go fridge is a compact open-fronted display, usually around 1.35–1.5 m high, that lets customers pick up chilled food and drinks without waiting to be served. Placed next to the till or along a counter run, it is one of the most effective ways to sell sandwiches, wraps, salads, yoghurts and bottled drinks in coffee shops, bakeries, canteens and petrol stations.</p>

<h2>Standard or premium?</h2>
<p>The <a href="/range/display-cabinets/standard-grab-and-go-display-fridges/">standard grab and go fridges</a> come in silver or black coloured steel with flat glass, three tiers of adjustable glass shelving, full-length LED lighting and a +5 to +7 °C operating range, in 600, 900 and 1200 mm widths. The <a href="/range/display-cabinets/premium-grab-and-go-display-fridges/">premium grab and go fridges</a> are built from mirrored 304 stainless steel with under-shelf lighting and heavy-duty castors, run from −1 to +7 °C and are the style used by major coffee chains.</p>

<h2>Features that make a difference</h2>
<ul>
<li>Low front to maximise the visible display and make picking easy.</li>
<li>Digital temperature control and automatic defrost.</li>
<li>Slide-out condensing system and stainless steel drip tray for cleaning.</li>
<li>Self-evaporating drainage, so there is no water to empty.</li>
</ul>

<h2>Where to place a grab and go fridge</h2>
<p>Keep it within sight of the till so customers add an item while they queue, but away from coffee machines, ovens and direct sunlight. Like all open-fronted fridges, it performs best in a room below about 25 °C.</p>
HTML,
		'faq'      => array(
			array( 'What is a grab and go fridge?', 'A grab and go fridge is a compact, open-fronted refrigerated display that customers serve themselves from. It is designed to sit by the till or on a counter run so chilled snacks and drinks become quick impulse purchases.' ),
			array( 'What can I sell from a grab and go fridge?', 'Pre-packed sandwiches, wraps, salads, sushi, fruit pots, yoghurts, desserts and bottled drinks all work well. Products should be wrapped or packaged, because the front is open to the shop.' ),
			array( 'What sizes are available?', 'Standard models come in 600, 900 and 1200 mm widths, about 730 mm deep and 1450 mm high. The premium mirrored stainless steel range adds models around 800 mm deep and 1500 mm high. See the comparison table for each model.' ),
			array( 'What is the difference between standard and premium grab and go fridges?', 'Standard models use silver or black coloured steel and hold +5 to +7 °C. Premium models use mirrored 304 stainless steel, add under-shelf lighting and castors, and hold −1 to +7 °C for a wider range of chilled foods.' ),
			array( 'Do grab and go fridges need a drain?', 'No. Our grab and go fridges use a self-evaporating drainage system, so defrost water is evaporated automatically and there is no drain connection or tray to empty.' ),
		),
		'articles' => array( 7380, 7446, 7435 ),
	),

	'patisserie-fridge' => array(
		'name'     => 'Patisserie Fridges',
		'short'    => '<p>Patisserie display fridges and cake fridges that protect cream cakes, desserts and pastries behind clear glass while showing them at their best, in standard slimline and premium stainless steel ranges.</p>',
		'long'     => <<<'HTML'
<h2>Show off cakes, keep them perfect</h2>
<p>A patisserie fridge is a closed glass display cabinet designed for cakes, gateaux, cheesecakes, tarts and desserts. The glass protects delicate products from the shop air while customers see every slice, and the cabinet runs a little warmer than a standard fridge so that cream, mousse and glazes keep their texture and shine.</p>

<h2>Our patisserie ranges</h2>
<ul>
<li><a href="/range/display-cabinets/standard-patisserie-slimline-display-fridges/">Standard slimline patisserie fridges</a> – double-glazed flat glass in silver or black steel, holding +3 to +7 °C in compact widths.</li>
<li><a href="/range/display-cabinets/patisserie-display-fridges/">Standard patisserie display fridges</a> – flat front and side glass, three tiers plus base, +3 to +12 °C, in 600, 900 and 1200 mm widths.</li>
<li><a href="/range/display-cabinets/premium-patisserie-display-fridges/">Premium patisserie display fridges</a> – mirrored 304 stainless steel with curved or flat double glazing, under-shelf lighting and, on some models, castors.</li>
<li><a href="/range/display-cabinets/upright-cake-displays/">Upright cake display fridges</a> – tall four-sided glass towers that show cakes from every angle.</li>
</ul>

<h2>Choosing a cake fridge</h2>
<p>Think about how many products you show at once and how they are served. Staff-served counters suit cabinets with rear access; self-service bakeries may prefer a <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go</a> unit for packaged items. Double-glazed glass reduces condensation and energy use, and LED lighting keeps colours true without warming the products.</p>
<p>Patisserie fridges are not designed to chill warm products quickly – cool cakes first, then place them in the cabinet. Keep the fridge out of direct sunlight and away from ovens for the best results.</p>
HTML,
		'faq'      => array(
			array( 'What is a patisserie fridge?', 'A patisserie fridge, or cake fridge, is a refrigerated glass display cabinet for cakes, pastries and desserts. It protects the products from the room while presenting them clearly, and runs at a temperature suited to cream and delicate fillings.' ),
			array( 'What temperature should a cake display fridge be?', 'Most cream cakes and desserts are best kept between about +3 and +7 °C. Our standard slimline models hold +3 to +7 °C, while several flat-glass and premium models offer a +3 to +12 °C range so you can set the cabinet to suit what you sell.' ),
			array( 'Curved glass or flat glass?', 'Curved glass gives a traditional patisserie look and wide viewing angles. Flat glass looks more contemporary, fits neatly into counter runs and is easy to clean. Both are available in our premium range.' ),
			array( 'Why is double glazing important?', 'Double-glazed glass keeps the cold in and the warm shop air out. That reduces misting on the glass, keeps products looking good and lowers running costs.' ),
			array( 'Can I put a patisserie fridge on a counter?', 'Our patisserie fridges are free-standing cabinets with their own base. For displays that sit on an existing worktop, see our <a href="/product-category/commercial-display-fridge/counter-fridge/top-counter/">countertop display fridges</a>.' ),
		),
		'articles' => array( 7358, 7395, 7366 ),
	),

	'upright-cake-fridge' => array(
		'name'     => 'Upright Cake Fridges',
		'short'    => '<p>Upright cake fridges – tall glass cake display towers that show cakes and desserts from every side. See our current models in the upright cake display range.</p>',
		'long'     => <<<'HTML'
<h2>Tall cake displays</h2>
<p>An upright cake fridge is a tall refrigerated glass tower for cakes, gateaux and desserts, usually with glass on all four sides so customers see every slice from across the room. It needs little floor space and works well in cafés, restaurants, hotels and bakeries.</p>
<p>Our current models – a mirrored stainless steel cake display with glass on four sides and a large upright glass display with rear sliding doors – are listed in <a href="/range/display-cabinets/upright-cake-displays/">upright cake display fridges</a>. For counter-height cake displays, see <a href="/product-category/commercial-display-fridge/patisserie-fridge/">patisserie fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an upright cake fridge?', 'A tall refrigerated glass cabinet for displaying cakes and desserts, often with glass on all four sides.' ),
			array( 'Where can I see the current models?', 'Our upright cake displays are listed in <a href="/range/display-cabinets/upright-cake-displays/">upright cake display fridges</a>.' ),
			array( 'What temperature do they hold?', 'Our upright cake displays hold −1 to +7 °C.' ),
			array( 'Are there counter-height alternatives?', 'Yes – see <a href="/product-category/commercial-display-fridge/patisserie-fridge/">patisserie fridges</a>.' ),
		),
		'articles' => array( 7357, 7358, 7388 ),
	),

	'commercial-drinks-fridge' => array(
		'name'     => 'Drinks Fridges',
		'short'    => '<p>Commercial drinks fridges that keep beer, soft drinks and white wine cold and on show – single and double glass door uprights, sliding-door chillers and back bar fridges for shops, bars and cafés.</p>',
		'long'     => <<<'HTML'
<h2>Cold drinks, clearly displayed</h2>
<p>A commercial drinks fridge is a glass door refrigerator designed to keep cans and bottles cold and visible. Customers or staff can see the full range at a glance, while the closed door holds the cold in far better than an open cabinet – which is why glass door fridges are the most economical way to chill drinks.</p>

<h2>Drinks fridge styles</h2>
<ul>
<li><strong>Single door uprights</strong> – around 600 mm wide, ideal for cafés, takeaways and small shops.</li>
<li><strong>Double door uprights</strong> – hinged or sliding glass doors around 1200 mm wide for high-volume ranges; sliding doors save space in narrow aisles.</li>
<li><strong>Back bar fridges</strong> – half-height chillers that sit under the bar counter, see <a href="/range/display-cabinets/back-bar-display-chillers/">back bar display chillers</a>.</li>
</ul>
<p>Our drinks fridges hold around +2 to +7 °C, which suits beer, cider, soft drinks, water and white, rosé or sparkling wine. Browse by use: <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/beer-fridge/">beer fridges</a>, <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/wine-fridge/">wine fridges</a> and <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/bar-fridge/">bar fridges</a>.</p>

<h2>Features to look for</h2>
<ul>
<li>Self-closing, double or triple-glazed doors that reduce condensation and energy use.</li>
<li>Vertical LED strip lighting that lights every shelf evenly.</li>
<li>Heavy-duty adjustable shelving for full crates of bottles.</li>
<li>Suitability for high ambient temperatures – important behind bars and in warm shops.</li>
</ul>

<h2>Placement and loading</h2>
<p>Leave a gap behind and above the cabinet for ventilation, load warm stock gradually so the fridge can recover, and keep products away from the back wall so cold air can circulate. Need a bigger range? See all <a href="/product-category/commercial-display-fridge/upright-fridge/">glass door fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a commercial drinks fridge?', 'A commercial drinks fridge is a glass door refrigerator built for chilling and displaying bottles and cans in a business. It is designed for frequent door openings and warm environments, unlike a domestic fridge.' ),
			array( 'What temperature should a drinks fridge be set to?', 'Most drinks are served cold at around 3–6 °C. Our drinks fridges operate between about +2 and +7 °C, which suits beer, soft drinks, water and white or sparkling wine.' ),
			array( 'Hinged or sliding doors?', 'Hinged doors open wide for easy loading and suit most shops. Sliding doors need no swing space, so they are ideal in narrow aisles, behind counters and in busy bars.' ),
			array( 'Are glass door fridges cheaper to run than open fridges?', 'Yes. A closed glass door keeps the cold air in, so a glass door fridge uses considerably less energy than an open-fronted multideck displaying the same drinks.' ),
			array( 'Can I use a drinks fridge for food?', 'You can store packaged food in a glass door chiller at the right temperature, but for open food or high-turnover self-service food a <a href="/product-category/commercial-display-fridge/multideck-fridge/">multideck</a> or <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go fridge</a> is a better fit.' ),
		),
		'articles' => array( 7445, 7421, 7424 ),
	),

	'bar-fridge' => array(
		'name'     => 'Bar Fridges',
		'short'    => '<p>Bar fridges for pubs, bars and restaurants – back bar chillers that fit under the counter and upright glass door fridges that keep beer, mixers and wine cold and on show.</p>',
		'long'     => <<<'HTML'
<h2>Refrigeration made for behind the bar</h2>
<p>A bar fridge is a compact commercial refrigerator that keeps drinks within easy reach of bar staff. Back bar fridges are around counter height and slide under or behind the bar; taller upright glass door fridges stock larger ranges where there is wall space. Both keep bottles cold and let staff and customers see what is available.</p>

<h2>What makes a good bar fridge</h2>
<ul>
<li><strong>Size to fit.</strong> Our half-height back bar chillers are about 900 mm high, so they fit under a standard bar counter; upright models are around 1960 mm high.</li>
<li><strong>Double-glazed doors.</strong> They reduce condensation and keep running costs down when the bar is warm and busy.</li>
<li><strong>Black or white finish.</strong> Black steel blends into most back bars; white suits cafés and lighter interiors.</li>
<li><strong>Heavy-duty shelves</strong> that take the weight of full bottles.</li>
</ul>
<p>These fridges hold about +2 to +7 °C, ideal for beer, cider, mixers, soft drinks and white or sparkling wine. See the dedicated <a href="/range/display-cabinets/back-bar-display-chillers/">back bar display chillers</a>, or compare all <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/">drinks fridges</a>.</p>

<h2>Installation tips</h2>
<p>Bar fridges need space to breathe: leave a gap around the condenser and avoid fully enclosing the cabinet unless it is designed for it. Keep the doors closed between services and restock gradually so the fridge recovers quickly.</p>
HTML,
		'faq'      => array(
			array( 'What is a bar fridge?', 'A bar fridge is a commercial fridge designed to keep drinks cold behind a bar. It is usually a half-height back bar chiller with glass doors, or an upright glass door fridge where there is more space.' ),
			array( 'Will a bar fridge fit under my counter?', 'Our half-height back bar chillers are around 900 mm high and 500–600 mm deep, so they fit under most bar counters. Check the height and depth under your counter and allow space for ventilation.' ),
			array( 'What temperature does a bar fridge run at?', 'Our bar fridges hold around +2 to +7 °C, which keeps beer, mixers, soft drinks and white or sparkling wine at serving temperature.' ),
			array( 'Is a bar fridge the same as a mini fridge?', 'A commercial bar fridge looks similar to a domestic mini fridge but is built for constant door openings, warm bars and heavy loads, with a stronger refrigeration system and sturdier shelving.' ),
		),
		'articles' => array( 7448, 7383, 7379 ),
	),

	'beer-fridge' => array(
		'name'     => 'Beer Fridges',
		'short'    => '<p>Commercial beer fridges and bottle coolers that keep beer, lager and cider ice-cold and on display – glass door uprights and back bar chillers for pubs, off-licences and shops.</p>',
		'long'     => <<<'HTML'
<h2>Keep every bottle at serving temperature</h2>
<p>A beer fridge, or bottle cooler, is a glass door commercial fridge that chills beer, lager and cider and shows the range to customers. In a shop it drives impulse sales of chilled packs; behind a bar it keeps bottles and cans at serving temperature right where staff need them.</p>

<h2>Choosing a beer fridge</h2>
<ul>
<li><strong>Volume.</strong> Single door uprights suit smaller ranges; double hinged or sliding door models around 1200 mm wide hold full multi-pack ranges.</li>
<li><strong>Location.</strong> Half-height <a href="/range/display-cabinets/back-bar-display-chillers/">back bar chillers</a> fit under the counter; upright models go against a wall or in the aisle.</li>
<li><strong>Doors.</strong> Self-closing, double or triple-glazed doors hold the temperature and cut condensation in busy, warm venues.</li>
<li><strong>Shelving.</strong> Heavy-duty adjustable shelves take the weight of glass bottles.</li>
</ul>
<p>Our beer fridges run at around +2 to +7 °C, the right range for lager, ale, cider and soft drinks. For wine and mixers, see <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/wine-fridge/">wine fridges</a> and <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/bar-fridge/">bar fridges</a>.</p>

<h2>Getting the best from a bottle cooler</h2>
<p>Restock with warm bottles at the back so the coldest are always at the front, avoid blocking the fan inside the cabinet, and keep the condenser clean – a dusty condenser is the most common reason a beer fridge stops chilling properly.</p>
HTML,
		'faq'      => array(
			array( 'What is a beer fridge?', 'A beer fridge is a commercial glass door fridge, sometimes called a bottle cooler, used to chill and display beer, lager and cider in shops, pubs and bars.' ),
			array( 'What temperature should a beer fridge be?', 'Most lagers are served at about 3–7 °C and ales a little warmer. Our beer fridges operate between about +2 and +7 °C.' ),
			array( 'How do I keep a beer fridge cold when the bar is busy?', 'Keep the doors closed between services, load warm stock gradually, leave space around the cabinet for ventilation and clean the condenser regularly. Self-closing doors help a lot in busy venues.' ),
			array( 'Can a beer fridge go outside or in a cellar?', 'Our beer fridges are designed for indoor use in normal room conditions. Many are suitable for high ambient temperatures, but they should not be exposed to rain, frost or direct sun.' ),
		),
		'articles' => array( 7408, 7355, 7425 ),
	),

	'wine-fridge' => array(
		'name'     => 'Wine Fridges',
		'short'    => '<p>Commercial wine fridges for chilling and displaying white, rosé and sparkling wine – glass door uprights and back bar chillers that keep bottles at serving temperature for restaurants, bars and off-licences.</p>',
		'long'     => <<<'HTML'
<h2>Chilled wine, ready to serve</h2>
<p>A commercial wine fridge keeps wine at serving temperature and shows the selection to customers. Our wine fridges are glass door chillers that hold about +2 to +7 °C – the right range for white, rosé, sparkling wine and Champagne, as well as beer and soft drinks in the same cabinet.</p>

<h2>Serving or storing?</h2>
<p>There is a difference between serving and long-term cellaring. These fridges are designed to chill and display wine that will be sold or served soon. Red wine is normally served warmer than these cabinets run, and fine wines laid down for years are better kept in dedicated wine storage at a steady cellar temperature.</p>

<h2>Choosing a wine fridge</h2>
<ul>
<li><strong>Back bar or upright.</strong> Half-height <a href="/range/display-cabinets/back-bar-display-chillers/">back bar chillers</a> keep bottles by the bar; upright single or double door models show a wider list in a restaurant or shop.</li>
<li><strong>Glass doors.</strong> Double or triple glazing reduces condensation on the glass and keeps labels visible.</li>
<li><strong>Shelving.</strong> Adjustable heavy-duty shelves take full bottles and magnums.</li>
<li><strong>Finish.</strong> Black steel suits bars and restaurants; white steel suits shops and cafés.</li>
</ul>
<p>Compare all <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/">drinks fridges</a>, or see <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/beer-fridge/">beer fridges</a> if beer is your main line.</p>
HTML,
		'faq'      => array(
			array( 'What is a commercial wine fridge?', 'A commercial wine fridge is a glass door refrigerator used by restaurants, bars and shops to chill and display wine at serving temperature. It is built for frequent door openings and busy service.' ),
			array( 'What temperature do your wine fridges run at?', 'They operate between about +2 and +7 °C, which is ideal for white, rosé and sparkling wine and for beer and soft drinks.' ),
			array( 'Can I keep red wine in these fridges?', 'You can chill light reds briefly, but most red wine is served warmer than these cabinets run. For long-term storage of fine wine, a dedicated cellar-temperature cabinet is the better choice.' ),
			array( 'Will the glass doors mist up?', 'Our wine and drinks fridges use double or triple-glazed doors, which keep condensation on the glass to a minimum in normal room conditions.' ),
		),
		'articles' => array( 7445, 7398, 7409 ),
	),

	'glass-front-fridge' => array(
		'name'     => 'Glass Front Fridges',
		'short'    => '<p>Glass front fridges for every kind of chilled display: glass door drinks chillers, patisserie cabinets, serve over counters and countertop displays that let customers see the products while keeping them cold.</p>',
		'long'     => <<<'HTML'
<h2>See it, want it, buy it</h2>
<p>A glass front fridge is any commercial fridge with a glass door or glass front that keeps products cold while showing them to customers. Because shoppers can see the products without opening the cabinet, glass fronts sell more and – on closed models – waste less cold air than open displays.</p>

<h2>Types of glass front fridge</h2>
<ul>
<li><a href="/product-category/commercial-display-fridge/upright-fridge/"><strong>Glass door fridges</strong></a> – upright single or double door chillers for drinks and packaged food.</li>
<li><a href="/product-category/commercial-display-fridge/patisserie-fridge/"><strong>Patisserie fridges</strong></a> – flat or curved glass cabinets for cakes and desserts.</li>
<li><a href="/product-category/commercial-display-fridge/serve-over/"><strong>Serve over counters</strong></a> – glass-fronted deli and butchery displays served by staff.</li>
<li><a href="/product-category/commercial-display-fridge/counter-fridge/"><strong>Counter</strong></a> and <a href="/product-category/commercial-display-fridge/counter-fridge/top-counter/"><strong>countertop fridges</strong></a> – double-glazed displays for sandwiches, salads and cakes.</li>
<li><a href="/range/display-cabinets/upright-cake-displays/"><strong>Upright cake displays</strong></a> – glass on four sides for maximum visibility.</li>
</ul>

<h2>What to look for in the glass</h2>
<p>Double-glazed or triple-glazed glass keeps the cold in and reduces misting, so products stay visible even in busy, warm shops. Flat glass gives a modern look and fits counter runs; curved glass offers wide viewing angles. LED lighting shows the products in their true colours without adding heat.</p>
<p>Compare the full range in the table below – it lists the size, operating temperature and price of every glass front model we sell.</p>
HTML,
		'faq'      => array(
			array( 'What is a glass front fridge?', 'A glass front fridge is a commercial refrigerator with a glass door or glass front panel, so customers can see the chilled products inside without opening it. It covers glass door chillers, patisserie cabinets, serve overs and countertop displays.' ),
			array( 'Are glass front fridges energy efficient?', 'Closed glass front fridges are more efficient than open displays because the glass keeps the cold air inside. Double or triple glazing improves this further and reduces condensation.' ),
			array( 'Why does the glass on my fridge mist up?', 'Misting happens when warm, humid air meets cold glass. Double-glazed glass, good ventilation around the cabinet and keeping it away from steam and direct sunlight all reduce it.' ),
			array( 'Which glass front fridge is best for drinks?', 'For bottles and cans, choose a <a href="/product-category/commercial-display-fridge/upright-fridge/">glass door fridge</a>. Patisserie, serve over and countertop models are designed for food rather than drinks.' ),
		),
		'articles' => array( 7426, 7432, 7425 ),
	),

	'upright-fridge' => array(
		'name'     => 'Glass Door Fridges',
		'short'    => '<p>Upright glass door display fridges for drinks and packaged food – single door, double hinged and double sliding door chillers in white or black steel that keep stock cold, visible and economical to run.</p>',
		'long'     => <<<'HTML'
<h2>The most efficient way to display cold drinks</h2>
<p>An upright glass door fridge is a tall commercial chiller with one or two glass doors. Customers can see every shelf through the glass, while the closed doors keep the cold air in – so these fridges use far less energy than open-fronted displays. They are the standard choice for soft drinks, beer, water and packaged dairy in shops, cafés, takeaways and bars.</p>

<h2>Single, double or sliding door?</h2>
<ul>
<li><strong>Single door</strong> models are about 600 mm wide – perfect for cafés and small shops.</li>
<li><strong>Double hinged door</strong> models are about 1200 mm wide for larger drinks ranges.</li>
<li><strong>Double sliding door</strong> models need no door swing space, ideal for narrow aisles and behind counters.</li>
<li><strong>Half-height</strong> <a href="/range/display-cabinets/back-bar-display-chillers/">back bar chillers</a> fit under a bar counter.</li>
</ul>

<h2>Features</h2>
<ul>
<li>Operating range of about +2 to +7 °C on the chilled models.</li>
<li>Self-closing double or triple-glazed doors on the high-spec models.</li>
<li>Vertical LED lighting and heavy-duty adjustable shelving.</li>
<li>Aluminium interior and stainless steel drip tray for easy cleaning; many models are rated for high ambient temperatures.</li>
</ul>
<p>Looking for the standard and premium ranges side by side? See <a href="/range/display-cabinets/upright-display-chiller/">standard upright display fridges</a> and <a href="/range/display-cabinets/upright-display-chillers/">premium upright display fridges</a>, or browse <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/">drinks fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a glass door fridge?', 'A glass door fridge is an upright commercial chiller with transparent doors, used to keep drinks and packaged food cold while customers can see the products. It is also called an upright display fridge or bottle cooler.' ),
			array( 'How wide are glass door fridges?', 'Single door models are about 600 mm wide and double door models about 880–1200 mm wide; most are around 1960 mm high and 660 mm deep. Half-height back bar models are about 900 mm high.' ),
			array( 'What temperature do they hold?', 'Our glass door chillers operate at about +2 to +7 °C, suitable for drinks, dairy and packaged chilled food.' ),
			array( 'Do glass door fridges have locks or castors?', 'Many models have castors fitted for easy cleaning and repositioning. Check each product’s specification for castors, door type and other features.' ),
		),
		'articles' => array( 7425, 7445, 7361 ),
	),

	'serve-over' => array(
		'name'     => 'Serve Over Counter Fridges',
		'short'    => '<p>Serve over counter fridges for delis, butchers, bakeries and cafés – glass-fronted displays served by staff, with chilled storage underneath, from 1000 mm to 2000 mm wide.</p>',
		'long'     => <<<'HTML'
<h2>The classic deli counter</h2>
<p>A serve over counter fridge is a staff-served chilled display: customers view the products through the front glass and staff serve from the back. It is the traditional choice for cooked meats, cheese, olives, salads, fresh fish and cakes, where products are weighed, sliced or plated to order.</p>

<h2>Our serve over range</h2>
<p>The <a href="/range/display-cabinets/serve-over-display/">standard serve over counters</a> are slim silver steel cabinets with a full flat front glass, top-mounted lighting and a digital temperature controller. They operate between 0 and +12 °C, include chilled under-counter storage and two ambient shelves, and come in 1000, 1200, 1500 and 2000 mm widths so you can build a counter that fits your shop.</p>

<h2>Why choose a serve over?</h2>
<ul>
<li><strong>Presentation.</strong> A low front and long, flat display deck show the full range at once.</li>
<li><strong>Control.</strong> Staff handle the products, which suits loose and unpackaged food.</li>
<li><strong>Storage.</strong> Chilled storage under the counter keeps back-up stock close to hand.</li>
<li><strong>Maintenance.</strong> Slide-out condensing systems and self-evaporating drainage make cleaning straightforward.</li>
</ul>
<p>Prefer self-service? Compare <a href="/product-category/commercial-display-fridge/multideck-fridge/">multidecks</a> and <a href="/product-category/commercial-display-fridge/counter-fridge/">counter display fridges</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a serve over counter fridge?', 'A serve over counter is a refrigerated display where customers see the products through the front glass and staff serve them from behind. It is widely used in delis, butchers, fishmongers, bakeries and cafés.' ),
			array( 'What temperature does a serve over run at?', 'Our standard serve over counters operate between 0 and +12 °C, so you can set them for cooked meats and dairy or for less sensitive products.' ),
			array( 'What sizes are available?', 'Widths of 1000, 1200, 1500 and 2000 mm, about 790 mm deep and 1480 mm high. Several units can be placed side by side to build a longer counter.' ),
			array( 'Is a serve over suitable for raw meat and fish?', 'Serve overs are widely used for meat and fish, but set the controller to the lower end of the range and keep raw and ready-to-eat products separated, in line with your food safety plan.' ),
		),
		'articles' => array( 7373, 7403, 7418 ),
	),

	'counter-fridge' => array(
		'name'     => 'Counter Display Fridges',
		'short'    => '<p>Counter display fridges with double-glazed glass and chilled under-counter storage – silver steel counters for sandwiches, cakes and deli items in cafés, bakeries and delis.</p>',
		'long'     => <<<'HTML'
<h2>Display and storage in one counter</h2>
<p>A counter display fridge combines a glass-fronted chilled display on top with refrigerated storage underneath. It forms the serving counter itself, so customers see the products at eye level while staff keep back-up stock within reach.</p>

<h2>Our counter range</h2>
<p>The <a href="/range/display-cabinets/standard-counter-display-chiller/">standard double glazed counters</a> are silver steel cabinets with full double-glazed glass, mounted LED lighting and a digital controller. They hold 0 to +5 °C – cold enough for sandwiches, salads, dairy and cream cakes – and come in 1000, 1200 and 1500 mm widths, which can be lined up to create a run.</p>

<h2>Counter or countertop?</h2>
<p>If you already have a counter and want a display on top of it, choose a <a href="/product-category/commercial-display-fridge/counter-fridge/top-counter/">countertop display fridge</a>. If you are fitting a new serving counter, a full counter display fridge gives you the display and the storage in one footprint.</p>

<h2>Built to last</h2>
<ul>
<li>Double-glazed glass to reduce condensation and energy use.</li>
<li>ECO low-energy compressor and long-life fan motor.</li>
<li>Glide-out condensing unit and auto-evaporating drainage for easy maintenance.</li>
<li>Heavy-duty feet for stable levelling.</li>
</ul>
HTML,
		'faq'      => array(
			array( 'What is a counter display fridge?', 'A counter display fridge is a refrigerated serving counter: a glass display on top for customers to see the products, and chilled storage below for staff.' ),
			array( 'What temperature do counter fridges hold?', 'Our standard double-glazed counters operate between 0 and +5 °C, suitable for sandwiches, dairy, deli items and cream cakes.' ),
			array( 'Can I join several counters together?', 'Yes. The 1000, 1200 and 1500 mm models share the same height and depth, so they can be placed side by side to form a longer counter.' ),
			array( 'What is the difference between a counter and a countertop fridge?', 'A counter fridge is the full counter, with storage underneath. A <a href="/product-category/commercial-display-fridge/counter-fridge/top-counter/">countertop fridge</a> sits on top of an existing worktop.' ),
		),
		'articles' => array( 7418, 7446, 7403 ),
	),

	'top-counter' => array(
		'name'     => 'Countertop Display Fridges',
		'short'    => '<p>Countertop display fridges that sit on an existing counter – double-glazed stainless steel chillers from 1000 to 1500 mm wide for sandwiches, cakes and salads.</p>',
		'long'     => <<<'HTML'
<h2>Chilled display on top of your counter</h2>
<p>A countertop display fridge is a compact, glass-sided chiller designed to stand on top of an existing worktop or counter. It adds chilled display space without taking up floor space, which makes it ideal for coffee shops, sandwich bars, hotels and buffet counters.</p>

<h2>Our countertop range</h2>
<p>Our <a href="/range/display-cabinets/double-glazed-counter-top-display/">premium double glazed countertop displays</a> are built from 304 stainless steel with fully double-glazed glass, mounted LED lighting and a digital controller. They run at 0 to +5 °C and come in 1000, 1200 and 1500 mm widths, all about 720 mm high, so they sit comfortably on a standard counter.</p>

<h2>Before you order</h2>
<ul>
<li>Check that your counter can take the weight and depth of the unit (about 750 mm).</li>
<li>Leave space around the cabinet so the condensing unit can ventilate.</li>
<li>Make sure there is a socket nearby – these units plug into a standard supply.</li>
</ul>
<p>Need a full serving counter instead? See <a href="/product-category/commercial-display-fridge/counter-fridge/">counter display fridges</a> with storage underneath.</p>
HTML,
		'faq'      => array(
			array( 'What is a countertop display fridge?', 'A countertop display fridge is a small refrigerated glass display that stands on an existing counter or worktop, adding chilled display space without using floor space.' ),
			array( 'What sizes are available?', 'Widths of 1000, 1200 and 1500 mm, about 750 mm deep and 720 mm high.' ),
			array( 'What can I display in a countertop fridge?', 'Sandwiches, wraps, cakes, salads, sushi and other chilled food. The units run at 0 to +5 °C.' ),
			array( 'Does a countertop fridge need a special installation?', 'No. It plugs into a standard socket. It needs a level, strong surface and space around it for ventilation.' ),
		),
		'articles' => array( 7446, 7347, 7388 ),
	),

	'storage-fridge' => array(
		'name'     => 'Storage Fridges',
		'short'    => '<p>Commercial storage fridges for kitchens, stockrooms and butchers – upright, under counter and gastronorm fridges in white or stainless steel, built for heavy daily use.</p>',
		'long'     => <<<'HTML'
<h2>Reliable back-of-house refrigeration</h2>
<p>A commercial storage fridge keeps ingredients and stock at a safe, steady temperature away from the customer. Solid doors, strong shelving and fan-assisted cooling make it far more robust and efficient than a domestic fridge when it is opened dozens of times a shift.</p>

<h2>Types of storage fridge</h2>
<ul>
<li><a href="/product-category/storage-fridge/upright-fridge-storage-fridge/"><strong>Upright storage fridges</strong></a> – single or double door cabinets for kitchens and stockrooms, in white or stainless steel.</li>
<li><a href="/product-category/storage-fridge/under-counter-fridge/"><strong>Under counter fridges</strong></a> – single door units and two or three door counters that put chilled storage under the worktop.</li>
<li><a href="/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/"><strong>Meat and gastronorm fridges</strong></a> – stainless steel cabinets that take gastronorm pans and hold down to −5 °C for meat and fish.</li>
</ul>

<h2>Choosing a storage fridge</h2>
<ul>
<li><strong>Capacity.</strong> Single door uprights are about 600 mm wide; large and double door models range up to about 1440 mm.</li>
<li><strong>Kitchen standards.</strong> Gastronorm-compatible stainless steel cabinets suit professional kitchens and are easy to keep clean.</li>
<li><strong>Environment.</strong> Many models are designed for high ambient temperatures – important next to cooking equipment.</li>
<li><strong>Temperature.</strong> General storage fridges hold about +2 to +7 °C; meat and fish chillers go down to −5 °C.</li>
</ul>
<p>For freezing, see <a href="/product-category/storage-freezer/">storage freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is a storage fridge?', 'A storage fridge is a commercial refrigerator with solid doors, used to keep ingredients and stock chilled in kitchens, stockrooms and back-of-house areas.' ),
			array( 'Upright or under counter?', 'Upright fridges give the most storage for their floor space. Under counter fridges and counters keep ingredients at the point of preparation and add a worktop on top.' ),
			array( 'What is a gastronorm fridge?', 'A gastronorm fridge is designed to take standard gastronorm (GN) containers and trays used in professional kitchens, usually in a stainless steel cabinet. See our <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/">meat and gastronorm fridges</a>.' ),
			array( 'What temperature should a storage fridge be?', 'Chilled food should be kept at 8 °C or below; most kitchens aim for 1–5 °C. Our storage fridges hold about +2 to +7 °C, and meat chillers −5 to +5 °C.' ),
			array( 'Can a storage fridge go next to an oven?', 'Choose a model rated for high ambient temperatures and leave space for ventilation. Keeping fridges away from direct heat reduces running costs and wear.' ),
		),
		'articles' => array( 7431, 7399, 7447 ),
	),

	'under-counter-fridge' => array(
		'name'     => 'Under Counter Fridges',
		'short'    => '<p>Under counter fridges and stainless steel counters that put chilled storage right under the worktop – single door units and two or three door gastronorm counters for busy kitchens and bars.</p>',
		'long'     => <<<'HTML'
<h2>Chilled storage at the point of use</h2>
<p>An under counter fridge is a commercial fridge about 850–900 mm high that fits beneath a worktop, or stands alone as a refrigerated counter with its own work surface. It keeps ingredients exactly where chefs and bar staff need them, saving steps and keeping the main fridge closed.</p>

<h2>Single door or counter?</h2>
<ul>
<li><strong>Single door under counter fridges</strong> are about 600 mm wide – ideal for bars, cafés and small kitchens.</li>
<li><strong>Two and three door counters</strong> are about 1480 and 1955 mm wide, built from 304 stainless steel inside and out and gastronorm-friendly, with a full worktop on top.</li>
</ul>
<p>Our single door models hold about 0/+2 to +7 °C; the premium stainless steel counters hold −5 to +5 °C, cold enough for meat, fish and dairy. See the <a href="/range/storage-cabinets/standard-stainless-steel-under-counter-chiller/">standard</a> and <a href="/range/storage-cabinets/stainless-steel-under-counter-chiller/">premium</a> under counter chillers and the <a href="/range/storage-cabinets/stainless-steel-counter-chillers/">stainless steel counter chillers</a>.</p>

<h2>Practical details</h2>
<ul>
<li>Aluminium or stainless steel interiors that are easy to clean.</li>
<li>Adjustable shelves or ladder racks for gastronorm pans.</li>
<li>Replaceable door gaskets for simple maintenance.</li>
<li>Models designed for high ambient kitchens.</li>
</ul>
<p>Need to freeze? See <a href="/product-category/storage-freezer/under-counter-freezer/">under counter freezers</a>.</p>
HTML,
		'faq'      => array(
			array( 'What is an under counter fridge?', 'An under counter fridge is a commercial fridge about 850–900 mm high that fits under a worktop or forms a refrigerated counter, keeping chilled ingredients at the point of preparation.' ),
			array( 'What sizes are available?', 'Single door models are about 600 mm wide and 600 mm deep. Two door counters are about 1480 mm wide and three door counters about 1955 mm wide, both about 700 mm deep.' ),
			array( 'Will it fit under my counter?', 'Measure the height under the worktop: our single door models are about 850–900 mm high. Leave a little space around the unit for ventilation, as recommended in the product manual.' ),
			array( 'What temperature do they run at?', 'Single door models hold about 0/+2 to +7 °C, and the premium stainless steel counters −5 to +5 °C.' ),
		),
		'articles' => array( 7399, 7369, 7393 ),
	),

	'upright-fridge-storage-fridge' => array(
		'name'     => 'Upright Storage Fridges',
		'short'    => '<p>Upright storage fridges for kitchens, stockrooms and back-of-house – single and double door chillers in white or stainless steel, including heavy-duty and gastronorm models.</p>',
		'long'     => <<<'HTML'
<h2>Maximum chilled storage, minimum floor space</h2>
<p>An upright storage fridge is a tall, solid-door commercial fridge – the workhorse of most kitchens and stockrooms. Its height makes the most of a small footprint, and its solid door keeps the temperature stable even when it is opened all day.</p>

<h2>Our upright storage fridges</h2>
<ul>
<li><a href="/range/storage-cabinets/upright-storage-chiller/">Standard white upright storage chillers</a> – about 600 mm wide, +2 to +7 °C, with adjustable heavy-duty shelves.</li>
<li><a href="/range/storage-cabinets/upright-storage-chillers/">Premium heavy-duty upright chillers</a> – single, large and double door models up to about 1200 mm wide.</li>
<li><a href="/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/">Stainless steel gastronorm and meat chillers</a> – −5 to +5 °C cabinets that take gastronorm pans.</li>
<li><a href="/range/storage-cabinets/stainless-steel-upright-bakery-cabinets/">Upright bakery fridges</a> – stainless steel cabinets for bakery trays.</li>
</ul>

<h2>What to compare</h2>
<p>Look at internal capacity and shelf count, the operating temperature for what you store, the interior material (aluminium or stainless steel), and whether the model suits a hot kitchen. Castors make cleaning behind the fridge easy; replaceable gaskets keep the seal efficient for years.</p>
HTML,
		'faq'      => array(
			array( 'What is an upright storage fridge?', 'An upright storage fridge is a tall commercial fridge with a solid door, used to store ingredients and stock in kitchens and stockrooms.' ),
			array( 'Single or double door?', 'Single door models (about 600–880 mm wide) suit most small and medium kitchens. Double door models (about 1200–1440 mm wide) roughly double the capacity for busy kitchens and stockrooms.' ),
			array( 'Which upright fridge is best for meat?', 'Choose a stainless steel <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/">meat or gastronorm chiller</a>: they hold −5 to +5 °C and take gastronorm pans.' ),
			array( 'Do upright storage fridges have castors?', 'Many of our models have castors fitted, which makes cleaning around them much easier. Others stand on adjustable feet – see each product’s specification.' ),
		),
		'articles' => array( 7431, 7414, 7367 ),
	),

	'gastronorm-fridge' => array(
		'name'     => 'Meat Fridges',
		'short'    => '<p>Stainless steel meat fridges and gastronorm chillers that hold −5 to +5 °C for meat, fish and poultry – single and double door cabinets for butchers, restaurants and professional kitchens.</p>',
		'long'     => <<<'HTML'
<h2>Colder storage for meat and fish</h2>
<p>A meat fridge is a commercial chiller designed to run colder than a standard fridge – down to −5 °C – so that fresh meat, poultry and fish stay at the low end of the safe chilled range. Our meat fridges are stainless steel gastronorm cabinets, built to professional kitchen standards and easy to keep hygienic.</p>

<h2>Our range</h2>
<p>The <a href="/range/storage-cabinets/upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet/">stainless steel gastronorm chillers and fish/meat cabinets</a> come as a large single door (about 740 mm wide) and a double door model (about 1440 mm wide), both around 2000 mm high. They have forced-air refrigeration for even temperatures, 75 mm polyurethane insulation, stainless steel ladder racks for gastronorm pans and fitted castors.</p>

<h2>Why a dedicated meat fridge?</h2>
<ul>
<li><strong>Lower temperature.</strong> −5 to +5 °C keeps meat and fish fresher for longer than a standard +2 to +7 °C cabinet.</li>
<li><strong>Hygiene.</strong> 18/10 stainless steel doors and removable racks are easy to clean and sanitise.</li>
<li><strong>Kitchen-ready.</strong> Gastronorm compatibility means pans move straight from fridge to prep station.</li>
</ul>
<p>Need to freeze meat as well? See <a href="/product-category/storage-freezer/upright-freezer-storage-freezer/gastronorm-freezer/">meat freezers</a>. Starting a butchery? Read our guide to butcher shop fridges below.</p>
HTML,
		'faq'      => array(
			array( 'What is a meat fridge?', 'A meat fridge is a commercial chiller that runs colder than a standard fridge – our models hold −5 to +5 °C – to keep fresh meat, poultry and fish at the low end of the safe chilled range.' ),
			array( 'What does gastronorm mean?', 'Gastronorm (GN) is the standard size system for professional kitchen pans and trays. A gastronorm fridge has racks sized for those pans, so food moves directly between storage, prep and service.' ),
			array( 'What sizes are available?', 'A large single door cabinet about 740 × 900 × 2000 mm and a double door cabinet about 1440 × 900 × 2000 mm (W × D × H).' ),
			array( 'Can I store fish and meat together?', 'Yes, at the right temperature, but keep raw products covered and separated from ready-to-eat food, following your food safety management system.' ),
		),
		'articles' => array( 7440, 7376, 7343 ),
	),
);
