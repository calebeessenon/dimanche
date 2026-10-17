<?php
/**
 * Accessibility helpers.
 *
 * Keyboard navigation, focus management for drawers and ARIA states are
 * handled in assets/js/theme.js; visible focus styles in main.css.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add aria-current to the current menu item link.
 *
 * @param array   $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @return array
 */
function premium_shop_nav_aria_current( $atts, $item ) {
	if ( ! empty( $item->current ) ) {
		$atts['aria-current'] = 'page';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'premium_shop_nav_aria_current', 10, 2 );

/**
 * Accessible "Read more" link text for excerpts.
 *
 * @return string
 */
function premium_shop_read_more_link() {
	return sprintf(
		'<a class="ps-link-arrow" href="%1$s">%2$s<span class="screen-reader-text"> %3$s</span></a>',
		esc_url( get_permalink() ),
		esc_html__( 'Read more', 'premium-shop' ),
		esc_html( get_the_title() )
	);
}
add_filter( 'the_content_more_link', 'premium_shop_read_more_link' );

/**
 * Decorative images in the custom logo keep the site name as alt text.
 *
 * @param array $attr Image attributes.
 * @return array
 */
function premium_shop_logo_alt( $attr ) {
	if ( empty( $attr['alt'] ) ) {
		$attr['alt'] = get_bloginfo( 'name' );
	}
	return $attr;
}
add_filter( 'get_custom_logo_image_attributes', 'premium_shop_logo_alt' );
