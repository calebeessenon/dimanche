<?php
/**
 * Product card helpers (used by woocommerce/content-product.php).
 *
 * The default WooCommerce loop callbacks are unhooked and replaced by the
 * theme markup, while every loop hook is still fired so that plugins
 * (wishlists, swatches, badges…) keep working.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Discount percentage for a product (max over variations).
 *
 * @param WC_Product $product Product.
 * @return int
 */
function premium_shop_discount_percent( $product ) {
	if ( ! $product->is_on_sale() ) {
		return 0;
	}

	$max = 0;

	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		foreach ( $prices['regular_price'] as $id => $regular ) {
			$sale = isset( $prices['sale_price'][ $id ] ) ? (float) $prices['sale_price'][ $id ] : 0;
			if ( (float) $regular > 0 && $sale > 0 && $sale < (float) $regular ) {
				$max = max( $max, ( (float) $regular - $sale ) / (float) $regular * 100 );
			}
		}
	} elseif ( $product->is_type( 'grouped' ) ) {
		return 0;
	} else {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $regular > 0 && $sale >= 0 && $sale < $regular ) {
			$max = ( $regular - $sale ) / $regular * 100;
		}
	}

	return (int) round( $max );
}

/**
 * Is the product "new"?
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function premium_shop_is_new( $product ) {
	$days = absint( premium_shop_option( 'badge_new_days' ) );
	if ( ! $days ) {
		return false;
	}

	$created = $product->get_date_created();

	return $created && ( time() - $created->getTimestamp() ) < $days * DAY_IN_SECONDS;
}

/**
 * Product badges (sale %, new, out of stock).
 *
 * @param WC_Product $product Product.
 */
function premium_shop_product_badges( $product ) {
	$badges = array();

	if ( ! $product->is_in_stock() ) {
		$badges[] = array( 'soldout', __( 'Sold out', 'premium-shop' ) );
	} elseif ( $product->is_on_sale() ) {
		$percent  = premium_shop_discount_percent( $product );
		$badges[] = array( 'sale', $percent ? '−' . $percent . '%' : __( 'Sale', 'premium-shop' ) );
	}

	if ( premium_shop_is_new( $product ) ) {
		$badges[] = array( 'new', __( 'New', 'premium-shop' ) );
	}

	$badges = apply_filters( 'premium_shop_product_badges', $badges, $product );

	if ( ! $badges ) {
		return;
	}

	echo '<div class="ps-badges">';
	foreach ( $badges as $badge ) {
		printf( '<span class="ps-badge ps-badge--%1$s">%2$s</span>', esc_attr( $badge[0] ), esc_html( $badge[1] ) );
	}
	echo '</div>';
}

/**
 * Short stock line for cards and product pages.
 *
 * @param WC_Product $product Product.
 */
function premium_shop_stock_label( $product ) {
	if ( ! $product->is_in_stock() ) {
		printf( '<p class="ps-stock ps-stock--out">%s</p>', esc_html__( 'Currently unavailable', 'premium-shop' ) );
		return;
	}

	if ( $product->is_on_backorder() ) {
		printf( '<p class="ps-stock ps-stock--backorder">%s</p>', esc_html__( 'Available on backorder', 'premium-shop' ) );
		return;
	}

	$qty = $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
	$low = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

	if ( null !== $qty && $qty > 0 && $qty <= max( 3, $low ) ) {
		/* translators: %d: quantity left. */
		printf( '<p class="ps-stock ps-stock--low">%s</p>', esc_html( sprintf( _n( 'Only %d left', 'Only %d left', $qty, 'premium-shop' ), $qty ) ) );
		return;
	}

	printf( '<p class="ps-stock ps-stock--in">%s</p>', esc_html__( 'In stock', 'premium-shop' ) );
}

/**
 * Second image (first gallery image) shown on hover.
 *
 * @param WC_Product $product Product.
 */
function premium_shop_card_hover_image( $product ) {
	if ( ! premium_shop_option( 'card_hover_image' ) ) {
		return;
	}

	$gallery = $product->get_gallery_image_ids();
	if ( ! $gallery ) {
		return;
	}

	echo wp_get_attachment_image(
		(int) $gallery[0],
		'woocommerce_thumbnail',
		false,
		array(
			'class'    => 'ps-card__img ps-card__img--hover',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'alt'      => '',
			'aria-hidden' => 'true',
		)
	);
}

/**
 * Wishlist & quick-view buttons on the card.
 *
 * @param WC_Product $product Product.
 */
function premium_shop_card_actions( $product ) {
	$wishlist   = premium_shop_option( 'card_wishlist' );
	$quick_view = premium_shop_option( 'card_quick_view' );

	if ( ! $wishlist && ! $quick_view ) {
		return;
	}

	$name = $product->get_name();

	echo '<div class="ps-card__actions">';

	if ( $wishlist ) {
		printf(
			'<button type="button" class="ps-card__action" data-ps-wishlist="%1$d" aria-pressed="false" aria-label="%2$s">%3$s</button>',
			absint( $product->get_id() ),
			/* translators: %s: product name. */
			esc_attr( sprintf( __( 'Add %s to wishlist', 'premium-shop' ), $name ) ),
			premium_shop_get_icon( 'heart', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
		);
	}

	if ( $quick_view ) {
		printf(
			'<button type="button" class="ps-card__action" data-ps-quick-view="%1$d" aria-haspopup="dialog" aria-label="%2$s">%3$s</button>',
			absint( $product->get_id() ),
			/* translators: %s: product name. */
			esc_attr( sprintf( __( 'Quick view: %s', 'premium-shop' ), $name ) ),
			premium_shop_get_icon( 'eye', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
		);
	}

	echo '</div>';
}

/**
 * Primary category name for the card.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function premium_shop_card_category( $product ) {
	$ids = $product->get_category_ids();
	if ( ! $ids ) {
		return '';
	}

	$default = (int) get_option( 'default_product_cat' );
	foreach ( $ids as $id ) {
		if ( (int) $id === $default ) {
			continue;
		}
		$term = get_term( $id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term->name;
		}
	}

	return '';
}

/**
 * Add-to-cart button classes in loops.
 *
 * @param array      $args    Args.
 * @param WC_Product $product Product.
 * @return array
 */
function premium_shop_loop_add_to_cart_args( $args, $product ) {
	$args['class'] .= ' ps-card__cart';
	if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) {
		/* translators: %s: product name. */
		$args['attributes']['aria-label'] = sprintf( __( 'Add “%s” to your cart', 'premium-shop' ), $product->get_name() );
	} else {
		/* translators: %s: product name. */
		$args['attributes']['aria-label'] = sprintf( __( 'View options for “%s”', 'premium-shop' ), $product->get_name() );
	}
	return $args;
}
add_filter( 'woocommerce_loop_add_to_cart_args', 'premium_shop_loop_add_to_cart_args', 10, 2 );

/**
 * Add an icon to loop add-to-cart buttons.
 *
 * @param string     $html    Button HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function premium_shop_loop_add_to_cart_link( $html, $product ) {
	$icon = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? 'plus' : 'arrow';
	return preg_replace( '/(<a[^>]*>)(.*?)(<\/a>)/s', '$1' . premium_shop_get_icon( $icon, array( 'size' => 18 ) ) . '<span class="ps-card__cart-label">$2</span>$3', $html, 1 );
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'premium_shop_loop_add_to_cart_link', 10, 2 );
