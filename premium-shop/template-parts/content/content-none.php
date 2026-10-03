<?php
/**
 * Nothing found.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ps-empty">
	<span class="ps-empty__icon" aria-hidden="true"><?php premium_shop_icon( 'search', array( 'size' => 28 ) ); ?></span>
	<h2 class="ps-empty__title"><?php esc_html_e( 'Nothing found', 'premium-shop' ); ?></h2>
	<p><?php is_search() ? esc_html_e( 'No results match your search. Try other keywords.', 'premium-shop' ) : esc_html_e( 'There is no content here yet.', 'premium-shop' ); ?></p>
	<?php if ( premium_shop_is_wc() ) : ?>
		<a class="ps-btn ps-btn--primary" href="<?php echo esc_url( premium_shop_shop_url() ); ?>"><span><?php esc_html_e( 'Go to the shop', 'premium-shop' ); ?></span><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></a>
	<?php endif; ?>
</div>
