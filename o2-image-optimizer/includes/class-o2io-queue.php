<?php
/**
 * Background conversion: small batches run by Action Scheduler (bundled with
 * WooCommerce) or WP-Cron, with a pause between batches, a time budget per
 * batch and a lock so two batches never overlap. Started, paused and stopped
 * from Media → Image Optimizer; new uploads are queued one by one.
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Bulk queue.
 */
class O2IO_Queue {

	const STATE = 'o2io_queue';
	const HOOK  = 'o2io_process_batch';
	const ONE   = 'o2io_process_one';
	const LOCK  = 'o2io_batch_lock';
	const LOG   = 'o2io_log';

	/** Seconds of work per batch before it hands over to the next one. */
	const BUDGET = 20;

	/** Hooks. */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run_batch' ) );
		add_action( self::ONE, array( __CLASS__, 'run_one' ) );
	}

	/**
	 * Queue state.
	 *
	 * @return array
	 */
	public static function state() {
		return wp_parse_args( (array) get_option( self::STATE, array() ), array(
			'status'    => 'idle', // idle | running | paused | finished.
			'mode'      => 'missing', // missing | all.
			'last_id'   => 0,
			'processed' => 0,
			'errors'    => 0,
			'started'   => 0,
			'updated'   => 0,
			'next_at'   => 0, // The next batch may not start before this time (pause between batches).
		) );
	}

	/**
	 * Save the queue state.
	 *
	 * @param array $s State.
	 */
	private static function save( $s ) {
		$s['updated'] = time();
		update_option( self::STATE, $s, false );
	}

	/**
	 * Start a bulk run.
	 *
	 * @param string $mode missing = images never converted | all = re-convert everything.
	 */
	public static function start( $mode = 'missing' ) {
		self::unschedule();
		self::save( array(
			'status'    => 'running',
			'mode'      => 'all' === $mode ? 'all' : 'missing',
			'last_id'   => 0,
			'processed' => 0,
			'errors'    => 0,
			'started'   => time(),
			'next_at'   => 0,
		) );
		self::log( 'Bulk conversion started (' . ( 'all' === $mode ? 're-convert all images' : 'images not converted yet' ) . ').' );
		self::schedule( 0 );
	}

	/** Pause: the current batch finishes, no new one starts. */
	public static function pause() {
		$s = self::state();
		if ( 'running' === $s['status'] ) {
			$s['status'] = 'paused';
			self::save( $s );
			self::unschedule();
			self::log( 'Paused.' );
		}
	}

	/** Resume a paused run where it stopped. */
	public static function resume() {
		$s = self::state();
		if ( 'paused' === $s['status'] ) {
			$s['status']  = 'running';
			$s['next_at'] = 0;
			self::save( $s );
			self::log( 'Resumed.' );
			self::schedule( 0 );
		}
	}

	/** Stop and forget the current run. */
	public static function stop() {
		self::unschedule();
		$s = self::state();
		if ( in_array( $s['status'], array( 'running', 'paused' ), true ) ) {
			$s['status'] = 'idle';
			self::save( $s );
			self::log( 'Stopped.' );
		}
	}

	/**
	 * Queue one attachment (new upload).
	 *
	 * @param int $id Attachment ID.
	 */
	public static function enqueue_one( $id ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ONE, array( (int) $id ), 'o2io' );
		} else {
			wp_schedule_single_event( time() + 5, self::ONE, array( (int) $id ) );
		}
	}

	/**
	 * Convert one queued attachment.
	 *
	 * @param int $id Attachment ID.
	 */
	public static function run_one( $id ) {
		$r = O2IO_Attachments::process( (int) $id, true );
		if ( 'error' === $r['status'] || 'partial' === $r['status'] ) {
			self::log( "Upload #$id: " . implode( '; ', $r['errors'] ) );
		}
	}

	/**
	 * Run the next batch now if it is due: called by the open dashboard, so
	 * the conversion does not depend on visitors triggering WP-Cron.
	 *
	 * @return bool Whether a batch ran.
	 */
	public static function tick() {
		$s = self::state();
		if ( 'running' !== $s['status'] || time() < (int) $s['next_at'] || get_transient( self::LOCK ) ) { return false; }
		self::run_batch();
		return true;
	}

	/** One batch of the bulk run. */
	public static function run_batch() {
		$s = self::state();
		if ( 'running' !== $s['status'] ) { return; }
		if ( time() < (int) $s['next_at'] ) {
			self::schedule( (int) $s['next_at'] - time() ); // The dashboard already ran the previous batch.
			return;
		}
		if ( get_transient( self::LOCK ) ) {
			self::schedule( 30 ); // Another batch is still at work: try again shortly.
			return;
		}
		set_transient( self::LOCK, 1, 5 * MINUTE_IN_SECONDS );
		$start = microtime( true );
		$force = 'all' === $s['mode'];
		$ids   = O2IO_Attachments::next_ids( (int) $s['last_id'], (int) O2IO_Settings::get( 'batch_size' ), ! $force );

		foreach ( $ids as $id ) {
			$r = O2IO_Attachments::process( $id, $force );
			$s['last_id'] = $id;
			$s['processed']++;
			if ( in_array( $r['status'], array( 'error', 'partial' ), true ) ) {
				$s['errors']++;
				self::log( "#$id " . get_the_title( $id ) . ': ' . implode( '; ', $r['errors'] ) );
			}
			if ( microtime( true ) - $start > self::BUDGET ) { break; }
		}
		delete_transient( self::LOCK );

		// The user may have paused or stopped while this batch ran.
		$now = self::state();
		if ( 'running' !== $now['status'] ) {
			$now['last_id']   = $s['last_id'];
			$now['processed'] = $s['processed'];
			$now['errors']    = $s['errors'];
			self::save( $now );
			return;
		}
		if ( ! $ids || ! O2IO_Attachments::next_ids( (int) $s['last_id'], 1, ! $force ) ) {
			$s['status'] = 'finished';
			self::save( $s );
			self::log( sprintf( 'Finished: %d images processed, %d with errors.', $s['processed'], $s['errors'] ) );
			return;
		}
		$s['next_at'] = time() + (int) O2IO_Settings::get( 'batch_pause' );
		self::save( $s );
		self::schedule( (int) O2IO_Settings::get( 'batch_pause' ) );
	}

	/**
	 * Schedule the next batch.
	 *
	 * @param int $delay Seconds from now.
	 */
	private static function schedule( $delay ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			if ( ! as_next_scheduled_action( self::HOOK, array(), 'o2io' ) ) {
				as_schedule_single_action( time() + $delay, self::HOOK, array(), 'o2io' );
			}
		} elseif ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + $delay, self::HOOK );
		}
	}

	/** Remove pending batches. */
	private static function unschedule() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::HOOK, array(), 'o2io' );
		}
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Add a line to the activity log (last 100 lines).
	 *
	 * @param string $msg Message.
	 */
	public static function log( $msg ) {
		$log   = (array) get_option( self::LOG, array() );
		$log[] = array( time(), $msg );
		update_option( self::LOG, array_slice( $log, -100 ), false );
	}

	/**
	 * The activity log, newest first.
	 *
	 * @return array[]
	 */
	public static function get_log() {
		return array_reverse( (array) get_option( self::LOG, array() ) );
	}
}
