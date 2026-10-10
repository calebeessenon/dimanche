<?php
/**
 * Theme setup: supports, menus, image sizes, widget areas.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core theme setup.
 */
function premium_shop_setup() {
	load_theme_textdomain( 'premium-shop', PREMIUM_SHOP_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 120,
			'width'                => 400,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);

	add_editor_style( array( 'assets/css/editor.css' ) );

	register_nav_menus(
		array(
			'primary'        => esc_html__( 'Main menu', 'premium-shop' ),
			'footer_shop'    => esc_html__( 'Footer — Shop', 'premium-shop' ),
			'footer_service' => esc_html__( 'Footer — Customer service', 'premium-shop' ),
			'footer_legal'   => esc_html__( 'Footer — Legal', 'premium-shop' ),
		)
	);

	add_image_size( 'ps-hero', 1400, 1600, false );
	add_image_size( 'ps-category', 720, 900, true );
	add_image_size( 'ps-banner', 1200, 900, true );
	add_image_size( 'ps-brand', 320, 160, false );

	$GLOBALS['content_width'] = apply_filters( 'premium_shop_content_width', 1200 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
add_action( 'after_setup_theme', 'premium_shop_setup' );

/**
 * Widget areas.
 */
function premium_shop_widgets_init() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="ps-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="ps-widget__title">',
		'after_title'   => '</h2>',
	);

	// The blog sidebar is registered first: on a new site WordPress puts its
	// default widgets (search, recent posts, recent comments) in the first area,
	// where they belong — not under the shop filters.
	register_sidebar(
		array_merge(
			$common,
			array(
				'id'          => 'blog-sidebar',
				'name'        => esc_html__( 'Blog sidebar', 'premium-shop' ),
				'description' => esc_html__( 'Widgets displayed next to blog posts.', 'premium-shop' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'id'          => 'shop-sidebar',
				'name'        => esc_html__( 'Shop sidebar (below the filters)', 'premium-shop' ),
				'description' => esc_html__( 'Optional widgets displayed under the built-in shop filters.', 'premium-shop' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'id'            => 'footer-extra',
				'name'          => esc_html__( 'Footer — extra column', 'premium-shop' ),
				'description'   => esc_html__( 'Optional widgets displayed as an additional footer column.', 'premium-shop' ),
				'before_title'  => '<h2 class="ps-footer__title">',
				'after_title'   => '</h2>',
			)
		)
	);
}
add_action( 'widgets_init', 'premium_shop_widgets_init' );

/**
 * Body classes used by the stylesheet.
 *
 * @param array $classes Classes.
 * @return array
 */
function premium_shop_body_classes( $classes ) {
	$classes[] = 'ps-theme';
	$classes[] = 'ps-buttons-' . sanitize_html_class( premium_shop_option( 'button_style' ) );
	$classes[] = 'ps-header-' . sanitize_html_class( premium_shop_option( 'header_layout' ) );

	if ( premium_shop_option( 'header_sticky' ) ) {
		$classes[] = 'ps-has-sticky-header';
	}
	if ( premium_shop_option( 'buttons_uppercase' ) ) {
		$classes[] = 'ps-buttons-uppercase';
	}
	if ( premium_shop_option( 'animations' ) ) {
		$classes[] = 'ps-animations';
	}
	if ( ! premium_shop_is_wc() ) {
		$classes[] = 'ps-no-woocommerce';
	}

	return $classes;
}
add_filter( 'body_class', 'premium_shop_body_classes' );

/**
 * Elegant excerpt ending.
 *
 * @return string
 */
function premium_shop_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'premium_shop_excerpt_more' );

/**
 * Shorter excerpts in cards.
 *
 * @return int
 */
function premium_shop_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'premium_shop_excerpt_length' );
