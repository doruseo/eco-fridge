<?php
/**
 * Post cards block — latest posts, brochures or videos.
 *
 * Options: hand-picked order (ids), ascending/descending date order, brochures
 * linking straight to their PDF, videos with the YouTube player embedded.
 *
 * @var array $attributes Block attributes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

$efb_type = in_array( $attributes['postType'], array( 'post', 'brochure', 'video' ), true ) ? $attributes['postType'] : 'post';
$efb_ids  = array_filter( array_map( 'absint', (array) $attributes['ids'] ) );
$efb_args = array(
	'post_type'           => $efb_type,
	'post_status'         => 'publish',
	'posts_per_page'      => max( 1, min( 48, (int) $attributes['count'] ) ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'order'               => 'asc' === $attributes['order'] ? 'ASC' : 'DESC',
);
if ( $efb_ids ) {
	$efb_args['post__in']       = $efb_ids;
	$efb_args['orderby']        = 'post__in';
	$efb_args['posts_per_page'] = count( $efb_ids );
}
$efb_q = new WP_Query( $efb_args );
if ( ! $efb_q->have_posts() ) {
	return;
}

$efb_embed   = 'video' === $efb_type && $attributes['embedVideo'];
$efb_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'efb-cards efb-cards--' . $efb_type . ( $efb_embed ? ' efb-cards--embed' : '' ),
		'style' => '--efb-cols:' . max( 1, min( 4, (int) $attributes['columns'] ) ),
	)
);
?>
<div <?php echo $efb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	foreach ( $efb_q->posts as $efb_post ) :
		$efb_link   = get_permalink( $efb_post );
		$efb_target = '';
		if ( 'brochure' === $efb_type && $attributes['linkToFile'] ) {
			$efb_file = get_post_meta( $efb_post->ID, '_efb_file_url', true );
			if ( $efb_file ) {
				$efb_link   = $efb_file;
				$efb_target = ' target="_blank" rel="noopener"';
			}
		}
		$efb_yt  = 'video' === $efb_type ? efb_youtube_id( get_post_meta( $efb_post->ID, '_efb_youtube_id', true ) ) : '';
		$efb_img = get_the_post_thumbnail( $efb_post, 'medium_large', array( 'loading' => 'lazy' ) );
		if ( ! $efb_img && $efb_yt ) {
			$efb_img = '<img src="' . esc_url( 'https://i.ytimg.com/vi/' . $efb_yt . '/hqdefault.jpg' ) . '" alt="" loading="lazy" />';
		}
		?>
		<article class="efb-card">
			<?php if ( $efb_embed && $efb_yt ) : ?>
				<div class="efb-video-embed"><iframe src="<?php echo esc_url( 'https://www.youtube-nocookie.com/embed/' . $efb_yt ); ?>" title="<?php echo esc_attr( get_the_title( $efb_post ) ); ?>" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
			<?php elseif ( $efb_img ) : ?>
				<a class="efb-card__image" href="<?php echo esc_url( $efb_link ); ?>"<?php echo $efb_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo $efb_img; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php endif; ?>
			<div class="efb-card__body">
				<h3 class="efb-card__title"><a href="<?php echo esc_url( $efb_link ); ?>"<?php echo $efb_target; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( get_the_title( $efb_post ) ); ?></a></h3>
				<?php if ( $attributes['showDate'] ) : ?>
					<time class="efb-card__date" datetime="<?php echo esc_attr( get_the_date( 'c', $efb_post ) ); ?>"><?php echo esc_html( get_the_date( '', $efb_post ) ); ?></time>
				<?php endif; ?>
				<?php if ( $attributes['showExcerpt'] ) : ?>
					<p class="efb-card__excerpt"><?php echo esc_html( $efb_embed ? wp_strip_all_tags( get_the_excerpt( $efb_post ) ) : wp_trim_words( get_the_excerpt( $efb_post ), 24 ) ); ?></p>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
</div>
