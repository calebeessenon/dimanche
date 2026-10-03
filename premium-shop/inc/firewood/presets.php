<?php
/**
 * Style presets. "firewood" adapts the whole theme to a firewood shop:
 * colors, homepage sections, icons and every default text.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Active preset.
 *
 * @return string firewood|maison
 */
function premium_shop_preset() {
	$preset = get_theme_mod( 'ps_preset', 'firewood' );
	return in_array( $preset, array( 'firewood', 'maison' ), true ) ? $preset : 'firewood';
}

/**
 * Default values replaced by the active preset.
 *
 * @return array
 */
function premium_shop_preset_overrides() {
	if ( 'firewood' !== premium_shop_preset() ) {
		return array();
	}

	return apply_filters(
		'premium_shop_firewood_overrides',
		array(
			'color_primary'     => '#1f3a2c',
			'color_accent'      => '#a4501a',
			'color_background'  => '#f8f4ec',
			'color_surface'     => '#ffffff',
			'color_soft'        => '#efe6d6',
			'color_text'        => '#1c211d',
			'color_text_muted'  => '#5d5f58',
			'color_border'      => '#e4dac8',
			'color_footer_bg'   => '#142219',
			'color_footer_text' => '#efe8da',
			'color_promo_bg'    => '#1f3a2c',
			'color_promo_text'  => '#f3ead9',
			'heading_weight'    => '500',
			'home_sections'     => 'hero:1,benefits:1,categories:1,bestsellers:1,delivery:1,calculator:1,campaign:1,popular:0,new:0,sale:1,testimonials:1,faq:1,brands:0,newsletter:1,content:0',
			'benefit_1_icon'    => 'flame',
			'benefit_2_icon'    => 'truck',
			'benefit_3_icon'    => 'ruler',
			'benefit_4_icon'    => 'support',
			'card_hover_image'  => true,
			'badge_new_days'    => 0,
			'shop_columns'      => '3',
			'shop_per_page'     => 12,
			'free_shipping_threshold' => 0,
			'returns_days'      => 0,
			'footer_payments'   => 'paypal,klarna,sepa,invoice,visa,mastercard',
		)
	);
}

/**
 * Firewood default texts (translated).
 *
 * @return array
 */
function premium_shop_firewood_fallbacks() {
	return array(
		'promo_text'           => implode(
			"\n",
			array(
				__( 'Kiln-dried firewood — residual moisture below 20%', 'premium-shop' ),
				__( 'Delivery by truck — check your postcode now', 'premium-shop' ),
				__( 'Fair prices per stacked cubic metre', 'premium-shop' ),
			)
		),
		'hero_eyebrow'         => __( 'Firewood from regional forests', 'premium-shop' ),
		'hero_title'           => __( 'Dry firewood, delivered to your door', 'premium-shop' ),
		'hero_subtitle'        => __( 'Kiln-dried beech, oak and birch with less than 20% residual moisture — split, ready for your stove and delivered by truck.', 'premium-shop' ),
		'hero_button_text'     => __( 'Order firewood', 'premium-shop' ),
		'hero_button2_text'    => __( 'Calculate my needs', 'premium-shop' ),
		'title_categories'     => __( 'Our firewood', 'premium-shop' ),
		'title_bestsellers'    => __( 'Most ordered', 'premium-shop' ),
		'title_sale'           => __( 'Current offers', 'premium-shop' ),
		'campaign_eyebrow'     => __( 'Stock up early', 'premium-shop' ),
		'campaign_title'       => __( 'Pre-season prices', 'premium-shop' ),
		'campaign_text'        => __( 'Order now, store your wood dry and enjoy a warm home when the cold season starts.', 'premium-shop' ),
		'campaign_button_text' => __( 'View offers', 'premium-shop' ),
		'benefit_1_title'      => __( 'Kiln-dried', 'premium-shop' ),
		'benefit_1_text'       => __( 'Residual moisture below 20%', 'premium-shop' ),
		'benefit_2_title'      => __( 'Delivered to your door', 'premium-shop' ),
		'benefit_2_text'       => __( 'By truck, on pallets or loose', 'premium-shop' ),
		'benefit_3_title'      => __( 'Honest quantities', 'premium-shop' ),
		'benefit_3_text'       => __( 'Clear price per stacked cubic metre', 'premium-shop' ),
		'benefit_4_title'      => __( 'Personal advice', 'premium-shop' ),
		'benefit_4_text'       => __( 'We help you choose the right wood', 'premium-shop' ),
		'newsletter_title'     => __( 'Season & price news', 'premium-shop' ),
		'newsletter_text'      => __( 'Be the first to know about pre-season prices and delivery dates. Unsubscribe at any time.', 'premium-shop' ),
		'company_about'        => __( 'Firewood from sustainable regional forestry — carefully dried, split and delivered to your door.', 'premium-shop' ),
		'delivery_time'        => __( 'Delivery within 3–10 business days', 'premium-shop' ),
		'warranty_text'        => __( 'Moisture guarantee: below 20%', 'premium-shop' ),
		'title_faq'            => __( 'Frequently asked questions', 'premium-shop' ),
		'title_calculator'     => __( 'How much firewood do I need?', 'premium-shop' ),
		'fw_delivery_ok'       => __( 'Good news — we deliver to your area!', 'premium-shop' ),
		'fw_delivery_no'       => __( 'Unfortunately we do not deliver to this postcode yet. Contact us — we will gladly find a solution.', 'premium-shop' ),
		'fw_delivery_info'     => __( 'Delivery by truck to the kerbside. Please make sure the unloading point is accessible for a truck. We will call you to arrange the delivery date.', 'premium-shop' ),
	);
}

/**
 * Keep the hero's second button pointing to the calculator on firewood shops.
 *
 * @param string $value Option value.
 * @return string
 */
function premium_shop_firewood_hero_button2( $value ) {
	if ( '' === (string) $value && 'firewood' === premium_shop_preset() && in_array( 'calculator', premium_shop_home_sections(), true ) ) {
		return '#ps-calculator';
	}
	return $value;
}
add_filter( 'theme_mod_ps_hero_button2_url', 'premium_shop_firewood_hero_button2' );
