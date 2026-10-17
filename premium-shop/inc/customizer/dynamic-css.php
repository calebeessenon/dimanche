<?php
/**
 * CSS custom properties generated from the Customizer.
 *
 * The whole stylesheet is driven by these variables, so a color change in
 * the Customizer re-themes the entire shop without touching any CSS file.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Map of setting key => [ CSS variable, unit ].
 *
 * @return array
 */
function premium_shop_css_variable_map() {
	return array(
		'color_primary'     => array( '--ps-primary', '' ),
		'color_accent'      => array( '--ps-accent', '' ),
		'color_background'  => array( '--ps-bg', '' ),
		'color_surface'     => array( '--ps-surface', '' ),
		'color_soft'        => array( '--ps-soft', '' ),
		'color_text'        => array( '--ps-text', '' ),
		'color_text_muted'  => array( '--ps-text-muted', '' ),
		'color_border'      => array( '--ps-border', '' ),
		'color_sale'        => array( '--ps-sale', '' ),
		'color_success'     => array( '--ps-success', '' ),
		'color_footer_bg'   => array( '--ps-footer-bg', '' ),
		'color_footer_text' => array( '--ps-footer-text', '' ),
		'color_promo_bg'    => array( '--ps-promo-bg', '' ),
		'color_promo_text'  => array( '--ps-promo-text', '' ),
		'card_radius'       => array( '--ps-radius-card', 'px' ),
		'logo_height'       => array( '--ps-logo-h', 'px' ),
	);
}

/**
 * Pick a readable text color (dark or light) for a background.
 *
 * @param string $hex Background color.
 * @return string
 */
function premium_shop_contrast_color( $hex ) {
	$hex = ltrim( premium_shop_hex( $hex, '#000000' ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$channels = array();
	foreach ( array( 0, 2, 4 ) as $offset ) {
		$c          = hexdec( substr( $hex, $offset, 2 ) ) / 255;
		$channels[] = ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	$luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

	return ( $luminance > 0.4 ) ? '#16181d' : '#ffffff';
}

/**
 * Font stacks.
 *
 * @param string $key Font key.
 * @return string
 */
function premium_shop_font_stack( $key ) {
	$stacks = array(
		'fraunces'     => '"Fraunces", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
		'inter'        => '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
		'system-serif' => '"Iowan Old Style", "Palatino Linotype", Palatino, Georgia, "Times New Roman", serif',
		'system-sans'  => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
	);

	return isset( $stacks[ $key ] ) ? $stacks[ $key ] : $stacks['system-sans'];
}

/**
 * Build the :root rule.
 *
 * @return string
 */
function premium_shop_dynamic_css() {
	$vars = array();

	foreach ( premium_shop_css_variable_map() as $key => $def ) {
		$value = premium_shop_option( $key );
		if ( '' === $def[1] ) {
			$value = premium_shop_hex( $value, premium_shop_defaults()[ $key ] );
		} else {
			$value = absint( $value ) . $def[1];
		}
		$vars[ $def[0] ] = $value;
	}

	$vars['--ps-on-primary'] = premium_shop_contrast_color( premium_shop_option( 'color_primary' ) );
	$vars['--ps-on-accent']  = premium_shop_contrast_color( premium_shop_option( 'color_accent' ) );
	$vars['--ps-font-heading'] = premium_shop_font_stack( premium_shop_option( 'font_heading' ) );
	$vars['--ps-font-body']    = premium_shop_font_stack( premium_shop_option( 'font_body' ) );
	$vars['--ps-font-size']    = absint( premium_shop_option( 'font_size_base' ) ) . 'px';
	$vars['--ps-heading-weight'] = absint( premium_shop_option( 'heading_weight' ) );

	$radius = array(
		'square' => '2px',
		'soft'   => '10px',
		'pill'   => '999px',
	);
	$style  = premium_shop_option( 'button_style' );
	$vars['--ps-radius-btn'] = isset( $radius[ $style ] ) ? $radius[ $style ] : '10px';

	$css = ':root{';
	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}
	$css .= '}';

	return apply_filters( 'premium_shop_dynamic_css', $css );
}
