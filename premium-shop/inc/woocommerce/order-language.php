<?php
/**
 * The customer's language follows the order: it is saved at checkout and
 * every e-mail sent to the customer (subject and content) — and the PDF
 * invoice of "PDF Invoices & Packing Slips for WooCommerce" — uses it, even
 * when the e-mail is sent later from the (German) dashboard.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Language code saved on an order ('' when unknown).
 *
 * @param WC_Order|int $order Order.
 * @return string
 */
function premium_shop_order_language( $order ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return '';
	}
	$lang = (string) $order->get_meta( '_ps_language' );
	return array_key_exists( $lang, premium_shop_languages() ) ? $lang : '';
}

/**
 * Language of the visitor placing the order.
 *
 * @return string
 */
function premium_shop_visitor_language() {
	if ( function_exists( 'premium_shop_builtin_applies' ) && premium_shop_builtin_applies() ) {
		return premium_shop_builtin_language();
	}
	return premium_shop_current_language();
}

/**
 * Save the language on new orders (classic and block checkout) and on the
 * customer account.
 *
 * @param WC_Order $order Order.
 */
function premium_shop_save_order_language( $order ) {
	if ( ! $order instanceof WC_Order || $order->get_meta( '_ps_language' ) ) {
		return;
	}
	$lang = premium_shop_visitor_language();
	if ( ! $lang ) {
		return;
	}
	$order->update_meta_data( '_ps_language', $lang );
	if ( $order->get_customer_id() ) {
		update_user_meta( $order->get_customer_id(), '_ps_language', $lang );
	}
}
add_action( 'woocommerce_checkout_create_order', 'premium_shop_save_order_language', 5 );
add_action( 'woocommerce_store_api_checkout_update_order_meta', 'premium_shop_save_order_language', 5 );

/**
 * Remember the language of customers who create an account.
 *
 * @param int $customer_id User ID.
 */
function premium_shop_save_customer_language( $customer_id ) {
	$lang = premium_shop_visitor_language();
	if ( $lang && ! is_admin() ) {
		update_user_meta( $customer_id, '_ps_language', $lang );
	}
}
add_action( 'woocommerce_created_customer', 'premium_shop_save_customer_language', 5 );

/**
 * Language for the object of an e-mail (order, refund, customer).
 *
 * @param mixed $object E-mail object.
 * @return string
 */
function premium_shop_email_object_language( $object ) {
	if ( $object instanceof WC_Order_Refund ) {
		$object = wc_get_order( $object->get_parent_id() );
	}
	if ( $object instanceof WC_Order ) {
		$lang = premium_shop_order_language( $object );
		if ( ! $lang && $object->get_customer_id() ) {
			$lang = (string) get_user_meta( $object->get_customer_id(), '_ps_language', true );
		}
		return $lang;
	}
	if ( $object instanceof WP_User ) {
		return (string) get_user_meta( $object->ID, '_ps_language', true );
	}
	return '';
}

/**
 * Switch every translation (WordPress, WooCommerce, theme) to a language.
 *
 * @param string $lang Language code.
 * @return bool Whether the locale was switched.
 */
function premium_shop_switch_language( $lang ) {
	if ( ! $lang || ! array_key_exists( $lang, premium_shop_languages() ) ) {
		return false;
	}
	$locale = premium_shop_locale_for( $lang );
	// switch_to_locale() returns false when WordPress already runs in that
	// locale; the translations are (re)loaded explicitly either way.
	$GLOBALS['premium_shop_locale_switched'] = (bool) switch_to_locale( $locale );
	premium_shop_load_translations( $locale );
	$GLOBALS['premium_shop_switched_language'] = $lang;
	return true;
}

/**
 * Load the WooCommerce and theme translations of a locale.
 *
 * @param string $locale Locale.
 */
function premium_shop_load_translations( $locale ) {
	if ( function_exists( 'WC' ) ) {
		unload_textdomain( 'woocommerce', true );
		if ( 'en_US' !== $locale ) {
			load_textdomain( 'woocommerce', WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo', $locale );
		}
	}
	unload_textdomain( 'premium-shop', true );
	if ( 'en_US' !== $locale ) {
		load_textdomain( 'premium-shop', PREMIUM_SHOP_DIR . '/languages/' . $locale . '.mo', $locale );
	}
}

/**
 * Undo premium_shop_switch_language().
 */
function premium_shop_restore_language() {
	if ( empty( $GLOBALS['premium_shop_switched_language'] ) ) {
		return;
	}
	unset( $GLOBALS['premium_shop_switched_language'] );
	if ( ! empty( $GLOBALS['premium_shop_locale_switched'] ) ) {
		restore_previous_locale();
	}
	unset( $GLOBALS['premium_shop_locale_switched'] );
	premium_shop_load_translations( determine_locale() );
}

/**
 * Customer e-mails: WooCommerce would switch to the site language before the
 * order is known; the theme switches to the order language instead, as soon
 * as the recipient is read (before the subject and content are built).
 *
 * @param bool     $allow Allow WooCommerce's own switch.
 * @param WC_Email $email E-mail.
 * @return bool
 */
function premium_shop_email_skip_site_locale( $allow, $email = null ) {
	if ( $email instanceof WC_Email && $email->is_customer_email() ) {
		return false;
	}
	return $allow;
}
add_filter( 'woocommerce_allow_switching_email_locale', 'premium_shop_email_skip_site_locale', 20, 2 );
add_filter( 'woocommerce_allow_restoring_email_locale', 'premium_shop_email_skip_site_locale', 20, 2 );

/**
 * Switch to the customer's language when a customer e-mail reads its recipient.
 *
 * @param string   $recipient Recipient.
 * @param mixed    $object    Order, user…
 * @param WC_Email $email     E-mail.
 * @return string
 */
function premium_shop_email_switch_language( $recipient, $object = null, $email = null ) {
	if ( ! $email instanceof WC_Email || premium_shop_email_switched( $email ) ) {
		return $recipient;
	}
	// A previous e-mail of the same request that did not restore (some
	// e-mails skip WooCommerce's restore step): start from a clean state.
	premium_shop_restore_language();
	// Customer e-mails: the customer's language. Shop e-mails (new order…):
	// always the shop language, even when the order is placed in French.
	$lang = $email->is_customer_email() ? premium_shop_email_object_language( $object ) : '';
	if ( ! $lang ) {
		$lang = premium_shop_default_language();
	}
	if ( premium_shop_switch_language( $lang ) ) {
		premium_shop_email_switched( $email, true );
		// WooCommerce keeps the default subject/heading of the first e-mail
		// sent in its settings cache: reload them for this language.
		$email->init_settings();
	}
	return $recipient;
}

/**
 * Remember which e-mail objects switched the language.
 *
 * @param WC_Email  $email E-mail.
 * @param bool|null $set   New state (null: read).
 * @return bool
 */
function premium_shop_email_switched( $email, $set = null ) {
	static $switched = array();
	$key = spl_object_id( $email );
	if ( null !== $set ) {
		if ( $set ) {
			$switched[ $key ] = true;
		} else {
			unset( $switched[ $key ] );
		}
	}
	return ! empty( $switched[ $key ] );
}

/**
 * Register the recipient filter for every customer e-mail.
 *
 * @param array $emails E-mail classes.
 * @return array
 */
function premium_shop_email_language_hooks( $emails ) {
	foreach ( (array) $emails as $email ) {
		if ( $email instanceof WC_Email ) {
			add_filter( 'woocommerce_email_recipient_' . $email->id, 'premium_shop_email_switch_language', 1, 3 );
		}
	}
	return $emails;
}
add_filter( 'woocommerce_email_classes', 'premium_shop_email_language_hooks', 999 );

/**
 * Back to the previous language after the e-mail was sent.
 *
 * @param bool     $allow Allow restoring.
 * @param WC_Email $email E-mail.
 * @return bool
 */
function premium_shop_email_restore_language( $allow, $email = null ) {
	if ( $email instanceof WC_Email && premium_shop_email_switched( $email ) ) {
		premium_shop_email_switched( $email, false );
		premium_shop_restore_language();
	}
	return $allow;
}
add_filter( 'woocommerce_allow_restoring_email_locale', 'premium_shop_email_restore_language', 30, 2 );

/**
 * Also restore once the e-mail is sent (covers e-mails without a restore step).
 *
 * @param bool     $sent  Sent.
 * @param string   $id    E-mail ID.
 * @param WC_Email $email E-mail.
 */
function premium_shop_email_sent_restore( $sent, $id = '', $email = null ) {
	premium_shop_email_restore_language( true, $email );
}
add_action( 'woocommerce_email_sent', 'premium_shop_email_sent_restore', 99, 3 );

/**
 * PDF Invoices & Packing Slips for WooCommerce: documents in the order's
 * language (also when downloaded from the dashboard).
 *
 * @param string $type     Document type.
 * @param object $document Document.
 */
function premium_shop_pdf_switch_language( $type = '', $document = null ) {
	if ( ! empty( $GLOBALS['premium_shop_switched_language'] ) ) {
		return; // Already in the order language (e-mail attachment).
	}
	$order = is_object( $document ) && isset( $document->order ) ? $document->order : null;
	$lang  = premium_shop_email_object_language( $order );
	if ( $lang && premium_shop_switch_language( $lang ) ) {
		$GLOBALS['premium_shop_pdf_switched'] = true;
	}
}
add_action( 'wpo_wcpdf_before_html', 'premium_shop_pdf_switch_language', 1, 2 );

/**
 * After the PDF: previous language.
 */
function premium_shop_pdf_restore_language() {
	if ( ! empty( $GLOBALS['premium_shop_pdf_switched'] ) ) {
		unset( $GLOBALS['premium_shop_pdf_switched'] );
		premium_shop_restore_language();
	}
}
add_action( 'wpo_wcpdf_after_html', 'premium_shop_pdf_restore_language', 99 );

/**
 * Order screen: show and change the customer's language.
 *
 * @param WC_Order $order Order.
 */
function premium_shop_order_language_field( $order ) {
	$current = premium_shop_order_language( $order );
	?>
	<p class="form-field form-field-wide">
		<label for="ps_order_language"><?php esc_html_e( 'Customer language (e-mails, invoice)', 'premium-shop' ); ?></label>
		<select id="ps_order_language" name="ps_order_language" style="width:100%">
			<option value=""><?php esc_html_e( 'Shop language', 'premium-shop' ); ?></option>
			<?php foreach ( premium_shop_languages() as $code => $lang ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>><?php echo esc_html( $lang['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php
}
add_action( 'woocommerce_admin_order_data_after_order_details', 'premium_shop_order_language_field' );

/**
 * Save the language chosen in the order screen.
 *
 * @param int $order_id Order ID.
 */
function premium_shop_order_language_save( $order_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checks the order nonce.
	if ( ! isset( $_POST['ps_order_language'] ) || ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$lang = sanitize_key( wp_unslash( $_POST['ps_order_language'] ) );
	if ( '' === $lang || array_key_exists( $lang, premium_shop_languages() ) ) {
		$order->update_meta_data( '_ps_language', $lang );
		$order->save_meta_data();
	}
}
add_action( 'woocommerce_process_shop_order_meta', 'premium_shop_order_language_save', 20 );

/**
 * Names typed in the WooCommerce settings in the shop language (shipping
 * methods, tax rates, payment methods): shown in the customer's language
 * through the theme catalogue or "[:de]..[:fr].." versions.
 *
 * @param array $rates Shipping rates.
 * @return array
 */
function premium_shop_translate_shipping_rates( $rates ) {
	if ( function_exists( 'premium_shop_i18n_label' ) ) {
		foreach ( $rates as $rate ) {
			if ( $rate instanceof WC_Shipping_Rate ) {
				$rate->set_label( premium_shop_i18n_label( $rate->get_label() ) );
			}
		}
	}
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'premium_shop_translate_shipping_rates', 99 );

/**
 * Tax rate names and payment method titles/descriptions.
 *
 * @param string $text Text.
 * @return string
 */
function premium_shop_translate_setting_label( $text ) {
	return function_exists( 'premium_shop_i18n_label' ) && is_string( $text ) ? premium_shop_i18n_label( $text ) : $text;
}
add_filter( 'woocommerce_rate_label', 'premium_shop_translate_setting_label', 20 );
add_filter( 'woocommerce_gateway_title', 'premium_shop_translate_setting_label', 20 );
add_filter( 'woocommerce_gateway_description', 'premium_shop_translate_setting_label', 20 );

/**
 * Common names typed in WooCommerce settings, kept in the catalogue.
 *
 * @return string[]
 */
function premium_shop_order_language_strings() {
	return array(
		__( 'VAT', 'premium-shop' ),
		__( 'Freight forwarding', 'premium-shop' ),
		__( 'Pickup', 'premium-shop' ),
		__( 'Delivery by truck', 'premium-shop' ),
		__( 'Prepayment', 'premium-shop' ),
		__( 'Invoice', 'premium-shop' ),
	);
}
