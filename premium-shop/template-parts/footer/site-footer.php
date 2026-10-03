<?php
/**
 * Main footer.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_company = premium_shop_option( 'company_name' );
$premium_shop_address = premium_shop_option( 'contact_address' );
$premium_shop_phone   = premium_shop_option( 'contact_phone' );
$premium_shop_email   = premium_shop_option( 'contact_email' );
$premium_shop_hours   = premium_shop_text( 'contact_hours' );
$premium_shop_wa      = preg_replace( '/[^0-9]/', '', (string) premium_shop_option( 'contact_whatsapp' ) );
?>
<footer class="ps-footer">
	<?php if ( premium_shop_option( 'newsletter_footer' ) && ! is_front_page() ) : ?>
		<div class="ps-footer__newsletter">
			<div class="ps-container ps-footer__newsletter-inner">
				<div>
					<h2 class="ps-footer__newsletter-title"><?php echo esc_html( premium_shop_text( 'newsletter_title' ) ); ?></h2>
					<p class="ps-footer__newsletter-text"><?php echo esc_html( premium_shop_text( 'newsletter_text' ) ); ?></p>
				</div>
				<?php get_template_part( 'template-parts/components/newsletter-form', null, array( 'id' => 'ps-footer-newsletter' ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="ps-container ps-footer__main">
		<div class="ps-footer__col ps-footer__col--brand">
			<div class="ps-footer__logo"><?php premium_shop_logo(); ?></div>
			<p class="ps-footer__about"><?php echo esc_html( premium_shop_text( 'company_about' ) ); ?></p>
			<?php premium_shop_social_links(); ?>
		</div>

		<?php premium_shop_footer_menu( 'footer_shop', __( 'Shop', 'premium-shop' ) ); ?>
		<?php premium_shop_footer_menu( 'footer_service', __( 'Customer service', 'premium-shop' ) ); ?>

		<?php if ( $premium_shop_company || $premium_shop_address || $premium_shop_phone || $premium_shop_email || $premium_shop_wa || $premium_shop_hours || current_user_can( 'edit_theme_options' ) ) : ?>
		<div class="ps-footer__col ps-footer__col--contact">
			<h2 class="ps-footer__title"><?php esc_html_e( 'Contact', 'premium-shop' ); ?></h2>
			<address class="ps-footer__contact">
				<?php if ( $premium_shop_company ) : ?>
					<strong><?php echo esc_html( $premium_shop_company ); ?></strong>
				<?php endif; ?>
				<?php if ( $premium_shop_address ) : ?>
					<span class="ps-footer__contact-line"><?php premium_shop_icon( 'pin', array( 'size' => 16 ) ); ?><span><?php echo nl2br( esc_html( $premium_shop_address ) ); ?></span></span>
				<?php endif; ?>
				<?php if ( $premium_shop_phone ) : ?>
					<a class="ps-footer__contact-line" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $premium_shop_phone ) ); ?>"><?php premium_shop_icon( 'phone', array( 'size' => 16 ) ); ?><span><?php echo esc_html( $premium_shop_phone ); ?></span></a>
				<?php endif; ?>
				<?php if ( $premium_shop_email ) : ?>
					<a class="ps-footer__contact-line" href="mailto:<?php echo esc_attr( antispambot( $premium_shop_email ) ); ?>"><?php premium_shop_icon( 'mail', array( 'size' => 16 ) ); ?><span><?php echo esc_html( antispambot( $premium_shop_email ) ); ?></span></a>
				<?php endif; ?>
				<?php if ( $premium_shop_wa ) : ?>
					<a class="ps-footer__contact-line" href="<?php echo esc_url( 'https://wa.me/' . $premium_shop_wa ); ?>" target="_blank" rel="noopener noreferrer"><?php premium_shop_icon( 'whatsapp', array( 'size' => 16 ) ); ?><span>WhatsApp</span></a>
				<?php endif; ?>
				<?php if ( $premium_shop_hours ) : ?>
					<span class="ps-footer__contact-line"><?php premium_shop_icon( 'clock', array( 'size' => 16 ) ); ?><span><?php echo esc_html( $premium_shop_hours ); ?></span></span>
				<?php endif; ?>
				<?php if ( ! $premium_shop_address && ! $premium_shop_phone && ! $premium_shop_email && current_user_can( 'edit_theme_options' ) ) : ?>
					<a class="ps-footer__hint" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=ps_contact' ) ); ?>"><?php esc_html_e( 'Add your contact details in the Customizer', 'premium-shop' ); ?></a>
				<?php endif; ?>
			</address>
		</div>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footer-extra' ) ) : ?>
			<div class="ps-footer__col">
				<?php dynamic_sidebar( 'footer-extra' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="ps-footer__bottom">
		<div class="ps-container ps-footer__bottom-inner">
			<p class="ps-footer__copy"><?php echo esc_html( premium_shop_copyright() ); ?></p>
			<?php
			if ( has_nav_menu( 'footer_legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer_legal',
						'container'      => 'nav',
						'container_class' => 'ps-footer__legal',
						'container_aria_label' => __( 'Legal', 'premium-shop' ),
						'menu_class'     => 'ps-footer__legal-list',
						'depth'          => 1,
					)
				);
			} else {
				$premium_shop_legal = premium_shop_footer_fallback_links( 'footer_legal' );
				if ( $premium_shop_legal ) {
					echo '<nav class="ps-footer__legal" aria-label="' . esc_attr__( 'Legal', 'premium-shop' ) . '"><ul class="ps-footer__legal-list">';
					foreach ( $premium_shop_legal as $premium_shop_link ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $premium_shop_link[0] ), esc_html( $premium_shop_link[1] ) );
					}
					echo '</ul></nav>';
				}
			}
			?>
			<?php premium_shop_payment_badges(); ?>
		</div>
	</div>

	<?php if ( premium_shop_option( 'back_to_top' ) ) : ?>
		<a class="ps-back-top" href="#ps-main" data-ps-back-top>
			<?php premium_shop_icon( 'arrow-up', array( 'size' => 18 ) ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Back to top', 'premium-shop' ); ?></span>
		</a>
	<?php endif; ?>
</footer>
