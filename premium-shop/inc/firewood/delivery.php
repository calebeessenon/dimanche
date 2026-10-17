<?php
/**
 * Delivery: postcode check + checkout question about truck access.
 *
 * The postcode check reads your WooCommerce shipping zones (WooCommerce →
 * Settings → Shipping): add the postcodes you serve to each zone and the
 * check answers customers automatically — fewer phone calls and e-mails.
 * The entered postcode is remembered and pre-fills the checkout.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Answer for a postcode.
 *
 * @param string $postcode Postcode.
 * @param string $country  Country code.
 * @return array ok, message, methods[], zone
 */
function premium_shop_delivery_lookup( $postcode, $country ) {
	$package = array(
		'destination' => array(
			'country'   => $country,
			'state'     => '',
			'postcode'  => $postcode,
			'city'      => '',
			'address'   => '',
			'address_2' => '',
		),
		'contents'    => array(),
	);

	$zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
	$methods = array();

	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		$cost = '';
		if ( 'flat_rate' === $method->id ) {
			$raw = (string) $method->get_option( 'cost' );
			if ( is_numeric( str_replace( ',', '.', $raw ) ) ) {
				$value = (float) str_replace( ',', '.', $raw );
				$cost  = $value > 0 ? premium_shop_plain_price( $value ) : __( 'free', 'premium-shop' );
			}
		} elseif ( 'free_shipping' === $method->id ) {
			$min  = (float) $method->get_option( 'min_amount' );
			/* translators: %s: amount. */
			$cost = $min > 0 ? sprintf( __( 'free from %s', 'premium-shop' ), premium_shop_plain_price( $min ) ) : __( 'free', 'premium-shop' );
		} elseif ( 'local_pickup' === $method->id || 'pickup_location' === $method->id ) {
			$cost = __( 'pick-up', 'premium-shop' );
		}

		$methods[] = array(
			'title' => wp_strip_all_tags( $method->get_title() ),
			'cost'  => $cost,
		);
	}

	$ok = ! empty( $methods );

	return array(
		'ok'      => $ok,
		'message' => $ok ? premium_shop_text( 'fw_delivery_ok' ) : premium_shop_text( 'fw_delivery_no' ),
		'methods' => $methods,
		'zone'    => $ok ? wp_strip_all_tags( $zone->get_zone_name() ) : '',
		'time'    => $ok ? premium_shop_text( 'delivery_time' ) : '',
	);
}

/**
 * AJAX endpoint (wc-ajax=ps_delivery_check).
 */
function premium_shop_ajax_delivery_check() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only public lookup.
	$postcode = isset( $_GET['postcode'] ) ? wc_format_postcode( sanitize_text_field( wp_unslash( $_GET['postcode'] ) ), '' ) : '';
	$country  = isset( $_GET['country'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['country'] ) ) ) : '';
	// phpcs:enable
	premium_shop_ajax_language();

	$countries = WC()->countries->get_shipping_countries();
	if ( ! $country || ! isset( $countries[ $country ] ) ) {
		$country = WC()->countries->get_base_country();
	}
	$postcode = wc_format_postcode( $postcode, $country );

	if ( '' === $postcode || ! WC_Validation::is_postcode( $postcode, $country ) || strlen( $postcode ) > 12 ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid postcode.', 'premium-shop' ) ) );
	}

	$result = premium_shop_delivery_lookup( $postcode, $country );

	// Remember the postcode for the cart and checkout.
	if ( WC()->customer ) {
		WC()->customer->set_shipping_country( $country );
		WC()->customer->set_shipping_postcode( $postcode );
		if ( ! WC()->customer->get_billing_postcode() ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_billing_postcode( $postcode );
		}
		WC()->customer->save();
		if ( WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	wp_send_json_success( $result );
}
add_action( 'wc_ajax_ps_delivery_check', 'premium_shop_ajax_delivery_check' );

/**
 * Render the delivery check.
 *
 * @param array $args compact (bool), title (string).
 */
function premium_shop_delivery_check( $args = array() ) {
	if ( ! premium_shop_option( 'fw_delivery_check' ) ) {
		return;
	}
	get_template_part( 'template-parts/components/delivery-check', null, $args );
}

/**
 * Shortcode [ps_delivery_check].
 *
 * @return string
 */
function premium_shop_delivery_shortcode() {
	ob_start();
	premium_shop_delivery_check( array( 'compact' => false ) );
	return ob_get_clean();
}
add_shortcode( 'ps_delivery_check', 'premium_shop_delivery_shortcode' );

/**
 * Product page: compact check under the reassurance box.
 */
function premium_shop_delivery_on_product() {
	global $product;
	if ( $product instanceof WC_Product && $product->needs_shipping() ) {
		premium_shop_delivery_check( array( 'compact' => true ) );
	}
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_delivery_on_product', 36 );

/**
 * Cart (classic): check above the totals.
 */
function premium_shop_delivery_on_cart() {
	premium_shop_delivery_check( array( 'compact' => true ) );
}
add_action( 'woocommerce_before_cart_collaterals', 'premium_shop_delivery_on_cart' );

/* -------------------------------------------------------------------------
 * Checkout: truck access
 * ---------------------------------------------------------------------- */

/**
 * Options of the truck question.
 *
 * @return array
 */
function premium_shop_truck_options() {
	return array(
		'yes'    => __( 'Yes, accessible for a truck', 'premium-shop' ),
		'no'     => __( 'No — please contact me', 'premium-shop' ),
		'unsure' => __( 'I am not sure', 'premium-shop' ),
	);
}

/**
 * Block checkout: register the field (WooCommerce 8.9+).
 */
function premium_shop_register_truck_field() {
	if ( ! premium_shop_option( 'fw_truck_field' ) || ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	$options = array();
	foreach ( premium_shop_truck_options() as $value => $label ) {
		$options[] = array(
			'value' => $value,
			'label' => $label,
		);
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'premium-shop/truck-access',
			'label'    => __( 'Is the unloading point accessible for a truck?', 'premium-shop' ),
			'location' => 'order',
			'type'     => 'select',
			'required' => false,
			'options'  => $options,
		)
	);
}
add_action( 'woocommerce_init', 'premium_shop_register_truck_field' );

/**
 * Classic checkout: add the field to the order notes group.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function premium_shop_classic_truck_field( $fields ) {
	if ( ! premium_shop_option( 'fw_truck_field' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return $fields;
	}

	$fields['order']['ps_truck_access'] = array(
		'type'     => 'select',
		'label'    => __( 'Is the unloading point accessible for a truck?', 'premium-shop' ),
		'required' => false,
		'class'    => array( 'form-row-wide' ),
		'options'  => array( '' => __( 'Please choose', 'premium-shop' ) ) + premium_shop_truck_options(),
		'priority' => 5,
	);

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'premium_shop_classic_truck_field' );

/**
 * Classic checkout: save the answer on the order (HPOS-safe).
 *
 * @param WC_Order $order Order.
 * @param array    $data  Posted data.
 */
function premium_shop_save_truck_field( $order, $data ) {
	if ( ! empty( $data['ps_truck_access'] ) && array_key_exists( $data['ps_truck_access'], premium_shop_truck_options() ) ) {
		$order->update_meta_data( '_ps_truck_access', sanitize_key( $data['ps_truck_access'] ) );
	}
}
add_action( 'woocommerce_checkout_create_order', 'premium_shop_save_truck_field', 10, 2 );

/**
 * Truck answer of an order, whichever checkout was used.
 *
 * @param WC_Order $order Order.
 * @return string Label or empty.
 */
function premium_shop_order_truck_answer( $order ) {
	$value = (string) $order->get_meta( '_ps_truck_access' );
	if ( '' === $value ) {
		$value = (string) $order->get_meta( '_wc_other/premium-shop/truck-access' );
	}
	$options = premium_shop_truck_options();
	return isset( $options[ $value ] ) ? $options[ $value ] : '';
}

/**
 * Show the classic answer in the admin order screen (block answers are shown by WooCommerce).
 *
 * @param WC_Order $order Order.
 */
function premium_shop_admin_truck_answer( $order ) {
	$answer = (string) $order->get_meta( '_ps_truck_access' );
	$labels = premium_shop_truck_options();
	if ( isset( $labels[ $answer ] ) ) {
		printf( '<p><strong>%1$s</strong><br>%2$s</p>', esc_html__( 'Truck access', 'premium-shop' ), esc_html( $labels[ $answer ] ) );
	}
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'premium_shop_admin_truck_answer' );

/**
 * Add the classic answer to order e-mails.
 *
 * @param array    $fields        Fields.
 * @param bool     $sent_to_admin To admin.
 * @param WC_Order $order         Order.
 * @return array
 */
function premium_shop_email_truck_answer( $fields, $sent_to_admin, $order ) {
	$answer = (string) $order->get_meta( '_ps_truck_access' );
	$labels = premium_shop_truck_options();
	if ( isset( $labels[ $answer ] ) ) {
		$fields['ps_truck_access'] = array(
			'label' => __( 'Truck access', 'premium-shop' ),
			'value' => $labels[ $answer ],
		);
	}
	return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'premium_shop_email_truck_answer', 10, 3 );
