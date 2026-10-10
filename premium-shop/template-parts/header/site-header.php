<?php
/**
 * Main header: logo, navigation, search, language, account, wishlist, cart.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="ps-header" data-ps-header>
	<div class="ps-header__inner ps-container">
		<button type="button" class="ps-icon-btn ps-header__burger" data-ps-open="ps-mobile-menu" aria-controls="ps-mobile-menu" aria-expanded="false">
			<?php premium_shop_icon( 'menu', array( 'size' => 22 ) ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'premium-shop' ); ?></span>
		</button>

		<div class="ps-header__brand">
			<?php premium_shop_logo(); ?>
		</div>

		<nav class="ps-nav" aria-label="<?php esc_attr_e( 'Main menu', 'premium-shop' ); ?>">
			<?php premium_shop_primary_menu( 'desktop' ); ?>
		</nav>

		<div class="ps-header__actions">
			<?php if ( premium_shop_option( 'header_languages' ) ) : ?>
				<div class="ps-header__lang-mini">
					<?php premium_shop_language_switcher_compact(); ?>
				</div>
			<?php endif; ?>

			<?php if ( premium_shop_option( 'header_search' ) ) : ?>
				<button type="button" class="ps-icon-btn" data-ps-open="ps-search" aria-controls="ps-search" aria-expanded="false">
					<?php premium_shop_icon( 'search', array( 'size' => 21 ) ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Search', 'premium-shop' ); ?></span>
				</button>
			<?php endif; ?>

			<?php if ( premium_shop_option( 'header_languages' ) ) : ?>
				<div class="ps-header__lang">
					<?php premium_shop_language_switcher( 'header' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( premium_shop_option( 'header_account' ) ) : ?>
				<a class="ps-icon-btn ps-header__account" href="<?php echo esc_url( premium_shop_account_url() ); ?>">
					<?php premium_shop_icon( 'user', array( 'size' => 21 ) ); ?>
					<span class="screen-reader-text"><?php is_user_logged_in() ? esc_html_e( 'My account', 'premium-shop' ) : esc_html_e( 'Sign in', 'premium-shop' ); ?></span>
				</a>
			<?php endif; ?>

			<?php
			$premium_shop_wishlist = premium_shop_option( 'header_wishlist' ) && premium_shop_option( 'card_wishlist' ) && premium_shop_is_wc() ? premium_shop_wishlist_url() : '';
			if ( $premium_shop_wishlist ) :
				?>
				<a class="ps-icon-btn ps-header__wishlist" href="<?php echo esc_url( $premium_shop_wishlist ); ?>">
					<?php premium_shop_icon( 'heart', array( 'size' => 21 ) ); ?>
					<span class="ps-badge-count" data-ps-wishlist-count hidden>0</span>
					<span class="screen-reader-text"><?php esc_html_e( 'Wishlist', 'premium-shop' ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( premium_shop_is_wc() ) : ?>
				<a class="ps-icon-btn ps-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-ps-open="ps-cart-drawer" aria-controls="ps-cart-drawer" aria-expanded="false">
					<?php premium_shop_icon( 'bag', array( 'size' => 22 ) ); ?>
					<?php premium_shop_cart_count_html(); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Cart', 'premium-shop' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
