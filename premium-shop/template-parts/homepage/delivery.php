<?php
/**
 * Homepage: delivery check by postcode.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( ! premium_shop_is_wc() || ! premium_shop_option( 'fw_delivery_check' ) ) {
	return;
}
?>
<section class="ps-section ps-delivery-section" id="ps-delivery" aria-labelledby="ps-delivery-title">
	<div class="ps-container">
		<div class="ps-delivery-section__card" data-reveal>
			<div class="ps-delivery-section__intro">
				<p class="ps-eyebrow"><?php esc_html_e( 'Delivery area', 'premium-shop' ); ?></p>
				<h2 class="ps-section-title" id="ps-delivery-title"><?php esc_html_e( 'Do we deliver to you?', 'premium-shop' ); ?></h2>
				<p class="ps-section-lead"><?php esc_html_e( 'Enter your postcode to see delivery options and costs instantly.', 'premium-shop' ); ?></p>
			</div>
			<?php premium_shop_delivery_check(); ?>
		</div>
	</div>
</section>
