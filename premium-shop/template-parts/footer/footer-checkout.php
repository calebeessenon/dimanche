<?php
/**
 * Minimal footer for the distraction-free checkout.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="ps-footer ps-footer--checkout">
	<div class="ps-container ps-footer__bottom-inner">
		<p class="ps-footer__copy"><?php echo esc_html( premium_shop_copyright() ); ?></p>
		<?php
		$premium_shop_legal = premium_shop_footer_fallback_links( 'footer_legal' );
		if ( $premium_shop_legal ) {
			echo '<nav class="ps-footer__legal" aria-label="' . esc_attr__( 'Legal', 'premium-shop' ) . '"><ul class="ps-footer__legal-list">';
			foreach ( $premium_shop_legal as $premium_shop_link ) {
				printf( '<li><a href="%1$s" target="_blank">%2$s</a></li>', esc_url( $premium_shop_link[0] ), esc_html( $premium_shop_link[1] ) );
			}
			echo '</ul></nav>';
		}
		premium_shop_payment_badges();
		?>
	</div>
</footer>
