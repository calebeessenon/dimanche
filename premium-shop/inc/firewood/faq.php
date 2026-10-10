<?php
/**
 * FAQ: firewood questions answered once, shown on the homepage
 * (and anywhere with [ps_faq]) with FAQPage structured data.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * FAQ items: custom ("Question :: Answer" per line) or built-in.
 *
 * @return array[] [ question, answer ]
 */
function premium_shop_faq_items() {
	$custom = trim( (string) get_theme_mod( 'ps_fw_faq', '' ) );
	$items  = array();

	if ( '' !== $custom ) {
		$custom = premium_shop_translate_value( $custom, 'fw_faq' );
		foreach ( preg_split( '/\r\n|\r|\n/', $custom ) as $line ) {
			$parts = explode( '::', $line, 2 );
			if ( 2 === count( $parts ) && '' !== trim( $parts[0] ) && '' !== trim( $parts[1] ) ) {
				$items[] = array( trim( $parts[0] ), trim( $parts[1] ) );
			}
		}
		return $items;
	}

	$items = array(
		array( __( 'What is the difference between RM, SRM and FM?', 'premium-shop' ), __( 'A stacked cubic metre (RM) is 1 m³ of neatly stacked logs including the gaps. A loose cubic metre (SRM) is 1 m³ of loosely tipped logs. A solid cubic metre (FM) is 1 m³ of pure wood without gaps. Rule of thumb: 1 FM ≈ 1.4 RM ≈ 2 SRM.', 'premium-shop' ) ),
		array( __( 'How dry is your firewood?', 'premium-shop' ), __( 'Our kiln-dried firewood has a residual moisture below 20% and can be burned immediately. Dry wood burns cleaner, gives more heat and protects your stove and chimney.', 'premium-shop' ) ),
		array( __( 'Which log length do I need?', 'premium-shop' ), __( 'Most stoves take 25 or 33 cm logs. Measure the width of your combustion chamber and choose logs about 5 cm shorter. 50 cm logs suit large fireplaces and boilers.', 'premium-shop' ) ),
		array( __( 'How is the firewood delivered?', 'premium-shop' ), __( 'We deliver by truck to the kerbside — on pallets, in crates or loose depending on the product. Please make sure the unloading point is accessible for a truck. We contact you to arrange the delivery date.', 'premium-shop' ) ),
		array( __( 'How should I store firewood?', 'premium-shop' ), __( 'Store your wood dry, raised off the ground and well ventilated, ideally under a roof and protected from rain. Indoors, keep only the amount you need for a few days next to the stove.', 'premium-shop' ) ),
		array( __( 'Which wood species should I choose?', 'premium-shop' ), __( 'Beech and oak give long-lasting embers and a high heat output, birch lights easily and burns with a beautiful flame, and hardwood mixes are ideal for everyday heating.', 'premium-shop' ) ),
	);

	return apply_filters( 'premium_shop_faq_items', $items );
}

/**
 * Render the FAQ list.
 *
 * @param array $items Items.
 */
function premium_shop_faq_list( $items ) {
	echo '<div class="ps-faq__list">';
	foreach ( $items as $i => $item ) {
		printf(
			'<details class="ps-faq__item"%3$s><summary class="ps-faq__q"><span>%1$s</span>%4$s</summary><div class="ps-faq__a"><p>%2$s</p></div></details>',
			esc_html( $item[0] ),
			esc_html( $item[1] ),
			0 === $i ? ' open' : '',
			premium_shop_get_icon( 'plus', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG.
		);
	}
	echo '</div>';

	premium_shop_faq_schema( $items );
}

/**
 * FAQPage JSON-LD (once per page).
 *
 * @param array $items Items.
 */
function premium_shop_faq_schema( $items ) {
	static $done = false;
	if ( $done || ! $items ) {
		return;
	}
	$done = true;

	$data = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(),
	);
	foreach ( $items as $item ) {
		$data['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $item[0] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $item[1] ),
			),
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
}

/**
 * Shortcode [ps_faq].
 *
 * @return string
 */
function premium_shop_faq_shortcode() {
	ob_start();
	premium_shop_faq_list( premium_shop_faq_items() );
	return ob_get_clean();
}
add_shortcode( 'ps_faq', 'premium_shop_faq_shortcode' );
