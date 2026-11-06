<?php
/**
 * Cart & checkout: progress steps, recommendations, reassurance.
 *
 * Works with both the classic (shortcode) and the block-based cart/checkout.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout progress steps (Cart → Checkout → Confirmation).
 */
function premium_shop_checkout_steps() {
	if ( is_wc_endpoint_url( 'order-received' ) ) {
		$current = 3;
	} elseif ( is_checkout() ) {
		$current = 2;
	} elseif ( is_cart() ) {
		$current = 1;
	} else {
		return;
	}

	$steps = array(
		1 => array( __( 'Cart', 'premium-shop' ), wc_get_cart_url() ),
		2 => array( __( 'Checkout', 'premium-shop' ), wc_get_checkout_url() ),
		3 => array( __( 'Confirmation', 'premium-shop' ), '' ),
	);
	?>
	<nav class="ps-steps" aria-label="<?php esc_attr_e( 'Order progress', 'premium-shop' ); ?>">
		<ol class="ps-steps__list">
			<?php foreach ( $steps as $number => $step ) : ?>
				<?php
				$state = $number < $current ? 'done' : ( $number === $current ? 'current' : 'todo' );
				?>
				<li class="ps-steps__item is-<?php echo esc_attr( $state ); ?>"<?php echo 'current' === $state ? ' aria-current="step"' : ''; ?>>
					<?php if ( 'done' === $state && $step[1] && 3 !== $current ) : ?>
						<a href="<?php echo esc_url( $step[1] ); ?>">
					<?php endif; ?>
					<span class="ps-steps__num" aria-hidden="true">
						<?php
						if ( 'done' === $state ) {
							premium_shop_icon( 'check', array( 'size' => 14 ) );
						} else {
							echo esc_html( $number );
						}
						?>
					</span>
					<span class="ps-steps__label"><?php echo esc_html( $step[0] ); ?></span>
					<?php if ( 'done' === $state && $step[1] && 3 !== $current ) : ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Keep only products a customer can buy now (published, visible, in stock).
 *
 * @param int[] $ids Product IDs.
 * @return int[]
 */
function premium_shop_recommendable_ids( $ids ) {
	$keep = array();
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && $product->is_visible() && $product->is_in_stock() && $product->is_purchasable() ) {
			$keep[] = (int) $id;
		}
	}
	return $keep;
}

/**
 * Recommended products for the cart ("You may also like").
 *
 * Cross-sells of the cart items first, completed by bestsellers.
 */
function premium_shop_cart_recommendations() {
	if ( ! premium_shop_option( 'cart_recommendations' ) || ! WC()->cart ) {
		return;
	}

	$in_cart = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$in_cart[] = (int) $item['product_id'];
	}

	$ids = premium_shop_recommendable_ids( array_diff( array_map( 'absint', WC()->cart->get_cross_sells() ), $in_cart ) );
	$ids = array_slice( $ids, 0, 4 );

	if ( count( $ids ) < 4 ) {
		$fill = premium_shop_product_query( 'bestsellers', 12, array_merge( $in_cart, $ids ) );
		$ids  = array_slice( array_merge( $ids, premium_shop_recommendable_ids( wp_list_pluck( $fill->posts, 'ID' ) ) ), 0, 4 );
	}

	if ( ! $ids ) {
		return;
	}

	$query = premium_shop_product_query( 'ids', 4, array(), $ids );
	?>
	<section class="ps-section ps-recommend" aria-labelledby="ps-recommend-title">
		<?php
		premium_shop_section_heading(
			array(
				'title' => __( 'You may also like', 'premium-shop' ),
				'id'    => 'ps-recommend-title',
			)
		);
		premium_shop_render_products( $query, array( 'class' => 'columns-4' ) );
		?>
	</section>
	<?php
}

// Classic cart: replace the cross-sells column with a full-width section.
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
add_action( 'woocommerce_after_cart', 'premium_shop_cart_recommendations' );

/**
 * Block cart: free-shipping bar before, recommendations after the cart block.
 *
 * @param string $content Block HTML.
 * @param array  $block   Block data.
 * @return string
 */
function premium_shop_cart_block_recommendations( $content, $block ) {
	// The theme section replaces the block's own cross-sells (no duplicate suggestions).
	if ( 'woocommerce/cart-cross-sells-block' === $block['blockName'] && premium_shop_option( 'cart_recommendations' ) ) {
		return '';
	}

	if ( 'woocommerce/cart' !== $block['blockName'] || is_admin() || ! WC()->cart || WC()->cart->is_empty() ) {
		return $content;
	}

	ob_start();
	premium_shop_free_shipping_bar();
	$bar = ob_get_clean();

	ob_start();
	premium_shop_cart_recommendations();
	return $bar . $content . ob_get_clean();
}
add_filter( 'render_block', 'premium_shop_cart_block_recommendations', 10, 2 );

/**
 * Reassurance under the place-order button (classic checkout).
 */
function premium_shop_checkout_reassurance() {
	$days = absint( premium_shop_option( 'returns_days' ) );
	?>
	<ul class="ps-checkout-trust">
		<li><?php premium_shop_icon( 'lock', array( 'size' => 16 ) ); ?><span><?php esc_html_e( 'SSL-encrypted payment', 'premium-shop' ); ?></span></li>
		<?php if ( $days ) : ?>
			<?php /* translators: %d: number of days. */ ?>
			<li><?php premium_shop_icon( 'return', array( 'size' => 16 ) ); ?><span><?php echo esc_html( sprintf( __( '%d-day free returns', 'premium-shop' ), $days ) ); ?></span></li>
		<?php endif; ?>
		<li><?php premium_shop_icon( 'support', array( 'size' => 16 ) ); ?><span><?php esc_html_e( 'Personal customer service', 'premium-shop' ); ?></span></li>
	</ul>
	<?php
}
add_action( 'woocommerce_review_order_after_submit', 'premium_shop_checkout_reassurance' );

/**
 * Coupon form on the classic checkout: compact toggle text.
 *
 * @param string $message Message.
 * @return string
 */
function premium_shop_checkout_coupon_message( $message ) {
	return esc_html__( 'Have a coupon?', 'premium-shop' ) . ' <a href="#" class="showcoupon">' . esc_html__( 'Enter your code', 'premium-shop' ) . '</a>';
}
add_filter( 'woocommerce_checkout_coupon_message', 'premium_shop_checkout_coupon_message' );

/**
 * Thank-you page: friendly intro.
 *
 * @param string   $text  Text.
 * @param WC_Order $order Order.
 * @return string
 */
function premium_shop_thankyou_text( $text, $order ) {
	if ( ! $order ) {
		return $text;
	}
	/* translators: %s: customer first name. */
	$hello = $order->get_billing_first_name() ? sprintf( __( 'Thank you, %s!', 'premium-shop' ), $order->get_billing_first_name() ) : __( 'Thank you!', 'premium-shop' );

	return '<span class="ps-thankyou__title">' . esc_html( $hello ) . '</span> ' . esc_html__( 'Your order has been received. You will receive a confirmation by email shortly.', 'premium-shop' );
}
add_filter( 'woocommerce_thankyou_order_received_text', 'premium_shop_thankyou_text', 10, 2 );

/**
 * Empty cart: show popular products to restart shopping.
 */
function premium_shop_empty_cart_products() {
	$query = premium_shop_product_query( 'bestsellers', 4 );
	if ( ! $query->have_posts() ) {
		return;
	}
	?>
	<section class="ps-section ps-recommend" aria-labelledby="ps-empty-cart-title">
		<?php
		premium_shop_section_heading(
			array(
				'title' => __( 'Popular products', 'premium-shop' ),
				'id'    => 'ps-empty-cart-title',
				'link'  => premium_shop_shop_url(),
			)
		);
		premium_shop_render_products( $query, array( 'class' => 'columns-4' ) );
		?>
	</section>
	<?php
}
add_action( 'woocommerce_cart_is_empty', 'premium_shop_empty_cart_products', 20 );
