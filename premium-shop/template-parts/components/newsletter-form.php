<?php
/**
 * Newsletter form: shortcode from a newsletter plugin, or the built-in
 * double opt-in form.
 *
 * @package Premium_Shop
 *
 * @var array $args id, theme (light|dark).
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_args      = wp_parse_args( isset( $args ) ? $args : array(), array( 'id' => 'ps-newsletter' ) );
$premium_shop_shortcode = trim( (string) premium_shop_option( 'newsletter_shortcode' ) );

if ( $premium_shop_shortcode ) {
	echo '<div class="ps-newsletter-plugin">' . do_shortcode( wp_kses_post( $premium_shop_shortcode ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
	return;
}

$premium_shop_id      = sanitize_html_class( $premium_shop_args['id'] );
$premium_shop_privacy = get_privacy_policy_url();
?>
<form class="ps-newsletter-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ps-newsletter novalidate>
	<input type="hidden" name="action" value="premium_shop_newsletter" />
	<?php wp_nonce_field( 'premium_shop_newsletter', 'ps_newsletter_nonce', false ); ?>
	<input type="hidden" name="ps_lang" value="<?php echo esc_attr( premium_shop_current_language() ); ?>" />
	<div class="ps-newsletter-form__hp" aria-hidden="true">
		<label for="<?php echo esc_attr( $premium_shop_id ); ?>-website"><?php esc_html_e( 'Leave this field empty', 'premium-shop' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $premium_shop_id ); ?>-website" name="ps_website" tabindex="-1" autocomplete="off" />
	</div>
	<div class="ps-newsletter-form__row">
		<label class="screen-reader-text" for="<?php echo esc_attr( $premium_shop_id ); ?>-email"><?php esc_html_e( 'Email address', 'premium-shop' ); ?></label>
		<input type="email" id="<?php echo esc_attr( $premium_shop_id ); ?>-email" name="ps_email" required autocomplete="email" placeholder="<?php esc_attr_e( 'Your email address', 'premium-shop' ); ?>" />
		<button type="submit" class="ps-btn ps-btn--accent">
			<span><?php esc_html_e( 'Subscribe', 'premium-shop' ); ?></span>
			<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
		</button>
	</div>
	<div class="ps-newsletter-form__consent">
		<input type="checkbox" id="<?php echo esc_attr( $premium_shop_id ); ?>-consent" name="ps_consent" value="1" required />
		<label for="<?php echo esc_attr( $premium_shop_id ); ?>-consent">
			<?php
			if ( $premium_shop_privacy ) {
				printf(
					/* translators: %s: privacy policy link. */
					esc_html__( 'I agree to receive the newsletter and accept the %s. I can unsubscribe at any time.', 'premium-shop' ),
					'<a href="' . esc_url( $premium_shop_privacy ) . '">' . esc_html__( 'privacy policy', 'premium-shop' ) . '</a>'
				);
			} else {
				esc_html_e( 'I agree to receive the newsletter. I can unsubscribe at any time.', 'premium-shop' );
			}
			?>
		</label>
	</div>
	<p class="ps-newsletter-form__msg" data-ps-newsletter-msg role="status" aria-live="polite"></p>
</form>
