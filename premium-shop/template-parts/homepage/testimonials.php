<?php
/**
 * Homepage: testimonials.
 *
 * Shows genuine WooCommerce reviews (4–5 stars) and/or testimonials entered
 * in the Customizer. Nothing is invented: the section is hidden when empty
 * (the Customizer preview shows a placeholder to help with the setup).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_source = premium_shop_option( 'testimonials_source' );
$premium_shop_items  = array();

if ( in_array( $premium_shop_source, array( 'custom', 'both' ), true ) ) {
	for ( $premium_shop_i = 1; $premium_shop_i <= 3; $premium_shop_i++ ) {
		$premium_shop_quote = premium_shop_text( 'testimonial_' . $premium_shop_i . '_quote' );
		if ( $premium_shop_quote ) {
			$premium_shop_items[] = array(
				'quote'  => $premium_shop_quote,
				'author' => premium_shop_option( 'testimonial_' . $premium_shop_i . '_author' ),
				'meta'   => premium_shop_text( 'testimonial_' . $premium_shop_i . '_meta' ),
				'rating' => 5,
				'url'    => '',
			);
		}
	}
}

if ( premium_shop_is_wc() && in_array( $premium_shop_source, array( 'reviews', 'both' ), true ) && count( $premium_shop_items ) < 3 ) {
	$premium_shop_reviews = get_comments(
		array(
			'type'       => 'review',
			'status'     => 'approve',
			'post_type'  => 'product',
			'number'     => 3 - count( $premium_shop_items ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'rating',
					'value'   => 4,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $premium_shop_reviews as $premium_shop_review ) {
		$premium_shop_text = wp_strip_all_tags( $premium_shop_review->comment_content );
		if ( mb_strlen( $premium_shop_text ) < 20 ) {
			continue;
		}
		$premium_shop_items[] = array(
			'quote'    => wp_html_excerpt( $premium_shop_text, 220, '…' ),
			'author'   => $premium_shop_review->comment_author,
			'meta'     => get_the_title( $premium_shop_review->comment_post_ID ),
			'rating'   => (int) get_comment_meta( $premium_shop_review->comment_ID, 'rating', true ),
			'url'      => get_permalink( $premium_shop_review->comment_post_ID ),
			'verified' => (bool) get_comment_meta( $premium_shop_review->comment_ID, 'verified', true ),
		);
	}
}

$premium_shop_placeholder = false;
if ( ! $premium_shop_items ) {
	if ( ! is_customize_preview() ) {
		return;
	}
	$premium_shop_placeholder = true;
}
?>
<section class="ps-section ps-testimonials" aria-labelledby="ps-testimonials-title">
	<div class="ps-container">
		<?php
		premium_shop_section_heading(
			array(
				'eyebrow' => __( 'Reviews', 'premium-shop' ),
				'title'   => premium_shop_text( 'title_testimonials' ),
				'id'      => 'ps-testimonials-title',
				'align'   => 'center',
			)
		);
		?>

		<?php if ( $premium_shop_placeholder ) : ?>
			<p class="ps-preview-hint"><?php esc_html_e( 'This section appears automatically as soon as you receive 4- or 5-star product reviews, or when you enter testimonials in Premium Shop → Homepage — testimonials. (This hint is only visible in the Customizer.)', 'premium-shop' ); ?></p>
		<?php else : ?>
			<ul class="ps-testimonials__list">
				<?php foreach ( $premium_shop_items as $premium_shop_i => $premium_shop_item ) : ?>
					<li class="ps-testimonial" data-reveal style="--ps-delay:<?php echo esc_attr( $premium_shop_i * 90 ); ?>ms">
						<figure>
							<?php if ( $premium_shop_item['rating'] ) : ?>
								<p class="ps-stars" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating. */ __( 'Rated %d out of 5', 'premium-shop' ), $premium_shop_item['rating'] ) ); ?>">
									<?php echo esc_html( str_repeat( '★', $premium_shop_item['rating'] ) . str_repeat( '☆', 5 - $premium_shop_item['rating'] ) ); ?>
								</p>
							<?php endif; ?>
							<blockquote class="ps-testimonial__quote"><p><?php echo esc_html( $premium_shop_item['quote'] ); ?></p></blockquote>
							<figcaption class="ps-testimonial__author">
								<span class="ps-testimonial__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( (string) $premium_shop_item['author'], 0, 1 ) ) ); ?></span>
								<span>
									<strong><?php echo esc_html( $premium_shop_item['author'] ); ?></strong>
									<?php if ( ! empty( $premium_shop_item['verified'] ) ) : ?>
										<span class="ps-testimonial__verified"><?php premium_shop_icon( 'check', array( 'size' => 12 ) ); ?><?php esc_html_e( 'Verified purchase', 'premium-shop' ); ?></span>
									<?php endif; ?>
									<?php if ( $premium_shop_item['meta'] ) : ?>
										<?php if ( $premium_shop_item['url'] ) : ?>
											<a href="<?php echo esc_url( $premium_shop_item['url'] ); ?>"><?php echo esc_html( $premium_shop_item['meta'] ); ?></a>
										<?php else : ?>
											<span><?php echo esc_html( $premium_shop_item['meta'] ); ?></span>
										<?php endif; ?>
									<?php endif; ?>
								</span>
							</figcaption>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
