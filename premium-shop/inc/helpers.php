<?php
/**
 * Generic helpers used across the theme.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is WooCommerce active?
 *
 * @return bool
 */
function premium_shop_is_wc() {
	return class_exists( 'WooCommerce' );
}

/**
 * Read a theme option (Customizer setting) with its registered default.
 *
 * @param string $key Setting key without prefix.
 * @return mixed
 */
function premium_shop_option( $key ) {
	$defaults = premium_shop_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';

	return get_theme_mod( 'ps_' . $key, $default );
}

/**
 * Get a customizable text, translated for the current language.
 *
 * - Empty value → theme default, translated through the .mo files.
 * - "[:de]Hallo[:fr]Bonjour[:en]Hello" → the block matching the current language.
 * - WPML / Polylang string translation is applied when available.
 *
 * @param string $key Setting key without prefix.
 * @return string
 */
function premium_shop_text( $key ) {
	$value = trim( (string) get_theme_mod( 'ps_' . $key, '' ) );

	if ( '' === $value ) {
		$fallbacks = premium_shop_text_fallbacks();
		return isset( $fallbacks[ $key ] ) ? $fallbacks[ $key ] : '';
	}

	return premium_shop_translate_value( $value, $key );
}

/**
 * Translate a stored value: inline language blocks first, then plugins.
 *
 * @param string $value Raw stored value.
 * @param string $name  String name (used by WPML / Polylang).
 * @return string
 */
function premium_shop_translate_value( $value, $name = '' ) {
	if ( false !== strpos( $value, '[:' ) ) {
		$value = premium_shop_pick_language_block( $value );
	}

	if ( $name ) {
		if ( function_exists( 'pll__' ) ) {
			$value = pll__( $value );
		} elseif ( has_filter( 'wpml_translate_single_string' ) ) {
			$value = apply_filters( 'wpml_translate_single_string', $value, 'Premium Shop', 'ps_' . $name );
		}
	}

	return $value;
}

/**
 * Extract the block for the current language from "[:de]..[:fr]..".
 *
 * Falls back to the default language block, then to the first block.
 *
 * @param string $value Multi-language value.
 * @return string
 */
function premium_shop_pick_language_block( $value ) {
	$parts = preg_split( '/\[:([a-z]{2}(?:[_-][a-zA-Z]{2})?)\]/', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

	if ( ! $parts || count( $parts ) < 2 ) {
		return $value;
	}

	$blocks = array();
	$count  = count( $parts );
	for ( $i = 0; $i < $count - 1; $i++ ) {
		if ( preg_match( '/^[a-z]{2}(?:[_-][a-zA-Z]{2})?$/', $parts[ $i ] ) ) {
			$blocks[ strtolower( substr( $parts[ $i ], 0, 2 ) ) ] = trim( $parts[ $i + 1 ] );
			$i++;
		}
	}

	if ( empty( $blocks ) ) {
		return $value;
	}

	$current = premium_shop_current_language();
	if ( isset( $blocks[ $current ] ) ) {
		return $blocks[ $current ];
	}

	$default = premium_shop_default_language();
	if ( isset( $blocks[ $default ] ) ) {
		return $blocks[ $default ];
	}

	return reset( $blocks );
}

/**
 * Free-shipping threshold configured in the theme (0 = disabled).
 *
 * @return float
 */
function premium_shop_free_shipping_threshold() {
	$threshold = (float) premium_shop_option( 'free_shipping_threshold' );
	if ( $threshold <= 0 ) {
		$threshold = premium_shop_wc_free_shipping_min_amount();
	}
	return (float) apply_filters( 'premium_shop_free_shipping_threshold', $threshold );
}

/**
 * Minimum amount of the enabled WooCommerce "Free shipping" method (setting
 * "requires a minimum order amount") in the customer's shipping zone, or in the
 * store's zone before an address is known. 0 when there is none.
 *
 * @return float
 */
function premium_shop_wc_free_shipping_min_amount() {
	static $cache = array();

	if ( ! class_exists( 'WC_Shipping_Zones' ) || ! did_action( 'woocommerce_init' ) ) {
		return 0.0;
	}

	$customer = function_exists( 'WC' ) && WC()->customer ? WC()->customer : null;
	$country  = $customer && $customer->get_shipping_country() ? $customer->get_shipping_country() : WC()->countries->get_base_country();
	$state    = $customer && $customer->get_shipping_country() ? $customer->get_shipping_state() : WC()->countries->get_base_state();
	$postcode = $customer && $customer->get_shipping_country() ? $customer->get_shipping_postcode() : WC()->countries->get_base_postcode();
	$key      = $country . '|' . $state . '|' . $postcode;

	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$zone = WC_Shipping_Zones::get_zone_matching_package(
		array(
			'destination' => array(
				'country'  => $country,
				'state'    => $state,
				'postcode' => $postcode,
			),
		)
	);

	$amount = 0.0;
	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' === $method->id && in_array( $method->get_option( 'requires' ), array( 'min_amount', 'either' ), true ) ) {
			$min = (float) $method->get_option( 'min_amount' );
			if ( $min > 0 && ( 0.0 === $amount || $min < $amount ) ) {
				$amount = $min;
			}
		}
	}

	$cache[ $key ] = $amount;
	return $amount;
}

/**
 * Format an amount as plain text with the shop currency.
 *
 * @param float $amount Amount.
 * @return string
 */
function premium_shop_plain_price( $amount ) {
	if ( function_exists( 'wc_price' ) ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'decimals' => ( floor( $amount ) == $amount ) ? 0 : 2 ) ) ), ENT_QUOTES, 'UTF-8' ); // phpcs:ignore Universal.Operators.StrictComparisons
	}

	return number_format_i18n( $amount, 0 ) . ' €';
}

/**
 * Get a page URL by one of several possible slugs (localized slugs first).
 *
 * @param array $slugs Candidate slugs.
 * @return string Empty string when no published page matches.
 */
function premium_shop_page_url_by_slugs( array $slugs ) {
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			return get_permalink( $page );
		}
	}

	return '';
}

/**
 * Shop page URL (falls back to the product archive or home).
 *
 * @return string
 */
function premium_shop_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}

	return home_url( '/' );
}

/**
 * Allowed HTML for inline SVG icons.
 *
 * @return array
 */
function premium_shop_svg_kses() {
	$common = array(
		'fill'              => true,
		'stroke'            => true,
		'stroke-width'      => true,
		'stroke-linecap'    => true,
		'stroke-linejoin'   => true,
		'stroke-miterlimit' => true,
		'opacity'           => true,
		'transform'         => true,
		'class'             => true,
		'fill-rule'         => true,
		'clip-rule'         => true,
	);

	return array(
		'svg'      => array_merge(
			$common,
			array(
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'role'        => true,
			)
		),
		'path'     => array_merge( $common, array( 'd' => true ) ),
		'circle'   => array_merge( $common, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
		'rect'     => array_merge( $common, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ),
		'line'     => array_merge( $common, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
		'polyline' => array_merge( $common, array( 'points' => true ) ),
		'polygon'  => array_merge( $common, array( 'points' => true ) ),
		'g'        => $common,
	);
}

/**
 * Sanitize a hex color, falling back to a default.
 *
 * @param string $color   Color.
 * @param string $default Fallback.
 * @return string
 */
function premium_shop_hex( $color, $default = '#000000' ) {
	$color = sanitize_hex_color( $color );
	return $color ? $color : $default;
}

/**
 * Is the current request the Customizer preview?
 *
 * @return bool
 */
function premium_shop_is_preview() {
	return is_customize_preview();
}
