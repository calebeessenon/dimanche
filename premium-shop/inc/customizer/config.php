<?php
/**
 * Declarative Customizer configuration.
 *
 * Every option of the theme is described here once: the Customizer controls,
 * sanitization and defaults are all generated from this array.
 *
 * Text fields default to an empty string: when empty, the theme displays a
 * built-in text that is translated automatically into every language.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Homepage sections that can be ordered / toggled.
 *
 * @return array id => label
 */
function premium_shop_home_section_labels() {
	return array(
		'hero'         => __( 'Hero', 'premium-shop' ),
		'categories'   => __( 'Categories', 'premium-shop' ),
		'popular'      => __( 'Popular products', 'premium-shop' ),
		'campaign'     => __( 'Campaign banner', 'premium-shop' ),
		'new'          => __( 'New arrivals', 'premium-shop' ),
		'sale'         => __( 'Offers', 'premium-shop' ),
		'bestsellers'  => __( 'Bestsellers', 'premium-shop' ),
		'benefits'     => __( 'Benefits', 'premium-shop' ),
		'testimonials' => __( 'Testimonials', 'premium-shop' ),
		'brands'       => __( 'Brands', 'premium-shop' ),
		'newsletter'   => __( 'Newsletter', 'premium-shop' ),
		'content'      => __( 'Page content (editor)', 'premium-shop' ),
		'calculator'   => __( 'Firewood calculator', 'premium-shop' ),
		'delivery'     => __( 'Delivery check (postcode)', 'premium-shop' ),
		'faq'          => __( 'FAQ', 'premium-shop' ),
	);
}

/**
 * Icon choices for benefit items.
 *
 * @return array
 */
function premium_shop_benefit_icon_choices() {
	return array(
		'truck'   => __( 'Truck', 'premium-shop' ),
		'shield'  => __( 'Shield', 'premium-shop' ),
		'return'  => __( 'Return arrow', 'premium-shop' ),
		'support' => __( 'Headset', 'premium-shop' ),
		'gift'    => __( 'Gift', 'premium-shop' ),
		'leaf'    => __( 'Leaf', 'premium-shop' ),
		'star'    => __( 'Star', 'premium-shop' ),
		'clock'   => __( 'Clock', 'premium-shop' ),
		'lock'    => __( 'Lock', 'premium-shop' ),
		'box'     => __( 'Box', 'premium-shop' ),
		'flame'   => __( 'Flame', 'premium-shop' ),
		'logs'    => __( 'Logs', 'premium-shop' ),
		'droplet' => __( 'Moisture drop', 'premium-shop' ),
		'ruler'   => __( 'Ruler', 'premium-shop' ),
		'tree'    => __( 'Tree', 'premium-shop' ),
	);
}

/**
 * Full Customizer configuration.
 *
 * @return array
 */
function premium_shop_customizer_config() {
	static $config = null;

	if ( null !== $config ) {
		return $config;
	}

	$text_hint = __( 'Leave empty to use the built-in text, translated automatically. Multilingual syntax: [:de]Text[:fr]Texte[:es]Texto[:en]Text', 'premium-shop' );

	$sections = array();

	/* ------------------------------------------------------------------ */
	$sections['ps_colors'] = array(
		'title'  => __( 'Colors', 'premium-shop' ),
		'fields' => array(
			'color_primary'     => array( 'type' => 'color', 'default' => '#16181d', 'label' => __( 'Primary (buttons, header text)', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_accent'      => array( 'type' => 'color', 'default' => '#8f602b', 'label' => __( 'Accent (highlights, hover)', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_background'  => array( 'type' => 'color', 'default' => '#fbf9f5', 'label' => __( 'Page background', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_surface'     => array( 'type' => 'color', 'default' => '#ffffff', 'label' => __( 'Cards & panels', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_soft'        => array( 'type' => 'color', 'default' => '#f2ede5', 'label' => __( 'Soft sections background', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_text'        => array( 'type' => 'color', 'default' => '#1b1d22', 'label' => __( 'Text', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_text_muted'  => array( 'type' => 'color', 'default' => '#5f626b', 'label' => __( 'Secondary text', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_border'      => array( 'type' => 'color', 'default' => '#e6e0d6', 'label' => __( 'Borders', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_sale'        => array( 'type' => 'color', 'default' => '#b3261e', 'label' => __( 'Sale / discount', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_success'     => array( 'type' => 'color', 'default' => '#2e6b4f', 'label' => __( 'In stock / success', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_footer_bg'   => array( 'type' => 'color', 'default' => '#14161a', 'label' => __( 'Footer background', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_footer_text' => array( 'type' => 'color', 'default' => '#ece7df', 'label' => __( 'Footer text', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_promo_bg'    => array( 'type' => 'color', 'default' => '#16181d', 'label' => __( 'Announcement bar background', 'premium-shop' ), 'transport' => 'postMessage' ),
			'color_promo_text'  => array( 'type' => 'color', 'default' => '#f2ede5', 'label' => __( 'Announcement bar text', 'premium-shop' ), 'transport' => 'postMessage' ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_typography'] = array(
		'title'  => __( 'Typography & buttons', 'premium-shop' ),
		'fields' => array(
			'font_heading'      => array(
				'type'    => 'select',
				'default' => 'fraunces',
				'label'   => __( 'Heading font', 'premium-shop' ),
				'choices' => array(
					'fraunces'     => 'Fraunces (serif, elegant)',
					'inter'        => 'Inter (sans-serif, modern)',
					'system-serif' => __( 'System serif (no download)', 'premium-shop' ),
					'system-sans'  => __( 'System sans-serif (no download)', 'premium-shop' ),
				),
			),
			'font_body'         => array(
				'type'    => 'select',
				'default' => 'inter',
				'label'   => __( 'Body font', 'premium-shop' ),
				'choices' => array(
					'inter'        => 'Inter',
					'system-sans'  => __( 'System sans-serif (no download)', 'premium-shop' ),
					'system-serif' => __( 'System serif (no download)', 'premium-shop' ),
				),
			),
			'font_size_base'    => array( 'type' => 'number', 'default' => 16, 'min' => 14, 'max' => 19, 'label' => __( 'Base font size (px)', 'premium-shop' ) ),
			'heading_weight'    => array(
				'type'    => 'select',
				'default' => '400',
				'label'   => __( 'Heading weight', 'premium-shop' ),
				'choices' => array(
					'300' => __( 'Light', 'premium-shop' ),
					'400' => __( 'Regular', 'premium-shop' ),
					'500' => __( 'Medium', 'premium-shop' ),
					'600' => __( 'Semi-bold', 'premium-shop' ),
				),
			),
			'button_style'      => array(
				'type'    => 'select',
				'default' => 'soft',
				'label'   => __( 'Button shape', 'premium-shop' ),
				'choices' => array(
					'square' => __( 'Square', 'premium-shop' ),
					'soft'   => __( 'Soft corners', 'premium-shop' ),
					'pill'   => __( 'Pill', 'premium-shop' ),
				),
			),
			'buttons_uppercase' => array( 'type' => 'checkbox', 'default' => false, 'label' => __( 'Uppercase buttons', 'premium-shop' ) ),
			'card_radius'       => array( 'type' => 'number', 'default' => 14, 'min' => 0, 'max' => 32, 'label' => __( 'Card corner radius (px)', 'premium-shop' ), 'transport' => 'postMessage' ),
			'animations'        => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Scroll-reveal animations', 'premium-shop' ), 'description' => __( 'Automatically disabled for visitors who prefer reduced motion.', 'premium-shop' ) ),
			'page_hero'         => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Illustrated page headers', 'premium-shop' ), 'description' => __( 'Pages, cart, checkout, account and order tracking get a lively header with photos of your products.', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_header'] = array(
		'title'  => __( 'Header & announcement bar', 'premium-shop' ),
		'fields' => array(
			'logo_height'         => array( 'type' => 'number', 'default' => 36, 'min' => 20, 'max' => 120, 'label' => __( 'Logo height (px)', 'premium-shop' ), 'transport' => 'postMessage' ),
			'header_layout'       => array(
				'type'    => 'select',
				'default' => 'logo-left',
				'label'   => __( 'Header layout', 'premium-shop' ),
				'choices' => array(
					'logo-left'   => __( 'Logo left, menu centered', 'premium-shop' ),
					'logo-center' => __( 'Logo centered, menu below', 'premium-shop' ),
				),
			),
			'header_sticky'       => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Sticky header', 'premium-shop' ) ),
			'header_search'       => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show search', 'premium-shop' ) ),
			'header_account'      => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show account icon', 'premium-shop' ) ),
			'header_wishlist'     => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show wishlist icon', 'premium-shop' ) ),
			'header_languages'    => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show language switcher', 'premium-shop' ) ),
			'mega_categories'     => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Categories mega menu', 'premium-shop' ), 'description' => __( 'Applies to menu items with the CSS class "mega-categories" (added automatically by the setup assistant).', 'premium-shop' ) ),
			'minimal_checkout'    => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Distraction-free checkout header', 'premium-shop' ) ),
			'promo_enabled'       => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show announcement bar', 'premium-shop' ) ),
			'promo_text'          => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Announcement messages (one per line, they rotate)', 'premium-shop' ), 'description' => $text_hint ),
			'promo_link'          => array( 'type' => 'url', 'default' => '', 'label' => __( 'Announcement link (optional)', 'premium-shop' ) ),
			'promo_dismissible'   => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Visitors can close the bar', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_home_layout'] = array(
		'title'  => __( 'Homepage — sections & order', 'premium-shop' ),
		'fields' => array(
			'home_sections'      => array(
				'type'        => 'sortable',
				'default'     => 'hero:1,categories:1,popular:1,campaign:1,new:1,sale:1,bestsellers:1,benefits:1,testimonials:1,brands:1,newsletter:1,content:0',
				'label'       => __( 'Sections', 'premium-shop' ),
				'description' => __( 'Drag to reorder, untick to hide.', 'premium-shop' ),
			),
			'home_products_count' => array( 'type' => 'number', 'default' => 8, 'min' => 4, 'max' => 16, 'label' => __( 'Products per section', 'premium-shop' ) ),
			'home_categories_count' => array( 'type' => 'number', 'default' => 5, 'min' => 3, 'max' => 12, 'label' => __( 'Categories shown', 'premium-shop' ) ),
			'home_categories_ids' => array( 'type' => 'text', 'default' => '', 'label' => __( 'Category IDs to show (optional)', 'premium-shop' ), 'description' => __( 'Comma-separated IDs, in the desired order. Empty = main categories with products.', 'premium-shop' ) ),
			'title_categories'   => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Categories — title', 'premium-shop' ), 'description' => $text_hint ),
			'title_popular'      => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Popular products — title', 'premium-shop' ) ),
			'title_new'          => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'New arrivals — title', 'premium-shop' ) ),
			'title_sale'         => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Offers — title', 'premium-shop' ) ),
			'title_bestsellers'  => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Bestsellers — title', 'premium-shop' ) ),
			'title_testimonials' => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Testimonials — title', 'premium-shop' ) ),
			'title_brands'       => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Brands — title', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_hero'] = array(
		'title'  => __( 'Homepage — hero', 'premium-shop' ),
		'fields' => array(
			'hero_layout'          => array(
				'type'    => 'select',
				'default' => 'split',
				'label'   => __( 'Layout', 'premium-shop' ),
				'choices' => array(
					'split' => __( 'Editorial split (text + arched image)', 'premium-shop' ),
					'cover' => __( 'Full-width image', 'premium-shop' ),
				),
			),
			'hero_image'           => array( 'type' => 'image', 'default' => 0, 'label' => __( 'Hero image', 'premium-shop' ), 'description' => __( 'Recommended: 1400 × 1600 px (split) or 2400 × 1200 px (full width). Without image, a featured product image or an abstract artwork is used.', 'premium-shop' ) ),
			'hero_eyebrow'         => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Small line above the title', 'premium-shop' ), 'description' => $text_hint ),
			'hero_title'           => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Title', 'premium-shop' ) ),
			'hero_subtitle'        => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Subtitle', 'premium-shop' ) ),
			'hero_button_text'     => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Main button text', 'premium-shop' ) ),
			'hero_button_url'      => array( 'type' => 'url', 'default' => '', 'label' => __( 'Main button link (empty = shop)', 'premium-shop' ) ),
			'hero_button2_text'    => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Second button text', 'premium-shop' ) ),
			'hero_button2_url'     => array( 'type' => 'url', 'default' => '', 'label' => __( 'Second button link (empty = offers)', 'premium-shop' ) ),
			'hero_overlay'         => array( 'type' => 'number', 'default' => 35, 'min' => 0, 'max' => 90, 'label' => __( 'Image overlay darkness (%) — full-width layout', 'premium-shop' ) ),
			'hero_badges'          => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show shipping & returns badges', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_campaign'] = array(
		'title'  => __( 'Homepage — campaign banner', 'premium-shop' ),
		'fields' => array(
			'campaign_image'       => array( 'type' => 'image', 'default' => 0, 'label' => __( 'Image', 'premium-shop' ) ),
			'campaign_eyebrow'     => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Small line above the title', 'premium-shop' ), 'description' => $text_hint ),
			'campaign_title'       => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Title', 'premium-shop' ) ),
			'campaign_text'        => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Text', 'premium-shop' ) ),
			'campaign_button_text' => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Button text', 'premium-shop' ) ),
			'campaign_button_url'  => array( 'type' => 'url', 'default' => '', 'label' => __( 'Button link (empty = offers)', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$benefits = array();
	$icons    = array( 'truck', 'shield', 'return', 'support' );
	for ( $i = 1; $i <= 4; $i++ ) {
		/* translators: %d: item number. */
		$benefits[ 'benefit_' . $i . '_icon' ]  = array( 'type' => 'select', 'default' => $icons[ $i - 1 ], 'label' => sprintf( __( 'Benefit %d — icon', 'premium-shop' ), $i ), 'choices' => premium_shop_benefit_icon_choices() );
		/* translators: %d: item number. */
		$benefits[ 'benefit_' . $i . '_title' ] = array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => sprintf( __( 'Benefit %d — title', 'premium-shop' ), $i ), 'description' => 1 === $i ? $text_hint : '' );
		/* translators: %d: item number. */
		$benefits[ 'benefit_' . $i . '_text' ]  = array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => sprintf( __( 'Benefit %d — text', 'premium-shop' ), $i ) );
	}
	$sections['ps_benefits'] = array(
		'title'  => __( 'Homepage — benefits', 'premium-shop' ),
		'fields' => $benefits,
	);

	/* ------------------------------------------------------------------ */
	$testimonials = array(
		'testimonials_source' => array(
			'type'        => 'select',
			'default'     => 'reviews',
			'label'       => __( 'Source', 'premium-shop' ),
			'choices'     => array(
				'reviews' => __( 'Latest 4- and 5-star WooCommerce reviews', 'premium-shop' ),
				'custom'  => __( 'Testimonials entered below', 'premium-shop' ),
				'both'    => __( 'Testimonials below, completed by reviews', 'premium-shop' ),
			),
			'description' => __( 'Only genuine customer feedback should be published. The section is hidden when there is nothing to show.', 'premium-shop' ),
		),
	);
	for ( $i = 1; $i <= 3; $i++ ) {
		/* translators: %d: item number. */
		$testimonials[ 'testimonial_' . $i . '_quote' ]  = array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => sprintf( __( 'Testimonial %d — quote', 'premium-shop' ), $i ) );
		/* translators: %d: item number. */
		$testimonials[ 'testimonial_' . $i . '_author' ] = array( 'type' => 'text', 'default' => '', 'label' => sprintf( __( 'Testimonial %d — name', 'premium-shop' ), $i ) );
		/* translators: %d: item number. */
		$testimonials[ 'testimonial_' . $i . '_meta' ]   = array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => sprintf( __( 'Testimonial %d — city or product', 'premium-shop' ), $i ) );
	}
	$sections['ps_testimonials'] = array(
		'title'  => __( 'Homepage — testimonials', 'premium-shop' ),
		'fields' => $testimonials,
	);

	/* ------------------------------------------------------------------ */
	$brands = array();
	for ( $i = 1; $i <= 8; $i++ ) {
		/* translators: %d: item number. */
		$brands[ 'brand_' . $i . '_logo' ] = array( 'type' => 'image', 'default' => 0, 'label' => sprintf( __( 'Brand %d — logo', 'premium-shop' ), $i ) );
		/* translators: %d: item number. */
		$brands[ 'brand_' . $i . '_url' ]  = array( 'type' => 'url', 'default' => '', 'label' => sprintf( __( 'Brand %d — link', 'premium-shop' ), $i ) );
	}
	$sections['ps_brands'] = array(
		'title'       => __( 'Homepage — brands', 'premium-shop' ),
		'description' => __( 'Upload partner logos. Without logos, WooCommerce brands (Products → Brands) are listed automatically.', 'premium-shop' ),
		'fields'      => $brands,
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_newsletter'] = array(
		'title'  => __( 'Newsletter', 'premium-shop' ),
		'fields' => array(
			'newsletter_title'     => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Title', 'premium-shop' ), 'description' => $text_hint ),
			'newsletter_text'      => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Text', 'premium-shop' ) ),
			'newsletter_shortcode' => array( 'type' => 'text', 'default' => '', 'label' => __( 'Form shortcode (MailPoet, Brevo, Mailchimp…)', 'premium-shop' ), 'description' => __( 'Optional. Without shortcode, the built-in double opt-in form is used (subscribers: Tools → Newsletter).', 'premium-shop' ) ),
			'newsletter_footer'    => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Also show the form in the footer', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_shop'] = array(
		'title'  => __( 'Shop & product cards', 'premium-shop' ),
		'fields' => array(
			'shop_columns'        => array(
				'type'    => 'select',
				'default' => '4',
				'label'   => __( 'Products per row (desktop)', 'premium-shop' ),
				'choices' => array(
					'3' => '3',
					'4' => '4',
				),
			),
			'shop_per_page'       => array( 'type' => 'number', 'default' => 16, 'min' => 4, 'max' => 60, 'label' => __( 'Products per page', 'premium-shop' ) ),
			'shop_filters'        => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show filters', 'premium-shop' ) ),
			'shop_ajax'           => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Instant (AJAX) filtering', 'premium-shop' ) ),
			'shop_view'           => array(
				'type'    => 'select',
				'default' => 'grid',
				'label'   => __( 'Default view', 'premium-shop' ),
				'choices' => array(
					'grid' => __( 'Grid', 'premium-shop' ),
					'list' => __( 'List', 'premium-shop' ),
				),
			),
			'card_button'         => array(
				'type'    => 'select',
				'default' => 'visible',
				'label'   => __( 'Add-to-cart button on cards', 'premium-shop' ),
				'choices' => array(
					'visible' => __( 'Always visible, under the price', 'premium-shop' ),
					'hover'   => __( 'On the image, when hovering', 'premium-shop' ),
				),
			),
			'card_hover_image'    => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show second image on hover', 'premium-shop' ) ),
			'card_quick_view'     => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Quick view', 'premium-shop' ) ),
			'card_wishlist'       => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Wishlist', 'premium-shop' ) ),
			'card_category'       => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show category', 'premium-shop' ) ),
			'card_stock'          => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show stock status', 'premium-shop' ) ),
			'badge_new_days'      => array( 'type' => 'number', 'default' => 30, 'min' => 0, 'max' => 365, 'label' => __( '"New" badge duration (days, 0 = off)', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_product'] = array(
		'title'  => __( 'Product page', 'premium-shop' ),
		'fields' => array(
			'product_buy_now'      => array( 'type' => 'checkbox', 'default' => true, 'label' => __( '"Buy now" button', 'premium-shop' ) ),
			'product_sticky_bar'   => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Sticky add-to-cart bar on mobile', 'premium-shop' ) ),
			'product_trust'        => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show delivery, returns, warranty & payment box', 'premium-shop' ) ),
			'delivery_time'        => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Delivery time', 'premium-shop' ), 'description' => $text_hint ),
			'returns_days'         => array( 'type' => 'number', 'default' => 30, 'min' => 0, 'max' => 365, 'label' => __( 'Return period (days)', 'premium-shop' ) ),
			'warranty_text'        => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Warranty', 'premium-shop' ) ),
			'payment_text'         => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Secure payment', 'premium-shop' ) ),
			'shipping_tab'         => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'html' => true, 'label' => __( '"Shipping & returns" tab content', 'premium-shop' ), 'description' => __( 'Basic HTML allowed. Empty = automatic text built from the values above.', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_cart'] = array(
		'title'  => __( 'Cart & shipping', 'premium-shop' ),
		'fields' => array(
			'free_shipping_threshold' => array( 'type' => 'number', 'default' => 50, 'min' => 0, 'max' => 100000, 'label' => __( 'Free shipping from (amount, 0 = automatic)', 'premium-shop' ), 'description' => __( 'Used for the progress bar in the cart and the default announcement. With 0, the minimum amount of the "Free shipping" method from WooCommerce → Settings → Shipping is used (no bar when there is none).', 'premium-shop' ) ),
			'cart_drawer'             => array( 'type' => 'checkbox', 'default' => false, 'label' => __( 'Open the side cart after adding a product', 'premium-shop' ), 'description' => __( 'Unchecked: the visitor stays on the page, the product flies to the cart icon and a message confirms it.', 'premium-shop' ) ),
			'cart_recommendations'    => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show "You may also like" in the cart', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_contact'] = array(
		'title'  => __( 'Company & contact', 'premium-shop' ),
		'fields' => array(
			'company_name'  => array( 'type' => 'text', 'default' => '', 'label' => __( 'Company name', 'premium-shop' ) ),
			'company_about' => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Short description (footer)', 'premium-shop' ), 'description' => $text_hint ),
			'contact_address' => array( 'type' => 'textarea', 'default' => '', 'label' => __( 'Address', 'premium-shop' ) ),
			'contact_phone' => array( 'type' => 'text', 'default' => '', 'label' => __( 'Phone', 'premium-shop' ) ),
			'contact_email' => array( 'type' => 'email', 'default' => '', 'label' => __( 'Email', 'premium-shop' ) ),
			'contact_hours' => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Opening hours', 'premium-shop' ) ),
			'contact_whatsapp' => array( 'type' => 'text', 'default' => '', 'label' => __( 'WhatsApp number (international format)', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_social'] = array(
		'title'  => __( 'Social networks', 'premium-shop' ),
		'fields' => array(
			'social_instagram' => array( 'type' => 'url', 'default' => '', 'label' => 'Instagram' ),
			'social_facebook'  => array( 'type' => 'url', 'default' => '', 'label' => 'Facebook' ),
			'social_tiktok'    => array( 'type' => 'url', 'default' => '', 'label' => 'TikTok' ),
			'social_pinterest' => array( 'type' => 'url', 'default' => '', 'label' => 'Pinterest' ),
			'social_youtube'   => array( 'type' => 'url', 'default' => '', 'label' => 'YouTube' ),
			'social_x'         => array( 'type' => 'url', 'default' => '', 'label' => 'X' ),
			'social_linkedin'  => array( 'type' => 'url', 'default' => '', 'label' => 'LinkedIn' ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_footer'] = array(
		'title'  => __( 'Footer', 'premium-shop' ),
		'fields' => array(
			'footer_copyright' => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Copyright text', 'premium-shop' ), 'description' => __( 'Use {year} and {company}. Empty = automatic.', 'premium-shop' ) ),
			'footer_payments'  => array( 'type' => 'text', 'default' => 'visa,mastercard,paypal,klarna,applepay,googlepay,sepa', 'label' => __( 'Payment methods shown', 'premium-shop' ), 'description' => __( 'Comma-separated: visa, mastercard, amex, paypal, klarna, applepay, googlepay, sepa, sofort, giropay, bancontact, ideal, invoice', 'premium-shop' ) ),
			'back_to_top'      => array( 'type' => 'checkbox', 'default' => true, 'label' => __( '"Back to top" button', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_firewood'] = array(
		'title'       => __( 'Firewood shop', 'premium-shop' ),
		'description' => __( 'Settings for selling firewood: style preset, unit prices, delivery check by postcode, calculator and FAQ. Product data (wood species, log length, moisture, volume, price per unit) is entered in each product, tab "Firewood".', 'premium-shop' ),
		'fields'      => array(
			'preset'              => array(
				'type'        => 'select',
				'default'     => 'firewood',
				'label'       => __( 'Style preset', 'premium-shop' ),
				'description' => __( 'Sets colors, homepage sections and default texts. Your own changes always take priority.', 'premium-shop' ),
				'choices'     => array(
					'firewood' => __( 'Firewood & stove wood (warm, natural)', 'premium-shop' ),
					'maison'   => __( 'General shop "Maison" (editorial)', 'premium-shop' ),
				),
			),
			'fw_unit_price'       => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show the unit price (e.g. price per stacked m³)', 'premium-shop' ), 'description' => __( 'Required in Germany for goods sold by volume (PAngV). Calculated automatically from the product volume.', 'premium-shop' ) ),
			'fw_specs_on_cards'   => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show key data (length, moisture, drying) on product cards', 'premium-shop' ) ),
			'fw_delivery_check'   => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Postcode delivery check (product page, cart, homepage)', 'premium-shop' ), 'description' => __( 'Uses your WooCommerce shipping zones: add postcodes (e.g. 10*, 12345, 80000...89999) to each zone.', 'premium-shop' ) ),
			'fw_delivery_ok'      => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Message when delivery is possible', 'premium-shop' ), 'description' => $text_hint ),
			'fw_delivery_no'      => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Message when the postcode is not served', 'premium-shop' ) ),
			'fw_delivery_info'    => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'Delivery information (shown under the check)', 'premium-shop' ) ),
			'fw_truck_field'      => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Ask at checkout whether the unloading point is accessible for a truck', 'premium-shop' ) ),
			'fw_calc_product'     => array( 'type' => 'checkbox', 'default' => true, 'label' => __( 'Show the firewood calculator on product pages', 'premium-shop' ) ),
			'fw_faq'              => array( 'type' => 'textarea', 'default' => '', 'translatable' => true, 'label' => __( 'FAQ (one question per line: Question :: Answer)', 'premium-shop' ), 'description' => __( 'Empty = built-in firewood FAQ, translated automatically.', 'premium-shop' ) ),
			'title_faq'           => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'FAQ — title', 'premium-shop' ) ),
			'title_calculator'    => array( 'type' => 'text', 'default' => '', 'translatable' => true, 'label' => __( 'Calculator — title', 'premium-shop' ) ),
		),
	);

	/* ------------------------------------------------------------------ */
	$sections['ps_language'] = array(
		'title'       => __( 'Languages', 'premium-shop' ),
		'description' => __( 'With WPML, Polylang or TranslatePress active, the plugin manages languages and the switcher uses it automatically. Otherwise, the built-in switcher changes the interface language (theme, WordPress and WooCommerce texts). Install the language packs in Settings → General or with the setup assistant.', 'premium-shop' ),
		'fields'      => array(
			'language_mode'      => array(
				'type'    => 'select',
				'default' => 'auto',
				'label'   => __( 'Mode', 'premium-shop' ),
				'choices' => array(
					'auto' => __( 'Automatic (plugin if available, otherwise built-in)', 'premium-shop' ),
					'off'  => __( 'Disabled', 'premium-shop' ),
				),
			),
			'language_default'   => array(
				'type'    => 'select',
				'default' => 'de',
				'label'   => __( 'Default language (first visit)', 'premium-shop' ),
				'choices' => wp_list_pluck( premium_shop_language_registry(), 'name' ),
			),
			'languages_enabled'  => array( 'type' => 'text', 'default' => 'de,fr,es,en', 'label' => __( 'Languages offered (codes, in order)', 'premium-shop' ), 'description' => __( 'Available codes: de, fr, es, en, it, pt, nl, pl. Example: de,fr,es,en,it', 'premium-shop' ) ),
		),
	);

	// Style preset: adapt the defaults (the visitor's own settings still win).
	foreach ( premium_shop_preset_overrides() as $key => $value ) {
		foreach ( $sections as $id => $section ) {
			if ( isset( $section['fields'][ $key ] ) ) {
				$sections[ $id ]['fields'][ $key ]['default'] = $value;
			}
		}
	}

	$config = apply_filters( 'premium_shop_customizer_config', $sections );

	return $config;
}

/**
 * Flat list of defaults (key without prefix => default).
 *
 * Call after `after_setup_theme` (labels are translated when the config is built).
 *
 * @return array
 */
function premium_shop_defaults() {
	static $defaults = null;

	if ( null === $defaults ) {
		$defaults = array();
		foreach ( premium_shop_customizer_config() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				$defaults[ $key ] = isset( $field['default'] ) ? $field['default'] : '';
			}
		}
	}

	return $defaults;
}

/**
 * Keys of translatable text options.
 *
 * @return array
 */
function premium_shop_translatable_option_keys() {
	$keys = array();
	foreach ( premium_shop_customizer_config() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			if ( ! empty( $field['translatable'] ) ) {
				$keys[] = $key;
			}
		}
	}
	return $keys;
}

/**
 * Built-in texts, translated into the current language.
 *
 * @return array
 */
function premium_shop_text_fallbacks() {
	$threshold = premium_shop_free_shipping_threshold();
	$days      = absint( premium_shop_option( 'returns_days' ) );

	$promo = array();
	if ( $threshold > 0 ) {
		/* translators: %s: amount, e.g. 50 €. */
		$promo[] = sprintf( __( 'Free shipping from %s', 'premium-shop' ), premium_shop_plain_price( $threshold ) );
	}
	if ( $days > 0 ) {
		/* translators: %d: number of days. */
		$promo[] = sprintf( __( '%d-day free returns', 'premium-shop' ), $days );
	}
	$promo[] = __( 'Secure payment with SSL encryption', 'premium-shop' );

	/* translators: %d: number of days. */
	$returns_text = $days > 0 ? sprintf( __( '%d days to change your mind', 'premium-shop' ), $days ) : __( 'Simple returns', 'premium-shop' );

	$fallbacks = array(
		'promo_text'           => implode( "\n", $promo ),
		'title_categories'     => __( 'Shop by category', 'premium-shop' ),
		'title_popular'        => __( 'Popular products', 'premium-shop' ),
		'title_new'            => __( 'New arrivals', 'premium-shop' ),
		'title_sale'           => __( 'Offers', 'premium-shop' ),
		'title_bestsellers'    => __( 'Bestsellers', 'premium-shop' ),
		'title_testimonials'   => __( 'What our customers say', 'premium-shop' ),
		'title_brands'         => __( 'Our brands', 'premium-shop' ),
		'hero_eyebrow'         => __( 'The new collection', 'premium-shop' ),
		'hero_title'           => __( 'Timeless pieces, carefully selected for you', 'premium-shop' ),
		'hero_subtitle'        => __( 'Discover quality products, fair prices and a service that takes care of every detail — delivered quickly to your door.', 'premium-shop' ),
		'hero_button_text'     => __( 'Shop now', 'premium-shop' ),
		'hero_button2_text'    => __( 'Discover offers', 'premium-shop' ),
		'campaign_eyebrow'     => __( 'Limited time', 'premium-shop' ),
		'campaign_title'       => __( 'The season’s offers', 'premium-shop' ),
		'campaign_text'        => __( 'Selected favourites at reduced prices — only while stocks last.', 'premium-shop' ),
		'campaign_button_text' => __( 'View offers', 'premium-shop' ),
		'benefit_1_title'      => __( 'Fast shipping', 'premium-shop' ),
		/* translators: %s: amount. */
		'benefit_1_text'       => $threshold > 0 ? sprintf( __( 'Free from %s', 'premium-shop' ), premium_shop_plain_price( $threshold ) ) : __( 'Dispatched within 24 hours', 'premium-shop' ),
		'benefit_2_title'      => __( 'Secure payment', 'premium-shop' ),
		'benefit_2_text'       => __( 'Encrypted & trusted methods', 'premium-shop' ),
		'benefit_3_title'      => __( 'Easy returns', 'premium-shop' ),
		'benefit_3_text'       => $returns_text,
		'benefit_4_title'      => __( 'Customer service', 'premium-shop' ),
		'benefit_4_text'       => __( 'Personal help, quick answers', 'premium-shop' ),
		'newsletter_title'     => __( 'Stay in the loop', 'premium-shop' ),
		'newsletter_text'      => __( 'New arrivals, exclusive offers and inspiration — straight to your inbox. Unsubscribe at any time.', 'premium-shop' ),
		'delivery_time'        => __( 'Delivery in 2–4 business days', 'premium-shop' ),
		'warranty_text'        => __( '2-year warranty', 'premium-shop' ),
		'payment_text'         => __( 'Secure, encrypted payment', 'premium-shop' ),
		'company_about'        => __( 'Carefully selected products, honest advice and fast delivery. We are here for you before and after your purchase.', 'premium-shop' ),
		'contact_hours'        => '',
		'footer_copyright'     => '',
	);

	if ( 'firewood' === premium_shop_preset() ) {
		$fallbacks = array_merge( $fallbacks, premium_shop_firewood_fallbacks() );
	}

	return apply_filters( 'premium_shop_text_fallbacks', $fallbacks );
}
