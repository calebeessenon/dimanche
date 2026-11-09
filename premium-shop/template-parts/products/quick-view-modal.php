<?php
/**
 * Quick view modal shell (content loaded on demand).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( ! premium_shop_option( 'card_quick_view' ) && ! is_product() ) {
	return;
}
?>
<div class="ps-drawer ps-drawer--modal ps-modal" id="ps-quick-view" data-ps-drawer role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Quick view', 'premium-shop' ); ?>" hidden>
	<div class="ps-drawer__overlay" data-ps-close></div>
	<div class="ps-drawer__panel ps-modal__panel">
		<button type="button" class="ps-icon-btn ps-modal__close" data-ps-close>
			<?php premium_shop_icon( 'close', array( 'size' => 22 ) ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Close', 'premium-shop' ); ?></span>
		</button>
		<div class="ps-modal__content" data-ps-modal-content></div>
	</div>
</div>
