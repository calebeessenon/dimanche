<?php
/**
 * Customizer registration, generated from inc/customizer/config.php.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the "Premium Shop" panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function premium_shop_customize_register( $wp_customize ) {
	require_once PREMIUM_SHOP_DIR . '/inc/customizer/class-premium-shop-sortable-control.php';

	$wp_customize->add_panel(
		'premium_shop',
		array(
			'title'       => __( 'Premium Shop — Theme options', 'premium-shop' ),
			'description' => __( 'Logo and favicon: Site Identity. Menus: Menus. Everything else about the design and content of the shop is here.', 'premium-shop' ),
			'priority'    => 25,
		)
	);

	$priority = 10;

	foreach ( premium_shop_customizer_config() as $section_id => $section ) {
		$wp_customize->add_section(
			$section_id,
			array(
				'title'       => $section['title'],
				'description' => isset( $section['description'] ) ? $section['description'] : '',
				'panel'       => 'premium_shop',
				'priority'    => $priority,
			)
		);
		$priority += 5;

		foreach ( $section['fields'] as $key => $field ) {
			$setting_id = 'ps_' . $key;

			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => isset( $field['default'] ) ? $field['default'] : '',
					'type'              => 'theme_mod',
					'capability'        => 'edit_theme_options',
					'transport'         => isset( $field['transport'] ) ? $field['transport'] : 'refresh',
					'sanitize_callback' => static function ( $value ) use ( $field ) {
						return premium_shop_sanitize_field( $value, $field );
					},
				)
			);

			$args = array(
				'label'       => $field['label'],
				'description' => isset( $field['description'] ) ? $field['description'] : '',
				'section'     => $section_id,
				'settings'    => $setting_id,
			);

			switch ( $field['type'] ) {
				case 'color':
					$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting_id, $args ) );
					break;

				case 'image':
					$args['mime_type'] = 'image';
					$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $setting_id, $args ) );
					break;

				case 'sortable':
					$args['choices'] = premium_shop_home_section_labels();
					$wp_customize->add_control( new Premium_Shop_Sortable_Control( $wp_customize, $setting_id, $args ) );
					break;

				case 'select':
					$args['type']    = 'select';
					$args['choices'] = $field['choices'];
					$wp_customize->add_control( $setting_id, $args );
					break;

				case 'number':
					$args['type']        = 'number';
					$args['input_attrs'] = array(
						'min'  => isset( $field['min'] ) ? $field['min'] : 0,
						'max'  => isset( $field['max'] ) ? $field['max'] : 9999,
						'step' => 1,
					);
					$wp_customize->add_control( $setting_id, $args );
					break;

				default:
					$args['type'] = in_array( $field['type'], array( 'text', 'textarea', 'url', 'email', 'checkbox' ), true ) ? $field['type'] : 'text';
					$wp_customize->add_control( $setting_id, $args );
			}
		}
	}

	// Live preview for the site title / tagline.
	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
}
add_action( 'customize_register', 'premium_shop_customize_register' );

/**
 * Sanitize a value according to its field definition.
 *
 * @param mixed $value Raw value.
 * @param array $field Field definition.
 * @return mixed
 */
function premium_shop_sanitize_field( $value, $field ) {
	$default = isset( $field['default'] ) ? $field['default'] : '';

	switch ( $field['type'] ) {
		case 'color':
			$color = sanitize_hex_color( $value );
			return $color ? $color : $default;

		case 'checkbox':
			return (bool) $value;

		case 'select':
			$value = (string) $value;
			return array_key_exists( $value, $field['choices'] ) ? $value : $default;

		case 'number':
			$value = is_numeric( $value ) ? 0 + $value : $default;
			if ( isset( $field['min'] ) ) {
				$value = max( $field['min'], $value );
			}
			if ( isset( $field['max'] ) ) {
				$value = min( $field['max'], $value );
			}
			return $value;

		case 'image':
			return absint( $value );

		case 'url':
			return esc_url_raw( $value );

		case 'email':
			return sanitize_email( $value );

		case 'textarea':
			return ! empty( $field['html'] ) ? wp_kses_post( $value ) : sanitize_textarea_field( $value );

		case 'sortable':
			return premium_shop_sanitize_sections( $value );

		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Sanitize the "sections" string: "id:1,id:0,…".
 *
 * @param string $value Raw value.
 * @return string
 */
function premium_shop_sanitize_sections( $value ) {
	$known  = array_keys( premium_shop_home_section_labels() );
	$tokens = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
	$clean  = array();

	foreach ( $tokens as $token ) {
		$parts = explode( ':', $token );
		$id    = sanitize_key( $parts[0] );
		if ( in_array( $id, $known, true ) && ! isset( $clean[ $id ] ) ) {
			$clean[ $id ] = ( isset( $parts[1] ) && '0' === $parts[1] ) ? '0' : '1';
		}
	}

	// Sections added in later versions are appended, hidden.
	foreach ( $known as $id ) {
		if ( ! isset( $clean[ $id ] ) ) {
			$clean[ $id ] = '0';
		}
	}

	$out = array();
	foreach ( $clean as $id => $on ) {
		$out[] = $id . ':' . $on;
	}

	return implode( ',', $out );
}

/**
 * Enabled homepage sections in display order.
 *
 * @return array
 */
function premium_shop_home_sections() {
	$value    = premium_shop_sanitize_sections( premium_shop_option( 'home_sections' ) );
	$sections = array();

	foreach ( explode( ',', $value ) as $token ) {
		list( $id, $on ) = explode( ':', $token );
		if ( '1' === $on ) {
			$sections[] = $id;
		}
	}

	return apply_filters( 'premium_shop_home_sections', $sections );
}

/**
 * Customizer control scripts (sortable sections).
 */
function premium_shop_customize_controls_scripts() {
	wp_enqueue_script(
		'premium-shop-customizer-controls',
		PREMIUM_SHOP_URI . '/assets/js/customizer-controls.js',
		array( 'jquery', 'jquery-ui-sortable', 'customize-controls' ),
		PREMIUM_SHOP_VERSION,
		true
	);
	wp_enqueue_style(
		'premium-shop-customizer-controls',
		PREMIUM_SHOP_URI . '/assets/css/customizer-controls.css',
		array(),
		PREMIUM_SHOP_VERSION
	);
}
add_action( 'customize_controls_enqueue_scripts', 'premium_shop_customize_controls_scripts' );

/**
 * Live preview script (colors, radius, logo size, site title).
 */
function premium_shop_customize_preview_scripts() {
	wp_enqueue_script(
		'premium-shop-customizer-preview',
		PREMIUM_SHOP_URI . '/assets/js/customizer-preview.js',
		array( 'customize-preview' ),
		PREMIUM_SHOP_VERSION,
		true
	);
	wp_localize_script( 'premium-shop-customizer-preview', 'premiumShopPreview', array( 'vars' => premium_shop_css_variable_map() ) );
}
add_action( 'customize_preview_init', 'premium_shop_customize_preview_scripts' );
