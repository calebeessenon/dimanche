<?php
/**
 * Distraction-free checkout header: logo, secure badge, back to cart.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="ps-header ps-header--checkout">
	<div class="ps-header__inner ps-container">
		<a class="ps-link-back" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<?php premium_shop_icon( 'arrow-left', array( 'size' => 18 ) ); ?>
			<span><?php esc_html_e( 'Back to cart', 'premium-shop' ); ?></span>
		</a>
		<div class="ps-header__brand">
			<?php premium_shop_logo(); ?>
		</div>
		<p class="ps-secure-badge">
			<?php premium_shop_icon( 'lock', array( 'size' => 18 ) ); ?>
			<span><?php esc_html_e( 'Secure checkout', 'premium-shop' ); ?></span>
		</p>
	</div>
</header>
