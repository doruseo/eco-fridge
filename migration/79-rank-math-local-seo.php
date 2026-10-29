<?php
/**
 * 79 — Rank Math Local SEO filled in with the shop's real details.
 *
 * Doru switched the Local SEO module on, which left most of its fields empty
 * and the opening hours on the plugin's default of 09:00-17:00 every day of the
 * week. Everything below comes from the Contact page, which is the shop's own
 * published information:
 *
 *   ECO-Fridge Ltd, 18 Top Angel, Buckingham Industrial Park, Buckingham,
 *   Buckinghamshire MK18 1TH
 *   Opening hours: Monday to Friday 8.30 am to 5.00 pm
 *   Phone 01280 811411 / 01280 811410, e-mail sales@eco-fridge.co.uk
 *
 * Two deliberate choices:
 *  - `local_business_type` stays "Organization". The shop is a B2B supplier with
 *    a trade counter, not a walk-in store, so claiming a LocalBusiness subtype
 *    (with the shop hours Google then shows) would overstate what it is.
 *  - `additional_info.legalName` is left empty on purpose: the Contact page says
 *    "ECO-Fridge Ltd" while the bank account is in the name "ECO Fridge UK Ltd",
 *    and a legal name in schema should match Companies House. It goes in once
 *    Mads sends the company number and VAT number.
 *
 * The geo coordinates are the centroid of postcode MK18 1TH (51.986, -0.9858).
 * Replace them with the rooftop position from Google Maps if a more exact point
 * is wanted.
 *
 * `url` keeps the site's own address; the launch search-replace turns it into
 * the live domain like every other stored URL.
 *
 * Safe to run again. Backup: ef_rank_math_local_before_79.
 * Run: wp eval-file migration/79-rank-math-local-seo.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$option  = 'rank-math-options-titles';
$titles  = (array) get_option( $option, array() );

if ( ! get_option( 'ef_rank_math_local_before_79' ) ) {
	add_option( 'ef_rank_math_local_before_79', $titles, '', false );
	echo "Backup saved in ef_rank_math_local_before_79.\n";
}

$about   = get_page_by_path( 'about-us' );
$contact = get_page_by_path( 'contact-us' );

$local = array(
	'knowledgegraph_type'      => 'company',
	'website_name'             => 'ECO-Fridge Ltd',
	'website_alternate_name'   => 'Eco-Fridge UK',
	'knowledgegraph_name'      => 'ECO-Fridge Ltd',
	'organization_description' => 'ECO-Fridge Ltd supplies and manufactures commercial refrigeration for UK businesses: display and storage fridges and freezers, multidecks, serve-over counters, bakery cabinets and ovens, delivered and installed by our own engineers.',
	'email'                    => 'sales@eco-fridge.co.uk',
	'phone'                    => '+441280811411',
	'local_business_type'      => 'Organization',
	'local_address'            => array(
		'streetAddress'   => '18 Top Angel, Buckingham Industrial Park',
		'addressLocality' => 'Buckingham',
		'addressRegion'   => 'Buckinghamshire',
		'postalCode'      => 'MK18 1TH',
		'addressCountry'  => 'GB',
	),
	'local_address_format'     => '{address} {locality}, {region} {postalcode}',
	'geo'                      => '51.986,-0.9858',
	'price_range'              => '££',
	'opening_hours'            => array(
		array(
			'day'  => 'Monday',
			'time' => '08:30-17:00',
		),
		array(
			'day'  => 'Tuesday',
			'time' => '08:30-17:00',
		),
		array(
			'day'  => 'Wednesday',
			'time' => '08:30-17:00',
		),
		array(
			'day'  => 'Thursday',
			'time' => '08:30-17:00',
		),
		array(
			'day'  => 'Friday',
			'time' => '08:30-17:00',
		),
		array(
			'day'  => 'Saturday',
			'time' => 'closed',
		),
		array(
			'day'  => 'Sunday',
			'time' => 'closed',
		),
	),
	'phone_numbers'            => array(
		array(
			'type'   => 'customer support',
			'number' => '+441280811411',
		),
		array(
			'type'   => 'sales',
			'number' => '+441280811410',
		),
	),
);

// The logo Doru picked in Rank Math on staging (eco-fridge-logo.png). Taken by
// attachment ID so every copy of the site resolves it to its own URL.
$logo_id = 7621;
if ( wp_attachment_is_image( $logo_id ) ) {
	$local['knowledgegraph_logo_id'] = (string) $logo_id;
	$local['knowledgegraph_logo']    = wp_get_attachment_url( $logo_id );
}

if ( $about ) {
	$local['local_seo_about_page'] = (string) $about->ID;
}
if ( $contact ) {
	$local['local_seo_contact_page'] = (string) $contact->ID;
}

// The legal name row is kept, still empty, waiting for the client's confirmation.
if ( empty( $titles['additional_info'] ) || ! is_array( $titles['additional_info'] ) ) {
	$local['additional_info'] = array(
		array(
			'type'  => 'legalName',
			'value' => '',
		),
	);
}

$changed = array();
foreach ( $local as $key => $value ) {
	$old = $titles[ $key ] ?? null;
	if ( $old !== $value ) {
		$titles[ $key ] = $value;
		$changed[]      = $key;
	}
}

update_option( $option, $titles );

echo 'Fields written: ' . ( $changed ? implode( ', ', $changed ) : 'none (already correct)' ) . "\n";
echo 'Address: ' . $local['local_address']['streetAddress'] . ', ' . $local['local_address']['addressLocality'] . ' ' . $local['local_address']['postalCode'] . "\n";
echo "Opening hours: Monday to Friday 08:30-17:00, closed at the weekend.\n";
echo "Still to come from the client: legal name, company number and VAT number.\n";
