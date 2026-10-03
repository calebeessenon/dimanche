<?php
/**
 * Announcement bar with rotating messages.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( ! premium_shop_option( 'promo_enabled' ) ) {
	return;
}

$premium_shop_messages = array_values( array_filter( array_map( 'trim', explode( "\n", premium_shop_text( 'promo_text' ) ) ) ) );

if ( ! $premium_shop_messages ) {
	return;
}

$premium_shop_link = premium_shop_option( 'promo_link' );
$premium_shop_key  = substr( md5( implode( '|', $premium_shop_messages ) ), 0, 8 );
?>
<div class="ps-promo" data-ps-promo="<?php echo esc_attr( $premium_shop_key ); ?>" role="region" aria-label="<?php esc_attr_e( 'Announcements', 'premium-shop' ); ?>">
	<div class="ps-promo__inner ps-container">
		<div class="ps-promo__track" aria-live="off">
			<?php foreach ( $premium_shop_messages as $premium_shop_i => $premium_shop_message ) : ?>
				<p class="ps-promo__msg<?php echo 0 === $premium_shop_i ? ' is-active' : ''; ?>"<?php echo 0 === $premium_shop_i ? '' : ' aria-hidden="true"'; ?>>
					<?php if ( $premium_shop_link ) : ?>
						<a href="<?php echo esc_url( $premium_shop_link ); ?>"><?php echo esc_html( $premium_shop_message ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $premium_shop_message ); ?>
					<?php endif; ?>
				</p>
			<?php endforeach; ?>
		</div>
		<?php if ( premium_shop_option( 'promo_dismissible' ) ) : ?>
			<button type="button" class="ps-promo__close" data-ps-promo-close aria-label="<?php esc_attr_e( 'Close announcement', 'premium-shop' ); ?>">
				<?php premium_shop_icon( 'close', array( 'size' => 16 ) ); ?>
			</button>
		<?php endif; ?>
	</div>
</div>
