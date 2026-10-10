<?php
/**
 * Off-canvas mobile navigation.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ps-drawer ps-drawer--left" id="ps-mobile-menu" data-ps-drawer role="dialog" aria-modal="true" aria-labelledby="ps-mobile-menu-title" hidden>
	<div class="ps-drawer__overlay" data-ps-close></div>
	<div class="ps-drawer__panel">
		<div class="ps-drawer__head">
			<p class="ps-drawer__title" id="ps-mobile-menu-title"><?php esc_html_e( 'Menu', 'premium-shop' ); ?></p>
			<button type="button" class="ps-icon-btn" data-ps-close>
				<?php premium_shop_icon( 'close', array( 'size' => 22 ) ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'premium-shop' ); ?></span>
			</button>
		</div>

		<div class="ps-drawer__body">
			<?php premium_shop_language_switcher( 'drawer' ); ?>

			<?php if ( premium_shop_option( 'header_search' ) ) : ?>
				<?php get_template_part( 'template-parts/components/search-form', null, array( 'id' => 'ps-mobile-search' ) ); ?>
			<?php endif; ?>

			<nav class="ps-mobile-nav" aria-label="<?php esc_attr_e( 'Main menu', 'premium-shop' ); ?>">
				<?php premium_shop_primary_menu( 'mobile' ); ?>
			</nav>

			<ul class="ps-mobile-links">
				<li>
					<a href="<?php echo esc_url( premium_shop_account_url() ); ?>">
						<?php premium_shop_icon( 'user', array( 'size' => 18 ) ); ?>
						<?php is_user_logged_in() ? esc_html_e( 'My account', 'premium-shop' ) : esc_html_e( 'Sign in / Register', 'premium-shop' ); ?>
					</a>
				</li>
				<?php
				$premium_shop_wishlist = premium_shop_option( 'card_wishlist' ) && premium_shop_is_wc() ? premium_shop_wishlist_url() : '';
				if ( $premium_shop_wishlist ) :
					?>
					<li>
						<a href="<?php echo esc_url( $premium_shop_wishlist ); ?>">
							<?php premium_shop_icon( 'heart', array( 'size' => 18 ) ); ?>
							<?php esc_html_e( 'Wishlist', 'premium-shop' ); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( premium_shop_option( 'contact_phone' ) ) : ?>
					<li>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', premium_shop_option( 'contact_phone' ) ) ); ?>">
							<?php premium_shop_icon( 'phone', array( 'size' => 18 ) ); ?>
							<?php echo esc_html( premium_shop_option( 'contact_phone' ) ); ?>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</div>

		<div class="ps-drawer__foot">
			<?php premium_shop_social_links(); ?>
		</div>
	</div>
</div>
