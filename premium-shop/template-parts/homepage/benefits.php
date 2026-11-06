<?php
/**
 * Homepage: benefits (shipping, payment, returns, service).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_items = array();
for ( $premium_shop_i = 1; $premium_shop_i <= 4; $premium_shop_i++ ) {
	$premium_shop_title = premium_shop_text( 'benefit_' . $premium_shop_i . '_title' );
	if ( $premium_shop_title ) {
		$premium_shop_items[] = array(
			'icon'  => premium_shop_option( 'benefit_' . $premium_shop_i . '_icon' ),
			'title' => $premium_shop_title,
			'text'  => premium_shop_text( 'benefit_' . $premium_shop_i . '_text' ),
		);
	}
}

if ( ! $premium_shop_items ) {
	return;
}
?>
<section class="ps-section ps-benefits" aria-label="<?php esc_attr_e( 'Our promises', 'premium-shop' ); ?>">
	<div class="ps-container">
		<ul class="ps-benefits__list">
			<?php foreach ( $premium_shop_items as $premium_shop_i => $premium_shop_item ) : ?>
				<li class="ps-benefit" data-reveal style="--ps-delay:<?php echo esc_attr( $premium_shop_i * 80 ); ?>ms">
					<span class="ps-benefit__icon"><?php premium_shop_icon( $premium_shop_item['icon'], array( 'size' => 26 ) ); ?></span>
					<span class="ps-benefit__text">
						<strong class="ps-benefit__title"><?php echo esc_html( $premium_shop_item['title'] ); ?></strong>
						<?php if ( $premium_shop_item['text'] ) : ?>
							<span class="ps-benefit__desc"><?php echo esc_html( $premium_shop_item['text'] ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
