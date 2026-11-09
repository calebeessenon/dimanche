<?php
/**
 * Security hardening that is safe for a theme to apply.
 *
 * Output escaping, nonces, capability checks and sanitization are applied
 * where data is handled (see newsletter.php, customizer.php, woocommerce/*).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Privacy (GDPR): WordPress replaces emoji characters with images loaded from
 * s.w.org when the browser has no emoji font, which sends the visitor's IP
 * address to a third party. All current browsers display emoji natively.
 * Return false from the "premium_shop_disable_emoji" filter to keep the script.
 */
function premium_shop_disable_emoji() {
	if ( ! apply_filters( 'premium_shop_disable_emoji', true ) ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'premium_shop_disable_emoji' );

/**
 * Safe external links in content: add rel="noopener" to target="_blank".
 *
 * @param string $content Post content.
 * @return string
 */
function premium_shop_safe_blank_links( $content ) {
	if ( false === strpos( $content, 'target="_blank"' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/<a\s[^>]*target="_blank"[^>]*>/i',
		static function ( $match ) {
			$tag = $match[0];
			if ( false === stripos( $tag, 'rel=' ) ) {
				$tag = str_replace( '<a ', '<a rel="noopener noreferrer" ', $tag );
			}
			return $tag;
		},
		$content
	);
}
add_filter( 'the_content', 'premium_shop_safe_blank_links', 20 );

/**
 * Generic login error message (does not reveal whether the user exists).
 *
 * @param string $error Error HTML.
 * @return string
 */
function premium_shop_login_errors( $error ) {
	global $errors;

	if ( is_wp_error( $errors ) ) {
		$codes = $errors->get_error_codes();
		if ( array_intersect( $codes, array( 'invalid_username', 'incorrect_password', 'invalid_email' ) ) ) {
			return esc_html__( 'The login details are incorrect.', 'premium-shop' );
		}
	}

	return $error;
}
add_filter( 'login_errors', 'premium_shop_login_errors' );

/**
 * Sanitize the ?lang= parameter globally so it never reaches output unsanitized.
 */
function premium_shop_sanitize_lang_param() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['lang'] ) && ! is_string( $_GET['lang'] ) ) {
		unset( $_GET['lang'] );
	}
	// phpcs:enable
}
add_action( 'init', 'premium_shop_sanitize_lang_param', 0 );
