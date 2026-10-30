<?php
/**
 * 80 — stop Rank Math's analytics email report, which fails on every run.
 *
 * Action Scheduler showed one failed action a day: "Scheduled action for
 * rank_math/analytics/email_report_event will not be executed as no callbacks
 * are registered". The report is switched on in Rank Math's settings, but the
 * Analytics module has no connected Rank Math account behind it (registration
 * was skipped on purpose, migration 52), so nothing is listening for the event.
 *
 * The setting is switched off and the scheduled action unhooked, so the failure
 * list stays empty and a real problem is easy to spot there. If a Rank Math
 * account is connected later, turn the report back on in
 * Rank Math > General Settings > Analytics.
 *
 * Safe to run again. Backup: ef_rank_math_general_before_80.
 * Run: wp eval-file migration/80-rank-math-email-report-off.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$option  = 'rank-math-options-general';
$general = (array) get_option( $option, array() );

if ( ! get_option( 'ef_rank_math_general_before_80' ) ) {
	add_option( 'ef_rank_math_general_before_80', $general, '', false );
	echo "Backup saved in ef_rank_math_general_before_80.\n";
}

$general['console_email_reports'] = 'off';
update_option( $option, $general );

// Clear the pending and failed runs of that event.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'rank_math/analytics/email_report_event' );
}
wp_clear_scheduled_hook( 'rank_math/analytics/email_report_event' );

echo "console_email_reports = " . $general['console_email_reports'] . "\n";
echo "Scheduled email-report actions cleared.\n";
