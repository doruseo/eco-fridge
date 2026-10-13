<?php
/**
 * Aggregated reviews for every product in a category (and its subcategories).
 *
 * Works with core WooCommerce reviews and with ReviewX, which stores its reviews as
 * WooCommerce reviews (comment_type "review", rating in the "rating" comment meta).
 * Only reviews with a 1–5 star rating are shown and counted.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

final class EFB_Landing_Reviews {

	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	public function __construct() {
		add_action( 'wp_ajax_efb_landing_reviews', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_efb_landing_reviews', array( $this, 'ajax' ) );

		// Anything that can change which reviews/products belong to a category invalidates the cache.
		foreach ( array( 'comment_post', 'edit_comment', 'wp_set_comment_status', 'deleted_comment', 'save_post_product', 'edited_product_cat', 'delete_product_cat', 'edited_product_tag', 'delete_product_tag', 'edited_market', 'delete_market' ) as $hook ) {
			add_action( $hook, array( 'EFB_Landing_Meta', 'bump_cache' ) );
		}
		add_action(
			'set_object_terms',
			function ( $object_id, $terms, $tt_ids, $taxonomy ) {
				if ( 'product_cat' === $taxonomy || in_array( $taxonomy, efb_landing_tag_taxonomies(), true ) ) {
					EFB_Landing_Meta::bump_cache();
				}
			},
			10,
			4
		);
	}

	private static function per_page() {
		return (int) apply_filters( 'efb_landing_reviews_per_page', 6 );
	}

	private static function cache_key( $name, $term_id ) {
		return 'efb_landing_' . $name . '_' . $term_id . '_' . EFB_Landing_Meta::cache_version();
	}

	/** Published products in the category, including subcategories. */
	public static function product_ids( $term_id ) {
		$key = self::cache_key( 'pids', $term_id );
		$ids = get_transient( $key );
		if ( false === $ids ) {
			$ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'taxonomy'         => efb_landing_is_tag( $term_id ) ? get_term( (int) $term_id )->taxonomy : 'product_cat',
							'terms'            => $term_id,
							'include_children' => true,
						),
					),
				)
			);
			set_transient( $key, $ids, self::CACHE_TTL );
		}
		return array_map( 'intval', (array) $ids );
	}

	/** @return array{count:int, average:float, dist:array<int,int>} */
	public static function stats( $term_id ) {
		$key   = self::cache_key( 'stats', $term_id );
		$stats = get_transient( $key );
		if ( false !== $stats ) {
			return $stats;
		}

		$stats = array(
			'count'   => 0,
			'average' => 0.0,
			'dist'    => array_fill_keys( array( 5, 4, 3, 2, 1 ), 0 ),
		);
		$ids   = self::product_ids( $term_id );

		if ( $ids ) {
			global $wpdb;
			$in   = implode( ',', $ids ); // Integers only, see product_ids().
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT CAST(m.meta_value AS UNSIGNED) AS rating, COUNT(*) AS n
				FROM {$wpdb->comments} c
				INNER JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = 'rating'
				WHERE c.comment_post_ID IN ($in) AND c.comment_approved = '1' AND c.comment_type = 'review' AND c.comment_parent = 0
				GROUP BY rating"
			);
			$sum = 0;
			foreach ( $rows as $row ) {
				$r = (int) $row->rating;
				if ( $r >= 1 && $r <= 5 ) {
					$stats['dist'][ $r ] += (int) $row->n;
					$stats['count']      += (int) $row->n;
					$sum                 += $r * (int) $row->n;
				}
			}
			$stats['average'] = $stats['count'] ? round( $sum / $stats['count'], 2 ) : 0.0;
		}

		set_transient( $key, $stats, self::CACHE_TTL );
		return $stats;
	}

	/**
	 * @param string $sort newest|highest|lowest
	 * @return array{comments: WP_Comment[], total:int}
	 */
	public static function query( $term_id, $page = 1, $rating = 0, $sort = 'newest' ) {
		$ids = self::product_ids( $term_id );
		if ( ! $ids ) {
			// An empty post__in would match every comment on the site.
			return array(
				'comments' => array(),
				'total'    => 0,
			);
		}

		$args = array(
			'post__in'      => $ids,
			'type'          => 'review',
			'status'        => 'approve',
			'parent'        => 0,
			'number'        => self::per_page(),
			'offset'        => ( max( 1, $page ) - 1 ) * self::per_page(),
			'orderby'       => 'comment_date_gmt',
			'order'         => 'DESC',
			'no_found_rows' => false,
		);

		if ( 'highest' === $sort || 'lowest' === $sort ) {
			$args['meta_key'] = 'rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value_num comment_date_gmt';
			$args['order']    = 'highest' === $sort ? 'DESC' : 'ASC';
		}
		// Only rated reviews, matching stats(). Spam posted as a "review" usually has no rating,
		// and without this the list and the summary would count different things.
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			$rating >= 1 && $rating <= 5
				? array(
					'key'     => 'rating',
					'value'   => $rating,
					'compare' => '=',
					'type'    => 'NUMERIC',
				)
				: array(
					'key'     => 'rating',
					'value'   => array( 1, 5 ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
		);

		$query    = new WP_Comment_Query( apply_filters( 'efb_landing_reviews_query_args', $args, $term_id ) );
		$comments = $query->get_comments();
		_prime_post_caches( array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) ) );

		return array(
			'comments' => $comments,
			'total'    => (int) $query->found_comments,
		);
	}

	public static function render( $term_id ) {
		$stats = self::stats( $term_id );
		if ( ! $stats['count'] ) {
			return '';
		}
		$result = self::query( $term_id );

		ob_start();
		?>
		<div class="efbl-reviews" data-term="<?php echo esc_attr( $term_id ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-error="<?php esc_attr_e( 'Reviews could not be loaded. Please try again.', 'ecofridge-blocks' ); ?>">
			<div class="efbl-reviews__summary">
				<div class="efbl-reviews__score">
					<span class="efbl-reviews__avg"><?php echo esc_html( number_format_i18n( $stats['average'], 1 ) ); ?></span>
					<?php echo self::stars( $stats['average'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="efbl-reviews__count">
						<?php
						/* translators: %s: number of reviews */
						echo esc_html( sprintf( _n( 'Based on %s review', 'Based on %s reviews', $stats['count'], 'ecofridge-blocks' ), number_format_i18n( $stats['count'] ) ) );
						?>
					</span>
				</div>
				<div class="efbl-reviews__bars" role="group" aria-label="<?php esc_attr_e( 'Filter reviews by rating', 'ecofridge-blocks' ); ?>">
					<?php foreach ( $stats['dist'] as $star => $n ) : ?>
						<button type="button" class="efbl-bar" data-rating="<?php echo esc_attr( $star ); ?>" aria-pressed="false" <?php disabled( 0, $n ); ?>>
							<span class="efbl-bar__label">
								<?php
								/* translators: %d: star rating */
								echo esc_html( sprintf( _n( '%d star', '%d stars', $star, 'ecofridge-blocks' ), $star ) );
								?>
							</span>
							<span class="efbl-bar__track"><span class="efbl-bar__fill" style="width:<?php echo esc_attr( round( $n / $stats['count'] * 100, 1 ) ); ?>%"></span></span>
							<span class="efbl-bar__n"><?php echo esc_html( number_format_i18n( $n ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="efbl-reviews__toolbar">
				<p class="efbl-reviews__status" aria-live="polite"><?php echo esc_html( self::status_text( 1, $result['total'], 0 ) ); ?></p>
				<label class="efbl-reviews__sort-label">
					<span><?php esc_html_e( 'Sort by', 'ecofridge-blocks' ); ?></span>
					<select class="efbl-reviews__sort">
						<option value="newest"><?php esc_html_e( 'Most recent', 'ecofridge-blocks' ); ?></option>
						<option value="highest"><?php esc_html_e( 'Highest rated', 'ecofridge-blocks' ); ?></option>
						<option value="lowest"><?php esc_html_e( 'Lowest rated', 'ecofridge-blocks' ); ?></option>
					</select>
				</label>
			</div>

			<ol class="efbl-reviews__list">
				<?php echo self::items_html( $result['comments'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</ol>

			<button type="button" class="efbl-reviews__more button" <?php echo $result['total'] > self::per_page() ? '' : 'hidden'; ?>><?php esc_html_e( 'Show more reviews', 'ecofridge-blocks' ); ?></button>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function status_text( $page, $total, $rating ) {
		$shown = min( $page * self::per_page(), $total );
		if ( $rating ) {
			/* translators: 1: reviews shown, 2: total reviews with that rating, 3: star rating */
			return sprintf( __( 'Showing %1$s of %2$s reviews with %3$d stars', 'ecofridge-blocks' ), number_format_i18n( $shown ), number_format_i18n( $total ), $rating );
		}
		/* translators: 1: reviews shown, 2: total reviews */
		return sprintf( __( 'Showing %1$s of %2$s reviews', 'ecofridge-blocks' ), number_format_i18n( $shown ), number_format_i18n( $total ) );
	}

	private static function stars( $rating ) {
		/* translators: %s: average rating */
		$label = sprintf( __( 'Rated %s out of 5', 'ecofridge-blocks' ), number_format_i18n( $rating, 1 ) );
		return '<span class="efbl-stars" role="img" style="--efbl-rating:' . esc_attr( (float) $rating ) . '" aria-label="' . esc_attr( $label ) . '"></span>';
	}

	private static function items_html( array $comments ) {
		if ( ! $comments ) {
			return '<li class="efbl-reviews__empty">' . esc_html__( 'No reviews match this filter yet.', 'ecofridge-blocks' ) . '</li>';
		}
		$html = '';
		foreach ( $comments as $comment ) {
			$html .= self::item_html( $comment );
		}
		return $html;
	}

	private static function item_html( WP_Comment $comment ) {
		$id      = (int) $comment->comment_ID;
		$product = wc_get_product( $comment->comment_post_ID );
		if ( ! $product ) {
			return '';
		}
		$rating   = (int) get_comment_meta( $id, 'rating', true );
		$title    = (string) get_comment_meta( $id, 'rvx_title', true ); // ReviewX review title.
		$author   = get_comment_meta( $id, 'is_anonymous', true ) ? __( 'Anonymous', 'ecofridge-blocks' ) : get_comment_author( $comment );
		$verified = wc_review_is_from_verified_owner( $id );
		$link     = get_permalink( $product->get_id() );

		$html  = '<li class="efbl-review">';
		$html .= '<a class="efbl-review__product" href="' . esc_url( $link ) . '">' . $product->get_image( 'woocommerce_gallery_thumbnail', array( 'loading' => 'lazy' ) ) . '<span>' . esc_html( $product->get_name() ) . '</span></a>';
		$html .= '<div class="efbl-review__head">';
		$html .= $rating ? self::stars( $rating ) : '';
		$html .= '<time datetime="' . esc_attr( get_comment_date( 'c', $comment ) ) . '">' . esc_html( get_comment_date( '', $comment ) ) . '</time>';
		$html .= '</div>';
		$html .= '' !== $title ? '<p class="efbl-review__title">' . esc_html( $title ) . '</p>' : '';
		$html .= '<div class="efbl-review__text">' . wpautop( wp_kses_post( $comment->comment_content ) ) . '</div>';
		$html .= '<p class="efbl-review__author">' . esc_html( $author );
		$html .= $verified ? ' <span class="efbl-review__verified">' . esc_html__( 'Verified buyer', 'ecofridge-blocks' ) . '</span>' : '';
		$html .= '</p></li>';

		return apply_filters( 'efb_landing_review_html', $html, $comment, $product );
	}

	/**
	 * Public, read-only endpoint. No nonce on purpose: category pages are page-cached
	 * (LiteSpeed), and a cached nonce would expire and break "Show more" for visitors.
	 */
	public function ajax() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$term_id = absint( $_GET['term'] ?? 0 );
		$page    = max( 1, absint( $_GET['page'] ?? 1 ) );
		$rating  = min( 5, absint( $_GET['rating'] ?? 0 ) );
		$sort    = sanitize_key( $_GET['sort'] ?? 'newest' );
		// phpcs:enable

		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) || ! efb_landing_applies( $term_id ) || ! EFB_Landing_Meta::get( $term_id )['reviews'] ) {
			wp_send_json_error( null, 404 );
		}

		$result = self::query( $term_id, $page, $rating, $sort );
		wp_send_json_success(
			array(
				'html'     => self::items_html( $result['comments'] ),
				'has_more' => $page * self::per_page() < $result['total'],
				'status'   => self::status_text( $page, $result['total'], $rating ),
			)
		);
	}
}
