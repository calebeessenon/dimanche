<?php
/**
 * Lively page headers: warm gradient, rising embers, floating product photos
 * (taken from the shop's own catalogue, or from the cart on the cart and
 * checkout pages), a tagline per page and reassurance chips.
 *
 * Customizer → Premium Shop → General → "Illustrated page headers".
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether illustrated page headers are enabled.
 *
 * @return bool
 */
function premium_shop_page_hero_enabled() {
	return (bool) apply_filters( 'premium_shop_page_hero_enabled', premium_shop_option( 'page_hero' ) );
}

/**
 * Attachment IDs of published product photos (cached).
 *
 * @return int[]
 */
function premium_shop_hero_photo_pool() {
	$pool = get_transient( 'premium_shop_hero_pool' );
	if ( is_array( $pool ) ) {
		return array_map( 'absint', $pool );
	}

	$pool = array();
	if ( premium_shop_is_wc() ) {
		$ids = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'posts_per_page'   => 40,
				'fields'           => 'ids',
				'orderby'          => 'menu_order date',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'       => array(
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		foreach ( $ids as $id ) {
			$thumb = (int) get_post_thumbnail_id( $id );
			if ( $thumb && ! in_array( $thumb, $pool, true ) ) {
				$pool[] = $thumb;
			}
		}
	}

	set_transient( 'premium_shop_hero_pool', $pool, DAY_IN_SECONDS );
	return $pool;
}

/**
 * Forget the cached photos when products change.
 *
 * @param int $post_id Post ID.
 */
function premium_shop_hero_flush_pool( $post_id = 0 ) {
	if ( ! $post_id || 'product' === get_post_type( $post_id ) ) {
		delete_transient( 'premium_shop_hero_pool' );
	}
}
add_action( 'save_post', 'premium_shop_hero_flush_pool' );
add_action( 'deleted_post', 'premium_shop_hero_flush_pool' );
add_action( 'woocommerce_product_import_inserted_product_object', 'premium_shop_hero_flush_pool' );

/**
 * Photos for a page header.
 *
 * @param int $count How many.
 * @param int $seed  Changes the selection from page to page.
 * @return array[] List of array( 'src' => …, 'alt' => … ).
 */
function premium_shop_hero_photos( $count = 3, $seed = 0 ) {
	$photos = array();
	$ids    = array();

	// Cart and checkout: the products the visitor is buying.
	if ( premium_shop_is_wc() && ( is_cart() || is_checkout() ) && WC()->cart && ! WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;
			$image   = $product instanceof WC_Product ? (int) $product->get_image_id() : 0;
			if ( ! $image && $product instanceof WC_Product && $product->get_parent_id() ) {
				$image = (int) get_post_thumbnail_id( $product->get_parent_id() );
			}
			if ( $image && ! in_array( $image, $ids, true ) ) {
				$ids[] = $image;
			}
		}
	}

	if ( has_post_thumbnail() && ! ( premium_shop_is_wc() && ( is_cart() || is_checkout() || is_account_page() ) ) ) {
		array_unshift( $ids, (int) get_post_thumbnail_id() );
	}

	$pool = premium_shop_hero_photo_pool();
	if ( $pool && count( $ids ) < $count ) {
		$offset = $seed % count( $pool );
		$pool   = array_merge( array_slice( $pool, $offset ), array_slice( $pool, 0, $offset ) );
		// Spread the picks over the catalogue (different products, not neighbours).
		$total = count( $pool );
		$step  = max( 1, (int) floor( $total / $count ) );
		for ( $i = 0; $i < $total; $i += $step ) {
			if ( ! in_array( $pool[ $i ], $ids, true ) ) {
				$ids[] = $pool[ $i ];
			}
			if ( count( $ids ) >= $count ) {
				break;
			}
		}
	}

	foreach ( array_slice( $ids, 0, $count ) as $id ) {
		$src = wp_get_attachment_image_src( $id, 'woocommerce_thumbnail' );
		if ( ! $src ) {
			$src = wp_get_attachment_image_src( $id, 'medium_large' );
		}
		if ( $src ) {
			$photos[] = array(
				'src' => $src[0],
				'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}
	}

	// Fresh shop without photos: the bundled firewood illustrations.
	if ( ! $photos && 'firewood' === premium_shop_preset() ) {
		$files = array( 'buche.jpg', 'box.jpg', 'birke.jpg', 'eiche.jpg', 'briketts.jpg', 'mischholz.jpg' );
		for ( $i = 0; $i < $count; $i++ ) {
			$photos[] = array(
				'src' => PREMIUM_SHOP_URI . '/assets/images/firewood/' . $files[ ( $seed + $i ) % count( $files ) ],
				'alt' => '',
			);
		}
	}

	return apply_filters( 'premium_shop_hero_photos', $photos, $count, $seed );
}

/**
 * What the header of the current page shows.
 *
 * @return array
 */
function premium_shop_page_hero_context() {
	$wc      = premium_shop_is_wc();
	$page_id = (int) get_queried_object_id();
	$context = array(
		'variant'  => 'page',
		'eyebrow'  => get_bloginfo( 'name' ),
		'title'    => get_the_title(),
		'subtitle' => has_excerpt() ? get_the_excerpt() : '',
		'art'      => 'photos',
		'steps'    => false,
		'chips'    => true,
		'icon'     => 'sparkle',
	);

	if ( $wc && is_cart() ) {
		$context = array_merge(
			$context,
			array(
				'variant'  => 'cart',
				'eyebrow'  => __( 'Your cart', 'premium-shop' ),
				'subtitle' => WC()->cart && ! WC()->cart->is_empty() ? __( 'Almost there: check your selection, and the warmth is on its way.', 'premium-shop' ) : __( 'Your cart is still empty. Let yourself be inspired by our products.', 'premium-shop' ),
				'steps'    => true,
				'icon'     => 'bag',
			)
		);
	} elseif ( $wc && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
		$context = array_merge(
			$context,
			array(
				'variant'  => 'checkout',
				'eyebrow'  => __( 'Secure checkout', 'premium-shop' ),
				'subtitle' => __( 'Just a few details and your order is on its way.', 'premium-shop' ),
				'steps'    => true,
				'icon'     => 'lock',
			)
		);
	} elseif ( $wc && is_account_page() ) {
		$user    = wp_get_current_user();
		$context = array_merge(
			$context,
			array(
				'variant'  => 'account',
				'eyebrow'  => __( 'Customer account', 'premium-shop' ),
				/* translators: %s: customer first name. */
				'title'    => is_user_logged_in() ? sprintf( __( 'Hello, %s', 'premium-shop' ), $user->first_name ? $user->first_name : $user->display_name ) : get_the_title(),
				'subtitle' => is_user_logged_in() ? __( 'Good to see you again. Here you will find your orders, addresses and account details.', 'premium-shop' ) : __( 'Sign in to follow your orders, order again in one click and save your addresses.', 'premium-shop' ),
				'icon'     => 'user',
			)
		);
	} elseif ( $wc && function_exists( 'premium_shop_is_tracking_page' ) && premium_shop_is_tracking_page( $page_id ) ) {
		$context = array_merge(
			$context,
			array(
				'variant'  => 'tracking',
				'eyebrow'  => __( 'Order tracking', 'premium-shop' ),
				'subtitle' => __( 'Where is my wood? Follow your delivery step by step.', 'premium-shop' ),
				'art'      => 'truck',
				'icon'     => 'truck',
			)
		);
	} elseif ( is_page_template( 'page-templates/template-contact.php' ) ) {
		$context = array_merge(
			$context,
			array(
				'variant'  => 'contact',
				'eyebrow'  => __( 'We are here for you', 'premium-shop' ),
				'subtitle' => __( 'A question about our products, a delivery or an order? Write to us, we answer quickly.', 'premium-shop' ),
				'icon'     => 'mail',
			)
		);
	} elseif ( is_page_template( 'page-templates/template-wishlist.php' ) ) {
		$context = array_merge(
			$context,
			array(
				'variant'  => 'wishlist',
				'eyebrow'  => __( 'Your favourites', 'premium-shop' ),
				'subtitle' => __( 'The products you love, kept for later.', 'premium-shop' ),
				'icon'     => 'heart',
			)
		);
	}

	return apply_filters( 'premium_shop_page_hero_context', $context, $page_id );
}

/**
 * Reassurance chips.
 *
 * @return array[] array( icon, text ).
 */
function premium_shop_page_hero_chips() {
	$chips = array();
	if ( premium_shop_is_wc() ) {
		$chips[] = array( 'truck', premium_shop_text( 'delivery_time' ) );
	}
	$chips[] = array( 'lock', premium_shop_text( 'payment_text' ) );
	$chips[] = array( 'support', __( 'Personal advice', 'premium-shop' ) );
	if ( 'firewood' === premium_shop_preset() ) {
		$chips[] = array( 'flame', __( 'Dry wood, ready to burn', 'premium-shop' ) );
	}
	$chips = array_filter(
		$chips,
		function ( $chip ) {
			return '' !== trim( (string) $chip[1] );
		}
	);
	return apply_filters( 'premium_shop_page_hero_chips', array_values( $chips ) );
}

/**
 * Print the page header.
 *
 * @param array $override Values replacing the automatic ones (title, subtitle…).
 */
function premium_shop_page_hero( $override = array() ) {
	$hero  = array_merge( premium_shop_page_hero_context(), $override );
	$class = 'ps-phero ps-phero--banner ps-phero--' . sanitize_html_class( $hero['variant'] );
	if ( in_array( $hero['variant'], array( 'cart', 'checkout' ), true ) ) {
		$class .= ' ps-phero--compact';
	}
	if ( 'truck' !== $hero['art'] ) {
		$class .= ' ps-phero--no-art';
	}
	// A page with a featured image uses it as the banner background.
	$style = '';
	if ( 'page' === $hero['variant'] && has_post_thumbnail() ) {
		$bg = wp_get_attachment_image_url( (int) get_post_thumbnail_id(), 'full' );
		if ( $bg ) {
			$style = '--ps-hero-bg:url(' . esc_url( $bg ) . ')';
		}
	}
	?>
	<header class="<?php echo esc_attr( $class ); ?>"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
		<div class="ps-phero__glow" aria-hidden="true"></div>
		<div class="ps-phero__embers" aria-hidden="true">
			<?php
			for ( $i = 0; $i < 14; $i++ ) {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- numbers only.
				printf(
					'<span style="--x:%1$d%%;--d:%2$.1fs;--t:%3$.1fs;--s:%4$dpx"></span>',
					( $i * 37 + 11 ) % 100,
					fmod( $i * 1.3, 7 ),
					6 + ( $i % 5 ) * 1.4,
					3 + ( $i * 7 ) % 5
				);
				// phpcs:enable
			}
			?>
		</div>
		<div class="ps-container ps-phero__inner">
			<div class="ps-phero__text">
				<?php premium_shop_breadcrumbs(); ?>
				<p class="ps-phero__eyebrow">
					<span class="ps-phero__eyebrow-icon"><?php premium_shop_icon( $hero['icon'], array( 'size' => 16 ) ); ?></span>
					<?php echo esc_html( wp_strip_all_tags( $hero['eyebrow'] ) ); ?>
				</p>
				<h1 class="ps-phero__title"><?php echo esc_html( wp_strip_all_tags( $hero['title'] ) ); ?></h1>
				<?php if ( $hero['subtitle'] ) : ?>
					<p class="ps-phero__subtitle"><?php echo esc_html( wp_strip_all_tags( $hero['subtitle'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( 'cart' === $hero['variant'] && WC()->cart && WC()->cart->is_empty() ) : ?>
					<p class="ps-phero__cta"><a class="ps-btn ps-btn--accent" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Discover the shop', 'premium-shop' ); ?> <?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?></a></p>
				<?php elseif ( $hero['steps'] ) : ?>
					<?php premium_shop_checkout_steps(); ?>
				<?php elseif ( $hero['chips'] && ! empty( $hero['show_chips'] ) ) : ?>
					<ul class="ps-phero__chips">
						<?php foreach ( premium_shop_page_hero_chips() as $i => $chip ) : ?>
							<li style="--i:<?php echo (int) $i; ?>"><?php premium_shop_icon( $chip[0], array( 'size' => 16 ) ); ?><span><?php echo esc_html( $chip[1] ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( 'truck' === $hero['art'] ) : ?>
				<div class="ps-phero__art ps-phero__art--truck" aria-hidden="true">
					<?php get_template_part( 'template-parts/components/truck-art' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<svg class="ps-phero__wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 34c160 22 320 26 480 12S800 0 960 8s320 38 480 30v22H0Z"/></svg>
	</header>
	<?php
}

/**
 * Celebration header of the "order received" page: animated check, confetti.
 */
function premium_shop_thanks_hero() {
	$order    = null;
	$order_id = absint( get_query_var( 'order-received' ) );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
	if ( $order_id && $key ) {
		$candidate = wc_get_order( $order_id );
		if ( $candidate && hash_equals( $candidate->get_order_key(), $key ) ) {
			$order = $candidate;
		}
	}
	$failed = $order && $order->has_status( 'failed' );
	$name   = $order ? $order->get_billing_first_name() : '';
	?>
	<header class="ps-phero ps-phero--thanks<?php echo $failed ? ' is-failed' : ''; ?>">
		<?php if ( ! $failed ) : ?>
			<canvas class="ps-confetti" data-ps-confetti aria-hidden="true"></canvas>
		<?php endif; ?>
		<div class="ps-container ps-phero__thanks">
			<?php if ( ! $failed ) : ?>
				<svg class="ps-thanks-check" viewBox="0 0 80 80" aria-hidden="true" focusable="false">
					<circle class="ps-thanks-check__circle" cx="40" cy="40" r="36"/>
					<path class="ps-thanks-check__mark" d="M24 41.5l11 11 21-23"/>
				</svg>
			<?php endif; ?>
			<h1 class="ps-phero__title">
				<?php
				if ( $failed ) {
					esc_html_e( 'Your payment could not be completed', 'premium-shop' );
				} elseif ( $name ) {
					/* translators: %s: customer first name. */
					echo esc_html( sprintf( __( 'Thank you, %s!', 'premium-shop' ), $name ) );
				} else {
					esc_html_e( 'Thank you for your order!', 'premium-shop' );
				}
				?>
			</h1>
			<?php if ( $order && ! $failed ) : ?>
				<p class="ps-phero__subtitle">
					<?php
					/* translators: 1: order number, 2: e-mail address. */
					echo esc_html( sprintf( __( 'Your order #%1$s has been received. A confirmation is on its way to %2$s.', 'premium-shop' ), $order->get_order_number(), $order->get_billing_email() ) );
					?>
				</p>
			<?php endif; ?>
			<?php premium_shop_checkout_steps(); ?>
		</div>
		<svg class="ps-phero__wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 34c160 22 320 26 480 12S800 0 960 8s320 38 480 30v22H0Z"/></svg>
	</header>
	<?php
}

/**
 * Product categories without an image show the photo of one of their
 * products (front end only; the category's own image always wins).
 *
 * @param mixed  $value     Value short-circuited so far.
 * @param int    $object_id Term ID.
 * @param string $meta_key  Meta key.
 * @param bool   $single    Single value requested.
 * @return mixed
 */
function premium_shop_category_fallback_image( $value, $object_id, $meta_key, $single ) {
	if ( null !== $value || 'thumbnail_id' !== $meta_key || is_admin() || ! premium_shop_is_wc() ) {
		return $value;
	}
	if ( ! apply_filters( 'premium_shop_category_fallback_image', true, $object_id ) ) {
		return $value;
	}

	static $map = null;
	if ( null === $map ) {
		$map = get_transient( 'premium_shop_cat_thumbs' );
		$map = is_array( $map ) ? $map : array();
	}
	$object_id = (int) $object_id;
	if ( ! array_key_exists( $object_id, $map ) ) {
		remove_filter( 'get_term_metadata', 'premium_shop_category_fallback_image', 10 );
		$own   = (int) get_term_meta( $object_id, 'thumbnail_id', true );
		$term  = get_term( $object_id );
		$thumb = 0;
		if ( ! $own && $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
			$ids = get_posts(
				array(
					'post_type'        => 'product',
					'post_status'      => 'publish',
					'posts_per_page'   => 1,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => false,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'tax_query'        => array(
						array(
							'taxonomy' => 'product_cat',
							'terms'    => $object_id,
						),
					),
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'       => array(
						array(
							'key'     => '_thumbnail_id',
							'compare' => 'EXISTS',
						),
					),
				)
			);
			$thumb = $ids ? (int) get_post_thumbnail_id( $ids[0] ) : 0;
		}
		add_filter( 'get_term_metadata', 'premium_shop_category_fallback_image', 10, 4 );
		$map[ $object_id ] = $own ? -1 : $thumb;
		set_transient( 'premium_shop_cat_thumbs', $map, DAY_IN_SECONDS );
	}

	if ( $map[ $object_id ] > 0 ) {
		return $single ? $map[ $object_id ] : array( $map[ $object_id ] );
	}
	return $value;
}
add_filter( 'get_term_metadata', 'premium_shop_category_fallback_image', 10, 4 );

/**
 * Forget the category photos when products or categories change.
 */
function premium_shop_flush_category_images() {
	delete_transient( 'premium_shop_cat_thumbs' );
}
add_action( 'save_post_product', 'premium_shop_flush_category_images' );
add_action( 'edited_product_cat', 'premium_shop_flush_category_images' );
add_action( 'created_product_cat', 'premium_shop_flush_category_images' );
add_action( 'woocommerce_product_import_inserted_product_object', 'premium_shop_flush_category_images' );

/**
 * Products shown in the bottom-of-page carousel (cached for an hour).
 *
 * @return int[]
 */
function premium_shop_showcase_product_ids() {
	$ids = get_transient( 'premium_shop_showcase_ids' );
	if ( is_array( $ids ) ) {
		return array_map( 'absint', $ids );
	}
	$ids = array();
	if ( premium_shop_is_wc() ) {
		$hidden = array_filter(
			array(
				get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility' ),
				get_term_by( 'slug', 'outofstock', 'product_visibility' ),
			)
		);
		$query  = array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'posts_per_page'   => 12,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_key'         => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'          => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
			// Only products with a photo look good in the carousel.
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		);
		if ( $hidden ) {
			$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => wp_list_pluck( $hidden, 'term_taxonomy_id' ),
					'operator' => 'NOT IN',
				),
			);
		}
		$ids = array_map( 'absint', get_posts( $query ) );
	}
	set_transient( 'premium_shop_showcase_ids', $ids, HOUR_IN_SECONDS );
	return $ids;
}
add_action(
	'save_post_product',
	static function () {
		delete_transient( 'premium_shop_showcase_ids' );
	}
);

/**
 * Bottom of the page: a scrolling band of reassurance messages and a
 * carousel of products moving gently from right to left (pauses on hover).
 *
 * @param string $variant Page variant (no carousel on the checkout).
 */
function premium_shop_page_showcase( $variant = 'page' ) {
	if ( ! premium_shop_page_hero_enabled() || ! apply_filters( 'premium_shop_page_showcase', true, $variant ) ) {
		return;
	}

	$chips = premium_shop_page_hero_chips();
	$ids   = in_array( $variant, array( 'checkout' ), true ) ? array() : premium_shop_showcase_product_ids();
	if ( count( $ids ) < 4 ) {
		$ids = array();
	}
	?>
	<section class="ps-showcase" aria-label="<?php esc_attr_e( 'Discover our products', 'premium-shop' ); ?>">
		<?php if ( $chips ) : ?>
			<div class="ps-trustbar">
				<ul class="ps-container ps-trustbar__list">
					<?php foreach ( $chips as $i => $chip ) : ?>
						<li class="ps-trustbar__item" data-reveal style="--ps-delay:<?php echo (int) $i * 80; ?>ms">
							<span class="ps-trustbar__icon"><?php premium_shop_icon( $chip[0], array( 'size' => 20 ) ); ?></span>
							<span class="ps-trustbar__text"><?php echo esc_html( $chip[1] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $ids ) : ?>
			<div class="ps-showcase__inner">
				<div class="ps-container ps-showcase__head" data-reveal>
					<div>
						<p class="ps-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
						<h2 class="ps-showcase__title"><?php esc_html_e( 'Discover our products', 'premium-shop' ); ?></h2>
					</div>
					<a class="ps-btn ps-btn--ghost" href="<?php echo esc_url( premium_shop_shop_url() ); ?>"><?php esc_html_e( 'View all products', 'premium-shop' ); ?> <?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?></a>
				</div>
				<div class="ps-marquee" data-ps-marquee>
					<div class="ps-marquee__track">
						<?php
						for ( $round = 0; $round < 2; $round++ ) {
							foreach ( $ids as $id ) {
								$product = wc_get_product( $id );
								if ( ! $product ) {
									continue;
								}
								premium_shop_showcase_card( $product, 1 === $round );
							}
						}
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * One product card of the carousel.
 *
 * @param WC_Product $product Product.
 * @param bool       $clone   Second copy used for the seamless loop (hidden from assistive technologies).
 */
function premium_shop_showcase_card( $product, $clone = false ) {
	$link    = $product->get_permalink();
	$tab     = $clone ? ' tabindex="-1"' : '';
	$can_add = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually();
	?>
	<article class="ps-mcard"<?php echo $clone ? ' aria-hidden="true"' : ''; ?>>
		<a class="ps-mcard__media" href="<?php echo esc_url( $link ); ?>"<?php echo $tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			echo wp_kses_post(
				$product->get_image(
					'woocommerce_thumbnail',
					array(
						'loading' => 'lazy',
						'class'   => 'ps-mcard__img',
					)
				)
			);
			?>
			<?php if ( $product->is_on_sale() ) : ?>
				<span class="ps-mcard__badge"><?php esc_html_e( 'Sale', 'premium-shop' ); ?></span>
			<?php endif; ?>
		</a>
		<div class="ps-mcard__body">
			<a class="ps-mcard__name" href="<?php echo esc_url( $link ); ?>"<?php echo $tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $product->get_name() ); ?></a>
			<span class="ps-mcard__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			<?php if ( $can_add ) : ?>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo (int) $product->get_id(); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" class="ps-btn ps-btn--accent ps-mcard__btn button add_to_cart_button ajax_add_to_cart" rel="nofollow"<?php echo $tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php premium_shop_icon( 'bag', array( 'size' => 16 ) ); ?><span><?php echo esc_html( $product->add_to_cart_text() ); ?></span></a>
			<?php else : ?>
				<a href="<?php echo esc_url( $link ); ?>" class="ps-btn ps-btn--ghost ps-mcard__btn"<?php echo $tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span><?php esc_html_e( 'View product', 'premium-shop' ); ?></span></a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}
