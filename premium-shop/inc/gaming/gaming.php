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
			'color_primary'           => '#7c5cff',
			'color_accent'            => '#00d4ff',
			'color_background'        => '#161b3d',
			'color_surface'           => '#1f2552',
			'color_soft'              => '#283070',
			'color_text'              => '#f6f7ff',
			'color_text_muted'        => '#c5cbef',
			'color_border'            => '#3a4282',
			'color_footer_bg'         => '#11152f',
			'color_footer_text'       => '#e6e8ff',
			'color_promo_bg'          => '#7c5cff',
			'color_promo_text'        => '#ffffff',
			'heading_weight'          => '700',
			'font_heading'            => 'space-grotesk',
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
	$festive = premium_shop_gaming_festive() ? premium_shop_gaming_festive_texts() : array();
	return $festive + array(
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
		'company_about'        => __( 'Consoles, controllers, speakers and graphics cards from official channels — fair prices, honest advice and fast delivery in Switzerland.', 'premium-shop' ),
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
		array( __( 'Which console should I give a child?', 'premium-shop' ), __( 'For children and families, Nintendo Switch 2 or Switch Lite are ideal: robust, easy to use and with parental controls. Teenagers usually prefer the PlayStation 5 Slim. Not sure? Write to us — we gladly advise you.', 'premium-shop' ) ),
		array( __( 'Can I give a gift card?', 'premium-shop' ), __( 'Yes: PlayStation Store and Nintendo eShop gift cards are the perfect last-minute present — the recipient chooses their own games. They work with accounts set to Switzerland.', 'premium-shop' ) ),
		array( __( 'Can you wrap my order as a gift?', 'premium-shop' ), __( 'Add a note to your order with your gift message: we include it free of charge, and the invoice is not put in the parcel on request.', 'premium-shop' ) ),
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
 * Sample catalogue (consoles, controllers, speakers, graphics cards, games
 * and gift cards), with texts in every language — see catalogue-data.php.
 *
 * @return array
 */
function premium_shop_gaming_catalogue() {
	static $data = null;
	if ( null === $data ) {
		$data = include __DIR__ . '/catalogue-data.php';
	}
	return apply_filters( 'premium_shop_gaming_catalogue', $data );
}

/**
 * Text of the catalogue in a language (falls back to English, then German).
 *
 * @param array  $texts Texts by language code.
 * @param string $code  Language code.
 * @return string
 */
function premium_shop_gaming_pick( $texts, $code ) {
	foreach ( array( $code, 'en', 'de' ) as $try ) {
		if ( ! empty( $texts[ $try ] ) ) {
			return $texts[ $try ];
		}
	}
	return '';
}

/**
 * Create the sample gaming catalogue: categories with images and every
 * product with photo, prices (incl. holiday deals) and texts in every
 * enabled language.
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
	$codes   = array_keys( premium_shop_languages() );

	$cats  = array();
	$order = 0;
	foreach ( $data['categories'] as $key => $cat ) {
		$name = premium_shop_gaming_pick( $cat['name'], $default );
		$term = term_exists( $name, 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'product_cat', array( 'description' => premium_shop_gaming_pick( $cat['desc'], $default ) ) );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$cats[ $key ] = (int) $term['term_id'];
		update_term_meta( $cats[ $key ], 'order', $order++ );
		foreach ( $codes as $code ) {
			if ( $code !== $default ) {
				update_term_meta( $cats[ $key ], '_ps_name_' . $code, premium_shop_gaming_pick( $cat['name'], $code ) );
				update_term_meta( $cats[ $key ], '_ps_desc_' . $code, premium_shop_gaming_pick( $cat['desc'], $code ) );
			}
		}
		if ( ! get_term_meta( $cats[ $key ], 'thumbnail_id', true ) ) {
			$image = premium_shop_fw_import_image( $cat['img'], $name, 'gaming' );
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
		$name    = premium_shop_gaming_pick( $def['name'], $default );
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_sku( $def['sku'] );
		$product->set_status( $publish ? 'publish' : 'draft' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_featured( ! empty( $def['featured'] ) );
		$product->set_regular_price( (string) $def['price'] );
		if ( '' !== (string) $def['sale'] ) {
			$product->set_sale_price( (string) $def['sale'] );
		}
		$product->set_short_description( premium_shop_gaming_pick( $def['short'], $default ) );
		$product->set_description( premium_shop_gaming_pick( $def['desc'], $default ) );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_category_ids( isset( $cats[ $def['cat'] ] ) ? array( $cats[ $def['cat'] ] ) : array() );
		$image = premium_shop_fw_import_image( $def['img'], $name, 'gaming' );
		if ( $image ) {
			$product->set_image_id( $image );
		}
		foreach ( $codes as $code ) {
			if ( $code !== $default ) {
				$product->update_meta_data( '_ps_name_' . $code, premium_shop_gaming_pick( $def['name'], $code ) );
				$product->update_meta_data( '_ps_short_' . $code, premium_shop_gaming_pick( $def['short'], $code ) );
				$product->update_meta_data( '_ps_desc_' . $code, premium_shop_gaming_pick( $def['desc'], $code ) );
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

/**
 * Pictures floating on the hero stage.
 *
 * @return string[] URLs.
 */
function premium_shop_gaming_stage_images() {
	$files = array( 'ps5-slim.jpg', 'switch2.jpg', 'speaker-red.jpg', premium_shop_gaming_festive() ? 'gifts.jpg' : 'dualsense-white.jpg' );
	$urls  = array();
	foreach ( $files as $file ) {
		$urls[] = PREMIUM_SHOP_URI . '/assets/images/gaming/' . $file;
	}
	return apply_filters( 'premium_shop_gaming_stage_images', $urls );
}

/**
 * Homepage of the gaming style: its own hero and the platform band.
 *
 * @param string[] $sections Sections.
 * @return string[]
 */
function premium_shop_gaming_home_sections( $sections ) {
	if ( 'gaming' !== premium_shop_preset() ) {
		return $sections;
	}
	$out = array();
	foreach ( $sections as $section ) {
		$out[] = 'hero' === $section ? 'hero-gaming' : $section;
		if ( 'categories' === $section ) {
			$out[] = 'gaming-marquee';
		}
	}
	if ( ! in_array( 'gaming-marquee', $out, true ) ) {
		array_splice( $out, min( 1, count( $out ) ), 0, 'gaming-marquee' );
	}
	return $out;
}
add_filter( 'premium_shop_home_sections', 'premium_shop_gaming_home_sections' );

/**
 * Styles and animations of the gaming style.
 */
function premium_shop_gaming_assets() {
	if ( 'gaming' !== premium_shop_preset() ) {
		return;
	}
	$stamp   = @filemtime( PREMIUM_SHOP_DIR . '/assets/css/gaming.css' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$version = PREMIUM_SHOP_VERSION . ( $stamp ? '.' . $stamp : '' );
	$deps    = premium_shop_is_wc() ? array( 'premium-shop', 'premium-shop-woocommerce' ) : array( 'premium-shop' );
	wp_enqueue_style( 'premium-shop-gaming', premium_shop_asset( 'css/gaming', 'css' ), $deps, $version );
	wp_enqueue_script( 'premium-shop-gaming', premium_shop_asset( 'js/gaming', 'js' ), array( 'premium-shop' ), $version, true );
	wp_localize_script(
		'premium-shop-gaming',
		'PremiumShopGaming',
		array(
			'endsIn' => __( 'Ends in', 'premium-shop' ),
			'units'  => array( __( 'days', 'premium-shop' ), __( 'hrs', 'premium-shop' ), __( 'min', 'premium-shop' ), __( 'sec', 'premium-shop' ) ),
			'merry'  => __( 'Happy holidays!', 'premium-shop' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'premium_shop_gaming_assets', 20 );

/**
 * Holiday season mode (gaming style): festive texts, garland, snow,
 * gift finder, Christmas countdown and confetti.
 *
 * @return bool
 */
function premium_shop_gaming_festive() {
	return 'gaming' === premium_shop_preset() && (bool) premium_shop_option( 'gaming_festive' );
}

/**
 * Holiday texts (replace the regular defaults while the mode is on).
 *
 * @return array
 */
function premium_shop_gaming_festive_texts() {
	return array(
		'promo_text'           => implode(
			"\n",
			array(
				__( 'Holiday deals: up to 20% off consoles and graphics cards', 'premium-shop' ),
				__( 'Order by 20 December — delivered before Christmas', 'premium-shop' ),
				__( 'Free gift message with every order', 'premium-shop' ),
			)
		),
		'hero_eyebrow'         => __( 'Holiday deals are here', 'premium-shop' ),
		'hero_title'           => __( 'The gifts every gamer is dreaming of', 'premium-shop' ),
		'hero_subtitle'        => __( 'PlayStation 5 Slim, Nintendo Switch 2, DualSense controllers and JBL speakers at holiday prices — original products, wrapped with care and delivered before Christmas.', 'premium-shop' ),
		'hero_button_text'     => __( 'Find the perfect gift', 'premium-shop' ),
		'hero_button2_text'    => __( 'Holiday deals', 'premium-shop' ),
		'title_bestsellers'    => __( 'Top gift ideas', 'premium-shop' ),
		'title_sale'           => __( 'Holiday deals', 'premium-shop' ),
		'title_new'            => __( 'New for the holidays', 'premium-shop' ),
		'campaign_eyebrow'     => __( 'End-of-year sale', 'premium-shop' ),
		'campaign_title'       => __( 'PS5 Slim at its holiday price', 'premium-shop' ),
		'campaign_text'        => __( 'The most wanted gift of the year: save up to CHF 50 on the PlayStation 5 Slim and up to CHF 30 on JBL speakers, while stocks last.', 'premium-shop' ),
		'campaign_button_text' => __( 'Unwrap the deals', 'premium-shop' ),
		'benefit_1_title'      => __( 'Delivered before Christmas', 'premium-shop' ),
		'benefit_1_text'       => __( 'Order by 20 December', 'premium-shop' ),
		'benefit_3_title'      => __( 'Extended returns', 'premium-shop' ),
		'benefit_3_text'       => __( 'Gifts can be exchanged until 15 January', 'premium-shop' ),
		'newsletter_title'     => __( 'Holiday deals straight to your inbox', 'premium-shop' ),
		'newsletter_text'      => __( 'Flash deals, restocks and last-minute gift ideas until New Year. Unsubscribe at any time.', 'premium-shop' ),
	);
}

/**
 * Body class of the holiday mode.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function premium_shop_gaming_festive_body_class( $classes ) {
	if ( premium_shop_gaming_festive() ) {
		$classes[] = 'ps-festive';
	}
	return $classes;
}
add_filter( 'body_class', 'premium_shop_gaming_festive_body_class' );

/**
 * Holiday garland across the top of the page and the snow switch.
 */
function premium_shop_gaming_festive_markup() {
	if ( ! premium_shop_gaming_festive() ) {
		return;
	}
	echo '<div class="g-garland" aria-hidden="true">';
	for ( $i = 0; $i < 36; $i++ ) {
		echo '<i></i>';
	}
	echo '</div>';
	printf(
		'<button type="button" class="g-snow-toggle" data-g-snow-toggle aria-pressed="true" title="%1$s"><span aria-hidden="true">❄</span><span class="screen-reader-text">%1$s</span></button>',
		esc_attr__( 'Snow on / off', 'premium-shop' )
	);
}
add_action( 'wp_body_open', 'premium_shop_gaming_festive_markup', 5 );

/**
 * Gift finder sections after the hero while the holiday mode is on.
 *
 * @param string[] $sections Sections.
 * @return string[]
 */
function premium_shop_gaming_festive_sections( $sections ) {
	if ( ! premium_shop_gaming_festive() ) {
		return $sections;
	}
	$at = array_search( 'hero-gaming', $sections, true );
	array_splice( $sections, false === $at ? 0 : $at + 1, 0, 'gaming-gifts' );
	return $sections;
}
add_filter( 'premium_shop_home_sections', 'premium_shop_gaming_festive_sections', 20 );

/**
 * Category names of the gaming CSV, kept in the catalogue so that they are
 * shown in the visitor's language.
 *
 * @return string[]
 */
function premium_shop_gaming_category_strings() {
	return array(
		__( 'Controllers', 'premium-shop' ),
		__( 'Audio & speakers', 'premium-shop' ),
		__( 'Games & gift cards', 'premium-shop' ),
		__( 'Graphics cards', 'premium-shop' ),
		__( 'Gift idea', 'premium-shop' ),
		__( 'Accessories', 'premium-shop' ),
	);
}
