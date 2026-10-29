<?php
/**
 * 65 — SEO titles and meta descriptions: the open items of the site audit.
 *
 * A crawl of the 428 sitemap URLs (2026-10-27) found, among indexable pages:
 * 60 without a meta description (56 blog tags, 3 brochures, Discount
 * Products), 61 titles over 70 characters, 14 groups of duplicate titles and
 * 27 groups of duplicate descriptions (titles and descriptions copied between
 * products in the old Yoast data; an upright chiller's description on ovens).
 *
 *  1. Products (88): a unique Rank Math title (≤ 65 characters, "| ECO-Fridge"
 *     where it fits) and description (≤ 160) written from each product's own
 *     name, model, dimensions and temperature (hand-written for the ovens,
 *     grill, stand, bakery cabinets and a few others).
 *  2. Blog tags (56): "<Tag>: Articles & Guides | ECO-Fridge" and a
 *     description naming the topic (several tags also matched a product
 *     category's or the shop's title).
 *  3. Product categories: the old Yoast titles over 70 characters shortened;
 *     a description for Discount Products.
 *  4. Blog posts: titles over 70 characters shortened.
 *  5. Brochures: descriptions (three had none, two shared one).
 *  6. The dead external link in /how-to-start-a-takeaway-business/ is removed
 *     (its text stays).
 *
 * The previous values are kept once in `_ef_seo_before_65` (post and term
 * meta). Safe to run again.
 *
 * Run: wp eval-file migration/65-seo-meta.php
 *
 * @package EcoFridgeMigration
 */

$products = array(
	10 => array( 'Black Patisserie Display Fridge 600mm (End of Line) | ECO-Fridge', 'Black Patisserie Display Fridge 600mm (End of Line), model BP75/60: 600 × 750 × 1350 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	11 => array( 'Black Slimline Multideck Fridge (600mm) | ECO-Fridge', 'Black Slimline Multideck Fridge (600mm), model BD60/60: 600 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	21 => array( 'Mirrored Steel Closed Patisserie with Castors (600mm)', 'Mirrored Steel Closed Patisserie with Castors (600mm), model SP75/60CC: 600 × 800 × 1500 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	22 => array( 'Stainless Steel Double Glazed Patisserie Fridge (1000mm)', 'Stainless Steel Double Glazed Patisserie Fridge (1000mm), model SP75/100DG: 1000 × 750 × 1350 mm, -1 / +8 °C. From ECO-Fridge, UK supplier.' ),
	23 => array( 'Mirrored Steel Grab & Go Display with Feet SLD75/150CC', 'Mirrored Steel Grab & Go Display with Feet SLD75/150CC: 1500 × 800 × 1500 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	24 => array( 'Mirrored Steel Heated Pastry Display (1000mm) | ECO-Fridge', 'Mirrored Steel Heated Pastry Display (1000mm), model SHP75/100DG: 1000 × 750 × 1350 mm, +65 / +90 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	26 => array( 'Mirrored Steel Patisserie Display Fridge 600mm (End of Line)', 'Mirrored Steel Patisserie Display Fridge 600mm (End of Line), model SP75/60: 600 × 750 × 1350 mm, +3 / +12 °C. From ECO-Fridge, UK supplier.' ),
	27 => array( 'Mirrored Steel Shutter Multideck Fridge (1000mm) | ECO-Fridge', 'Mirrored Steel Shutter Multideck Fridge (1000mm), model SD75/100SH: 1000 × 750 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	32 => array( 'White Double Sliding Door Display Chiller (Ex-Showroom)', 'White Double Sliding Door Display Chiller (Ex-Showroom), model USGC88: 880 × 660 × 1960 mm, +2 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	49 => array( 'Used Stainless Steel Grab & Go Display Fridge (1200mm)', 'Used Stainless Steel Grab & Go Display Fridge (1200mm), model X-SLD75/120CC: 1200 × 800 × 1500 mm, -1 / +7 °C. Ex-exhibition, reduced price.' ),
	50 => array( 'White Double Door Upright Storage Chiller (Discount) | ECO-Fridge', 'White upright high-capacity double door storage chiller, offered as a discount product while stock lasts. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	51 => array( 'Used Stainless Steel R290 Multideck Fridge (1200mm) | ECO-Fridge', 'Used Stainless Steel R290 Multideck Fridge (1200mm), model X-SD75/120HC: 1200 × 750 × 1950 mm. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	54 => array( 'Silver Slimline Multideck Fridge ECO-MD60/180 (1800mm)', 'Silver Slimline Multideck Fridge ECO-MD60/180 (1800mm): 1800 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	55 => array( 'Silver Slimline Multideck Fridge ECO-MD60/100 (1000mm)', 'Silver Slimline Multideck Fridge ECO-MD60/100 (1000mm): 1000 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	56 => array( 'Silver Slimline Multideck Fridge ECO-MD60/150 (1500mm)', 'Silver Slimline Multideck Fridge ECO-MD60/150 (1500mm): 1500 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	57 => array( 'Silver Patisserie Fridge ECO-P75/90CC (900mm) | ECO-Fridge', 'Silver Patisserie Fridge ECO-P75/90CC (900mm): 900 × 750 × 1450 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	58 => array( 'Silver Grab and Go Fridge ECO-LD75/120CC (1200mm) | ECO-Fridge', 'Silver Grab and Go Fridge ECO-LD75/120CC (1200mm): 1200 × 730 × 1450 mm, +5 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	59 => array( 'Silver Grab and Go Fridge ECO-LD75/60CC (600mm) | ECO-Fridge', 'Silver Grab and Go Fridge ECO-LD75/60CC (600mm): 600 × 730 × 1450 mm, +5 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	60 => array( 'Silver Grab and Go Fridge ECO-LD75/90CC (900mm) | ECO-Fridge', 'Silver Grab and Go Fridge ECO-LD75/90CC (900mm): 900 × 730 × 1450 mm, +5 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	61 => array( 'Silver Patisserie Fridge ECO-P75/120CC (1200mm) | ECO-Fridge', 'Silver Patisserie Fridge ECO-P75/120CC (1200mm): 1200 × 750 × 1450 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	62 => array( 'Silver Patisserie Fridge ECO-P75/60CC (600mm) | ECO-Fridge', 'Silver Patisserie Fridge ECO-P75/60CC (600mm): 600 × 750 × 1450 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	65 => array( 'Silver Steel Double Glazed Patisserie Counter (1000mm)', 'Silver Steel Double Glazed Patisserie Counter (1000mm), model ECO-SGC100: 1000 × 790 × 1540 mm, +0 / +5 °C. From ECO-Fridge, UK supplier.' ),
	67 => array( 'Large Upright Gastronorm Chiller, Stainless Steel | ECO-Fridge', 'Large Upright Gastronorm Chiller, Stainless Steel, model ECO-GN700M: 740 × 900 × 2000 mm, -5 / +5 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	70 => array( 'Double Glazed Counter Top Chiller (1500mm) | ECO-Fridge', 'Double Glazed Counter Top Chiller (1500mm), model STC150: 1500 × 750 × 720 mm, +0 / +5 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	77 => array( 'Standard White Upright Storage Chiller | ECO-Fridge', 'Standard White Upright Storage Chiller, model ECO-USC60: 600 × 700 × 1860 mm, +2 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	81 => array( 'White Double Glass Door Display Chiller (1200mm) | ECO-Fridge', 'White Double Glass Door Display Chiller (1200mm), model ECO-UHGC120: 1200 × 660 × 1960 mm, +2 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	84 => array( 'Silver Steel Glass Door Upright Display Freezer | ECO-Fridge', 'Silver Steel Glass Door Upright Display Freezer, model ECO-UGF60: 600 × 660 × 1960 mm, -18 / -24 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	85 => array( 'Mirrored Steel Upright Cake Display Fridge | ECO-Fridge', 'Mirrored Steel Upright Cake Display Fridge, model GDT70C: 700 × 800 × 1900 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	86 => array( 'Premium Mirrored Slimline Multideck SD60/60CC (600mm)', 'Premium Mirrored Slimline Multideck SD60/60CC (600mm): 600 × 600 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	87 => array( 'Premium Mirrored Slimline Multideck SD60/100CC (1000mm)', 'Premium Mirrored Slimline Multideck SD60/100CC (1000mm): 1000 × 600 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	88 => array( 'Premium Mirrored Slimline Multideck SD60/120CC (1200mm)', 'Premium Mirrored Slimline Multideck SD60/120CC (1200mm): 1200 × 600 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	89 => array( 'Premium Mirrored Slimline Multideck SD60/150CC (1500mm)', 'Premium Mirrored Slimline Multideck SD60/150CC (1500mm): 1500 × 600 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	90 => array( 'Premium Mirrored Slimline Multideck SD60/180CC (1800mm)', 'Premium Mirrored Slimline Multideck SD60/180CC (1800mm): 1800 × 600 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	92 => array( 'Used Silver Steel Slimline Multideck (600mm) | ECO-Fridge', 'Used Silver Steel Slimline Multideck (600mm), model X-ECO-MD60/60: 600 × 600 × 1950 mm. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	93 => array( 'Mirrored Steel Grab & Go Display with Castors SLD75/60CC', 'Mirrored Steel Grab & Go Display with Castors SLD75/60CC: 600 × 800 × 1500 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	94 => array( 'Mirrored Steel Grab & Go Display with Castors SLD75/90CC', 'Mirrored Steel Grab & Go Display with Castors SLD75/90CC: 900 × 800 × 1500 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	95 => array( 'Mirrored Steel Grab & Go Display with Castors SLD75/120CC', 'Mirrored Steel Grab & Go Display with Castors SLD75/120CC: 1200 × 800 × 1500 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	96 => array( 'Black Slimline Multideck Fridge (1000mm) | ECO-Fridge', 'Black Slimline Multideck Fridge (1000mm), model BD60/100: 1000 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	97 => array( 'Black Slimline Multideck Fridge (1200mm) | ECO-Fridge', 'Black Slimline Multideck Fridge (1200mm), model BD60/120: 1200 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	98 => array( 'Black Slimline Multideck Fridge (1500mm) | ECO-Fridge', 'Black Slimline Multideck Fridge (1500mm), model BD60/150: 1500 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	99 => array( 'Black Slimline Multideck Fridge (1800mm) | ECO-Fridge', 'Black Slimline Multideck Fridge (1800mm), model BD60/180: 1800 × 600 × 1950 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	100 => array( 'Mirrored Steel Shutter Multideck Fridge (1200mm) | ECO-Fridge', 'Mirrored Steel Shutter Multideck Fridge (1200mm), model SD75/120SH: 1200 × 750 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	101 => array( 'Mirrored Steel Shutter Multideck Fridge (1800mm) | ECO-Fridge', 'Mirrored Steel Shutter Multideck Fridge (1800mm), model SD75/180SH: 1800 × 750 × 2000 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	102 => array( 'Mirrored Steel Double Glazed Patisserie Fridge (1200mm)', 'Mirrored Steel Double Glazed Patisserie Fridge (1200mm), model SP75/120DG: 1200 × 750 × 1350 mm, -1 / +8 °C. From ECO-Fridge, UK supplier.' ),
	103 => array( 'Stainless Steel Double Glazed Patisserie Fridge (600mm)', 'Stainless Steel Double Glazed Patisserie Fridge (600mm), model SP75/60DG: 600 × 750 × 1350 mm, -1 / +8 °C. From ECO-Fridge, UK supplier.' ),
	104 => array( 'Mirrored Steel Closed Patisserie with Castors (900mm)', 'Mirrored Steel Closed Patisserie with Castors (900mm), model SP75/90CC: 900 × 800 × 1500 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	105 => array( 'Mirrored Steel Closed Patisserie with Castors (1200mm)', 'Mirrored Steel Closed Patisserie with Castors (1200mm), model SP75/120CC: 1200 × 800 × 1500 mm, +3 / +12 °C. From ECO-Fridge, UK supplier.' ),
	106 => array( 'Premium UV Bonded Double Glazed Counter Fridge (1000mm)', 'Premium UV Bonded Double Glazed Counter Fridge (1000mm), model SGC100: 1000 × 740 × 1250 mm. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	107 => array( 'Silver Slimline Double Glazed Patisserie Fridge (900mm)', 'Silver Slimline Double Glazed Patisserie Fridge (900mm), model ECO-P60/90: 900 × 600 × 1200 mm, +3 / +7 °C. From ECO-Fridge, UK supplier.' ),
	108 => array( 'Silver Slimline Double Glazed Patisserie Fridge (1200mm)', 'Silver Slimline Double Glazed Patisserie Fridge (1200mm), model ECO-P60/120: 1200 × 600 × 1200 mm, +3 / +7 °C. From ECO-Fridge, UK supplier.' ),
	109 => array( 'Silver Slimline Double Glazed Patisserie Fridge (1500mm)', 'Silver Slimline Double Glazed Patisserie Fridge (1500mm), model ECO-P60/150: 1500 × 600 × 1200 mm, +3 / +7 °C. From ECO-Fridge, UK supplier.' ),
	110 => array( 'Double Glazed Counter Top Chiller (1200mm) | ECO-Fridge', 'Double Glazed Counter Top Chiller (1200mm), model STC120: 1200 × 750 × 720 mm, +0 / +5 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	111 => array( 'Double Glazed Counter Top Chiller (1000mm) | ECO-Fridge', 'Double Glazed Counter Top Chiller (1000mm), model STC100: 1000 × 750 × 720 mm, +0 / +5 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	112 => array( 'White Double Glass Door Display Chiller (880mm) | ECO-Fridge', 'White Double Glass Door Display Chiller (880mm), model ECO-UHGC88: 880 × 660 × 1960 mm, +2 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	113 => array( 'Black Slimline Double Glazed Patisserie Fridge (900mm)', 'Black Slimline Double Glazed Patisserie Fridge (900mm), model ECO-P60/90B: 900 × 600 × 1200 mm, +3 / +7 °C. From ECO-Fridge, UK supplier.' ),
	114 => array( 'Black Slimline Double Glazed Patisserie Fridge (1200mm)', 'Black Slimline Double Glazed Patisserie Fridge (1200mm), model ECO-P60/120B: 1200 × 600 × 1200 mm, +3 / +7 °C. From ECO-Fridge, UK supplier.' ),
	115 => array( 'Black Slimline Double Glazed Patisserie Fridge (1500mm)', 'Black Slimline Double Glazed Patisserie Fridge (1500mm), model ECO-P60/150B: 1500 × 600 × 1200 mm, +3 / +7 °C. From ECO-Fridge, UK supplier.' ),
	116 => array( 'Used Stainless Steel Two Door Counter Fridge | ECO-Fridge', 'Used Stainless Steel Two Door Counter Fridge, model X-EP12M: 1480 × 700 × 880 mm. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	117 => array( 'Used Silver Steel Slimline Serve Over Display (2000mm)', 'Used Silver Steel Slimline Serve Over Display (2000mm), model X-ECO-SO75/200: 2000 × 790 × 1480 mm. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	118 => array( 'Used Dual Temperature Uni-Drawer Fridge (Ex-Exhibition)', 'Used stainless steel uni-drawer fridge, model X-UD1-2: 900 × 700 × 600 mm, switches between chiller (+1 to +4 °C) and freezer. Ex-exhibition, reduced price.' ),
	120 => array( 'Used Premium Slimline Multideck X-SD60/150CC (1500mm)', 'Used Premium Slimline Multideck X-SD60/150CC (1500mm): 1500 × 600 × 2000 mm, -1 / +7 °C. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	121 => array( 'Large Upright Gastronorm Fish & Meat Storage Cabinet | ECO-Fridge', 'Large Upright Gastronorm Fish & Meat Storage Cabinet, model ECO-GN700-FISH: 740 × 900 × 2000 mm. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	122 => array( 'Dual Temperature Hot & Cold Mirrored Multideck DTM60 | ECO-Fridge', 'Dual Temperature Hot & Cold Mirrored Multideck DTM60: 600 × 600 × 2000 mm. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	123 => array( 'Mirrored Steel Heated Grab & Go Open Pastry Display | ECO-Fridge', 'Mirrored Steel Heated Grab & Go Open Pastry Display, model HLM75/90DG: 900 × 750 × 1350 mm, +65 / +90 °C. From ECO-Fridge, UK supplier.' ),
	124 => array( 'Mirrored Steel Hot Pastry Display (900mm) | ECO-Fridge', 'Mirrored Steel Hot Pastry Display (900mm), model HSP75/90DG: 900 × 750 × 1350 mm, +65 / +90 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	125 => array( 'Mirrored Steel Curved Glass Patisserie with Castors (900mm)', 'Mirrored Steel Curved Glass Patisserie with Castors (900mm), model SP75/90CC Curved: 900 × 800 × 1500 mm, +3 / +12 °C. From ECO-Fridge, UK supplier.' ),
	126 => array( 'Mirrored Steel Curved Glass Patisserie with Castors (1200mm)', 'Mirrored Steel Curved Glass Patisserie with Castors (1200mm), model SP75/120CC Curved: 1200 × 800 × 1500 mm, +3 / +12 °C. From ECO-Fridge, UK supplier.' ),
	131 => array( 'Stainless Steel Upright Bakery Retarder Prover | ECO-Fridge', 'Upright stainless steel bakery retarder prover, model BK600RP: ten 600 × 400 mm trays, 0 to +40 °C with humidity control and timer. From ECO-Fridge.' ),
	132 => array( 'Stainless Steel Upright Bakery Fridge | ECO-Fridge', 'Upright stainless steel bakery fridge, model BK600C: ten 600 × 400 mm trays, +2 to +7 °C, rated for 43 °C kitchens. From ECO-Fridge, UK supplier.' ),
	133 => array( 'Stainless Steel Upright Bakery Freezer | ECO-Fridge', 'Upright stainless steel bakery freezer, model BK600F: ten 600 × 400 mm trays, −18 to −24 °C, rated for 43 °C kitchens. From ECO-Fridge, UK supplier.' ),
	134 => array( 'Medical White Upright Storage Chiller | ECO-Fridge', 'Medical White Upright Storage Chiller, model MED60: 600 × 700 × 1900 mm. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	135 => array( 'Used Silver Steel Patisserie Display Fridge (900mm) | ECO-Fridge', 'Used Silver Steel Patisserie Display Fridge (900mm), model X-ECO-P75/90CC: 900 × 750 × 1450 mm, +3 / +12 °C. Ex-exhibition, reduced price.' ),
	136 => array( 'Used Double Door Upright Gastronorm Chiller | ECO-Fridge', 'Used Double Door Upright Gastronorm Chiller, model X-ECO-GN1440M: 1440 × 900 × 2000 mm. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	137 => array( 'Used Stainless Steel Double Glazed Patisserie Fridge (1000mm)', 'Used Stainless Steel Double Glazed Patisserie Fridge (1000mm), model X-CNP75/100: 1000 × 750 × 1400 mm. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	138 => array( 'Used Premium Slimline Multideck X-SD60/100CC (1000mm)', 'Used Premium Slimline Multideck X-SD60/100CC (1000mm): 1000 × 600 × 2000 mm, -1 / +7 °C. Ex-exhibition unit at a reduced price from ECO-Fridge.' ),
	140 => array( 'Commercial Oven and Grill with Steamer | ECO-Fridge', 'Commercial oven and grill with steamer, model OVE10: four 440 × 320 mm trays, oven and grill +50 to +300 °C, 2.6 kW on a 13-amp plug. From ECO-Fridge.' ),
	141 => array( 'Large Commercial Oven with Steamer (8 Trays) | ECO-Fridge', 'Large commercial oven with steamer, model OVE50: 920 × 850 × 825 mm, eight 520 × 390 mm trays, 9 kW. From ECO-Fridge, UK supplier.' ),
	142 => array( 'Stainless Steel Contact Grill | ECO-Fridge', 'Stainless steel contact grill, model GR430, for panini and toasties: 340 × 235 mm cooking area, cast iron plates, 50 to 300 °C. From ECO-Fridge.' ),
	143 => array( 'Mirrored Steel Large Glass Upright Display Fridge | ECO-Fridge', 'Mirrored Steel Large Glass Upright Display Fridge, model GDT120C: 1200 × 760 × 1850 mm, -1 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	144 => array( 'Black Grab and Go Fridge ECO-BLD75/60CC (600mm) | ECO-Fridge', 'Black Grab and Go Fridge ECO-BLD75/60CC (600mm): 600 × 730 × 1450 mm, +5 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	145 => array( 'Black Grab and Go Fridge ECO-BLD75/90CC (900mm) | ECO-Fridge', 'Black Grab and Go Fridge ECO-BLD75/90CC (900mm): 900 × 730 × 1450 mm, +5 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	146 => array( 'Black Grab and Go Fridge ECO-BLD75/120CC (1200mm) | ECO-Fridge', 'Black Grab and Go Fridge ECO-BLD75/120CC (1200mm): 1200 × 730 × 1450 mm, +5 / +7 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	147 => array( 'Black Patisserie Fridge ECO-BP75/60CC (600mm) | ECO-Fridge', 'Black Patisserie Fridge ECO-BP75/60CC (600mm): 600 × 750 × 1450 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	148 => array( 'Black Patisserie Fridge ECO-BP75/90CC (900mm) | ECO-Fridge', 'Black Patisserie Fridge ECO-BP75/90CC (900mm): 900 × 750 × 1450 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	149 => array( 'Black Patisserie Fridge ECO-BP75/120CC (1200mm) | ECO-Fridge', 'Black Patisserie Fridge ECO-BP75/120CC (1200mm): 1200 × 750 × 1450 mm, +3 / +12 °C. From ECO-Fridge, UK commercial refrigeration supplier.' ),
	150 => array( 'Large Commercial Oven with Grill and Steamer | ECO-Fridge', 'Large commercial oven with grill and steamer, model ZH-8A: 860 × 750 × 590 mm, four 590 × 390 mm trays, 6 kW. From ECO-Fridge, UK supplier.' ),
	151 => array( 'Commercial Stainless Steel Oven (4 Trays) | ECO-Fridge', 'Commercial stainless steel convection oven, model ZH-D61: four 400 × 300 mm trays, +50 to +300 °C, 3 kW on a 13-amp plug. From ECO-Fridge, UK supplier.' ),
	152 => array( 'Stainless Steel Oven Stand with Tray Runners | ECO-Fridge', 'Heavy-duty stainless steel oven stand, model Z01089: 850 × 700 × 690 mm, tray runners for 600 × 600 mm trays, adjustable feet. From ECO-Fridge.' ),
);

$blog_tags = array(
	'bar-fridge', 'bar-fridges', 'beer-fridge', 'bottle-chiller', 'bottle-cooler', 'butcher-shop-fridge',
	'butcher-shop', 'cafe-fridges', 'cafe', 'cake-fridge', 'chest-freezer', 'coffee-shop',
	'commercial-freezer', 'commercial-fridge', 'commercial-fridges', 'commercial-refrigeration', 'commercial-refrigerator', 'deli-counter',
	'display-freezer', 'display-fridge', 'display-fridges', 'drinks-fridge', 'drinks-fridges', 'food-regulations-in-the-uk',
	'food-regulations', 'food-safety-regulations', 'fridge-for-shop', 'glass-door-fridge', 'grocery-store', 'ice-cream-freezer',
	'laboratory-freezer', 'low-temp-chest-freezer', 'low-temp-freezer', 'low-temperature-freezer', 'meat-freezer', 'meat-fridge',
	'multideck-chiller', 'multideck-fridge', 'multideck', 'patisserie-fridge', 'pub-fridge', 'refrigeration-maintenance',
	'restaurant-chillers', 'restaurant-equipment', 'restaurant-fridges', 'retail-drinks-refrigerators', 'serve-over', 'shop',
	'storage-fridge', 'takeaway', 'under-counter-freezer', 'under-counter-fridge', 'upright-freezer', 'upright-fridge',
	'wine-cooler', 'wine-fridge',
);

// Product categories: shorter titles (old Yoast ones ran over 70 characters).
$categories = array(
	'counter-fridge'                                             => array( 'Counter Display Fridges, Double Glazed | ECO-Fridge Ltd', '' ),
	'top-counter'                                                => array( 'Countertop Display Fridges, Double Glazed | ECO-Fridge', '' ),
	'display-freezer'                                            => array( 'Display Freezers for Shops | ECO-Fridge Ltd UK', '' ),
	'under-counter-freezer'                                      => array( 'Under Counter Freezers | ECO-Fridge Ltd UK', '' ),
	'under-counter-fridge'                                       => array( 'Under Counter Fridges & Chillers | ECO-Fridge Ltd', '' ),
	'upright-fridge-storage-fridge'                              => array( 'Upright Storage Fridges & Chillers | ECO-Fridge Ltd', '' ),
	'serve-over-display'                                         => array( 'Standard Serve Over Counters | ECO-Fridge Ltd', '' ),
	'standard-counter-display-chiller'                           => array( 'Standard Double Glazed Counter Fridges | ECO-Fridge', '' ),
	'upright-display-chiller'                                    => array( 'Standard Upright Display Fridges | ECO-Fridge Ltd', '' ),
	'upright-display-chillers'                                   => array( 'Premium Upright Glass Door Display Fridges | ECO-Fridge', '' ),
	'upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet' => array( 'Gastronorm Chillers & Fish/Meat Cabinets | ECO-Fridge', '' ),
	'upright-display-cabinets'                                   => array( 'Upright Display Cabinets | ECO-Fridge Ltd UK', '' ),
	'discount-products'                                          => array( '', 'Discounted commercial refrigeration from ECO-Fridge: end-of-line and clearance fridges at reduced prices while stocks last.' ),
);

// Blog posts: titles over 70 characters.
$posts = array(
	7368 => 'Choosing a Laboratory Freezer: 5 Key Considerations | ECO-Fridge',
	7351 => 'Coffee Shop with Style: Display Fridge Ideas | ECO-Fridge',
	7349 => 'How to Meet Food Safety Requirements | ECO-Fridge Ltd',
	7367 => 'How to Keep Food Fresh in a Commercial Fridge | ECO-Fridge',
	7366 => 'How to Start a Patisserie Business | ECO-Fridge Ltd',
	7358 => 'Can a Cake Display Fridge Improve Your Business? | ECO-Fridge',
	7345 => 'Which Commercial Fridge Suits a Restaurant? | ECO-Fridge',
	7352 => 'How to Make Your Coffee Shop Stand Out | ECO-Fridge Ltd',
	7354 => 'Premium Multideck Display Fridges | ECO-Fridge Ltd',
);

// Brochures: descriptions.
$brochures = array(
	7728 => 'Frost-Tech refrigerated cabinet manual (PDF): guide to installing and using your display cabinet, from proper use and siting to loading and care.',
	7727 => 'Refrigerant gas presentation from ECO-Fridge (PDF): background on the refrigerants used in commercial fridges and freezers.',
	7737 => 'Retarder prover controller user manual (PDF): how to set the retarding and proving cycles, timer and humidity on the ECO-Fridge retarder prover.',
	7721 => 'Multideck chiller displays brochure (PDF): ECO-Fridge open-fronted multideck fridges for shops, with ranges, sizes and specifications.',
	7725 => 'Upright display fridges brochure (PDF): ECO-Fridge glass door display fridges and freezers, with ranges, sizes and specifications.',
);

/** Keeps the previous title/description once, then sets the new ones. */
$set_post = static function ( $id, $title, $desc ) {
	if ( '' === (string) get_post_meta( $id, '_ef_seo_before_65', true ) ) {
		update_post_meta( $id, '_ef_seo_before_65', array(
			'title'       => (string) get_post_meta( $id, 'rank_math_title', true ),
			'description' => (string) get_post_meta( $id, 'rank_math_description', true ),
		) );
	}
	if ( '' !== $title ) { update_post_meta( $id, 'rank_math_title', $title ); }
	if ( '' !== $desc ) { update_post_meta( $id, 'rank_math_description', $desc ); }
};
$set_term = static function ( $term_id, $title, $desc ) {
	if ( '' === (string) get_term_meta( $term_id, '_ef_seo_before_65', true ) ) {
		update_term_meta( $term_id, '_ef_seo_before_65', array(
			'title'       => (string) get_term_meta( $term_id, 'rank_math_title', true ),
			'description' => (string) get_term_meta( $term_id, 'rank_math_description', true ),
		) );
	}
	if ( '' !== $title ) { update_term_meta( $term_id, 'rank_math_title', $title ); }
	if ( '' !== $desc ) { update_term_meta( $term_id, 'rank_math_description', $desc ); }
};

// 1. Products — only the expected ones (ID + product type).
$n = 0;
foreach ( $products as $id => $meta ) {
	if ( 'product' !== get_post_type( $id ) ) {
		WP_CLI::warning( "Not a product: $id (skipped)." );
		continue;
	}
	$set_post( $id, $meta[0], $meta[1] );
	$n++;
}
WP_CLI::log( "Products: $n" );

// 2. Blog tags.
$small = array( 'a', 'an', 'and', 'for', 'in', 'of', 'the', 'to' );
$n     = 0;
foreach ( $blog_tags as $slug ) {
	$t = get_term_by( 'slug', $slug, 'post_tag' );
	if ( ! $t ) {
		WP_CLI::warning( "Blog tag not found: $slug" );
		continue;
	}
	$name  = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
	$words = explode( ' ', $name );
	foreach ( $words as $i => $w ) {
		$words[ $i ] = ( $i && in_array( strtolower( $w ), $small, true ) ) ? strtolower( $w ) : ( ctype_upper( $w ) ? $w : ucfirst( strtolower( $w ) ) );
	}
	$label = implode( ' ', $words );
	$set_term(
		$t->term_id,
		$label . ': Articles & Guides | ECO-Fridge',
		'Articles about ' . strtolower( $name ) . ' from the ECO-Fridge blog: buying guides, tips and news on commercial refrigeration for UK businesses.'
	);
	$n++;
}
WP_CLI::log( "Blog tags: $n" );

// 3. Product categories.
foreach ( $categories as $slug => $meta ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t ) {
		WP_CLI::warning( "Category not found: $slug" );
		continue;
	}
	$set_term( $t->term_id, $meta[0], $meta[1] );
}
WP_CLI::log( 'Categories: ' . count( $categories ) );

// 4. Blog posts.
foreach ( $posts as $id => $title ) {
	if ( 'post' === get_post_type( $id ) ) {
		$set_post( $id, $title, '' );
	}
}
WP_CLI::log( 'Posts: ' . count( $posts ) );

// 5. Brochures.
foreach ( $brochures as $id => $desc ) {
	if ( 'brochure' === get_post_type( $id ) ) {
		$set_post( $id, '', $desc );
	}
}
WP_CLI::log( 'Brochures: ' . count( $brochures ) );

// 6. The dead external link (the target domain no longer resolves): keep its text.
$post = get_page_by_path( 'how-to-start-a-takeaway-business', OBJECT, 'post' );
if ( $post && false !== strpos( $post->post_content, 'seo2agency.com' ) ) {
	global $wpdb;
	$content = preg_replace( '~<a\s[^>]*href="https?://seo2agency\.com/[^"]*"[^>]*>(.*?)</a>~is', '$1', $post->post_content );
	$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $post->ID ) ); // No revision; the article keeps its date.
	clean_post_cache( $post->ID );
	WP_CLI::log( 'Removed the dead seo2agency.com link.' );
}

WP_CLI::success( 'SEO titles and descriptions set.' );
