<?php
/**
 * Pasul 11: setează paleta de culori brand în Astra (Global Colors), editabilă
 * din Customizer. Sursă paletă = tema originală eco-fridge (_variables.scss):
 * brand #70b036, hover #5c972d, text #333, bg #fcfcfc, border #eaedf2.
 * Setează astra-settings[global-color-palette][palette] (generează CSS vars)
 * + astra-color-palettes (UI Customizer). Rulare: wp eval-file _run.php
 *
 * @package Eco-Fridge-Migration
 */
if(!defined('ABSPATH'))exit;
// 0:link/brand 1:hover 2:heading 3:text 4:bg 5:bg2(alb) 6:dark 7:border 8:dark
// Verde forest #228B22 (cu alb/negru pe el după contrast). Fundal alb peste tot
// ca imaginile de produs (fundal alb) să se îmbine natural.
$colors = array('#228B22','#1b6f1b','#222222','#333333','#ffffff','#ffffff','#111111','#e5e7eb','#111111');

// 1) paletă „flat" care generează --ast-global-color-N
$s = get_option('astra-settings', array());
if(!is_array($s)) $s=array();
$gcp = (isset($s['global-color-palette']) && is_array($s['global-color-palette'])) ? $s['global-color-palette'] : array();
$gcp['palette'] = $colors;
$s['global-color-palette'] = $gcp;
// culori de bază explicite (ca să nu rămână default Astra pe unele elemente)
$s['text-color']       = $colors[3];
$s['link-color']       = $colors[0];
$s['theme-color']      = $colors[0];
$s['link-hover-color'] = $colors[1];
$s['heading-base-color']= $colors[2];
update_option('astra-settings', $s);

// 2) toate paletele pt. Customizer (editabil), palette_1 = brand, current = palette_1
$cp = get_option('astra-color-palettes', false);
if(!$cp || !is_array($cp) || empty($cp['palettes'])){ $cp = Astra_Global_Palette::get_default_color_palette(); }
$cp['currentPalette'] = 'palette_1';
$cp['palettes']['palette_1'] = $colors;
update_option('astra-color-palettes', $cp);

echo "palette[0]=".$s['global-color-palette']['palette'][0]." current=".$cp['currentPalette']."\n";
WP_CLI::success('Paleta brand setata in Astra.');
