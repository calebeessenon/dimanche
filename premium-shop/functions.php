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

define( 'PREMIUM_SHOP_VERSION', '1.1.0' );
define( 'PREMIUM_SHOP_DIR', get_template_directory() );
define( 'PREMIUM_SHOP_URI', get_template_directory_uri() );

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
	'inc/firewood/faq.php',
	'inc/firewood/calculator.php',
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
			'inc/firewood/product-data.php',
			'inc/firewood/price-tool.php',
			'inc/firewood/delivery.php',
			'inc/firewood/demo-catalog.php',
		)
	);
}

foreach ( $premium_shop_includes as $premium_shop_file ) {
	require_once PREMIUM_SHOP_DIR . '/' . $premium_shop_file;
}

unset( $premium_shop_includes, $premium_shop_file );
