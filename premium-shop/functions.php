<?php
/**
 * Premium Shop — theme bootstrap.
 *
 * Every feature lives in its own file inside /inc so the theme stays easy to
 * maintain. WooCommerce-specific code is only loaded when WooCommerce is active.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

define( 'PREMIUM_SHOP_VERSION', '2.3.12' );
define( 'PREMIUM_SHOP_DIR', get_template_directory() );
define( 'PREMIUM_SHOP_URI', get_template_directory_uri() );
if ( ! defined( 'PREMIUM_SHOP_DEFAULT_PRESET' ) ) {
	define( 'PREMIUM_SHOP_DEFAULT_PRESET', 'firewood' );
}

$premium_shop_includes = array(
	'inc/helpers.php',
	'inc/i18n.php',
	'inc/icons.php',
	'inc/setup.php',
	'inc/customizer/config.php',
	'inc/customizer/customizer.php',
	'inc/customizer/dynamic-css.php',
	'inc/enqueue.php',
	'inc/template-tags.php',
	'inc/class-premium-shop-walker-nav.php',
	'inc/newsletter.php',
	'inc/security.php',
	'inc/accessibility.php',
	'inc/seo.php',
	'inc/admin/onboarding.php',
	'inc/firewood/presets.php',
	'inc/gaming/gaming.php',
	'inc/firewood/faq.php',
	'inc/firewood/calculator.php',
	'inc/contact.php',
	'inc/page-hero.php',
	'inc/home-slider.php',
	'inc/content-sections.php',
	'inc/legal.php',
	'inc/policies.php',
	'inc/i18n-content.php',
	'inc/upgrade.php',
);

if ( class_exists( 'WooCommerce' ) ) {
	$premium_shop_includes = array_merge(
		$premium_shop_includes,
		array(
			'inc/woocommerce/setup.php',
			'inc/woocommerce/product-card.php',
			'inc/woocommerce/shop-filters.php',
			'inc/woocommerce/single-product.php',
			'inc/woocommerce/cart-checkout.php',
			'inc/woocommerce/account.php',
			'inc/woocommerce/ajax.php',
			'inc/woocommerce/admin-fields.php',
			'inc/woocommerce/import-images.php',
			'inc/woocommerce/import-mapping.php',
			'inc/woocommerce/order-tracking.php',
			'inc/woocommerce/order-language.php',
			'inc/woocommerce/wc-fallback-strings.php',
			'inc/woocommerce/product-translations.php',
			'inc/firewood/product-data.php',
			'inc/firewood/price-tool.php',
			'inc/firewood/delivery.php',
			'inc/firewood/demo-catalog.php',
		)
	);
}

// Firewood-only modules (product data tab, price tool, postcode check, truck
// question, calculator): not loaded in the "Gaming & tech" edition.
$premium_shop_firewood_only = array(
	'inc/firewood/calculator.php',
	'inc/firewood/product-data.php',
	'inc/firewood/price-tool.php',
	'inc/firewood/delivery.php',
);

foreach ( $premium_shop_includes as $premium_shop_file ) {
	if ( in_array( $premium_shop_file, $premium_shop_firewood_only, true ) && function_exists( 'premium_shop_is_gaming' ) && premium_shop_is_gaming() ) {
		continue;
	}
	require_once PREMIUM_SHOP_DIR . '/' . $premium_shop_file;
}

unset( $premium_shop_includes, $premium_shop_file, $premium_shop_firewood_only );
