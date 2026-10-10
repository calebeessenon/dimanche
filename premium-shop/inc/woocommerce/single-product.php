<?php
/**
 * Product page: gallery wrapper, badges, video, trust box, "Buy now",
 * shipping & returns tab and sticky mobile add-to-cart bar.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

// Badges are rendered by the theme on top of the gallery.
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

/**
 * Open the media column (badges + gallery + video).
 */
function premium_shop_product_media_open() {
	global $product;
	echo '<div class="ps-product-media">';
	if ( $product instanceof WC_Product ) {
		premium_shop_product_badges( $product );
		if ( premium_shop_option( 'card_wishlist' ) ) {
			printf(
				'<button type="button" class="ps-card__action ps-product-media__wishlist" data-ps-wishlist="%1$d" aria-pressed="false" aria-label="%2$s">%3$s</button>',
				absint( $product->get_id() ),
				esc_attr__( 'Add to wishlist', 'premium-shop' ),
				premium_shop_get_icon( 'heart', array( 'size' => 20 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
			);
		}
	}
}
add_action( 'woocommerce_before_single_product_summary', 'premium_shop_product_media_open', 5 );

/**
 * Video button + close the media column.
 */
function premium_shop_product_media_close() {
	global $product;

	$url = $product instanceof WC_Product ? (string) $product->get_meta( '_ps_video_url' ) : '';

	if ( $url ) {
		$embed = premium_shop_video_embed( $url );
		if ( $embed ) {
			?>
			<button type="button" class="ps-video-btn" data-ps-video>
				<?php premium_shop_icon( 'play', array( 'size' => 22 ) ); ?>
				<span><?php esc_html_e( 'Watch the video', 'premium-shop' ); ?></span>
			</button>
			<template data-ps-video-template><?php echo $embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from oEmbed / wp_video_shortcode. ?></template>
			<?php
		}
	}

	echo '</div>';
}
add_action( 'woocommerce_before_single_product_summary', 'premium_shop_product_media_close', 25 );

/**
 * Embed HTML for a product video (YouTube, Vimeo… or a video file).
 *
 * @param string $url Video URL.
 * @return string
 */
function premium_shop_video_embed( $url ) {
	$url = esc_url_raw( $url );
	if ( ! $url ) {
		return '';
	}

	if ( preg_match( '/\.(mp4|webm|ogv|mov)(\?.*)?$/i', $url ) ) {
		return wp_video_shortcode(
			array(
				'src'      => $url,
				'autoplay' => 'on',
				'preload'  => 'metadata',
			)
		);
	}

	$embed = wp_oembed_get( $url, array( 'width' => 1280 ) );
	if ( ! $embed ) {
		return '';
	}

	// Autoplay when the visitor explicitly asked for the video.
	return preg_replace_callback(
		'/src="([^"]+)"/',
		static function ( $m ) {
			return 'src="' . esc_url( add_query_arg( array( 'autoplay' => 1, 'rel' => 0 ), html_entity_decode( $m[1] ) ) ) . '" allow="autoplay; fullscreen; picture-in-picture"';
		},
		$embed,
		1
	);
}

/**
 * Eyebrow above the title: main category + brand.
 */
function premium_shop_product_eyebrow() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$parts = array();
	$cat   = premium_shop_card_category( $product );
	if ( $cat ) {
		$parts[] = $cat;
	}

	$brand_tax = premium_shop_brand_taxonomy();
	if ( $brand_tax ) {
		$brands = get_the_terms( $product->get_id(), $brand_tax );
		if ( $brands && ! is_wp_error( $brands ) ) {
			$parts[] = $brands[0]->name;
		}
	}

	if ( $parts ) {
		echo '<p class="ps-eyebrow ps-product-eyebrow">' . esc_html( implode( ' · ', $parts ) ) . '</p>';
	}
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_product_eyebrow', 3 );

/**
 * SKU right under the title.
 */
function premium_shop_product_sku() {
	global $product;
	if ( $product instanceof WC_Product && wc_product_sku_enabled() && $product->get_sku() ) {
		printf(
			'<p class="ps-product-sku">%1$s <span class="sku">%2$s</span></p>',
			esc_html__( 'Item no.:', 'premium-shop' ),
			esc_html( $product->get_sku() )
		);
	}
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_product_sku', 6 );

/**
 * Reassurance box (delivery, returns, warranty, secure payment).
 */
function premium_shop_product_trust() {
	if ( ! premium_shop_option( 'product_trust' ) ) {
		return;
	}

	global $product;
	$days  = absint( premium_shop_option( 'returns_days' ) );
	$items = array();

	if ( $product instanceof WC_Product && $product->needs_shipping() ) {
		$threshold = premium_shop_free_shipping_threshold();
		$items[]   = array(
			'truck',
			premium_shop_text( 'delivery_time' ),
			/* translators: %s: amount. */
			$threshold > 0 ? sprintf( __( 'Free shipping from %s', 'premium-shop' ), premium_shop_plain_price( $threshold ) ) : '',
		);
	} elseif ( $product instanceof WC_Product && ( $product->is_downloadable() || $product->is_virtual() ) ) {
		$items[] = array( 'download', __( 'Instant access', 'premium-shop' ), __( 'Available right after payment', 'premium-shop' ) );
	}

	if ( $days && $product instanceof WC_Product && $product->needs_shipping() ) {
		/* translators: %d: number of days. */
		$items[] = array( 'return', sprintf( __( '%d-day free returns', 'premium-shop' ), $days ), __( 'Simple and hassle-free', 'premium-shop' ) );
	}

	$warranty = premium_shop_text( 'warranty_text' );
	if ( $warranty ) {
		$items[] = array( 'shield', $warranty, '' );
	}

	$items[] = array( 'lock', premium_shop_text( 'payment_text' ), '' );

	$items = apply_filters( 'premium_shop_product_trust_items', $items );
	?>
	<ul class="ps-trust">
		<?php foreach ( $items as $item ) : ?>
			<?php
			if ( ! $item[1] ) {
				continue;
			}
			?>
			<li class="ps-trust__item">
				<span class="ps-trust__icon"><?php premium_shop_icon( $item[0], array( 'size' => 20 ) ); ?></span>
				<span class="ps-trust__text">
					<strong><?php echo esc_html( $item[1] ); ?></strong>
					<?php if ( ! empty( $item[2] ) ) : ?>
						<span><?php echo esc_html( $item[2] ); ?></span>
					<?php endif; ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php premium_shop_payment_badges(); ?>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_product_trust', 35 );

/**
 * "Buy now" button next to "Add to cart".
 */
function premium_shop_buy_now_button() {
	global $product;

	if ( ! premium_shop_option( 'product_buy_now' ) || ! $product instanceof WC_Product ) {
		return;
	}
	if ( ! $product->is_type( array( 'simple', 'variable' ) ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}

	if ( $product->is_type( 'simple' ) ) {
		printf( '<input type="hidden" name="add-to-cart" value="%d" />', absint( $product->get_id() ) );
	}

	printf(
		'<button type="submit" name="ps_buy_now" value="1" class="ps-btn ps-btn--accent ps-buy-now" data-ps-buy-now>%1$s%2$s</button>',
		esc_html__( 'Buy now', 'premium-shop' ),
		premium_shop_get_icon( 'arrow', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'premium_shop_buy_now_button' );

/**
 * Redirect "Buy now" straight to the checkout.
 *
 * @param string $url Redirect URL.
 * @return string
 */
function premium_shop_buy_now_redirect( $url ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- WooCommerce handles the add-to-cart request.
	if ( ! empty( $_REQUEST['ps_buy_now'] ) && 0 === wc_notice_count( 'error' ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'premium_shop_buy_now_redirect', 99 );

/**
 * "Shipping & returns" tab.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function premium_shop_product_tabs( $tabs ) {
	global $product;

	if ( $product instanceof WC_Product && ( $product->needs_shipping() || premium_shop_option( 'shipping_tab' ) ) ) {
		$tabs['ps_shipping'] = array(
			'title'    => __( 'Shipping & returns', 'premium-shop' ),
			'priority' => 40,
			'callback' => 'premium_shop_shipping_tab_content',
		);
	}

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'premium_shop_product_tabs' );

/**
 * Content of the "Shipping & returns" tab.
 */
function premium_shop_shipping_tab_content() {
	$custom = trim( (string) get_theme_mod( 'ps_shipping_tab', '' ) );

	echo '<h2>' . esc_html__( 'Shipping & returns', 'premium-shop' ) . '</h2>';

	if ( $custom ) {
		echo wp_kses_post( wpautop( premium_shop_translate_value( $custom, 'shipping_tab' ) ) );
		return;
	}

	$threshold = premium_shop_free_shipping_threshold();
	$days      = absint( premium_shop_option( 'returns_days' ) );
	$shipping  = premium_shop_page_url_by_slugs( array( 'versand-und-zahlung', 'versand', 'shipping', 'livraison', 'envio' ) );
	?>
	<div class="ps-shipping-tab">
		<div class="ps-shipping-tab__item">
			<?php premium_shop_icon( 'truck', array( 'size' => 24 ) ); ?>
			<h3><?php esc_html_e( 'Delivery', 'premium-shop' ); ?></h3>
			<p>
				<?php echo esc_html( premium_shop_text( 'delivery_time' ) ); ?>.
				<?php
				if ( $threshold > 0 ) {
					/* translators: %s: amount. */
					echo esc_html( sprintf( __( 'Free shipping from %s.', 'premium-shop' ), premium_shop_plain_price( $threshold ) ) );
				}
				?>
			</p>
		</div>
		<?php if ( $days ) : ?>
			<div class="ps-shipping-tab__item">
				<?php premium_shop_icon( 'return', array( 'size' => 24 ) ); ?>
				<h3><?php esc_html_e( 'Returns', 'premium-shop' ); ?></h3>
				<p>
					<?php
					/* translators: %d: number of days. */
					echo esc_html( sprintf( __( 'You can return your order within %d days of delivery.', 'premium-shop' ), $days ) );
					?>
				</p>
			</div>
		<?php endif; ?>
		<div class="ps-shipping-tab__item">
			<?php premium_shop_icon( 'lock', array( 'size' => 24 ) ); ?>
			<h3><?php esc_html_e( 'Payment', 'premium-shop' ); ?></h3>
			<p><?php echo esc_html( premium_shop_text( 'payment_text' ) ); ?>.</p>
		</div>
	</div>
	<?php if ( $shipping ) : ?>
		<p><a class="ps-link-arrow" href="<?php echo esc_url( $shipping ); ?>"><?php esc_html_e( 'All shipping & payment information', 'premium-shop' ); ?><?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?></a></p>
	<?php endif; ?>
	<?php
}

/**
 * Gallery slider options: thumbnails + arrows.
 *
 * @param array $options Flexslider options.
 * @return array
 */
function premium_shop_gallery_options( $options ) {
	$options['directionNav'] = true;
	$options['controlNav']   = 'thumbnails';
	$options['animationSpeed'] = 450;
	return $options;
}
add_filter( 'woocommerce_single_product_carousel_options', 'premium_shop_gallery_options' );

/**
 * Sticky add-to-cart bar on mobile.
 */
function premium_shop_sticky_add_to_cart() {
	if ( ! is_product() || ! premium_shop_option( 'product_sticky_bar' ) ) {
		return;
	}

	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	?>
	<div class="ps-sticky-cart" data-ps-sticky-cart hidden>
		<div class="ps-sticky-cart__info">
			<?php echo wp_kses_post( $product->get_image( 'woocommerce_gallery_thumbnail', array( 'loading' => 'lazy' ) ) ); ?>
			<div>
				<p class="ps-sticky-cart__name"><?php echo esc_html( $product->get_name() ); ?></p>
				<p class="ps-sticky-cart__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
			</div>
		</div>
		<button type="button" class="ps-btn ps-btn--primary" data-ps-sticky-cart-btn>
			<?php echo $product->is_type( 'simple' ) ? esc_html__( 'Add to cart', 'premium-shop' ) : esc_html__( 'Choose options', 'premium-shop' ); ?>
		</button>
	</div>
	<?php
}
add_action( 'wp_footer', 'premium_shop_sticky_add_to_cart', 5 );
