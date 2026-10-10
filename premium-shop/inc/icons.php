<?php
/**
 * Inline SVG icon set (stroke icons, 24×24, currentColor).
 *
 * Inline icons avoid an icon font request and inherit the text color.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Raw SVG path data for every icon.
 *
 * @return array
 */
function premium_shop_icon_paths() {
	return array(
		'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
		'bag'         => '<path d="M5 8h14l-1 13H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'heart'       => '<path d="M12 20s-7-4.4-9-9.2C1.7 7.5 3.8 4 7.2 4c2 0 3.6 1.1 4.8 2.8C13.2 5.1 14.8 4 16.8 4c3.4 0 5.5 3.5 4.2 6.8C19 15.6 12 20 12 20Z"/>',
		'menu'        => '<path d="M3 7h18M3 12h18M3 17h12"/>',
		'close'       => '<path d="M6 6l12 12M18 6 6 18"/>',
		'chevron'     => '<path d="m6 9 6 6 6-6"/>',
		'arrow'       => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-left'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'eye'         => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'truck'       => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'shield'      => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
		'return'      => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
		'support'     => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19c0 1.5-2 2.5-5 2.5"/>',
		'gift'        => '<rect x="3" y="8" width="18" height="5" rx="1"/><path d="M5 13v8h14v-8M12 8v13M12 8S10.5 3 8 3.8C6 4.5 7 8 12 8Zm0 0s1.5-5 4-4.2c2 .7 1 4.2-4 4.2Z"/>',
		'leaf'        => '<path d="M5 19c0-8 5-14 15-15-1 10-7 15-15 15Z"/><path d="M5 19 13 11"/>',
		'star'        => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>',
		'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'lock'        => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		'check'       => '<path d="m5 12 5 5 9-10"/>',
		'filter'      => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'grid'        => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
		'list'        => '<rect x="4" y="5" width="5" height="5" rx="1"/><rect x="4" y="14" width="5" height="5" rx="1"/><path d="M12 7h8M12 16h8"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'minus'       => '<path d="M5 12h14"/>',
		'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'phone'       => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
		'pin'         => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3Z"/>',
		'play'        => '<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5v-7Z"/>',
		'tag'         => '<path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
		'sparkle'     => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
		'box'         => '<path d="m3 7 9-4 9 4v10l-9 4-9-4V7Z"/><path d="m3 7 9 4 9-4M12 11v10"/>',
		'download'    => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
		'card'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
		'logout'      => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h10"/>',
		'home'        => '<path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10"/>',
		'flame'       => '<path d="M12 21c-4 0-7-2.7-7-6.6 0-3.1 2-5.2 3.6-7 .3 1.6 1.2 2.8 2.4 3.4C11 7.4 12.3 4.6 15 3c-.4 2.6.7 4.4 2 6 1.2 1.5 2 3.1 2 5.4 0 3.9-3 6.6-7 6.6Z"/><path d="M12 21c-1.7 0-3-1.2-3-2.9 0-1.5 1-2.5 1.8-3.4.4.8 1 1.2 1.6 1.4.2-1.4.9-2.6 2-3.3-.1 1.4.6 2.3 1.1 3.1.4.6.5 1.2.5 1.9 0 1.9-1.6 3.2-4 3.2Z"/>',
		'logs'        => '<circle cx="6.5" cy="16" r="3.5"/><circle cx="17.5" cy="16" r="3.5"/><circle cx="12" cy="8.5" r="3.5"/><circle cx="6.5" cy="16" r="1"/><circle cx="17.5" cy="16" r="1"/><circle cx="12" cy="8.5" r="1"/>',
		'droplet'     => '<path d="M12 3s6 6.6 6 11a6 6 0 0 1-12 0c0-4.4 6-11 6-11Z"/><path d="M9 14.5a3 3 0 0 0 3 3"/>',
		'ruler'       => '<rect x="2.5" y="8" width="19" height="8" rx="1.5"/><path d="M6.5 8v3M10.5 8v4M14.5 8v3M18.5 8v4"/>',
		'tree'        => '<path d="M12 3 6 11h3l-4 6h14l-4-6h3L12 3Z"/><path d="M12 17v4"/>',
		'calculator'  => '<rect x="5" y="3" width="14" height="18" rx="2"/><rect x="8" y="6" width="8" height="3.5" rx=".5"/><path d="M8.5 13h.01M12 13h.01M15.5 13h.01M8.5 16.5h.01M12 16.5h.01M15.5 16.5h.01"/>',
		'arrow-up'    => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'facebook'    => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.5c0-.3.2-.5.5-.5Z"/>',
		'instagram'   => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6"/>',
		'tiktok'      => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.6 2.4 4.4 5 4.6"/>',
		'youtube'     => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9 5 3-5 3V9Z"/>',
		'pinterest'   => '<circle cx="12" cy="12" r="9"/><path d="M11 8.5c2.8-.8 5 .6 5 3 0 2.6-1.8 4-3.6 3.5-1-.3-1.4-1.3-1.4-1.3L10 21"/>',
		'x'           => '<path d="M4 4l16 16M20 4 4 20"/>',
		'linkedin'    => '<rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path d="M8 10.5V17M8 7.5v.01M12 17v-6.5M12 13.5c0-1.8 1-3 2.5-3s2.5 1 2.5 3V17"/>',
		'whatsapp'    => '<path d="M4 20l1.3-4A8 8 0 1 1 8.4 19L4 20Z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8c-1-.5-1.8-1.3-2.3-2.3l.8-1-1-2L9 9.5Z"/>',
	);
}

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon name.
 * @param array  $args  Optional: size, class, title.
 * @return string
 */
function premium_shop_get_icon( $name, $args = array() ) {
	$paths = premium_shop_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'size'  => 20,
			'class' => '',
		)
	);

	$class = trim( 'ps-icon ps-icon--' . $name . ' ' . $args['class'] );
	$fill  = in_array( $name, array( 'star-filled' ), true ) ? 'currentColor' : 'none';

	return sprintf(
		'<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="%3$s" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%4$s</svg>',
		esc_attr( $class ),
		absint( $args['size'] ),
		esc_attr( $fill ),
		$paths[ $name ]
	);
}

/**
 * Echo an inline SVG icon.
 *
 * @param string $name Icon name.
 * @param array  $args Optional args.
 */
function premium_shop_icon( $name, $args = array() ) {
	echo wp_kses( premium_shop_get_icon( $name, $args ), premium_shop_svg_kses() );
}
