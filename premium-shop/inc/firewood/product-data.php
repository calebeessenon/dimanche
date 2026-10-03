<?php
/**
 * Firewood product data.
 *
 * - "Firewood" tab in the product editor: species, log length, moisture,
 *   drying, sales unit, volume, origin, certificate, price per unit.
 * - Automatic variation prices: price per unit × volume (− quantity discount).
 *   The volume of each variation is read from its attributes ("2 RM",
 *   "1,8 SRM"…) — nothing to type twice.
 * - Automatic unit price (Grundpreis, PAngV) on cards, product pages and
 *   variations.
 * - Automatic product description and short description when left empty.
 * - Data sheet tab and key-data chips.
 *
 * Works with the WooCommerce CRUD API only (HPOS-safe).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Vocabularies
 * ---------------------------------------------------------------------- */

/**
 * Sales units.
 *
 * @return array key => [ short label, long label ]
 */
function premium_shop_fw_units() {
	return apply_filters(
		'premium_shop_fw_units',
		array(
			'rm'    => array( __( 'stacked m³', 'premium-shop' ), __( 'Stacked cubic metre (RM)', 'premium-shop' ) ),
			'srm'   => array( __( 'loose m³', 'premium-shop' ), __( 'Loose cubic metre (SRM)', 'premium-shop' ) ),
			'fm'    => array( __( 'solid m³', 'premium-shop' ), __( 'Solid cubic metre (FM)', 'premium-shop' ) ),
			'kg'    => array( 'kg', __( 'Kilogram (kg)', 'premium-shop' ) ),
			'liter' => array( __( 'litre', 'premium-shop' ), __( 'Litre (box volume)', 'premium-shop' ) ),
			'piece' => array( __( 'piece', 'premium-shop' ), __( 'Piece', 'premium-shop' ) ),
		)
	);
}

/**
 * Wood species with indicative energy content (kWh per stacked m³, air-dry).
 *
 * @return array slug => [ name, kWh/RM ]
 */
function premium_shop_fw_species() {
	return apply_filters(
		'premium_shop_fw_species',
		array(
			'beech'    => array( __( 'Beech', 'premium-shop' ), 1900 ),
			'oak'      => array( __( 'Oak', 'premium-shop' ), 2000 ),
			'ash'      => array( __( 'Ash', 'premium-shop' ), 1900 ),
			'birch'    => array( __( 'Birch', 'premium-shop' ), 1700 ),
			'hornbeam' => array( __( 'Hornbeam', 'premium-shop' ), 2100 ),
			'mixed'    => array( __( 'Mixed hardwood', 'premium-shop' ), 1850 ),
			'alder'    => array( __( 'Alder', 'premium-shop' ), 1500 ),
			'pine'     => array( __( 'Pine', 'premium-shop' ), 1500 ),
			'spruce'   => array( __( 'Spruce', 'premium-shop' ), 1350 ),
		)
	);
}

/**
 * Drying methods.
 *
 * @return array
 */
function premium_shop_fw_drying() {
	return array(
		'kiln'  => __( 'Kiln-dried', 'premium-shop' ),
		'air'   => __( 'Air-dried', 'premium-shop' ),
		'fresh' => __( 'Freshly cut (for self-drying)', 'premium-shop' ),
	);
}

/**
 * Firewood meta keys (product level).
 *
 * @return array
 */
function premium_shop_fw_meta_keys() {
	return array( '_ps_species', '_ps_log_length', '_ps_moisture', '_ps_drying', '_ps_unit', '_ps_unit_qty', '_ps_heat_value', '_ps_origin', '_ps_certificate', '_ps_price_per_unit', '_ps_auto_prices', '_ps_disc1_qty', '_ps_disc1_pct', '_ps_disc2_qty', '_ps_disc2_pct' );
}

/**
 * Read a firewood value (variations inherit from their parent).
 *
 * @param WC_Product $product Product.
 * @param string     $key     Meta key.
 * @return string
 */
function premium_shop_fw_get( $product, $key ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$value = (string) $product->get_meta( $key );
	if ( '' === $value && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		$value  = $parent ? (string) $parent->get_meta( $key ) : '';
	}
	return $value;
}

/**
 * Is this a firewood product (has firewood data)?
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function premium_shop_fw_is_firewood( $product ) {
	return '' !== premium_shop_fw_get( $product, '_ps_species' ) || '' !== premium_shop_fw_get( $product, '_ps_unit' );
}

/**
 * Parse a decimal number "1,8" / "1.8".
 *
 * @param mixed $value Value.
 * @return float
 */
function premium_shop_fw_number( $value ) {
	$value = str_replace( array( ' ', ',' ), array( '', '.' ), (string) $value );
	return is_numeric( $value ) ? (float) $value : 0.0;
}

/**
 * Extract a quantity from a text like "2 RM", "1,8 SRM", "Palette 1.5 Raummeter", "500 kg".
 *
 * @param string $text Text.
 * @return float
 */
function premium_shop_fw_parse_qty( $text ) {
	static $units = null;
	if ( null === $units ) {
		$labels = array( 'srm', 'rm', 'fm', 'raummeter', 'schüttraummeter', 'schuettraummeter', 'festmeter', 'stères', 'stère', 'st', 'm³', 'm3', 'mst', 'kg', 'l', 'liter', 'litre', 'litres', 'stück', 'stk', 'pcs' );
		foreach ( premium_shop_fw_units() as $unit ) {
			$labels[] = $unit[0];
		}
		foreach ( array( 'stacked m³', 'loose m³', 'solid m³', 'litre', 'piece' ) as $source ) {
			$labels[] = premium_shop_fw_t( $source );
		}
		$labels = array_unique( array_map( 'mb_strtolower', array_filter( $labels ) ) );
		usort(
			$labels,
			static function ( $a, $b ) {
				return mb_strlen( $b ) - mb_strlen( $a );
			}
		);
		$units = implode( '|', array_map( 'preg_quote', $labels ) );
	}
	if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*(?:' . $units . ')(?![\p{L}\d])/iu', $text, $m ) ) {
		return premium_shop_fw_number( $m[1] );
	}
	return 0.0;
}

/**
 * "33" → "33 cm", "25/33/50" → "25/33/50 cm".
 *
 * @param string $length Length.
 * @return string
 */
function premium_shop_fw_length_label( $length ) {
	$length = trim( (string) $length );
	return preg_match( '#^[\d.,/ \-]+$#', $length ) ? $length . ' cm' : $length;
}

/**
 * Quantity of sales units contained in a product or variation.
 *
 * @param WC_Product $product Product.
 * @return float
 */
function premium_shop_fw_qty( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return 0.0;
	}

	$own = premium_shop_fw_number( $product->get_meta( '_ps_unit_qty' ) );
	if ( $own > 0 ) {
		return $own;
	}

	if ( $product->is_type( 'variation' ) ) {
		foreach ( $product->get_variation_attributes( false ) as $taxonomy => $value ) {
			$label = $value;
			if ( taxonomy_exists( $taxonomy ) ) {
				$term  = get_term_by( 'slug', $value, $taxonomy );
				$label = $term ? $term->name : $value;
			}
			$qty = premium_shop_fw_parse_qty( (string) $label );
			if ( $qty > 0 ) {
				return $qty;
			}
		}
		return 0.0;
	}

	return premium_shop_fw_parse_qty( $product->get_name() );
}

/**
 * Sales unit key of a product.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function premium_shop_fw_unit( $product ) {
	$unit  = premium_shop_fw_get( $product, '_ps_unit' );
	$units = premium_shop_fw_units();
	return isset( $units[ $unit ] ) ? $unit : '';
}

/**
 * Short unit label.
 *
 * @param string $unit Unit key.
 * @return string
 */
function premium_shop_fw_unit_label( $unit ) {
	$units = premium_shop_fw_units();
	return isset( $units[ $unit ] ) ? $units[ $unit ][0] : '';
}

/* -------------------------------------------------------------------------
 * Unit price (Grundpreis)
 * ---------------------------------------------------------------------- */

/**
 * Lowest unit price of a product (cached until the product changes).
 *
 * @param WC_Product $product Product.
 * @return float 0 when unknown.
 */
function premium_shop_fw_unit_price( $product ) {
	if ( ! $product instanceof WC_Product || ! premium_shop_fw_unit( $product ) ) {
		return 0.0;
	}

	if ( ! $product->is_type( 'variable' ) ) {
		$qty   = premium_shop_fw_qty( $product );
		$price = (float) wc_get_price_to_display( $product );
		return ( $qty > 0 && $price > 0 ) ? $price / $qty : 0.0;
	}

	$key    = 'ps_up_' . $product->get_id() . '_' . WC_Cache_Helper::get_transient_version( 'product' );
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return (float) $cached;
	}

	$best = 0.0;
	foreach ( $product->get_visible_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation || ! $variation->is_purchasable() ) {
			continue;
		}
		$qty   = premium_shop_fw_qty( $variation );
		$price = (float) wc_get_price_to_display( $variation );
		if ( $qty > 0 && $price > 0 ) {
			$unit_price = $price / $qty;
			$best       = ( 0.0 === $best ) ? $unit_price : min( $best, $unit_price );
		}
	}

	set_transient( $key, $best, DAY_IN_SECONDS );
	return $best;
}

/**
 * Unit price HTML.
 *
 * @param WC_Product $product Product.
 * @param bool       $from    Prefix with "from".
 * @return string
 */
function premium_shop_fw_unit_price_html( $product, $from = false ) {
	if ( ! premium_shop_option( 'fw_unit_price' ) ) {
		return '';
	}

	$price = premium_shop_fw_unit_price( $product );
	if ( $price <= 0 ) {
		return '';
	}

	$label = premium_shop_fw_unit_label( premium_shop_fw_unit( $product ) );
	$text  = $from
		/* translators: 1: price, 2: unit (e.g. stacked m³). */
		? sprintf( __( 'from %1$s / %2$s', 'premium-shop' ), wc_price( $price ), esc_html( $label ) )
		/* translators: 1: price, 2: unit (e.g. stacked m³). */
		: sprintf( __( '%1$s / %2$s', 'premium-shop' ), wc_price( $price ), esc_html( $label ) );

	return '<span class="ps-unit-price">' . esc_html__( 'Unit price:', 'premium-shop' ) . ' ' . wp_kses_post( $text ) . '</span>';
}

/**
 * Key data chips (species, length, moisture, drying).
 *
 * @param WC_Product $product Product.
 * @param int        $max     Max chips.
 * @return string
 */
function premium_shop_fw_chips( $product, $max = 4 ) {
	$chips   = array();
	$species = premium_shop_fw_species();
	$drying  = premium_shop_fw_drying();

	$s = premium_shop_fw_get( $product, '_ps_species' );
	if ( isset( $species[ $s ] ) ) {
		$chips[] = array( 'tree', $species[ $s ][0] );
	}
	$length = premium_shop_fw_get( $product, '_ps_log_length' );
	if ( '' !== $length ) {
		$chips[] = array( 'ruler', premium_shop_fw_length_label( $length ) );
	}
	$moisture = premium_shop_fw_get( $product, '_ps_moisture' );
	if ( '' !== $moisture ) {
		/* translators: %s: residual moisture in percent. */
		$chips[] = array( 'droplet', sprintf( __( '< %s %% moisture', 'premium-shop' ), $moisture ) );
	}
	$d = premium_shop_fw_get( $product, '_ps_drying' );
	if ( isset( $drying[ $d ] ) && 'fresh' !== $d ) {
		$chips[] = array( 'flame', $drying[ $d ] );
	}

	$chips = array_slice( apply_filters( 'premium_shop_fw_chips', $chips, $product ), 0, $max );
	if ( ! $chips ) {
		return '';
	}

	$html = '<ul class="ps-fw-chips">';
	foreach ( $chips as $chip ) {
		$html .= '<li>' . premium_shop_get_icon( $chip[0], array( 'size' => 14 ) ) . '<span>' . esc_html( $chip[1] ) . '</span></li>';
	}
	return $html . '</ul>';
}

/**
 * Cards: unit price + chips under the price.
 */
function premium_shop_fw_card_extras() {
	global $product;
	if ( ! $product instanceof WC_Product || ! premium_shop_fw_is_firewood( $product ) ) {
		return;
	}
	echo premium_shop_fw_unit_price_html( $product, $product->is_type( 'variable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function.
	if ( premium_shop_option( 'fw_specs_on_cards' ) ) {
		echo wp_kses( premium_shop_fw_chips( $product, 3 ), premium_shop_fw_kses() );
	}
}
add_action( 'woocommerce_after_shop_loop_item_title', 'premium_shop_fw_card_extras', 5 );

/**
 * Product page: chips under the title, unit price under the price.
 */
function premium_shop_fw_single_chips() {
	global $product;
	if ( $product instanceof WC_Product && premium_shop_fw_is_firewood( $product ) ) {
		echo wp_kses( premium_shop_fw_chips( $product ), premium_shop_fw_kses() );
	}
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_fw_single_chips', 7 );

/**
 * Product page unit price.
 */
function premium_shop_fw_single_unit_price() {
	global $product;
	if ( $product instanceof WC_Product && premium_shop_fw_is_firewood( $product ) ) {
		echo '<p class="ps-unit-price-wrap" data-ps-unit-price>' . premium_shop_fw_unit_price_html( $product, $product->is_type( 'variable' ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function.
	}
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_fw_single_unit_price', 11 );

/**
 * Selected variation: show its own unit price.
 *
 * @param array                $data      Variation data.
 * @param WC_Product_Variable  $parent    Parent.
 * @param WC_Product_Variation $variation Variation.
 * @return array
 */
function premium_shop_fw_variation_data( $data, $parent, $variation ) {
	if ( premium_shop_fw_is_firewood( $variation ) ) {
		$unit_html = premium_shop_fw_unit_price_html( $variation );
		if ( $unit_html ) {
			$data['price_html'] .= '<p class="ps-unit-price-wrap">' . $unit_html . '</p>';
		}
	}
	return $data;
}
add_filter( 'woocommerce_available_variation', 'premium_shop_fw_variation_data', 10, 3 );

/**
 * Allowed HTML for chips.
 *
 * @return array
 */
function premium_shop_fw_kses() {
	return array_merge(
		premium_shop_svg_kses(),
		array(
			'ul'   => array( 'class' => true ),
			'li'   => array( 'class' => true ),
			'span' => array( 'class' => true ),
		)
	);
}

/* -------------------------------------------------------------------------
 * Data sheet tab
 * ---------------------------------------------------------------------- */

/**
 * Rows of the wood data sheet.
 *
 * @param WC_Product $product Product.
 * @return array label => value
 */
function premium_shop_fw_sheet_rows( $product ) {
	$rows    = array();
	$species = premium_shop_fw_species();
	$drying  = premium_shop_fw_drying();
	$units   = premium_shop_fw_units();

	$s = premium_shop_fw_get( $product, '_ps_species' );
	if ( isset( $species[ $s ] ) ) {
		$rows[ __( 'Wood species', 'premium-shop' ) ] = $species[ $s ][0];
	}
	$length = premium_shop_fw_get( $product, '_ps_log_length' );
	if ( '' !== $length ) {
		$rows[ __( 'Log length', 'premium-shop' ) ] = premium_shop_fw_length_label( $length );
	}
	$moisture = premium_shop_fw_get( $product, '_ps_moisture' );
	if ( '' !== $moisture ) {
		/* translators: %s: percent. */
		$rows[ __( 'Residual moisture', 'premium-shop' ) ] = sprintf( __( 'below %s %%', 'premium-shop' ), $moisture );
	}
	$d = premium_shop_fw_get( $product, '_ps_drying' );
	if ( isset( $drying[ $d ] ) ) {
		$rows[ __( 'Drying', 'premium-shop' ) ] = $drying[ $d ];
	}
	$unit = premium_shop_fw_unit( $product );
	if ( $unit ) {
		$rows[ __( 'Sales unit', 'premium-shop' ) ] = $units[ $unit ][1];
	}
	$heat = premium_shop_fw_number( premium_shop_fw_get( $product, '_ps_heat_value' ) );
	if ( ! $heat && isset( $species[ $s ] ) && in_array( $unit, array( 'rm', 'srm', 'fm' ), true ) ) {
		$factor = array( 'rm' => 1, 'srm' => 1 / 1.4, 'fm' => 1.4 );
		$heat   = round( $species[ $s ][1] * $factor[ $unit ] );
	}
	if ( $heat && $unit ) {
		/* translators: 1: kWh, 2: unit. */
		$rows[ __( 'Energy content (approx.)', 'premium-shop' ) ] = sprintf( __( '%1$s kWh per %2$s', 'premium-shop' ), number_format_i18n( $heat ), premium_shop_fw_unit_label( $unit ) );
	}
	$origin = premium_shop_fw_get( $product, '_ps_origin' );
	if ( '' !== $origin ) {
		$rows[ __( 'Origin', 'premium-shop' ) ] = $origin;
	}
	$cert = premium_shop_fw_get( $product, '_ps_certificate' );
	if ( '' !== $cert ) {
		$rows[ __( 'Certification', 'premium-shop' ) ] = $cert;
	}

	return apply_filters( 'premium_shop_fw_sheet_rows', $rows, $product );
}

/**
 * Add the data sheet tab.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function premium_shop_fw_tabs( $tabs ) {
	global $product;
	if ( $product instanceof WC_Product && premium_shop_fw_sheet_rows( $product ) ) {
		$tabs['ps_wood_sheet'] = array(
			'title'    => __( 'Wood data sheet', 'premium-shop' ),
			'priority' => 15,
			'callback' => 'premium_shop_fw_sheet_tab',
		);
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'premium_shop_fw_tabs' );

/**
 * Data sheet tab content.
 */
function premium_shop_fw_sheet_tab() {
	global $product;
	echo '<h2>' . esc_html__( 'Wood data sheet', 'premium-shop' ) . '</h2>';
	echo '<table class="woocommerce-product-attributes shop_attributes ps-wood-sheet"><tbody>';
	foreach ( premium_shop_fw_sheet_rows( $product ) as $label => $value ) {
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), esc_html( $value ) );
	}
	echo '</tbody></table>';
	echo '<p class="ps-wood-sheet__note">' . esc_html__( 'Energy values are indicative and depend on moisture and storage.', 'premium-shop' ) . '</p>';
}

/* -------------------------------------------------------------------------
 * Admin: "Firewood" tab in the product editor
 * ---------------------------------------------------------------------- */

/**
 * Register the tab.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function premium_shop_fw_admin_tab( $tabs ) {
	$tabs['premium_shop_firewood'] = array(
		'label'    => __( 'Firewood', 'premium-shop' ),
		'target'   => 'premium_shop_firewood_data',
		'class'    => array(),
		'priority' => 15,
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'premium_shop_fw_admin_tab' );

/**
 * Tab panel.
 */
function premium_shop_fw_admin_panel() {
	global $product_object;
	$p = $product_object instanceof WC_Product ? $product_object : null;
	$v = static function ( $key ) use ( $p ) {
		return $p ? (string) $p->get_meta( $key ) : '';
	};

	$species = array( '' => __( '— Not firewood —', 'premium-shop' ) ) + wp_list_pluck( premium_shop_fw_species(), 0 );
	$units   = array( '' => '—' ) + wp_list_pluck( premium_shop_fw_units(), 1 );
	?>
	<div id="premium_shop_firewood_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<p class="form-field"><strong><?php esc_html_e( 'Wood data', 'premium-shop' ); ?></strong> — <?php esc_html_e( 'Shown as chips, data sheet and used for the automatic description.', 'premium-shop' ); ?></p>
			<?php
			woocommerce_wp_select( array( 'id' => '_ps_species', 'label' => __( 'Wood species', 'premium-shop' ), 'options' => $species, 'value' => $v( '_ps_species' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_log_length', 'label' => __( 'Log length (cm)', 'premium-shop' ), 'placeholder' => '33', 'value' => $v( '_ps_log_length' ), 'desc_tip' => true, 'description' => __( 'e.g. 25, 33, 50 or "25/33".', 'premium-shop' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_moisture', 'label' => __( 'Residual moisture below (%)', 'premium-shop' ), 'placeholder' => '20', 'value' => $v( '_ps_moisture' ), 'type' => 'number', 'custom_attributes' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ) );
			woocommerce_wp_select( array( 'id' => '_ps_drying', 'label' => __( 'Drying', 'premium-shop' ), 'options' => array( '' => '—' ) + premium_shop_fw_drying(), 'value' => $v( '_ps_drying' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_origin', 'label' => __( 'Origin', 'premium-shop' ), 'placeholder' => __( 'e.g. Black Forest, Germany', 'premium-shop' ), 'value' => $v( '_ps_origin' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_certificate', 'label' => __( 'Certification', 'premium-shop' ), 'placeholder' => 'PEFC / FSC', 'value' => $v( '_ps_certificate' ) ) );
			?>
		</div>
		<div class="options_group">
			<p class="form-field"><strong><?php esc_html_e( 'Unit & automatic prices', 'premium-shop' ); ?></strong></p>
			<?php
			woocommerce_wp_select( array( 'id' => '_ps_unit', 'label' => __( 'Sales unit', 'premium-shop' ), 'options' => $units, 'value' => $v( '_ps_unit' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_unit_qty', 'label' => __( 'Units per product', 'premium-shop' ), 'placeholder' => __( 'automatic', 'premium-shop' ), 'value' => $v( '_ps_unit_qty' ), 'desc_tip' => true, 'description' => __( 'Simple products only (e.g. 1.8 for a pallet of 1.8 RM). Variations read it from their attribute ("2 RM", "1,8 SRM") or from their own field.', 'premium-shop' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_heat_value', 'label' => __( 'Energy content (kWh per unit)', 'premium-shop' ), 'placeholder' => __( 'automatic', 'premium-shop' ), 'value' => $v( '_ps_heat_value' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_price_per_unit', 'label' => __( 'Price per unit', 'premium-shop' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'placeholder' => '0,00', 'value' => $v( '_ps_price_per_unit' ), 'data_type' => 'price' ) );
			woocommerce_wp_checkbox( array( 'id' => '_ps_auto_prices', 'label' => __( 'Calculate prices automatically', 'premium-shop' ), 'value' => $v( '_ps_auto_prices' ) ? 'yes' : 'no', 'description' => __( 'On save, the regular price of the product / every variation = price per unit × units (− quantity discount). Sale prices are kept.', 'premium-shop' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_disc1_qty', 'label' => __( 'Quantity discount 1: from (units)', 'premium-shop' ), 'value' => $v( '_ps_disc1_qty' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_disc1_pct', 'label' => __( 'Quantity discount 1 (%)', 'premium-shop' ), 'value' => $v( '_ps_disc1_pct' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_disc2_qty', 'label' => __( 'Quantity discount 2: from (units)', 'premium-shop' ), 'value' => $v( '_ps_disc2_qty' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_ps_disc2_pct', 'label' => __( 'Quantity discount 2 (%)', 'premium-shop' ), 'value' => $v( '_ps_disc2_pct' ) ) );
			?>
			<p class="form-field"><em><?php esc_html_e( 'Empty description? A description and a short description are written automatically from these data when you save (you can edit them afterwards).', 'premium-shop' ); ?></em></p>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'premium_shop_fw_admin_panel' );

/**
 * Save the tab (WooCommerce has already verified its nonce and permissions).
 *
 * @param WC_Product $product Product.
 */
function premium_shop_fw_admin_save( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce (woocommerce_meta_nonce).
	$text_keys = array( '_ps_log_length', '_ps_origin', '_ps_certificate' );
	foreach ( $text_keys as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}

	if ( isset( $_POST['_ps_species'] ) ) {
		$species = sanitize_key( wp_unslash( $_POST['_ps_species'] ) );
		$product->update_meta_data( '_ps_species', array_key_exists( $species, premium_shop_fw_species() ) ? $species : '' );
	}
	if ( isset( $_POST['_ps_drying'] ) ) {
		$drying = sanitize_key( wp_unslash( $_POST['_ps_drying'] ) );
		$product->update_meta_data( '_ps_drying', array_key_exists( $drying, premium_shop_fw_drying() ) ? $drying : '' );
	}
	if ( isset( $_POST['_ps_unit'] ) ) {
		$unit = sanitize_key( wp_unslash( $_POST['_ps_unit'] ) );
		$product->update_meta_data( '_ps_unit', array_key_exists( $unit, premium_shop_fw_units() ) ? $unit : '' );
	}

	$number_keys = array( '_ps_moisture', '_ps_unit_qty', '_ps_heat_value', '_ps_disc1_qty', '_ps_disc1_pct', '_ps_disc2_qty', '_ps_disc2_pct' );
	foreach ( $number_keys as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			$product->update_meta_data( $key, '' === $raw ? '' : (string) premium_shop_fw_number( $raw ) );
		}
	}

	if ( isset( $_POST['_ps_price_per_unit'] ) ) {
		$product->update_meta_data( '_ps_price_per_unit', wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_ps_price_per_unit'] ) ) ) );
	}
	$product->update_meta_data( '_ps_auto_prices', empty( $_POST['_ps_auto_prices'] ) ? '' : 'yes' );
	// phpcs:enable

	premium_shop_fw_auto_texts( $product );
}
add_action( 'woocommerce_admin_process_product_object', 'premium_shop_fw_admin_save', 20 );

/**
 * Variation field: units contained in the variation.
 *
 * @param int     $loop           Index.
 * @param array   $variation_data Data.
 * @param WP_Post $variation      Variation post.
 */
function premium_shop_fw_variation_field( $loop, $variation_data, $variation ) {
	$product = wc_get_product( $variation->ID );
	woocommerce_wp_text_input(
		array(
			'id'            => '_ps_unit_qty_' . $loop,
			'name'          => '_ps_unit_qty[' . $loop . ']',
			'label'         => __( 'Units in this variation', 'premium-shop' ),
			'placeholder'   => __( 'automatic from the attribute', 'premium-shop' ),
			'value'         => $product ? (string) $product->get_meta( '_ps_unit_qty' ) : '',
			'wrapper_class' => 'form-row form-row-full',
			'desc_tip'      => true,
			'description'   => __( 'Leave empty: read from the attribute name (e.g. "2 RM" = 2).', 'premium-shop' ),
		)
	);
}
add_action( 'woocommerce_variation_options_pricing', 'premium_shop_fw_variation_field', 10, 3 );

/**
 * Save the variation field (WooCommerce verified the nonce).
 *
 * @param int $variation_id Variation ID.
 * @param int $i            Index.
 */
function premium_shop_fw_variation_save( $variation_id, $i ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_POST['_ps_unit_qty'][ $i ] ) ) {
		return;
	}
	$raw       = sanitize_text_field( wp_unslash( $_POST['_ps_unit_qty'][ $i ] ) );
	// phpcs:enable
	$variation = wc_get_product( $variation_id );
	if ( $variation ) {
		$variation->update_meta_data( '_ps_unit_qty', '' === $raw ? '' : (string) premium_shop_fw_number( $raw ) );
		$variation->save();
	}
}
add_action( 'woocommerce_save_product_variation', 'premium_shop_fw_variation_save', 10, 2 );

/* -------------------------------------------------------------------------
 * Automatic prices
 * ---------------------------------------------------------------------- */

/**
 * Regular price for a quantity of units.
 *
 * @param WC_Product $parent Product holding the pricing rules.
 * @param float      $qty    Units.
 * @return float 0 when not computable.
 */
function premium_shop_fw_price_for( $parent, $qty ) {
	$per_unit = (float) $parent->get_meta( '_ps_price_per_unit' );
	if ( $per_unit <= 0 || $qty <= 0 ) {
		return 0.0;
	}

	$discount = 0.0;
	foreach ( array( 1, 2 ) as $n ) {
		$from = premium_shop_fw_number( $parent->get_meta( '_ps_disc' . $n . '_qty' ) );
		$pct  = premium_shop_fw_number( $parent->get_meta( '_ps_disc' . $n . '_pct' ) );
		if ( $from > 0 && $pct > 0 && $qty >= $from ) {
			$discount = max( $discount, $pct );
		}
	}

	return round( $per_unit * $qty * ( 1 - min( 90, $discount ) / 100 ), wc_get_price_decimals() );
}

/**
 * Recalculate the prices of a product (and its variations).
 *
 * @param int $product_id Product ID.
 * @return int Number of prices updated.
 */
function premium_shop_fw_recalculate( $product_id ) {
	static $running = array();
	if ( isset( $running[ $product_id ] ) ) {
		return 0;
	}
	$running[ $product_id ] = true;

	$product = wc_get_product( $product_id );
	$updated = 0;

	if ( ! $product || 'yes' !== $product->get_meta( '_ps_auto_prices' ) ) {
		unset( $running[ $product_id ] );
		return 0;
	}

	$items = $product->is_type( 'variable' ) ? array_filter( array_map( 'wc_get_product', $product->get_children() ) ) : array( $product );

	foreach ( $items as $item ) {
		$price = premium_shop_fw_price_for( $product, premium_shop_fw_qty( $item ) );
		if ( $price <= 0 || (float) $item->get_regular_price() === $price ) {
			continue;
		}
		$item->set_regular_price( (string) $price );
		$sale = $item->get_sale_price();
		if ( '' !== $sale && (float) $sale >= $price ) {
			$item->set_sale_price( '' );
		}
		$item->save();
		$updated++;
	}

	if ( $product->is_type( 'variable' ) && $updated ) {
		WC_Product_Variable::sync( $product_id );
	}
	if ( $updated ) {
		wc_delete_product_transients( $product_id );
	}

	unset( $running[ $product_id ] );
	return $updated;
}

/**
 * Recalculate after the product form is saved.
 *
 * @param int $post_id Product ID.
 */
function premium_shop_fw_after_save( $post_id ) {
	premium_shop_fw_recalculate( $post_id );
}
add_action( 'woocommerce_process_product_meta', 'premium_shop_fw_after_save', 99 );
add_action( 'woocommerce_ajax_save_product_variations', 'premium_shop_fw_after_save', 99 );

/* -------------------------------------------------------------------------
 * Automatic texts
 * ---------------------------------------------------------------------- */

/**
 * Translate a text into the shop's default language (descriptions are
 * written in the main language, then translated with your multilingual plugin).
 *
 * @param string $text English text.
 * @return string
 */
function premium_shop_fw_t( $text ) {
	return premium_shop_translate_in( $text, premium_shop_locale_for( premium_shop_default_language() ) );
}

/**
 * Fill empty description / short description from the firewood data.
 *
 * @param WC_Product $product Product (not saved yet).
 */
function premium_shop_fw_auto_texts( $product ) {
	$species_key = (string) $product->get_meta( '_ps_species' );
	$species     = premium_shop_fw_species();
	if ( ! isset( $species[ $species_key ] ) ) {
		return;
	}

	$t       = 'premium_shop_fw_t';
	$name    = $t( premium_shop_fw_species_english( $species_key ) );
	$length  = (string) $product->get_meta( '_ps_log_length' );
	$moist   = (string) $product->get_meta( '_ps_moisture' );
	$drying  = (string) $product->get_meta( '_ps_drying' );
	$origin  = (string) $product->get_meta( '_ps_origin' );
	$cert    = (string) $product->get_meta( '_ps_certificate' );
	$dry_txt = array(
		'kiln'  => 'kiln-dried',
		'air'   => 'naturally air-dried',
		'fresh' => 'freshly cut',
	);

	$dried = $t( isset( $dry_txt[ $drying ] ) ? $dry_txt[ $drying ] : 'carefully dried' );
	if ( '' !== $length ) {
		$intro = sprintf(
			/* translators: 1: wood species, 2: drying method, 3: log length. */
			$t( '%1$s firewood, %2$s and split to a log length of %3$s — ready for your stove or fireplace.' ),
			$name,
			$dried,
			premium_shop_fw_length_label( $length )
		);
	} else {
		$intro = sprintf(
			/* translators: 1: wood species, 2: drying method. */
			$t( '%1$s firewood, %2$s and split — ready for your stove or fireplace.' ),
			$name,
			$dried
		);
	}

	$paragraphs   = array( $intro, $t( premium_shop_fw_species_sentence( $species_key ) ) );
	$paragraphs[] = '' !== $moist
		/* translators: %s: percent. */
		? sprintf( $t( 'Residual moisture below %s%%: dry wood burns cleanly, gives more heat and protects your stove and chimney.' ), $moist )
		: $t( 'Dry wood burns cleanly, gives more heat and protects your stove and chimney.' );

	$facts = array();
	if ( '' !== $origin ) {
		/* translators: %s: origin. */
		$facts[] = sprintf( $t( 'Origin: %s.' ), $origin );
	}
	if ( '' !== $cert ) {
		/* translators: %s: certificate. */
		$facts[] = sprintf( $t( 'Certified: %s.' ), $cert );
	}
	$facts[] = $t( 'Tip: store your wood dry and well ventilated, ideally under a roof.' );

	if ( '' === trim( $product->get_description() ) ) {
		$html = '';
		foreach ( $paragraphs as $paragraph ) {
			$html .= '<p>' . esc_html( $paragraph ) . "</p>\n";
		}
		$html .= '<p>' . esc_html( implode( ' ', $facts ) ) . '</p>';
		$product->set_description( $html );
	}

	if ( '' === trim( $product->get_short_description() ) ) {
		$product->set_short_description( '<p>' . esc_html( $intro ) . '</p>' );
	}
}

/**
 * English species names (source strings of the generated texts).
 *
 * @param string $key Species key.
 * @return string
 */
function premium_shop_fw_species_english( $key ) {
	$names = array(
		'beech'    => 'Beech',
		'oak'      => 'Oak',
		'ash'      => 'Ash',
		'birch'    => 'Birch',
		'hornbeam' => 'Hornbeam',
		'mixed'    => 'Mixed hardwood',
		'alder'    => 'Alder',
		'pine'     => 'Pine',
		'spruce'   => 'Spruce',
	);
	return isset( $names[ $key ] ) ? $names[ $key ] : ucfirst( $key );
}

/**
 * One characteristic sentence per species (English source).
 *
 * @param string $key Species key.
 * @return string
 */
function premium_shop_fw_species_sentence( $key ) {
	$sentences = array(
		'beech'    => 'Beech is the classic: long, even embers and a cosy, calm flame.',
		'oak'      => 'Oak burns slowly and produces long-lasting embers — ideal for long evenings.',
		'ash'      => 'Ash offers a high heat output and burns calmly with a beautiful flame.',
		'birch'    => 'Birch lights easily, burns with a bright flame and hardly sparks.',
		'hornbeam' => 'Hornbeam has one of the highest energy contents of all native woods.',
		'mixed'    => 'A balanced mix of hardwoods for reliable everyday heating.',
		'alder'    => 'Alder burns evenly with little sparking and a pleasant smell.',
		'pine'     => 'Pine lights quickly — perfect for getting the fire started.',
		'spruce'   => 'Spruce lights quickly — perfect for getting the fire started.',
	);
	return isset( $sentences[ $key ] ) ? $sentences[ $key ] : $sentences['mixed'];
}

/**
 * Source strings used by the automatic descriptions. Never called: it only
 * makes these strings visible to the translation tools (.pot).
 *
 * @return array
 */
function premium_shop_fw_i18n_catalog() {
	return array(
		__( 'kiln-dried', 'premium-shop' ),
		__( 'naturally air-dried', 'premium-shop' ),
		__( 'freshly cut', 'premium-shop' ),
		__( 'carefully dried', 'premium-shop' ),
		__( 'Beech is the classic: long, even embers and a cosy, calm flame.', 'premium-shop' ),
		__( 'Oak burns slowly and produces long-lasting embers — ideal for long evenings.', 'premium-shop' ),
		__( 'Ash offers a high heat output and burns calmly with a beautiful flame.', 'premium-shop' ),
		__( 'Birch lights easily, burns with a bright flame and hardly sparks.', 'premium-shop' ),
		__( 'Hornbeam has one of the highest energy contents of all native woods.', 'premium-shop' ),
		__( 'A balanced mix of hardwoods for reliable everyday heating.', 'premium-shop' ),
		__( 'Alder burns evenly with little sparking and a pleasant smell.', 'premium-shop' ),
		__( 'Pine lights quickly — perfect for getting the fire started.', 'premium-shop' ),
		__( 'Spruce lights quickly — perfect for getting the fire started.', 'premium-shop' ),
	);
}

/**
 * Variable firewood products: "from 139,00 €" instead of a long price range.
 *
 * @param string              $html    Price HTML.
 * @param WC_Product_Variable $product Product.
 * @return string
 */
function premium_shop_fw_from_price( $html, $product ) {
	if ( ! premium_shop_fw_is_firewood( $product ) ) {
		return $html;
	}
	$min = $product->get_variation_price( 'min', true );
	$max = $product->get_variation_price( 'max', true );
	if ( '' === $min || $min === $max ) {
		return $html;
	}
	/* translators: %s: lowest price. */
	return sprintf( __( 'from %s', 'premium-shop' ), wc_price( $min ) ) . $product->get_price_suffix();
}
add_filter( 'woocommerce_variable_price_html', 'premium_shop_fw_from_price', 20, 2 );

/**
 * CSV import (Products → Import): imported firewood products get their
 * descriptions and automatic prices just like products saved in the editor.
 * Columns "Meta: _ps_species", "Meta: _ps_price_per_unit"… fill the data.
 *
 * Everything is set on the object *before* WooCommerce saves it, so the
 * import does not cost a single extra save (light on shared hosting).
 *
 * @param WC_Product $object Product or variation about to be saved.
 * @return WC_Product
 */
function premium_shop_fw_before_import_save( $object ) {
	static $parents = array();

	if ( ! $object instanceof WC_Product ) {
		return $object;
	}

	if ( $object->is_type( 'variation' ) ) {
		$parent_id = $object->get_parent_id();
		if ( ! $parent_id ) {
			return $object;
		}
		if ( ! array_key_exists( $parent_id, $parents ) ) {
			$parent                = wc_get_product( $parent_id );
			$parents[ $parent_id ] = ( $parent && 'yes' === $parent->get_meta( '_ps_auto_prices' ) ) ? $parent : null;
		}
		if ( $parents[ $parent_id ] ) {
			$price = premium_shop_fw_price_for( $parents[ $parent_id ], premium_shop_fw_qty( $object ) );
			if ( $price > 0 ) {
				$object->set_regular_price( (string) $price );
			}
		}
		return $object;
	}

	if ( premium_shop_fw_is_firewood( $object ) ) {
		premium_shop_fw_auto_texts( $object );

		if ( ! $object->is_type( 'variable' ) && 'yes' === $object->get_meta( '_ps_auto_prices' ) ) {
			$price = premium_shop_fw_price_for( $object, premium_shop_fw_qty( $object ) );
			if ( $price > 0 ) {
				$object->set_regular_price( (string) $price );
			}
		}
	}

	return $object;
}
add_filter( 'woocommerce_product_import_pre_insert_product_object', 'premium_shop_fw_before_import_save' );

/**
 * Lighter import batches: 10 rows and max. 10 seconds per request instead of
 * 30 rows / 20 seconds. Avoids "503 Service Unavailable" on shared hosting.
 */
add_filter(
	'woocommerce_product_import_batch_size',
	static function () {
		return (int) apply_filters( 'premium_shop_import_batch_size', 10 );
	}
);
add_filter(
	'woocommerce_product_importer_default_time_limit',
	static function () {
		return (int) apply_filters( 'premium_shop_import_time_limit', 10 );
	}
);
