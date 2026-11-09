<?php
/**
 * Homepage hero carousel: the main hero (Customizer texts) followed by
 * automatic slides built from the shop — current offers and the main
 * categories, each with photos of its own products.
 *
 * Customizer → Premium Shop → Homepage hero → "Hero carousel".
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Photos (attachment IDs) of products matching a query.
 *
 * @param array $args  Extra WP_Query arguments.
 * @param int   $count How many.
 * @return int[]
 */
function premium_shop_product_photo_ids( $args, $count = 3 ) {
	$query = array_merge(
		array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'posts_per_page'   => $count * 3,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'orderby'          => 'menu_order date',
			'order'            => 'ASC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'       => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		),
		$args
	);
	$photos = array();
	foreach ( get_posts( $query ) as $id ) {
		$thumb = (int) get_post_thumbnail_id( $id );
		if ( $thumb && ! in_array( $thumb, $photos, true ) ) {
			$photos[] = $thumb;
		}
		if ( count( $photos ) >= $count ) {
			break;
		}
	}
	return $photos;
}

/**
 * Data of the automatic slides (cached for an hour; texts are translated at
 * display time).
 *
 * @return array[] Each: type (sale|category), term_id, photos.
 */
function premium_shop_hero_slides_data() {
	$data = get_transient( 'premium_shop_hero_slides' );
	if ( is_array( $data ) ) {
		return $data;
	}
	$data = array();
	if ( premium_shop_is_wc() ) {
		$sale = array_slice( wc_get_product_ids_on_sale(), 0, 60 );
		if ( $sale ) {
			$photos = premium_shop_product_photo_ids( array( 'post__in' => $sale ), 3 );
			if ( $photos ) {
				$data[] = array(
					'type'   => 'sale',
					'photos' => $photos,
				);
			}
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 6,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		);
		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			$photos = premium_shop_product_photo_ids(
				array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'tax_query' => array(
						array(
							'taxonomy' => 'product_cat',
							'terms'    => $term->term_id,
						),
					),
				),
				3
			);
			if ( count( $photos ) >= 2 ) {
				$data[] = array(
					'type'    => 'category',
					'term_id' => (int) $term->term_id,
					'photos'  => $photos,
				);
			}
			if ( count( $data ) >= 3 ) {
				break;
			}
		}
	}
	// Complete each collage to three photos with other photos of the shop.
	$pool = function_exists( 'premium_shop_hero_photo_pool' ) ? premium_shop_hero_photo_pool() : array();
	foreach ( $data as $n => $item ) {
		$extra = array_values( array_diff( $pool, $item['photos'] ) );
		$shift = ( $n * 3 ) % max( 1, count( $extra ) );
		$extra = array_merge( array_slice( $extra, $shift ), array_slice( $extra, 0, $shift ) );
		$data[ $n ]['photos'] = array_slice( array_merge( $item['photos'], $extra ), 0, 3 );
	}
	$data = array_values(
		array_filter(
			$data,
			static function ( $item ) {
				return count( $item['photos'] ) >= 2;
			}
		)
	);
	set_transient( 'premium_shop_hero_slides', $data, HOUR_IN_SECONDS );
	return $data;
}

/**
 * Forget the slides when products or categories change.
 */
function premium_shop_hero_slides_flush() {
	delete_transient( 'premium_shop_hero_slides' );
}
add_action( 'save_post_product', 'premium_shop_hero_slides_flush' );
add_action( 'edited_product_cat', 'premium_shop_hero_slides_flush' );
add_action( 'woocommerce_product_import_inserted_product_object', 'premium_shop_hero_slides_flush' );

/**
 * Slides shown after the main hero.
 *
 * @return array[] Each: eyebrow, title, text, button, url, photos.
 */
function premium_shop_hero_extra_slides() {
	if ( ! premium_shop_option( 'hero_slider' ) || 'split' !== premium_shop_option( 'hero_layout' ) ) {
		return array();
	}
	$slides = array();
	foreach ( premium_shop_hero_slides_data() as $item ) {
		if ( 'sale' === $item['type'] ) {
			$slides[] = array(
				'eyebrow' => premium_shop_text( 'campaign_eyebrow' ),
				'title'   => premium_shop_text( 'campaign_title' ),
				'text'    => premium_shop_text( 'campaign_text' ),
				'button'  => premium_shop_text( 'campaign_button_text' ),
				'url'     => premium_shop_option( 'campaign_button_url' ) ? premium_shop_option( 'campaign_button_url' ) : add_query_arg( 'on_sale', '1', premium_shop_shop_url() ),
				'photos'  => $item['photos'],
				'badge'   => '%',
			);
			continue;
		}
		$term = get_term( $item['term_id'], 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$text = trim( wp_strip_all_tags( term_description( $term ) ) );
		$slides[] = array(
			'eyebrow' => __( 'Our range', 'premium-shop' ),
			'title'   => $term->name,
			/* translators: %d: number of products. */
			'text'    => '' !== $text && 'de' === premium_shop_current_language() ? wp_trim_words( $text, 28 ) : sprintf( _n( '%d product, carefully selected and delivered to your door.', '%d products, carefully selected and delivered to your door.', (int) $term->count, 'premium-shop' ), (int) $term->count ),
			'button'  => __( 'Discover', 'premium-shop' ),
			'url'     => get_term_link( $term ),
			'photos'  => $item['photos'],
			'badge'   => '',
		);
	}
	return apply_filters( 'premium_shop_hero_extra_slides', $slides );
}

/**
 * Print one automatic slide.
 *
 * @param array $slide Slide.
 * @param int   $index Position (1-based, after the main hero).
 * @param int   $total Number of slides.
 */
function premium_shop_hero_slide( $slide, $index, $total ) {
	?>
	<section class="ps-hero ps-hero--split ps-hero-slide" role="group" aria-roledescription="<?php esc_attr_e( 'slide', 'premium-shop' ); ?>" aria-label="<?php echo esc_attr( sprintf( '%1$d / %2$d', $index + 1, $total ) ); ?>" aria-hidden="true" inert>
		<div class="ps-container ps-hero__inner">
			<div class="ps-hero__content">
				<?php if ( $slide['eyebrow'] ) : ?>
					<p class="ps-eyebrow ps-hero__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></p>
				<?php endif; ?>
				<p class="ps-hero__title"><?php echo esc_html( $slide['title'] ); ?></p>
				<?php if ( $slide['text'] ) : ?>
					<p class="ps-hero__subtitle"><?php echo esc_html( $slide['text'] ); ?></p>
				<?php endif; ?>
				<div class="ps-hero__actions">
					<a class="ps-btn ps-btn--primary ps-btn--lg" href="<?php echo esc_url( $slide['url'] ); ?>" tabindex="-1">
						<span><?php echo esc_html( $slide['button'] ); ?></span>
						<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
					</a>
				</div>
			</div>
			<div class="ps-hero__visual ps-hero__visual--photos" aria-hidden="true">
				<div class="ps-phero__photos ps-phero__photos--<?php echo count( $slide['photos'] ); ?> ps-hero__collage">
					<?php foreach ( $slide['photos'] as $i => $photo ) : ?>
						<figure class="ps-phero__photo ps-phero__photo--<?php echo (int) $i + 1; ?>">
							<?php echo wp_get_attachment_image( $photo, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
						</figure>
					<?php endforeach; ?>
					<span class="ps-phero__ring"></span>
				</div>
				<?php if ( $slide['badge'] ) : ?>
					<span class="ps-hero-slide__badge"><?php echo esc_html( $slide['badge'] ); ?></span>
				<?php endif; ?>
				<span class="ps-hero__orb ps-hero__orb--1"></span>
				<span class="ps-hero__orb ps-hero__orb--2"></span>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Carousel controls.
 *
 * @param int $total Number of slides.
 */
function premium_shop_hero_slider_controls( $total ) {
	?>
	<div class="ps-container ps-hero-slider__controls">
		<button type="button" class="ps-hero-slider__arrow" data-ps-slide-prev aria-label="<?php esc_attr_e( 'Previous slide', 'premium-shop' ); ?>"><?php premium_shop_icon( 'arrow-left', array( 'size' => 18 ) ); ?></button>
		<div class="ps-hero-slider__dots" role="tablist">
			<?php for ( $i = 0; $i < $total; $i++ ) : ?>
				<button type="button" class="ps-hero-slider__dot" data-ps-slide-dot="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( '%1$d / %2$d', $i + 1, $total ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>><span></span></button>
			<?php endfor; ?>
		</div>
		<button type="button" class="ps-hero-slider__arrow" data-ps-slide-next aria-label="<?php esc_attr_e( 'Next slide', 'premium-shop' ); ?>"><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></button>
	</div>
	<?php
}
