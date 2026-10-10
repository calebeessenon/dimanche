<?php
/**
 * One-time maintenance after a theme update.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the upgrade routines once per theme version.
 */
function premium_shop_maybe_upgrade() {
	$done = (string) get_option( 'premium_shop_version', '' );
	if ( PREMIUM_SHOP_VERSION === $done || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( '' === $done || version_compare( $done, '1.3.0', '<' ) ) {
		premium_shop_move_default_widgets();
		do_action( 'premium_shop_upgrade_1_3' );
	}

	if ( '' === $done || version_compare( $done, '1.4.0', '<' ) ) {
		// Adding to the cart now keeps the visitor on the page (side cart opt-in).
		remove_theme_mod( 'ps_cart_drawer' );
		delete_transient( 'premium_shop_hero_pool' );
		delete_transient( 'premium_shop_cat_thumbs' );
		do_action( 'premium_shop_upgrade_1_4' );
	}

	if ( '' === $done || version_compare( $done, '1.4.1', '<' ) ) {
		premium_shop_fill_contact_details();
	}

	if ( '' === $done || version_compare( $done, '1.5.0', '<' ) ) {
		do_action( 'premium_shop_upgrade_1_5' );
	}

	update_option( 'premium_shop_version', PREMIUM_SHOP_VERSION, false );
}
add_action( 'admin_init', 'premium_shop_maybe_upgrade' );

/**
 * WordPress puts its default widgets (search, recent posts, recent comments,
 * archives, categories) in the first widget area of a theme, which was the
 * shop sidebar before 1.3: move them to the blog sidebar. Only untouched
 * default widgets are moved, and only when nothing else is in the area.
 */
function premium_shop_move_default_widgets() {
	$sidebars = get_option( 'sidebars_widgets', array() );
	if ( empty( $sidebars['shop-sidebar'] ) || ! is_array( $sidebars['shop-sidebar'] ) ) {
		return;
	}

	$blocks = get_option( 'widget_block', array() );
	foreach ( $sidebars['shop-sidebar'] as $widget_id ) {
		if ( ! preg_match( '/^block-(\d+)$/', $widget_id, $m ) || empty( $blocks[ $m[1] ]['content'] ) ) {
			return;
		}
		$content = $blocks[ $m[1] ]['content'];
		if ( ! preg_match( '/^<!-- wp:search \/-->$|wp:latest-posts|wp:latest-comments|wp:archives|wp:categories/', $content ) ) {
			return;
		}
	}

	$blog                     = isset( $sidebars['blog-sidebar'] ) && is_array( $sidebars['blog-sidebar'] ) ? $sidebars['blog-sidebar'] : array();
	$sidebars['blog-sidebar'] = array_merge( $sidebars['shop-sidebar'], $blog );
	$sidebars['shop-sidebar'] = array();
	update_option( 'sidebars_widgets', $sidebars );
}

/**
 * Shop contact details (contact page, footer, contact form recipient):
 * only empty fields are filled, values entered in the Customizer are kept.
 */
function premium_shop_fill_contact_details() {
	$details = apply_filters(
		'premium_shop_shop_contact_details',
		array(
			'contact_address' => "9 route du Beauregard\n1180 Rolle, Suisse",
			'contact_phone'   => '+4175731714',
			'contact_email'   => 'info@mirop-bois.ch',
		)
	);
	foreach ( $details as $key => $value ) {
		if ( '' === trim( (string) get_theme_mod( 'ps_' . $key, '' ) ) && '' !== $value ) {
			set_theme_mod( 'ps_' . $key, $value );
		}
	}
}
