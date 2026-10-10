<?php
/**
 * Delivery check by postcode.
 *
 * @package Premium_Shop
 *
 * @var array $args compact (bool).
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_compact   = ! empty( $args['compact'] );
$premium_shop_id        = wp_unique_id( 'ps-plz-' );
$premium_shop_countries = WC()->countries->get_shipping_countries();
$premium_shop_country   = WC()->customer && WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : WC()->countries->get_base_country();
$premium_shop_postcode  = WC()->customer ? WC()->customer->get_shipping_postcode() : '';
$premium_shop_info      = premium_shop_text( 'fw_delivery_info' );
?>
<div class="ps-delivery<?php echo $premium_shop_compact ? ' ps-delivery--compact' : ''; ?>" data-ps-delivery>
	<form class="ps-delivery__form" data-ps-delivery-form novalidate>
		<label class="ps-delivery__label" for="<?php echo esc_attr( $premium_shop_id ); ?>">
			<?php premium_shop_icon( 'truck', array( 'size' => 18 ) ); ?>
			<span><?php esc_html_e( 'Do we deliver to you? Enter your postcode', 'premium-shop' ); ?></span>
		</label>
		<div class="ps-delivery__row">
			<?php if ( count( $premium_shop_countries ) > 1 ) : ?>
				<label class="screen-reader-text" for="<?php echo esc_attr( $premium_shop_id ); ?>-country"><?php esc_html_e( 'Country', 'premium-shop' ); ?></label>
				<select id="<?php echo esc_attr( $premium_shop_id ); ?>-country" name="country" class="ps-delivery__country">
					<?php foreach ( $premium_shop_countries as $premium_shop_code => $premium_shop_name ) : ?>
						<option value="<?php echo esc_attr( $premium_shop_code ); ?>" <?php selected( $premium_shop_code, $premium_shop_country ); ?>><?php echo esc_html( $premium_shop_code ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input type="hidden" name="country" value="<?php echo esc_attr( (string) key( $premium_shop_countries ) ); ?>" />
			<?php endif; ?>
			<input type="text" id="<?php echo esc_attr( $premium_shop_id ); ?>" name="postcode" value="<?php echo esc_attr( $premium_shop_postcode ); ?>" inputmode="numeric" autocomplete="postal-code" maxlength="10" placeholder="<?php esc_attr_e( 'Postcode', 'premium-shop' ); ?>" required />
			<button type="submit" class="ps-btn ps-btn--primary"><?php esc_html_e( 'Check', 'premium-shop' ); ?></button>
		</div>
		<div class="ps-delivery__result" data-ps-delivery-result role="status" aria-live="polite"></div>
	</form>
	<?php if ( ! $premium_shop_compact && $premium_shop_info ) : ?>
		<p class="ps-delivery__info"><?php echo esc_html( $premium_shop_info ); ?></p>
	<?php endif; ?>
</div>
