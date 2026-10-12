<?php
/**
 * Extended category copy — Commercial Fridge tree (used by migration 39).
 *
 * Per category: long (extra HTML sections, added to the category's long
 * description before its closing "Buying from ECO-Fridge" / "Trade prices"
 * section) and faq (extra [question, answer] pairs, added after the first
 * ones). Figures come from the product specification tables.
 *
 * @package EcoFridgeMigration
 */

return array(

	'commercial-fridge' => array(
		'long' => <<<'HTML'
<h2>Commercial fridges by type of business</h2>
<ul>
<li><strong>Convenience stores, forecourts and supermarkets</strong> – runs of <a href="/product-category/commercial-display-fridge/multideck-fridge/">multidecks</a> for dairy, meat and food-to-go, with <a href="/product-category/commercial-display-fridge/upright-fridge/">glass door fridges</a> for drinks where running costs matter most.</li>
<li><strong>Cafés and coffee shops</strong> – <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go fridges</a> by the till, <a href="/product-category/commercial-display-fridge/patisserie-fridge/">patisserie fridges</a> for cakes and an <a href="/product-category/storage-fridge/under-counter-fridge/">under counter fridge</a> for milk behind the bar.</li>
<li><strong>Restaurants, hotels and catering kitchens</strong> – stainless steel <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/">upright storage fridges</a> and refrigerated prep counters, sized around deliveries and menu.</li>
<li><strong>Butchers, delis and fishmongers</strong> – <a href="/product-category/commercial-display-fridge/serve-over/">serve over counters</a> at the front and <a href="/product-category/storage-fridge/upright-fridge-storage-fridge/gastronorm-fridge/">meat fridges</a> holding −5 to +5 °C at the back.</li>
<li><strong>Pubs and bars</strong> – <a href="/product-category/commercial-display-fridge/commercial-drinks-fridge/bar-fridge/">back bar fridges</a> under the counter and upright bottle coolers for packaged drinks.</li>
<li><strong>Bakeries</strong> – cake displays at the counter and <a href="/range/storage-cabinets/stainless-steel-upright-bakery-cabinets/">bakery cabinets</a> that take 400 × 600 mm trays.</li>
</ul>

<h2>Temperature control and food safety</h2>
<p>In England, Wales and Northern Ireland chilled food must normally be kept at 8 °C or below, and Food Standards Scotland advises 5 °C or below. Most food businesses set their fridges between 1 and 5 °C so they keep a safety margin through busy periods. Every fridge we sell has a digital controller and display, which makes the daily checks in a <em>Safer Food, Better Business</em> pack or a HACCP plan quick to record. Check the display at opening and closing, and confirm it from time to time with a probe thermometer in the product itself.</p>
<p>Keep raw meat and fish below ready-to-eat food, or in a separate cabinet, and do not load hot food straight into a fridge – cool it first so the cabinet can hold its temperature.</p>

<h2>Running costs and efficiency</h2>
<p>For most businesses the fridge’s running cost is driven by how it is used as much as by the model. A few habits make a measurable difference:</p>
<ul>
<li><strong>Doors and blinds.</strong> Glass door and solid door cabinets keep the cold in far better than open fronts; on multideck and grab and go fridges, use the night blind or shutter whenever the shop is closed.</li>
<li><strong>Location.</strong> Keep cabinets away from ovens, radiators, direct sun and draughty doorways, and leave the ventilation space given in the manual.</li>
<li><strong>Maintenance.</strong> A dusty condenser and a split door gasket are the two most common causes of a fridge working harder than it should. Clean the condenser regularly and replace worn gaskets – they are replaceable on our models.</li>
<li><strong>Loading.</strong> Do not block the air outlets at the back of the cabinet or load above the load line on open displays.</li>
</ul>

<h2>Planning delivery and installation</h2>
<p>Commercial fridges are heavy and often large, so check the route before you order: door widths and heights, steps, corridors and lifts. As a guide, a 1200 mm slimline multideck weighs about 247 kg and an 1800 mm model about 330 kg, while a double door stainless steel meat fridge weighs about 195 kg. Most plug-in cabinets run from a standard socket – each product page lists the specification. After delivery, let the cabinet stand upright for the time given in its manual before switching it on, and allow it to reach temperature before loading.</p>

<h2>Equipping several sites</h2>
<p>Standard and premium ranges share consistent sizes and finishes, so a chain of shops or cafés can use the same models across every site, which simplifies staff training, spares and store layouts. If you are fitting out several sites or a whole store, call us to discuss quantities and delivery scheduling.</p>
HTML,
		'faq'  => array(
			array( 'How much does a commercial fridge cost?', 'Our commercial fridges start at about £650 + VAT for a half-height glass door chiller and £661 + VAT for an under counter fridge. Multidecks run from about £1,295 to £3,850 + VAT depending on width and finish. The comparison table above shows the starting price of each range.' ),
			array( 'Can my business reclaim the VAT?', 'Our prices are shown excluding VAT and VAT is added at checkout. VAT-registered businesses can normally reclaim it as input tax – check with your accountant.' ),
			array( 'How do I keep a commercial fridge running efficiently?', 'Keep it away from heat sources and direct sun, leave ventilation space around it, keep doors closed or blinds down out of hours, clean the condenser regularly and replace worn door gaskets.' ),
			array( 'Do you supply businesses with several sites?', 'Yes. Our standard and premium ranges use consistent sizes and finishes, so the same models can be used across every site. Call 01280 811411 to discuss larger orders.' ),
		),
	),

	'commercial-display-fridge' => array(
		'long' => <<<'HTML'
<h2>Matching the display fridge to what you sell</h2>
<ul>
<li><strong>Dairy, meat, ready meals and food-to-go</strong> – a <a href="/product-category/commercial-display-fridge/multideck-fridge/">multideck</a> holding −1 to +7 °C gives the most facings per metre of floor space.</li>
<li><strong>Sandwiches, wraps and salads by the till</strong> – a <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go fridge</a> around 1.45–1.5 m high keeps products at the customer’s eye and hand level.</li>
<li><strong>Cakes, desserts and patisserie</strong> – a closed <a href="/product-category/commercial-display-fridge/patisserie-fridge/">patisserie fridge</a> protects delicate products from the shop air.</li>
<li><strong>Loose deli products, cooked meats and cheese</strong> – a <a href="/product-category/commercial-display-fridge/serve-over/">serve over counter</a> for staff service.</li>
<li><strong>Bottled and canned drinks</strong> – an upright <a href="/product-category/commercial-display-fridge/upright-fridge/">glass door fridge</a>, the most economical way to keep drinks cold.</li>
</ul>

<h2>Merchandising for higher sales</h2>
<p>A display fridge earns its keep through what it sells, so plan the layout as carefully as the purchase. Put best-sellers and high-margin lines at eye level, keep each product in a clear vertical block, label prices on the shelf edge and rotate stock so the oldest date is always at the front. Bright, even LED lighting and clean glass make a noticeable difference to how fresh the products look.</p>
<p>Position matters too: impulse lines sell best within sight of the till, while destination products such as milk can sit further into the store and draw customers past other displays.</p>

<h2>Display fridge price guide</h2>
<p>Prices below are trade prices excluding VAT, at the time of writing:</p>
<ul>
<li>Glass door and back bar drinks fridges – from about £650.</li>
<li>Slimline patisserie fridges – from about £698; premium stainless steel models up to about £3,800.</li>
<li>Serve over counters – about £1,150 to £1,885, by width.</li>
<li>Grab and go fridges – from about £1,249.</li>
<li>Multideck fridges – from about £1,295 for a 600 mm model to £3,850 for an 1800 mm shutter multideck.</li>
</ul>
<p>The comparison table on this page is updated with live prices and shows the temperature range and widths of each type.</p>
HTML,
		'faq'  => array(
			array( 'How much does a display fridge cost?', 'Glass door drinks fridges start at about £650 + VAT, grab and go fridges at about £1,249 + VAT and multidecks at about £1,295 + VAT. Premium stainless steel models cost more; the comparison table shows the starting price of each range.' ),
			array( 'Which display fridge has the lowest running costs?', 'Closed cabinets – glass door fridges, patisserie fridges and counters – keep the cold in better than open-fronted multidecks and grab and go fridges. On open fridges, using the night blind or shutter out of hours makes a big difference.' ),
			array( 'Can I put a display fridge in a shop window?', 'Avoid direct sunlight on any refrigerated display: it warms the products and makes the refrigeration work much harder. Choose a shaded spot, ideally in a room below about 25 °C.' ),
		),
	),

	'multideck-fridge' => array(
		'long' => <<<'HTML'
<h2>Capacity by width</h2>
<p>Net display volume grows with the width of the cabinet. In the 600 mm deep slimline ranges, a 600 mm multideck offers about 150 litres, a 1000 mm model about 260–270 litres, 1200 mm about 316–326 litres, 1500 mm about 400–413 litres and 1800 mm about 484–500 litres. The deeper 750 mm shutter multidecks hold about 409, 549 and 828 litres in their 1000, 1200 and 1800 mm widths – a good choice when you need maximum stock on the shelf between deliveries.</p>

<h2>Planning a multideck run</h2>
<p>Most shops plan chilled space in linear metres. Start from your chilled ranges – dairy, cooked meats, ready meals, sandwiches, drinks – and give each enough frontage for its best-sellers. Combining widths lets you fill a wall exactly: for example, a 1500 mm and a 1200 mm cabinet side by side give 2.7 metres of chilled display. Each multideck is a self-contained cabinet with its own refrigeration, so units can be added or moved as the business grows.</p>

<h2>Delivery and handling</h2>
<p>Multidecks are heavy: from about 163 kg for a 600 mm cabinet to about 330–340 kg for an 1800 mm slimline model and 410 kg for the 1800 mm shutter multideck. The 600 mm depth of the slimline ranges helps with doorways and narrow aisles, but check the route in and the floor where the cabinet will stand. The premium mirrored and shutter models have castors fitted, which makes cleaning behind them easier once in place.</p>

<h2>Keeping products at temperature</h2>
<p>An open multideck relies on a curtain of cold air across the front. Keep products behind the load line, do not block the air grilles at the top and bottom of the display, and pull the night blind or shutter down every evening. Load chilled products straight from delivery and avoid placing the cabinet opposite entrance doors, heaters or hot food counters.</p>
HTML,
		'faq'  => array(
			array( 'How much does a multideck fridge cost?', 'From about £1,295 + VAT for a 600 mm black slimline model and £1,299 + VAT in silver, up to about £3,400 + VAT for an 1800 mm premium mirrored stainless steel model and £3,850 + VAT for the 1800 mm shutter multideck.' ),
			array( 'How much stock does a multideck hold?', 'In the slimline ranges, net volume runs from about 150 litres (600 mm) to about 500 litres (1800 mm). The 750 mm deep shutter multidecks hold about 409 to 828 litres.' ),
			array( 'Can I put several multidecks side by side?', 'Yes. Each cabinet is self-contained, so different widths can be placed side by side to build a run of the length you need.' ),
			array( 'What is the load line on a multideck?', 'It is the marked limit at the front of each shelf. Products stacked beyond it break the curtain of cold air across the open front and will not stay at temperature.' ),
		),
	),

	'grab-and-go' => array(
		'long' => <<<'HTML'
<h2>Capacity and sizes</h2>
<p>The standard grab and go fridges hold about 165 litres (600 mm), 264 litres (900 mm) and 363 litres (1200 mm) of chilled display, on three glass shelves plus the base. The premium mirrored stainless steel range covers 600, 900, 1200 and 1500 mm widths at about 800 mm deep and 1500 mm high, and the 1000 mm stainless steel open flat glass cabinet offers about 286 litres in a lower, 1350 mm high body that suits a counter line.</p>

<h2>Merchandising for impulse sales</h2>
<p>Grab and go sales are decided in seconds, so make the display easy to read. Group products by meal – breakfast, lunch, snacks, drinks – put the best-sellers on the middle shelf, face every product forward with its label visible and keep shelves full during peak hours. Rotate stock by date so the oldest is at the front, and use shelf-edge price labels so customers do not need to ask.</p>

<h2>Choosing the right temperature range</h2>
<p>The standard models run at +5 to +7 °C, which suits pre-packed sandwiches, wraps, cakes and drinks. If you sell higher-risk chilled food such as dairy desserts, cooked meats or sushi, choose a premium model with a −1 to +7 °C range so you can run it colder and keep a margin below the legal 8 °C limit.</p>

<h2>Weight and positioning</h2>
<p>Standard models weigh about 150–240 kg and premium models about 160–309 kg, so plan the delivery route. The premium cabinets have castors fitted for easy cleaning, and all models use self-evaporating drainage, so no drain connection is needed – just a socket and ventilation space.</p>
HTML,
		'faq'  => array(
			array( 'How much does a grab and go fridge cost?', 'Standard models cost from about £1,249 + VAT (600 mm) to £1,575 + VAT (1200 mm). Premium mirrored stainless steel models range from about £1,980 to £3,500 + VAT, and the stainless steel open flat glass cabinet is about £3,550 + VAT.' ),
			array( 'How much does a grab and go fridge hold?', 'The standard models hold about 165, 264 and 363 litres in their 600, 900 and 1200 mm widths.' ),
			array( 'Which grab and go fridge is best for dairy and cooked meats?', 'A premium model running −1 to +7 °C, so you can set it colder for higher-risk chilled food. The standard models (+5 to +7 °C) are best for pre-packed sandwiches, cakes and drinks.' ),
		),
	),

	'patisserie-fridge' => array(
		'long' => <<<'HTML'
<h2>Patisserie fridge price guide</h2>
<ul>
<li><strong><a href="/range/display-cabinets/standard-patisserie-slimline-display-fridges/">Slimline double-glazed patisserie fridges</a></strong> – about £698 to £895 + VAT in 900, 1200 and 1500 mm widths, silver or black.</li>
<li><strong><a href="/range/display-cabinets/patisserie-display-fridges/">Standard patisserie fridges</a></strong> – about £1,245 to £1,570 + VAT in 600, 900 and 1200 mm widths, holding 165–363 litres.</li>
<li><strong><a href="/range/display-cabinets/premium-patisserie-display-fridges/">Premium mirrored stainless steel patisserie fridges</a></strong> – from about £1,160 for end-of-line 600 mm models up to £3,800 + VAT for a 1200 mm double-glazed curved model.</li>
</ul>

<h2>Staff service or self-service?</h2>
<p>Patisserie cabinets are usually loaded and served from the back – the slimline models, for example, have sliding rear doors – so cakes are portioned to order and stay protected from customers’ hands. If customers serve themselves packaged cakes and desserts, an open <a href="/product-category/commercial-display-fridge/grab-and-go/">grab and go fridge</a> is the better fit.</p>

<h2>Presentation tips</h2>
<ul>
<li>Show whole cakes on the top tier and slices lower down, where customers look first.</li>
<li>Keep the glass spotless inside and out – smears show up under LED lighting.</li>
<li>Use the temperature range: set the cabinet colder for fresh cream and cheesecake, a little warmer for ganache and glazed tarts that dull when too cold.</li>
<li>Restock from the back in small quantities through the day rather than overfilling in the morning.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'How much does a patisserie fridge cost?', 'From about £698 + VAT for a 900 mm slimline double-glazed model, about £1,245 + VAT for a 600 mm standard model and up to about £3,800 + VAT for a 1200 mm premium double-glazed stainless steel model.' ),
			array( 'Are patisserie fridges served from the back?', 'Usually, yes – the slimline models, for example, have sliding rear doors – so staff load and serve from behind the counter while customers view through the front glass.' ),
			array( 'How much do the standard patisserie fridges hold?', 'About 165 litres (600 mm), 264 litres (900 mm) and 363 litres (1200 mm), on three glass shelves plus the base.' ),
		),
	),

	'upright-cake-fridge' => array(
		'long' => <<<'HTML'
<h2>When an upright cake fridge makes sense</h2>
<p>A tall cake display suits venues where floor space is limited but presentation matters: hotel lobbies, restaurant entrances, cafés with a narrow counter and bakeries that want a centrepiece. Because it shows four sides, it can stand in the middle of a room or at the end of a counter and still look complete. Our current models are about 700 mm and 1200 mm wide and hold −1 to +7 °C, cold enough for cream cakes, cheesecakes and desserts.</p>

<h2>Practical points</h2>
<ul>
<li>The tall format makes products easy to see from a distance, but staff need easy access – the larger model loads through rear sliding doors.</li>
<li>Both models have castors, so the display can be moved for cleaning or for events.</li>
<li>Place the tower away from windows and heat sources; four glass sides let in more heat than a solid cabinet.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'How much does an upright cake display cost?', 'Our upright mirrored stainless steel cake display is about £1,590 + VAT and the large upright glass display about £2,800 + VAT. See <a href="/range/display-cabinets/upright-cake-displays/">upright cake display fridges</a>.' ),
			array( 'What can I display in an upright cake fridge?', 'Whole cakes, slices, cheesecakes, desserts, pastries and sandwiches – anything that benefits from being seen from every side and kept at −1 to +7 °C.' ),
		),
	),

	'commercial-drinks-fridge' => array(
		'long' => <<<'HTML'
<h2>Sizing a drinks fridge</h2>
<p>Choose capacity by the range you stock and how often you restock. As a guide, our single door uprights hold about 307–362 litres, the 880 mm double sliding door model about 569 litres and the 1200 mm double door models about 806 litres. Half-height back bar chillers are smaller, for mixers and bottled beer within reach of bar staff. If deliveries are weekly, size for the busiest day plus a buffer so warm stock can cool before it reaches the front.</p>

<h2>Merchandising drinks</h2>
<ul>
<li>Block brands and categories vertically – water, soft drinks, energy drinks, beer, wine – so customers find their choice in a glance.</li>
<li>Put the highest-margin and best-selling lines at eye level.</li>
<li>Load new stock at the back and pull cold stock forward, so customers always take a cold drink.</li>
<li>Keep the glass clear and the LED lighting on – it is the cabinet’s main sales tool.</li>
</ul>

<h2>Price guide</h2>
<p>Drinks fridges start from about £650 + VAT for a half-height chiller and about £795 + VAT for a standard single door upright, up to about £1,295 + VAT for a premium double sliding door model. Ex-showroom models are available at reduced prices while stocks last.</p>
HTML,
		'faq'  => array(
			array( 'How much does a commercial drinks fridge cost?', 'From about £650 + VAT for a half-height chiller and £795 + VAT for a standard single door upright, up to about £1,295 + VAT for a premium double sliding door model.' ),
			array( 'How big a drinks fridge do I need?', 'Single door uprights hold about 307–362 litres and double door models about 806 litres. Size for your busiest trading day plus room for warm stock to cool behind the front row.' ),
			array( 'Do brands supply drinks fridges?', 'Some drinks brands supply branded chillers under conditions on what you stock. Owning your own fridge leaves you free to choose your range and layout.' ),
		),
	),

	'bar-fridge' => array(
		'long' => <<<'HTML'
<h2>Planning the back bar</h2>
<p>Measure the opening under the counter – height, width and depth – and the space above and around the unit. Our half-height chillers are about 900–930 mm high and 500–600 mm deep: the single door model is about 600 mm wide and the double door model about 900 mm. A full-height glass door fridge (about 1960 mm high) holds far more and suits a back wall or a bar that also sells packaged drinks to take away.</p>
<p>Group fridges by what staff reach for most: mixers and bottled beer next to the main service point, wine by the glassware, and back-up stock in a taller unit away from the busiest area.</p>

<h2>Built for warm, busy bars</h2>
<p>The half-height back bar chillers are rated to work in room temperatures up to 32 °C – an important figure behind a crowded bar with glass washers and lighting. Keep the doors closed between orders, restock after service rather than during the rush, and keep the condenser clean so the fridge recovers quickly.</p>

<h2>Price guide</h2>
<p>Ex-showroom back bar chillers start from about £650 + VAT; full-height glass door fridges from about £957 to £1,295 + VAT.</p>
HTML,
		'faq'  => array(
			array( 'How much does a bar fridge cost?', 'Our ex-showroom half-height back bar chillers start at about £650 + VAT. Full-height glass door models cost from about £957 to £1,295 + VAT.' ),
			array( 'What room temperature can a back bar fridge cope with?', 'Our half-height back bar chillers are rated for room temperatures up to 32 °C, provided they have space to ventilate.' ),
			array( 'How heavy is a back bar fridge?', 'About 65 kg for the 600 mm single door model and about 125 kg for the 900 mm double door model.' ),
		),
	),

	'beer-fridge' => array(
		'long' => <<<'HTML'
<h2>Beer fridges for shops and off-licences</h2>
<p>In retail, chilled beer sells at a premium over warm stock, so a beer fridge pays for itself through sales. Place it near the entrance or the till for impulse purchases, keep multipacks on the lower shelves and single cans and bottles at eye level, and keep a strong range of best-sellers so the fridge never looks half empty at peak times.</p>

<h2>Capacity guide</h2>
<ul>
<li>Single door uprights (about 600 mm wide) hold about 307–362 litres – enough for a focused range in a small shop or café.</li>
<li>The 880 mm double sliding door model holds about 569 litres.</li>
<li>Double door models about 1200 mm wide hold about 806 litres for a full beer and cider range.</li>
<li>Half-height back bar chillers suit bars where staff serve bottles over the counter.</li>
</ul>

<h2>Keeping beer at its best</h2>
<p>Keep beer out of direct sunlight – light spoils beer in clear and green bottles quickly – and avoid repeated warming and chilling where possible. A steady +2 to +7 °C and good rotation, oldest stock first, keep quality consistent.</p>
HTML,
		'faq'  => array(
			array( 'How much does a beer fridge cost?', 'From about £650 + VAT for a half-height back bar chiller and about £795 + VAT for a single door upright, up to about £1,295 + VAT for a premium double sliding door model.' ),
			array( 'How much beer does a beer fridge hold?', 'Single door uprights hold about 307–362 litres, the 880 mm sliding door model about 569 litres and the 1200 mm double door models about 806 litres.' ),
			array( 'Where should a beer fridge go in a shop?', 'Near the entrance or the till for impulse purchases, away from windows and heaters, with space around it for ventilation.' ),
		),
	),

	'wine-fridge' => array(
		'long' => <<<'HTML'
<h2>Serving temperatures</h2>
<p>Sparkling wine and Champagne are usually served well chilled, and crisp white and rosé wines cold to cool. Our wine fridges hold +2 to +7 °C, which keeps sparkling and white wine ready to serve; for fuller whites some restaurants take the bottle out a few minutes before pouring. Red wine is normally served warmer and is best kept out of the fridge, except light reds that benefit from a short chill.</p>

<h2>Wine fridges for restaurants, bars and shops</h2>
<ul>
<li><strong>Restaurants</strong> – a glass door fridge near the bar or the dining room keeps the by-the-glass list at serving temperature and shows guests the selection.</li>
<li><strong>Bars</strong> – a half-height <a href="/range/display-cabinets/back-bar-display-chillers/">back bar chiller</a> keeps the most-poured wines and sparkling within reach.</li>
<li><strong>Off-licences and farm shops</strong> – an upright glass door fridge sells chilled whites, rosé and fizz ready to drink.</li>
</ul>

<h2>Looking after wine in a chiller</h2>
<p>Store bottles away from the fan outlet, avoid frequent temperature swings and keep the cabinet out of direct sunlight. Lay bottles with natural corks down only if they will stay in the fridge for a long time; for fast-moving stock upright storage is fine.</p>
HTML,
		'faq'  => array(
			array( 'How much does a commercial wine fridge cost?', 'From about £650 + VAT for a half-height chiller and about £795 + VAT for a single door upright, up to about £1,295 + VAT for a premium double sliding door model.' ),
			array( 'Is a commercial wine fridge suitable for a restaurant?', 'Yes. A glass door chiller keeps white, rosé and sparkling wine at serving temperature through service and lets guests see the selection.' ),
		),
	),

	'glass-front-fridge' => array(
		'long' => <<<'HTML'
<h2>Which glass front for which product?</h2>
<ul>
<li><strong>Drinks and packaged food</strong> – upright glass door fridges, single or double door, +2 to +7 °C.</li>
<li><strong>Cakes and desserts</strong> – patisserie fridges with flat or curved glass, from +3 to +7 °C slimline models to −1 to +8 °C premium double-glazed models.</li>
<li><strong>Loose deli products</strong> – serve over counters at 0 to +12 °C, served by staff.</li>
<li><strong>Sandwiches and salads on a counter</strong> – double-glazed countertop and counter display fridges at 0 to +5 °C.</li>
</ul>

<h2>Price guide</h2>
<p>Glass front fridges start from about £650 + VAT for a half-height glass door chiller and about £698 + VAT for a slimline patisserie fridge. Serve over counters cost about £1,150 to £1,885 + VAT, countertop displays about £2,350 to £2,995 + VAT and premium double-glazed patisserie fridges up to about £3,800 + VAT.</p>

<h2>Keeping the glass clear</h2>
<p>Condensation is the main enemy of a glass front display. Double-glazed glass, as used on our patisserie, counter and countertop ranges, keeps the outer surface warmer and clearer. Keep the cabinet away from steam from coffee machines and dishwashers, maintain good ventilation, close doors promptly and clean the glass daily with a non-abrasive cleaner.</p>
HTML,
		'faq'  => array(
			array( 'How much does a glass front fridge cost?', 'From about £650 + VAT for a half-height glass door chiller and £698 + VAT for a slimline patisserie fridge, up to about £3,800 + VAT for a premium double-glazed patisserie fridge.' ),
			array( 'How should I clean the glass?', 'Use a soft cloth and a non-abrasive glass cleaner daily, inside and out. Avoid scourers, which scratch the glass and coatings.' ),
		),
	),

	'upright-fridge' => array(
		'long' => <<<'HTML'
<h2>Capacity guide</h2>
<ul>
<li>Single door uprights, about 600 mm wide: about 307–362 litres on four or five shelves.</li>
<li>Double sliding door, about 880 mm wide: about 569 litres.</li>
<li>Double hinged or sliding door, about 1200 mm wide: about 806 litres.</li>
<li>Half-height back bar chillers, about 600–900 mm wide: compact capacity under a counter.</li>
</ul>

<h2>Standard or premium?</h2>
<p>The <a href="/range/display-cabinets/upright-display-chiller/">standard white steel chillers</a> (from about £795 + VAT) are a great-value choice for cafés, takeaways and shops with a steady flow of customers. The <a href="/range/display-cabinets/upright-display-chillers/">premium models</a> (from about £995 + VAT) add self-closing glazed doors and vertical LED side lights, which pay back in busy stores where doors are opened all day – the cabinet recovers faster and the products look brighter.</p>

<h2>Delivery and positioning</h2>
<p>Single door uprights weigh about 103 kg and double door models about 190–195 kg, around 1960 mm high, so check door heights and the route in. Leave space above and behind the cabinet for air, and allow room in front for the doors to open fully – or choose sliding doors where space is tight.</p>
HTML,
		'faq'  => array(
			array( 'How much does a glass door fridge cost?', 'From about £650 + VAT for a half-height chiller and £795 + VAT for a standard single door upright, to about £1,295 + VAT for a premium double sliding door model.' ),
			array( 'How much does a double door glass fridge hold?', 'Our 1200 mm double door models hold about 806 litres; the 880 mm double sliding door model about 569 litres.' ),
			array( 'Can I use a glass door fridge for milk and dairy?', 'Yes. At +2 to +7 °C a glass door chiller suits milk, yoghurt and packaged dairy; set it towards the colder end of the range for dairy.' ),
		),
	),

	'serve-over' => array(
		'long' => <<<'HTML'
<h2>Planning a serve over counter</h2>
<p>Start from the products you serve and the length of counter you have. The four widths – 1000, 1200, 1500 and 2000 mm – can be combined: for example a 2000 mm and a 1500 mm cabinet together give a 3.5 metre deli counter. Allow working space behind the counter for staff, scales and a slicer, and a clear queue area in front for customers.</p>
<p>The rear work surface and two ambient shelves keep wrapping, labels and serving tools to hand, while the chilled under-counter storage holds back-up stock so staff do not leave the counter during service.</p>

<h2>Food safety at the counter</h2>
<ul>
<li>Set the controller for the most sensitive product on display – cooked meats and dairy at the colder end of the 0 to +12 °C range.</li>
<li>Keep raw and ready-to-eat products in separate cabinets, or clearly separated with their own utensils.</li>
<li>Check and record the display temperature at opening and closing, and with a probe in the product during the day.</li>
<li>Remove products at the end of the day and clean the display deck before restocking.</li>
</ul>

<h2>Weight and delivery</h2>
<p>The slim serve overs weigh from about 158 kg (1000 mm) to about 256 kg (2000 mm). At about 790 mm deep and 1480 mm high they fit through most commercial doorways, but check the route for the longer models.</p>
HTML,
		'faq'  => array(
			array( 'How much does a serve over counter cost?', 'About £1,150 + VAT (1000 mm), £1,285 + VAT (1200 mm), £1,495 + VAT (1500 mm) and £1,885 + VAT (2000 mm).' ),
			array( 'Can I build a longer counter from several serve overs?', 'Yes. The four widths share the same depth and height, so they can be placed side by side to build a counter of the length you need.' ),
			array( 'How heavy is a serve over counter?', 'From about 158 kg for the 1000 mm model to about 256 kg for the 2000 mm model.' ),
		),
	),

	'counter-fridge' => array(
		'long' => <<<'HTML'
<h2>Choosing between counters</h2>
<ul>
<li><a href="/range/display-cabinets/standard-counter-display-chiller/"><strong>Standard double glazed counters</strong></a> – silver steel, 1000, 1200 and 1500 mm wide, about 790 × 1540 mm, from about £1,515 + VAT.</li>
<li><a href="/range/display-cabinets/double-glazed-counter-display-chiller/"><strong>Premium UV bonded counter</strong></a> – stainless steel inside and out with frameless bonded glass and castors, about £3,700 + VAT.</li>
<li><a href="/product-category/commercial-display-fridge/counter-fridge/top-counter/"><strong>Countertop displays</strong></a> – 720 mm high units that stand on an existing worktop, from about £2,350 + VAT.</li>
</ul>

<h2>Planning a serving counter</h2>
<p>A counter display fridge often becomes the focal point of a café or sandwich bar, so plan it with the customer journey in mind: products on display where the queue forms, the till at the end of the run and the coffee station within reach of staff. The standard counters weigh about 230–300 kg; check the floor and the delivery route, and leave ventilation space as the manual advises.</p>

<h2>What sells best from a counter</h2>
<p>At 0 to +5 °C, counter displays suit sandwiches, salads, cream cakes, dairy desserts and deli items. Present products on trays or platters, label them clearly with prices and allergen information, and restock from the rear storage so the display always looks full.</p>
HTML,
		'faq'  => array(
			array( 'How much does a counter display fridge cost?', 'Standard double glazed counters cost from about £1,515 + VAT (1000 mm) to £1,845 + VAT (1500 mm). Countertop displays cost about £2,350 to £2,995 + VAT and the premium UV bonded counter about £3,700 + VAT.' ),
			array( 'How heavy are the counters?', 'The standard double glazed counters weigh about 230 kg (1000 mm), 250 kg (1200 mm) and 300 kg (1500 mm).' ),
		),
	),

	'top-counter' => array(
		'long' => <<<'HTML'
<h2>Check the counter first</h2>
<p>A countertop chiller is heavier than it looks: about 90 kg for the 1000 mm model, 100 kg for the 1200 mm and 120 kg for the 1500 mm, before any food is loaded. Make sure the worktop and the cabinet underneath can take that weight, that the surface is level and at least as deep as the unit (about 750 mm), and that there is space around it for the condensing unit to ventilate.</p>

<h2>Where countertop displays work best</h2>
<ul>
<li><strong>Coffee shops and sandwich bars</strong> – a chilled display of sandwiches, wraps and cakes on the serving counter.</li>
<li><strong>Hotels and conference venues</strong> – buffet and breakfast displays for yoghurts, fruit, cold meats and cheese.</li>
<li><strong>Delis and farm shops</strong> – a short run of chilled products beside the till.</li>
</ul>

<h2>Price guide</h2>
<p>About £2,350 + VAT (1000 mm), £2,595 + VAT (1200 mm) and £2,995 + VAT (1500 mm), all in 304 stainless steel with full double glazing and LED lighting.</p>
HTML,
		'faq'  => array(
			array( 'How much does a countertop display fridge cost?', 'About £2,350 + VAT (1000 mm), £2,595 + VAT (1200 mm) and £2,995 + VAT (1500 mm).' ),
			array( 'How heavy is a countertop display fridge?', 'About 90 kg (1000 mm), 100 kg (1200 mm) and 120 kg (1500 mm). Check that your counter can take the weight.' ),
		),
	),

	'storage-fridge' => array(
		'long' => <<<'HTML'
<h2>Planning refrigeration for a professional kitchen</h2>
<p>Good kitchen refrigeration follows the flow of food from delivery to plate. Bulk stock goes into upright storage near the delivery door, ingredients for each section go into under counter fridges or refrigerated counters at the point of preparation, and raw meat and fish are kept in their own cabinet or on the lowest shelves. Planning around that flow reduces door openings, saves staff steps and makes food safety easier to manage.</p>

<h2>Capacity guide</h2>
<ul>
<li>Under counter fridges, about 600 mm wide: about 125 litres – a section’s ingredients for a service.</li>
<li>Single door uprights, about 600 mm wide: about 307–362 litres.</li>
<li>Large door uprights, about 880 mm wide: about 569 litres.</li>
<li>Double door uprights, about 1200 mm wide: about 806 litres.</li>
<li>Stainless steel gastronorm chillers: single door about 740 mm and double door about 1440 mm wide, with racks for GN pans.</li>
</ul>

<h2>Food safety and record keeping</h2>
<p>Keep chilled food at 8 °C or below (most kitchens aim for 1–5 °C), record temperatures at least daily, label food with prepared and use-by dates, and store raw products below ready-to-eat food. Digital controllers on every model make checks quick, and replaceable gaskets help cabinets hold their temperature as they age.</p>

<h2>Price guide</h2>
<p>Storage fridges start from about £661 + VAT for an under counter fridge and £933 + VAT for a standard upright. Premium heavy-duty uprights cost about £1,175 to £1,881 + VAT, stainless steel meat fridges about £1,190 to £1,790 + VAT and two and three door refrigerated counters about £1,358 to £1,706 + VAT.</p>
HTML,
		'faq'  => array(
			array( 'How much does a commercial storage fridge cost?', 'From about £661 + VAT for an under counter fridge and £933 + VAT for a standard upright, up to about £1,881 + VAT for a heavy-duty double door upright.' ),
			array( 'How much storage do I need?', 'Size for your delivery cycle: the stock held between deliveries plus a buffer. As a guide, under counter fridges hold about 125 litres, single door uprights 307–362 litres and double door uprights about 806 litres.' ),
			array( 'Should raw meat have its own fridge?', 'Ideally yes, especially in busy kitchens. Where it shares a fridge, keep raw meat and fish covered on the lowest shelves, below ready-to-eat food.' ),
		),
	),

	'under-counter-fridge' => array(
		'long' => <<<'HTML'
<h2>Built for the hottest part of the kitchen</h2>
<p>Under counter fridges usually sit next to fryers, grills and ranges, so performance in heat matters. The premium two and three door stainless steel counters are designed to operate in room temperatures up to 43 °C and hold −5 to +5 °C – cold enough for meat, fish and dairy at the pass. The single door models suit bars, cafés and cooler kitchens.</p>

<h2>Capacity and sizes</h2>
<ul>
<li>Single door models: about 600 × 600 mm and 850–860 mm high, about 125 litres, from about £661 + VAT.</li>
<li>Two door counter: about 1480 × 700 × 880 mm, about 200 kg, around £1,358 + VAT.</li>
<li>Three door counter: about 1955 × 700 × 850 mm, about 252 kg, around £1,706 + VAT.</li>
</ul>

<h2>Organising an under counter fridge</h2>
<p>Store each section’s mise en place in labelled, covered containers – gastronorm pans in the stainless steel counters – with raw products at the bottom. Restock from the main storage fridge before service, keep the door closed during service rushes and leave the ventilation grille clear.</p>
HTML,
		'faq'  => array(
			array( 'How much does an under counter fridge cost?', 'From about £661 + VAT for a standard single door model and £825 + VAT for the premium model. Two and three door stainless steel counters cost about £1,358 and £1,706 + VAT.' ),
			array( 'Can an under counter fridge work next to a fryer?', 'The premium stainless steel counters are designed for room temperatures up to 43 °C. Any model still needs ventilation space and should not sit directly against a heat source.' ),
			array( 'How much does a single door under counter fridge hold?', 'About 125 litres.' ),
		),
	),

	'upright-fridge-storage-fridge' => array(
		'long' => <<<'HTML'
<h2>Capacity guide</h2>
<ul>
<li>Standard white upright, about 600 mm wide: four shelves, around £933 + VAT.</li>
<li>Heavy-duty single door, about 600 mm wide: about 362 litres, 0 to +5 °C.</li>
<li>Heavy-duty large door, about 880 mm wide: about 569 litres.</li>
<li>Heavy-duty double door, about 1200 mm wide: about 806 litres.</li>
<li>Stainless steel gastronorm chillers: about 740 mm (four shelves) and 1440 mm wide (eight shelves), −5 to +5 °C.</li>
</ul>

<h2>Where an upright storage fridge fits</h2>
<p>Uprights are the main chilled store in restaurants, cafés, takeaways, schools, care homes and staff canteens. Put them close to the delivery entrance and the main prep area, away from cooking equipment, with room in front for the door to open fully. In a stockroom, white steel is cost-effective; in a professional kitchen stainless steel is easier to keep hygienic.</p>

<h2>Weight and delivery</h2>
<p>The heavy-duty white uprights weigh about 117–162 kg and the stainless steel gastronorm chillers about 140–195 kg. At around 1960–2000 mm high, check door heights and allow for tilting the cabinet through doorways. Let the fridge stand upright after delivery as the manual advises before switching on.</p>
HTML,
		'faq'  => array(
			array( 'How much does an upright storage fridge cost?', 'From about £933 + VAT for the standard white upright, about £1,175 to £1,881 + VAT for the heavy-duty models and about £1,190 to £1,790 + VAT for the stainless steel gastronorm chillers.' ),
			array( 'How much does a double door storage fridge hold?', 'Our heavy-duty 1200 mm double door upright holds about 806 litres.' ),
			array( 'Is white or stainless steel better for a kitchen?', 'Stainless steel is tougher and easier to sanitise, so it suits professional kitchens. White steel is a cost-effective choice for stockrooms and lighter use.' ),
		),
	),

	'gastronorm-fridge' => array(
		'long' => <<<'HTML'
<h2>For butchers, fishmongers and professional kitchens</h2>
<p>Butchers use meat fridges to hold carcass cuts, prepared joints and mince between the cutting room and the counter. Fishmongers hold fresh fish close to 0 °C to protect quality, and restaurant kitchens keep portioned proteins in gastronorm pans ready for service. The −5 to +5 °C range lets you run these cabinets colder than a general-purpose fridge.</p>

<h2>Capacity</h2>
<p>The single door chiller (about 740 × 900 × 2000 mm, about 140 kg) has four shelves, and the double door model (about 1440 × 900 × 2000 mm, about 195 kg) eight shelves. The 900 mm depth takes full gastronorm pans and trays. Prices are about £1,190 + VAT for the single door models and £1,790 + VAT for the double door.</p>

<h2>Cleaning routine</h2>
<ul>
<li>Wipe spills straight away – meat juices are the main source of odour and contamination.</li>
<li>Remove the ladder racks regularly and wash them in the sink or dishwasher.</li>
<li>Clean the door gaskets with warm soapy water and check they seal along their full length.</li>
<li>Keep raw meat covered, separate from cooked products, and record temperatures daily.</li>
</ul>
HTML,
		'faq'  => array(
			array( 'How much does a meat fridge cost?', 'About £1,190 + VAT for the large single door gastronorm chiller or fish/meat cabinet and about £1,790 + VAT for the double door model.' ),
			array( 'Why are the cabinets 900 mm deep?', 'The extra depth takes full-size gastronorm pans and trays, so prepared meat and fish move straight from the fridge to the prep station.' ),
			array( 'How many shelves do the meat fridges have?', 'The single door model has four shelves and the double door model eight, on removable stainless steel ladder racks.' ),
		),
	),
);
