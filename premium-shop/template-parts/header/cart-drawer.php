<?php
/**
 * Side cart (mini-cart drawer). The content is refreshed by WooCommerce
 * cart fragments after each AJAX add-to-cart.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( is_cart() || is_checkout() ) {
	return;
}
?>
<div class="ps-drawer ps-drawer--right ps-cart-drawer" id="ps-cart-drawer" data-ps-drawer role="dialog" aria-modal="true" aria-labelledby="ps-cart-drawer-title" hidden>
	<div class="ps-drawer__overlay" data-ps-close></div>
	<div class="ps-drawer__panel">
		<div class="ps-drawer__head">
			<p class="ps-drawer__title" id="ps-cart-drawer-title">
				<?php esc_html_e( 'Your cart', 'premium-shop' ); ?>
				<?php premium_shop_cart_count_html( 'ps-cart-count--inline' ); ?>
			</p>
			<button type="button" class="ps-icon-btn" data-ps-close>
				<?php premium_shop_icon( 'close', array( 'size' => 22 ) ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close cart', 'premium-shop' ); ?></span>
			</button>
		</div>
		<?php premium_shop_free_shipping_bar(); ?>
		<div class="ps-drawer__body">
			<div class="widget_shopping_cart_content">
				<?php woocommerce_mini_cart(); ?>
			</div>
		</div>
	</div>
</div>
