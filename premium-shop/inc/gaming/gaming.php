<?php
/**
 * "Gaming & tech" preset: consoles (Nintendo, PlayStation 5), graphics cards
 * and accessories. Colors, homepage, texts, FAQ, bundled illustrations and a
 * ready-made sample catalogue.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values of the gaming preset.
 *
 * @return array
 */
function premium_shop_gaming_overrides() {
	return apply_filters(
		'premium_shop_gaming_overrides',
		array(
			'color_primary'           => '#14112b',
			'color_accent'            => '#6d4aff',
			'color_background'        => '#f6f7fb',
			'color_surface'           => '#ffffff',
			'color_soft'              => '#eceefe',
			'color_text'              => '#12131a',
			'color_text_muted'        => '#5b6070',
			'color_border'            => '#e2e5f0',
			'color_footer_bg'         => '#0d0b1f',
			'color_footer_text'       => '#e8e6ff',
			'color_promo_bg'          => '#6d4aff',
			'color_promo_text'        => '#ffffff',
			'heading_weight'          => '700',
			'font_heading'            => 'inter',
			'font_body'               => 'inter',
			'home_sections'           => 'hero:1,benefits:1,categories:1,bestsellers:1,campaign:1,new:1,sale:1,testimonials:1,faq:1,newsletter:1,popular:0,brands:0,content:0',
			'benefit_1_icon'          => 'truck',
			'benefit_2_icon'          => 'shield',
			'benefit_3_icon'          => 'return',
			'benefit_4_icon'          => 'support',
			'card_hover_image'        => false,
			'badge_new_days'          => 30,
			'shop_columns'            => '4',
			'shop_per_page'           => 16,
			'free_shipping_threshold' => 0,
			'returns_days'            => 14,
			'footer_payments'         => 'twint,postfinance,visa,mastercard,paypal,applepay,googlepay',
			// Firewood tools off.
			'fw_unit_price'           => false,
			'fw_specs_on_cards'       => false,
			'fw_delivery_check'       => false,
			'fw_truck_field'          => false,
			'fw_calc_product'         => false,
		)
	);
}

/**
 * Default texts of the gaming preset (translated).
 *
 * @return array
 */
function premium_shop_gaming_fallbacks() {
	return array(
		'promo_text'           => implode(
			"\n",
			array(
				__( 'Original products with manufacturer warranty', 'premium-shop' ),
				__( 'Ordered before 3 pm, shipped the same day', 'premium-shop' ),
				__( 'Pay with TWINT, card or invoice', 'premium-shop' ),
			)
		),
		'hero_eyebrow'         => __( 'Consoles · graphics cards · accessories', 'premium-shop' ),
		'hero_title'           => __( 'Next-level gaming, delivered fast', 'premium-shop' ),
		'hero_subtitle'        => __( 'Nintendo Switch, PlayStation 5 and the latest graphics cards — original products, fair prices and express delivery.', 'premium-shop' ),
		'hero_button_text'     => __( 'Shop now', 'premium-shop' ),
		'hero_button2_text'    => __( 'Current deals', 'premium-shop' ),
		'title_categories'     => __( 'Shop by platform', 'premium-shop' ),
		'title_bestsellers'    => __( 'Bestsellers', 'premium-shop' ),
		'title_new'            => __( 'New arrivals', 'premium-shop' ),
		'title_sale'           => __( 'Deals', 'premium-shop' ),
		'campaign_eyebrow'     => __( 'Level up', 'premium-shop' ),
		'campaign_title'       => __( 'Graphics card week', 'premium-shop' ),
		'campaign_text'        => __( 'More frames, less money: selected graphics cards at special prices while stocks last.', 'premium-shop' ),
		'campaign_button_text' => __( 'See the deals', 'premium-shop' ),
		'benefit_1_title'      => __( 'Express shipping', 'premium-shop' ),
		'benefit_1_text'       => __( 'Ordered before 3 pm, shipped today', 'premium-shop' ),
		'benefit_2_title'      => __( '100% original', 'premium-shop' ),
		'benefit_2_text'       => __( 'With manufacturer warranty', 'premium-shop' ),
		'benefit_3_title'      => __( '14-day returns', 'premium-shop' ),
		'benefit_3_text'       => __( 'Simple and free of charge', 'premium-shop' ),
		'benefit_4_title'      => __( 'Gamer support', 'premium-shop' ),
		'benefit_4_text'       => __( 'Advice from real players', 'premium-shop' ),
		'newsletter_title'     => __( 'Drops & deals first', 'premium-shop' ),
		'newsletter_text'      => __( 'Be the first to know about new consoles, restocks and limited deals. Unsubscribe at any time.', 'premium-shop' ),
		'company_about'        => __( 'Consoles, graphics cards and accessories from official channels — tested, fairly priced and shipped fast.', 'premium-shop' ),
		'delivery_time'        => __( 'Delivery within 1–3 business days', 'premium-shop' ),
		'warranty_text'        => __( '2-year manufacturer warranty', 'premium-shop' ),
		'title_faq'            => __( 'Frequently asked questions', 'premium-shop' ),
	);
}

/**
 * Gaming FAQ.
 *
 * @param array $items Items.
 * @return array
 */
function premium_shop_gaming_faq( $items ) {
	if ( 'gaming' !== premium_shop_preset() || '' !== trim( (string) get_theme_mod( 'ps_fw_faq', '' ) ) ) {
		return $items;
	}
	return array(
		array( __( 'Are your products original?', 'premium-shop' ), __( 'Yes. All consoles, graphics cards and accessories come from official distribution channels, are new and sealed, and include the full manufacturer warranty.', 'premium-shop' ) ),
		array( __( 'How fast do you deliver?', 'premium-shop' ), __( 'Orders placed before 3 pm on business days leave our warehouse the same day. Delivery usually takes 1–3 business days, with tracking.', 'premium-shop' ) ),
		array( __( 'Which graphics card suits my PC?', 'premium-shop' ), __( 'Check the length of the card, your power supply (wattage and connectors) and your screen resolution. Send us your configuration — we gladly recommend the right model.', 'premium-shop' ) ),
		array( __( 'Can I return a product?', 'premium-shop' ), __( 'You can return unused products in their original packaging within 14 days. Defective products are repaired or replaced under warranty.', 'premium-shop' ) ),
		array( __( 'Which payment methods do you accept?', 'premium-shop' ), __( 'TWINT, credit and debit cards, PayPal, Apple Pay, Google Pay and bank transfer — all payments are encrypted.', 'premium-shop' ) ),
		array( __( 'Do consoles work with games from other regions?', 'premium-shop' ), __( 'Current Nintendo and PlayStation consoles play games from all regions. Online services and some content may depend on your account region.', 'premium-shop' ) ),
	);
}
add_filter( 'premium_shop_faq_items', 'premium_shop_gaming_faq' );

/**
 * Feature cards (shipping/about pages).
 *
 * @param array $items Items.
 * @return array
 */
function premium_shop_gaming_features( $items ) {
	if ( 'gaming' === premium_shop_preset() && $items ) {
		$items[0] = array( 'shield', __( '100% original', 'premium-shop' ), __( 'New, sealed products with the full manufacturer warranty.', 'premium-shop' ) );
	}
	return $items;
}
add_filter( 'premium_shop_features_items', 'premium_shop_gaming_features' );

/**
 * Sample catalogue.
 *
 * @return array
 */
function premium_shop_gaming_catalogue() {
	return array(
		'categories' => array(
			'nintendo'    => array( 'Nintendo', 'nintendo-hybrid.jpg', 'Nintendo Switch consoles, controllers and games — play at home and on the go.' ),
			'playstation' => array( 'PlayStation 5', 'console-tower.jpg', 'PlayStation 5 consoles and DualSense controllers for next-generation gaming.' ),
			'gpu'         => array( 'Graphics cards', 'gpu-triple.jpg', 'Graphics cards for gaming, streaming and creative work — from Full HD to 4K.' ),
			'accessories' => array( 'Accessories', 'headset.jpg', 'Headsets, controllers and everything else that makes gaming better.' ),
		),
		'products'   => array(
			array( 'sku' => 'GM-NSW2', 'name' => 'Nintendo Switch 2 console', 'short' => 'The new hybrid console: bigger screen, more power — play on the TV or on the go.', 'cat' => 'nintendo', 'image' => 'nintendo-hybrid.jpg', 'price' => 499 ),
			array( 'sku' => 'GM-NSW-OLED', 'name' => 'Nintendo Switch OLED console – neon edition', 'short' => '7-inch OLED screen, stand and dock included.', 'cat' => 'nintendo', 'image' => 'nintendo-hybrid-alt.jpg', 'price' => 349, 'sale' => 319 ),
			array( 'sku' => 'GM-NSW-LITE', 'name' => 'Nintendo Switch Lite – turquoise', 'short' => 'Compact and light, made for gaming on the go.', 'cat' => 'nintendo', 'image' => 'nintendo-lite.jpg', 'price' => 219 ),
			array( 'sku' => 'GM-PS5', 'name' => 'PlayStation 5 console', 'short' => 'Ultra-fast SSD, 4K gaming and haptic feedback with the DualSense controller.', 'cat' => 'playstation', 'image' => 'console-tower.jpg', 'price' => 549 ),
			array( 'sku' => 'GM-PS5-PRO', 'name' => 'PlayStation 5 Pro console', 'short' => 'More graphics power, 2 TB SSD — for the best picture quality.', 'cat' => 'playstation', 'image' => 'console-tower.jpg', 'price' => 799 ),
			array( 'sku' => 'GM-PS5-PAD-W', 'name' => 'DualSense wireless controller – white', 'short' => 'Haptic feedback and adaptive triggers.', 'cat' => 'playstation', 'image' => 'gamepad.jpg', 'price' => 74.9 ),
			array( 'sku' => 'GM-PS5-PAD-B', 'name' => 'DualSense wireless controller – midnight black', 'short' => 'Haptic feedback and adaptive triggers.', 'cat' => 'playstation', 'image' => 'gamepad-dark.jpg', 'price' => 74.9, 'sale' => 64.9 ),
			array( 'sku' => 'GM-RTX5070', 'name' => 'GeForce RTX 5070 graphics card, 12 GB', 'short' => 'Smooth 1440p gaming with ray tracing and DLSS.', 'cat' => 'gpu', 'image' => 'gpu-triple.jpg', 'price' => 599 ),
			array( 'sku' => 'GM-RTX5080', 'name' => 'GeForce RTX 5080 graphics card, 16 GB', 'short' => 'High-end 4K gaming, triple-fan cooling and RGB lighting.', 'cat' => 'gpu', 'image' => 'gpu-triple-rgb.jpg', 'price' => 1199, 'sale' => 1099 ),
			array( 'sku' => 'GM-RX9070XT', 'name' => 'Radeon RX 9070 XT graphics card, 16 GB', 'short' => 'Excellent value for 1440p and 4K, quiet dual-fan design.', 'cat' => 'gpu', 'image' => 'gpu-dual-white.jpg', 'price' => 699 ),
			array( 'sku' => 'GM-HEADSET', 'name' => 'Wireless gaming headset', 'short' => '3D audio, noise-cancelling microphone, 30 hours of battery life.', 'cat' => 'accessories', 'image' => 'headset.jpg', 'price' => 129 ),
			array( 'sku' => 'GM-GAMES', 'name' => 'Game bundle – 3 bestsellers of your choice', 'short' => 'Pick three top titles for your console and save.', 'cat' => 'accessories', 'image' => 'games.jpg', 'price' => 159, 'sale' => 139 ),
		),
	);
}

/**
 * Source strings of the catalogue (only for the translation tools).
 *
 * @return array
 */
function premium_shop_gaming_catalogue_i18n() {
	return array(
		__( 'Nintendo', 'premium-shop' ),
		__( 'PlayStation 5', 'premium-shop' ),
		__( 'Graphics cards', 'premium-shop' ),
		__( 'Accessories', 'premium-shop' ),
		__( 'Nintendo Switch consoles, controllers and games — play at home and on the go.', 'premium-shop' ),
		__( 'PlayStation 5 consoles and DualSense controllers for next-generation gaming.', 'premium-shop' ),
		__( 'Graphics cards for gaming, streaming and creative work — from Full HD to 4K.', 'premium-shop' ),
		__( 'Headsets, controllers and everything else that makes gaming better.', 'premium-shop' ),
		__( 'Nintendo Switch 2 console', 'premium-shop' ),
		__( 'Nintendo Switch OLED console – neon edition', 'premium-shop' ),
		__( 'Nintendo Switch Lite – turquoise', 'premium-shop' ),
		__( 'PlayStation 5 console', 'premium-shop' ),
		__( 'PlayStation 5 Pro console', 'premium-shop' ),
		__( 'DualSense wireless controller – white', 'premium-shop' ),
		__( 'DualSense wireless controller – midnight black', 'premium-shop' ),
		__( 'GeForce RTX 5070 graphics card, 12 GB', 'premium-shop' ),
		__( 'GeForce RTX 5080 graphics card, 16 GB', 'premium-shop' ),
		__( 'Radeon RX 9070 XT graphics card, 16 GB', 'premium-shop' ),
		__( 'Wireless gaming headset', 'premium-shop' ),
		__( 'Game bundle – 3 bestsellers of your choice', 'premium-shop' ),
		__( 'The new hybrid console: bigger screen, more power — play on the TV or on the go.', 'premium-shop' ),
		__( '7-inch OLED screen, stand and dock included.', 'premium-shop' ),
		__( 'Compact and light, made for gaming on the go.', 'premium-shop' ),
		__( 'Ultra-fast SSD, 4K gaming and haptic feedback with the DualSense controller.', 'premium-shop' ),
		__( 'More graphics power, 2 TB SSD — for the best picture quality.', 'premium-shop' ),
		__( 'Haptic feedback and adaptive triggers.', 'premium-shop' ),
		__( 'Smooth 1440p gaming with ray tracing and DLSS.', 'premium-shop' ),
		__( 'High-end 4K gaming, triple-fan cooling and RGB lighting.', 'premium-shop' ),
		__( 'Excellent value for 1440p and 4K, quiet dual-fan design.', 'premium-shop' ),
		__( '3D audio, noise-cancelling microphone, 30 hours of battery life.', 'premium-shop' ),
		__( 'Pick three top titles for your console and save.', 'premium-shop' ),
	);
}

/**
 * Create the sample gaming catalogue (categories with images, 12 products,
 * names and short descriptions in every enabled language).
 *
 * @param bool $publish Publish immediately (else drafts).
 * @return int Number of products created.
 */
function premium_shop_gaming_create_catalogue( $publish = false ) {
	if ( ! premium_shop_is_wc() ) {
		return 0;
	}
	$data    = premium_shop_gaming_catalogue();
	$default = premium_shop_default_language();
	$tr      = static function ( $text, $code ) {
		$locale = premium_shop_locale_for( $code );
		return 0 === strpos( $locale, 'en_' ) ? $text : premium_shop_translate_in( $text, $locale );
	};

	$cats  = array();
	$order = 0;
	foreach ( $data['categories'] as $key => $cat ) {
		$name = $tr( $cat[0], $default );
		$term = term_exists( $name, 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'product_cat', array( 'description' => $tr( $cat[2], $default ) ) );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$cats[ $key ] = (int) $term['term_id'];
		update_term_meta( $cats[ $key ], 'order', $order++ );
		foreach ( array_keys( premium_shop_languages() ) as $code ) {
			if ( $code !== $default ) {
				update_term_meta( $cats[ $key ], '_ps_name_' . $code, $tr( $cat[0], $code ) );
			}
		}
		if ( ! get_term_meta( $cats[ $key ], 'thumbnail_id', true ) ) {
			$image = premium_shop_fw_import_image( $cat[1], $name, 'gaming' );
			if ( $image ) {
				update_term_meta( $cats[ $key ], 'thumbnail_id', $image );
			}
		}
	}

	$created = 0;
	foreach ( $data['products'] as $def ) {
		if ( wc_get_product_id_by_sku( $def['sku'] ) ) {
			continue;
		}
		$name    = $tr( $def['name'], $default );
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_sku( $def['sku'] );
		$product->set_status( $publish ? 'publish' : 'draft' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( (string) $def['price'] );
		if ( ! empty( $def['sale'] ) ) {
			$product->set_sale_price( (string) $def['sale'] );
		}
		$product->set_short_description( $tr( $def['short'], $default ) );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_category_ids( isset( $cats[ $def['cat'] ] ) ? array( $cats[ $def['cat'] ] ) : array() );
		$image = premium_shop_fw_import_image( $def['image'], $name, 'gaming' );
		if ( $image ) {
			$product->set_image_id( $image );
		}
		foreach ( array_keys( premium_shop_languages() ) as $code ) {
			if ( $code !== $default ) {
				$product->update_meta_data( '_ps_name_' . $code, $tr( $def['name'], $code ) );
				$product->update_meta_data( '_ps_short_' . $code, $tr( $def['short'], $code ) );
			}
		}
		$product->save();
		$created++;
	}
	if ( $created ) {
		delete_transient( 'premium_shop_hero_pool' );
		delete_transient( 'premium_shop_showcase_ids' );
		delete_transient( 'premium_shop_hero_slides' );
	}
	return $created;
}
