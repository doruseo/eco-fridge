<?php
/**
 * Step 11: sets the brand colour palette in Astra (Global Colors), editable
 * from the Customizer. Palette source = the original eco-fridge theme (_variables.scss):
 * brand #70b036, hover #5c972d, text #333, bg #fcfcfc, border #eaedf2.
 * Sets astra-settings[global-color-palette][palette] (generates the CSS vars)
 * + astra-color-palettes (Customizer UI). Run: wp eval-file _run.php
 *
 * @package Eco-Fridge-Migration
 */
if(!defined('ABSPATH'))exit;
// 0:link/brand 1:hover 2:heading 3:text 4:bg 5:bg2(white) 6:dark 7:border 8:dark
// Forest green #228B22 (white or black on it, by contrast). White background everywhere
// so the product images (white background) blend in naturally.
$colors = array('#228B22','#1b6f1b','#222222','#333333','#ffffff','#ffffff','#111111','#e5e7eb','#111111');

// 1) "flat" palette that generates --ast-global-color-N
$s = get_option('astra-settings', array());
if(!is_array($s)) $s=array();
$gcp = (isset($s['global-color-palette']) && is_array($s['global-color-palette'])) ? $s['global-color-palette'] : array();
$gcp['palette'] = $colors;
$s['global-color-palette'] = $gcp;
// explicit base colours (so no element keeps the Astra default)
$s['text-color']       = $colors[3];
$s['link-color']       = $colors[0];
$s['theme-color']      = $colors[0];
$s['link-hover-color'] = $colors[1];
$s['heading-base-color']= $colors[2];
update_option('astra-settings', $s);

// 2) every palette for the Customizer (editable), palette_1 = brand, current = palette_1
$cp = get_option('astra-color-palettes', false);
if(!$cp || !is_array($cp) || empty($cp['palettes'])){ $cp = Astra_Global_Palette::get_default_color_palette(); }
$cp['currentPalette'] = 'palette_1';
$cp['palettes']['palette_1'] = $colors;
update_option('astra-color-palettes', $cp);

echo "palette[0]=".$s['global-color-palette']['palette'][0]." current=".$cp['currentPalette']."\n";
WP_CLI::success('Brand palette set in Astra.');
