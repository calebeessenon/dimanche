<?php
/**
 * Firewood calculator: annual need + unit converter (RM / SRM / FM).
 *
 * Answers the most frequent customer question ("how much wood do I need?")
 * automatically. Shortcode: [ps_firewood_calculator].
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Species for the calculator (works without WooCommerce too).
 *
 * @return array slug => [ name, kWh per stacked m³ ]
 */
function premium_shop_calc_species() {
	if ( function_exists( 'premium_shop_fw_species' ) ) {
		return premium_shop_fw_species();
	}
	return array(
		'beech' => array( __( 'Beech', 'premium-shop' ), 1900 ),
		'oak'   => array( __( 'Oak', 'premium-shop' ), 2000 ),
		'birch' => array( __( 'Birch', 'premium-shop' ), 1700 ),
		'mixed' => array( __( 'Mixed hardwood', 'premium-shop' ), 1850 ),
	);
}

/**
 * Render the calculator.
 *
 * @param array $args compact (bool), heading (h2|h3).
 */
function premium_shop_firewood_calculator( $args = array() ) {
	get_template_part( 'template-parts/components/firewood-calculator', null, $args );
}

/**
 * Shortcode.
 *
 * @return string
 */
function premium_shop_calculator_shortcode() {
	ob_start();
	premium_shop_firewood_calculator( array( 'heading' => 'h2' ) );
	return ob_get_clean();
}
add_shortcode( 'ps_firewood_calculator', 'premium_shop_calculator_shortcode' );

/**
 * Product page: calculator as a tab.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function premium_shop_calculator_tab( $tabs ) {
	global $product;
	if ( premium_shop_option( 'fw_calc_product' ) && $product instanceof WC_Product && function_exists( 'premium_shop_fw_is_firewood' ) && premium_shop_fw_is_firewood( $product ) ) {
		$tabs['ps_calculator'] = array(
			'title'    => __( 'Calculate your needs', 'premium-shop' ),
			'priority' => 35,
			'callback' => static function () {
				premium_shop_firewood_calculator( array( 'compact' => true, 'heading' => 'h2' ) );
			},
		);
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'premium_shop_calculator_tab', 20 );

/**
 * Should the firewood script be loaded on this page?
 *
 * @return bool
 */
function premium_shop_needs_firewood_js() {
	if ( is_front_page() ) {
		return true;
	}
	if ( function_exists( 'is_product' ) && ( is_product() || is_cart() ) ) {
		return true;
	}
	$post = get_post();
	return $post && ( has_shortcode( $post->post_content, 'ps_firewood_calculator' ) || has_shortcode( $post->post_content, 'ps_delivery_check' ) );
}

/**
 * Enqueue the small firewood script (calculator + delivery check).
 */
function premium_shop_firewood_assets() {
	if ( ! premium_shop_needs_firewood_js() ) {
		return;
	}
	wp_enqueue_script(
		'premium-shop-firewood',
		premium_shop_asset( 'js/firewood', 'js' ),
		array( 'premium-shop' ),
		PREMIUM_SHOP_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'premium-shop-firewood',
		'premiumShopFirewood',
		array(
			'decimal' => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : ',',
			'i18n'    => array(
				'checking'   => __( 'Checking…', 'premium-shop' ),
				'invalid'    => __( 'Please enter a valid postcode.', 'premium-shop' ),
				'perYear'    => __( 'stacked m³ per season', 'premium-shop' ),
				/* translators: 1: loose m³, 2: solid m³. */
				'equivalent' => __( '≈ %1$s loose m³ · %2$s solid m³', 'premium-shop' ),
				'rm'         => __( 'stacked m³', 'premium-shop' ),
				'srm'        => __( 'loose m³', 'premium-shop' ),
				'fm'         => __( 'solid m³', 'premium-shop' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'premium_shop_firewood_assets', 20 );
