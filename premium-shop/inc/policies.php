<?php
/**
 * Delivery terms (times and costs) and the returns & refund policy, with
 * links on product pages, in the cart, at checkout and in the footer
 * ("Gaming & tech" edition).
 *
 * Texts: inc/policies/{delivery|returns}-{de|fr|en|es}.html, filled with the
 * shop's details (return period, contact, WooCommerce shipping costs) in the
 * visitor's language.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slug of the returns & refund page.
 */
const PREMIUM_SHOP_RETURNS_SLUG = 'rueckgabe-und-erstattung';

/**
 * Shipping page slugs (first one is created when none exists).
 *
 * @return string[]
 */
function premium_shop_shipping_slugs() {
	return array( 'versand-und-zahlung', 'versand', 'shipping', 'livraison', 'envio' );
}

/**
 * Whether the policies are active (gaming edition).
 *
 * @return bool
 */
function premium_shop_policies_active() {
	return (bool) apply_filters( 'premium_shop_policies_active', function_exists( 'premium_shop_preset' ) && 'gaming' === premium_shop_preset() );
}

/**
 * Language of the policy texts.
 *
 * @return string de|fr|en|es
 */
function premium_shop_policy_lang() {
	$lang = function_exists( 'premium_shop_current_language' ) ? premium_shop_current_language() : 'de';
	return in_array( $lang, array( 'de', 'fr', 'en', 'es' ), true ) ? $lang : 'en';
}

/**
 * Small texts of this feature in the four languages.
 *
 * @param string $key  Text key.
 * @param string $lang Language ('' = current).
 * @return string
 */
function premium_shop_policy_label( $key, $lang = '' ) {
	$lang  = '' !== $lang ? $lang : premium_shop_policy_lang();
	$texts = array(
		'imprint_title'  => array( 'de' => 'Impressum', 'fr' => 'Mentions légales', 'en' => 'Legal notice', 'es' => 'Aviso legal' ),
		'sales_title'    => array( 'de' => 'AGB', 'fr' => 'Conditions générales de vente', 'en' => 'Terms and conditions of sale', 'es' => 'Condiciones generales de venta' ),
		'returns_title'  => array( 'de' => 'Rückgabe & Erstattung', 'fr' => 'Retours & remboursements', 'en' => 'Returns & refunds', 'es' => 'Devoluciones y reembolsos' ),
		'shipping_title' => array( 'de' => 'Versand & Zahlung', 'fr' => 'Livraison & paiement', 'en' => 'Shipping & payment', 'es' => 'Envío y pago' ),
		'link_shipping'  => array( 'de' => 'Lieferung: Fristen & Versandkosten', 'fr' => 'Livraison : délais & frais', 'en' => 'Delivery: times & costs', 'es' => 'Envío: plazos y gastos' ),
		'link_returns'   => array( 'de' => 'Rückgabe & Erstattung', 'fr' => 'Retours & remboursements', 'en' => 'Returns & refunds', 'es' => 'Devoluciones y reembolsos' ),
		'zone'           => array( 'de' => 'Liefergebiet', 'fr' => 'Zone de livraison', 'en' => 'Delivery area', 'es' => 'Zona de entrega' ),
		'method'         => array( 'de' => 'Versandart', 'fr' => 'Mode de livraison', 'en' => 'Shipping method', 'es' => 'Método de envío' ),
		'cost'           => array( 'de' => 'Kosten', 'fr' => 'Frais', 'en' => 'Cost', 'es' => 'Coste' ),
		'free'           => array( 'de' => 'Kostenlos', 'fr' => 'Gratuit', 'en' => 'Free', 'es' => 'Gratis' ),
		'free_from'      => array( 'de' => 'Kostenlos ab %s Bestellwert', 'fr' => 'Gratuit dès %s d’achat', 'en' => 'Free from %s order value', 'es' => 'Gratis a partir de %s de compra' ),
		'in_cart'        => array( 'de' => 'Wird im Warenkorb berechnet', 'fr' => 'Calculé dans le panier', 'en' => 'Calculated in the cart', 'es' => 'Se calcula en el carrito' ),
		'free_all'       => array( 'de' => 'Kostenloser Versand ab %s Bestellwert.', 'fr' => 'Livraison gratuite dès %s d’achat.', 'en' => 'Free shipping from %s order value.', 'es' => 'Envío gratuito a partir de %s de compra.' ),
		'phone'          => array( 'de' => ' oder telefonisch unter %s', 'fr' => ' ou par téléphone au %s', 'en' => ' or call us on %s', 'es' => ' o llámenos al %s' ),
		'phone_label'    => array( 'de' => 'Telefon', 'fr' => 'Téléphone', 'en' => 'Phone', 'es' => 'Teléfono' ),
	);
	return isset( $texts[ $key ][ $lang ] ) ? $texts[ $key ][ $lang ] : ( isset( $texts[ $key ]['en'] ) ? $texts[ $key ]['en'] : '' );
}

/**
 * URL of the returns & refund page ('' when it does not exist).
 *
 * @return string
 */
function premium_shop_returns_url() {
	$id = (int) get_option( 'premium_shop_returns_page' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return (string) get_permalink( $id );
	}
	return premium_shop_page_url_by_slugs( array( PREMIUM_SHOP_RETURNS_SLUG ) );
}

/**
 * URL of the shipping page ('' when it does not exist).
 *
 * @return string
 */
function premium_shop_shipping_url() {
	return premium_shop_page_url_by_slugs( premium_shop_shipping_slugs() );
}

/**
 * Shipping costs table built from WooCommerce → Settings → Shipping.
 *
 * @param string $lang Language.
 * @return string HTML ('' when no shipping zone is set up).
 */
function premium_shop_policy_fees_table( $lang ) {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return '';
	}
	$price = static function ( $amount ) {
		return function_exists( 'premium_shop_plain_price' ) ? premium_shop_plain_price( (float) $amount ) : wp_strip_all_tags( wc_price( (float) $amount ) );
	};
	$label = static function ( $text ) {
		return function_exists( 'premium_shop_translate_wc_text' ) ? premium_shop_translate_wc_text( $text ) : $text;
	};

	$rows = '';
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		$first = true;
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'yes' !== $method->enabled ) {
				continue;
			}
			$settings = (array) $method->instance_settings;
			if ( 'free_shipping' === $method->id ) {
				$min  = isset( $settings['min_amount'] ) ? (float) $settings['min_amount'] : 0;
				$cost = $min > 0 ? sprintf( premium_shop_policy_label( 'free_from', $lang ), $price( $min ) ) : premium_shop_policy_label( 'free', $lang );
			} else {
				$raw  = isset( $settings['cost'] ) ? trim( (string) $settings['cost'] ) : '';
				$raw  = str_replace( ',', '.', $raw );
				$cost = '' === $raw || ( is_numeric( $raw ) && 0.0 === (float) $raw )
					? premium_shop_policy_label( 'free', $lang )
					: ( is_numeric( $raw ) ? $price( $raw ) : premium_shop_policy_label( 'in_cart', $lang ) );
			}
			$rows .= '<tr><td>' . ( $first ? esc_html( $label( $zone['zone_name'] ) ) : '' ) . '</td><td>' . esc_html( $label( $method->get_title() ) ) . '</td><td><strong>' . esc_html( $cost ) . '</strong></td></tr>';
			$first = false;
		}
	}

	$out = '';
	if ( '' !== $rows ) {
		$out .= '<div class="ps-zones"><table class="ps-zones__table ps-policy-table"><thead><tr><th>' . esc_html( premium_shop_policy_label( 'zone', $lang ) ) . '</th><th>' . esc_html( premium_shop_policy_label( 'method', $lang ) ) . '</th><th>' . esc_html( premium_shop_policy_label( 'cost', $lang ) ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';
	}
	$threshold = function_exists( 'premium_shop_free_shipping_threshold' ) ? premium_shop_free_shipping_threshold() : 0;
	if ( $threshold > 0 ) {
		$out .= '<p><strong>' . esc_html( sprintf( premium_shop_policy_label( 'free_all', $lang ), $price( $threshold ) ) ) . '</strong></p>';
	}
	return $out;
}

/**
 * Policy text with the shop's details.
 *
 * @param string $doc  delivery|returns.
 * @param string $lang Language ('' = current).
 * @return string HTML.
 */
function premium_shop_policy_html( $doc, $lang = '' ) {
	$lang = '' !== $lang ? $lang : premium_shop_policy_lang();
	$file = PREMIUM_SHOP_DIR . '/inc/policies/' . sanitize_key( $doc ) . '-' . $lang . '.html';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	$html = (string) file_get_contents( $file );

	$company = trim( (string) premium_shop_option( 'company_name' ) );
	$company = '' !== $company ? $company : get_bloginfo( 'name' );
	$email   = trim( (string) premium_shop_option( 'contact_email' ) );
	$email   = is_email( $email ) ? $email : (string) get_option( 'admin_email' );
	$phone   = trim( (string) premium_shop_option( 'contact_phone' ) );
	$tel     = '' !== $phone ? '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' : '';
	$lines   = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) premium_shop_option( 'contact_address' ) ) ) ) );
	$uid     = trim( (string) premium_shop_option( 'company_uid' ) );
	$city    = '';
	$city_ln = (string) end( $lines );
	if ( '' !== $city_ln ) {
		$city = trim( strtok( (string) preg_replace( '/^\s*[A-Z]{0,2}-?\d{4,5}\s+/', '', $city_ln ), ',' ) );
	}
	$days    = absint( premium_shop_option( 'returns_days' ) );
	$days    = $days ? $days : 14;

	$html = strtr(
		$html,
		array(
			'{days}'        => (string) $days,
			'{uid_line}'    => '' !== $uid ? '<br>' . esc_html( ( 'fr' === $lang ? 'IDE : ' : 'UID: ' ) . $uid ) : '',
			'{uid}'         => esc_html( $uid ),
			'{address_inline}' => esc_html( implode( ', ', $lines ) ),
			'{company}'     => esc_html( $company ),
			'{address}'     => implode( '<br>', array_map( 'esc_html', $lines ) ),
			'{email}'       => esc_html( $email ),
			'{phone_text}'  => '' !== $tel ? sprintf( premium_shop_policy_label( 'phone', $lang ), $tel ) : '',
			'{phone_line}'  => '' !== $tel ? '<br>' . esc_html( premium_shop_policy_label( 'phone_label', $lang ) ) . ( 'fr' === $lang ? ' : ' : ': ' ) . $tel : '',
			'{returns_url}' => esc_url( premium_shop_returns_url() ? premium_shop_returns_url() : home_url( '/' ) ),
			'{fees}'        => 'delivery' === $doc ? premium_shop_policy_fees_table( $lang ) : '',
			'{shop_name}'   => esc_html( get_bloginfo( 'name' ) ),
			'{site_url}'    => esc_url( home_url( '/' ) ),
			'{shipping_url}' => esc_url( premium_shop_shipping_url() ? premium_shop_shipping_url() : home_url( '/' ) ),
			'{privacy_url}' => esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/' ) ),
			'{city}'        => esc_html( $city ),
		)
	);
	$html = str_replace( array( '<br><br>', '<table class="ps-policy-table">', '</table>' . "\n" ), array( '<br>', '<div class="ps-zones"><table class="ps-zones__table ps-policy-table">', '</table></div>' . "\n" ), $html );

	$html = premium_shop_policy_table_labels( $html );

	return '<div class="ps-policy ps-policy--' . esc_attr( $doc ) . '">' . $html . '</div>';
}

/**
 * Give each cell of the policy tables its column title (data-label), shown
 * above the value when the table is stacked on phones.
 *
 * @param string $html HTML.
 * @return string
 */
function premium_shop_policy_table_labels( $html ) {
	return (string) preg_replace_callback(
		'#<table class="ps-zones__table ps-policy-table">(.*?)</table>#s',
		static function ( $m ) {
			preg_match( '#<thead>.*?</thead>#s', $m[1], $head );
			preg_match_all( '#<th>(.*?)</th>#s', isset( $head[0] ) ? $head[0] : '', $ths );
			$labels = array_map( 'wp_strip_all_tags', $ths[1] );
			$body   = preg_replace_callback(
				'#<tr>(.*?)</tr>#s',
				static function ( $row ) use ( $labels ) {
					$i = 0;
					return '<tr>' . preg_replace_callback(
						'#<td>#',
						static function () use ( $labels, &$i ) {
							$label = isset( $labels[ $i ] ) ? $labels[ $i ] : '';
							$i++;
							return '' !== $label ? '<td data-label="' . esc_attr( $label ) . '">' : '<td>';
						},
						$row[1]
					) . '</tr>';
				},
				preg_replace( '#<thead>.*?</thead>#s', '', $m[1] )
			);
			return '<table class="ps-zones__table ps-policy-table">' . ( isset( $head[0] ) ? $head[0] : '' ) . $body . '</table>';
		},
		$html
	);
}

/**
 * [ps_delivery_terms] Delivery times and shipping costs.
 *
 * @return string
 */
function premium_shop_delivery_terms_shortcode() {
	return premium_shop_policy_html( 'delivery' );
}
add_shortcode( 'ps_delivery_terms', 'premium_shop_delivery_terms_shortcode' );

/**
 * [ps_legal_notice] Legal notice (Impressum).
 *
 * @return string
 */
function premium_shop_legal_notice_shortcode() {
	return premium_shop_policy_html( 'imprint' );
}
add_shortcode( 'ps_legal_notice', 'premium_shop_legal_notice_shortcode' );

/**
 * [ps_terms_of_sale] General terms and conditions of sale.
 *
 * @return string
 */
function premium_shop_terms_of_sale_shortcode() {
	return premium_shop_policy_html( 'sales' );
}
add_shortcode( 'ps_terms_of_sale', 'premium_shop_terms_of_sale_shortcode' );

/**
 * [ps_returns_policy] Returns & refund policy.
 *
 * @return string
 */
function premium_shop_returns_policy_shortcode() {
	return premium_shop_policy_html( 'returns' );
}
add_shortcode( 'ps_returns_policy', 'premium_shop_returns_policy_shortcode' );

/**
 * Add a page to the "Customer service" footer menu (when one is assigned).
 *
 * @param int $page_id Page ID.
 */
function premium_shop_policy_menu_add( $page_id ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menu_id   = ! empty( $locations['footer_service'] ) ? (int) $locations['footer_service'] : 0;
	if ( ! $page_id || ! $menu_id || ! wp_get_nav_menu_object( $menu_id ) ) {
		return;
	}
	foreach ( (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) ) as $item ) {
		if ( (int) $item->object_id === (int) $page_id ) {
			return;
		}
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-object-id' => (int) $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
}

/**
 * Create the returns & refund page, put the delivery times and costs on the
 * shipping page and link both in the footer (gaming edition).
 */
function premium_shop_policies_install() {
	if ( ! premium_shop_policies_active() ) {
		return;
	}
	$default = function_exists( 'premium_shop_default_language' ) ? premium_shop_default_language() : 'de';
	$default = in_array( $default, array( 'de', 'fr', 'en', 'es' ), true ) ? $default : 'en';

	// Returns & refund page.
	$returns = get_page_by_path( PREMIUM_SHOP_RETURNS_SLUG, OBJECT, 'page' );
	if ( ! $returns || 'trash' === $returns->post_status ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => PREMIUM_SHOP_RETURNS_SLUG,
				'post_title'   => premium_shop_policy_label( 'returns_title', $default ),
				'post_content' => '<!-- wp:shortcode -->[ps_returns_policy]<!-- /wp:shortcode -->',
			),
			true
		);
		$returns = is_wp_error( $id ) ? null : get_post( $id );
	}
	if ( $returns ) {
		foreach ( array( 'de', 'fr', 'en', 'es' ) as $lang ) {
			if ( $lang !== $default && '' === (string) get_post_meta( $returns->ID, '_ps_name_' . $lang, true ) ) {
				update_post_meta( $returns->ID, '_ps_name_' . $lang, premium_shop_policy_label( 'returns_title', $lang ) );
			}
		}
		update_option( 'premium_shop_returns_page', (int) $returns->ID, false );
		update_option( 'woocommerce_refund_returns_page_id', (int) $returns->ID );
		premium_shop_policy_menu_add( (int) $returns->ID );
	}

	// Shipping page: delivery times and costs.
	$shipping = null;
	foreach ( premium_shop_shipping_slugs() as $slug ) {
		$shipping = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $shipping && 'trash' !== $shipping->post_status ) {
			break;
		}
		$shipping = null;
	}
	$block = '<!-- wp:shortcode -->[ps_delivery_terms]<!-- /wp:shortcode -->';
	if ( ! $shipping ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => 'versand-und-zahlung',
				'post_title'   => premium_shop_policy_label( 'shipping_title', $default ),
				'post_content' => $block . '<!-- wp:shortcode -->[ps_payment_methods]<!-- /wp:shortcode -->',
			),
			true
		);
		$shipping = is_wp_error( $id ) ? null : get_post( $id );
		if ( $shipping ) {
			foreach ( array( 'de', 'fr', 'en', 'es' ) as $lang ) {
				if ( $lang !== $default ) {
					update_post_meta( $shipping->ID, '_ps_name_' . $lang, premium_shop_policy_label( 'shipping_title', $lang ) );
				}
			}
		}
	} else {
		$add = static function ( $content ) use ( $block ) {
			if ( false !== strpos( $content, '[ps_delivery_terms]' ) ) {
				return $content;
			}
			$anchor = '[ps_shipping_info]<!-- /wp:shortcode -->';
			return false !== strpos( $content, $anchor )
				? str_replace( $anchor, $anchor . $block, $content )
				: $block . $content;
		};
		$new = $add( $shipping->post_content );
		if ( $new !== $shipping->post_content ) {
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $shipping->ID,
						'post_content' => $new,
					)
				)
			);
		}
		foreach ( array( 'de', 'fr', 'en', 'es' ) as $lang ) {
			$meta = (string) get_post_meta( $shipping->ID, '_ps_desc_' . $lang, true );
			if ( '' !== $meta && $add( $meta ) !== $meta ) {
				update_post_meta( $shipping->ID, '_ps_desc_' . $lang, wp_slash( $add( $meta ) ) );
			}
		}
	}
	if ( $shipping ) {
		premium_shop_policy_menu_add( (int) $shipping->ID );
	}
}
add_action( 'premium_shop_upgrade_2_3_7', 'premium_shop_policies_install' );
add_action( 'after_switch_theme', 'premium_shop_policies_install', 20 );

/**
 * The two links (delivery, returns) as HTML.
 *
 * @param string $class Extra class.
 * @return string
 */
function premium_shop_policy_links_html( $class = '' ) {
	if ( ! premium_shop_policies_active() ) {
		return '';
	}
	$links = array();
	if ( premium_shop_shipping_url() ) {
		$links[] = array( 'truck', premium_shop_shipping_url(), premium_shop_policy_label( 'link_shipping' ) );
	}
	if ( premium_shop_returns_url() ) {
		$links[] = array( 'return', premium_shop_returns_url(), premium_shop_policy_label( 'link_returns' ) );
	}
	if ( ! $links ) {
		return '';
	}
	ob_start();
	echo '<ul class="ps-policy-links ' . esc_attr( $class ) . '">';
	foreach ( $links as $link ) {
		echo '<li><a href="' . esc_url( $link[1] ) . '">';
		premium_shop_icon( $link[0], array( 'size' => 18 ) );
		echo '<span>' . esc_html( $link[2] ) . '</span></a></li>';
	}
	echo '</ul>';
	return (string) ob_get_clean();
}

/**
 * Product page: links under the reassurance box.
 */
function premium_shop_policy_links_product() {
	echo premium_shop_policy_links_html( 'ps-policy-links--product' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
}
add_action( 'woocommerce_single_product_summary', 'premium_shop_policy_links_product', 36 );

/**
 * Cart: links under the totals.
 */
function premium_shop_policy_links_cart() {
	echo premium_shop_policy_links_html( 'ps-policy-links--cart' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
}
add_action( 'woocommerce_after_cart_totals', 'premium_shop_policy_links_cart' );

/**
 * Checkout: links under the "Place order" button.
 */
function premium_shop_policy_links_checkout() {
	echo premium_shop_policy_links_html( 'ps-policy-links--checkout' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
}
add_action( 'woocommerce_review_order_after_submit', 'premium_shop_policy_links_checkout', 20 );

/**
 * Cart and checkout blocks: links after the block.
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function premium_shop_policy_links_blocks( $html, $block ) {
	if ( isset( $block['blockName'] ) && in_array( $block['blockName'], array( 'woocommerce/cart', 'woocommerce/checkout' ), true ) ) {
		$html .= premium_shop_policy_links_html( 'ps-policy-links--block' );
	}
	return $html;
}
add_filter( 'render_block', 'premium_shop_policy_links_blocks', 20, 2 );

/**
 * Footer "Customer service" list without a menu: add the returns page.
 *
 * @param array  $links    [ url, label ] pairs.
 * @param string $location Menu location.
 * @return array
 */
function premium_shop_policy_footer_links( $links, $location ) {
	if ( 'footer_service' === $location && premium_shop_policies_active() && premium_shop_returns_url() ) {
		$links[] = array( premium_shop_returns_url(), premium_shop_policy_label( 'link_returns' ) );
	}
	return $links;
}
add_filter( 'premium_shop_footer_fallback_links', 'premium_shop_policy_footer_links', 10, 2 );

/**
 * Legal notice (Impressum) and terms of sale (AGB) pages: filled with the
 * theme's texts when they do not exist yet or still hold the setup
 * assistant's placeholder ("Please complete this page…"). A page you wrote
 * yourself is never touched.
 */
function premium_shop_company_pages_install() {
	if ( ! premium_shop_policies_active() ) {
		return;
	}
	$default = function_exists( 'premium_shop_default_language' ) ? premium_shop_default_language() : 'de';
	$default = in_array( $default, array( 'de', 'fr', 'en', 'es' ), true ) ? $default : 'en';
	$pages   = array(
		'impressum' => array( 'imprint_title', '[ps_legal_notice]' ),
		'agb'       => array( 'sales_title', '[ps_terms_of_sale]' ),
	);
	$placeholders = array();
	foreach ( array( 'de_DE', 'fr_FR', 'en_US', 'es_ES' ) as $locale ) {
		$text = function_exists( 'premium_shop_translate_in' ) ? premium_shop_translate_in( 'Please complete this page with your legally required company information before publishing.', $locale ) : '';
		if ( '' !== $text ) {
			$placeholders[] = $text;
		}
	}
	$placeholders[] = 'Please complete this page with your legally required company information before publishing.';

	foreach ( $pages as $slug => $page ) {
		$content = '<!-- wp:shortcode -->' . $page[1] . '<!-- /wp:shortcode -->';
		$post    = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $post && 'trash' !== $post->post_status ) {
			$plain = trim( wp_strip_all_tags( $post->post_content ) );
			$empty = '' === $plain;
			foreach ( $placeholders as $text ) {
				$empty = $empty || $plain === $text;
			}
			if ( ! $empty && false === strpos( $post->post_content, $page[1] ) ) {
				continue; // Written by hand.
			}
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $post->ID,
						'post_content' => $content,
						'post_status'  => 'publish',
					)
				)
			);
			$id = (int) $post->ID;
		} else {
			$id = wp_insert_post(
				wp_slash(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_name'    => $slug,
						'post_title'   => premium_shop_policy_label( $page[0], $default ),
						'post_content' => $content,
					)
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
		}
		foreach ( array( 'de', 'fr', 'en', 'es' ) as $lang ) {
			if ( $lang !== $default ) {
				update_post_meta( $id, '_ps_name_' . $lang, premium_shop_policy_label( $page[0], $lang ) );
			}
			delete_post_meta( $id, '_ps_desc_' . $lang ); // The shortcode follows the visitor's language.
		}
	}
	if ( function_exists( 'premium_shop_legal_menu_sync' ) ) {
		premium_shop_legal_menu_sync();
	}
}
add_action( 'after_switch_theme', 'premium_shop_company_pages_install', 25 );

/**
 * Gaming edition 2.3.16: NeoPlay is run by Retroverse AG. Contact details
 * still holding the former defaults (NeoPlay, Rolle) follow the new ones,
 * and the legal pages are updated.
 */
function premium_shop_company_update() {
	if ( ! premium_shop_policies_active() ) {
		return;
	}
	$old = array(
		'company' => (string) get_theme_mod( 'ps_company_name', '' ),
		'address' => (string) get_theme_mod( 'ps_contact_address', '' ),
	);
	if ( in_array( strtolower( trim( $old['company'] ) ), array( 'neoplay', '' ), true ) ) {
		remove_theme_mod( 'ps_company_name' );
	}
	if ( false !== stripos( $old['address'], 'Beauregard' ) || false !== stripos( $old['address'], 'Rolle' ) ) {
		remove_theme_mod( 'ps_contact_address' );
	}
	$company = trim( (string) premium_shop_option( 'company_name' ) );
	$lines   = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) premium_shop_option( 'contact_address' ) ) ) ) );
	// Pages edited by hand: replace the former details.
	$map = array(
		'9 route du Beauregard' => isset( $lines[0] ) ? $lines[0] : '',
		'1180 Rolle, Suisse'    => isset( $lines[1] ) ? $lines[1] : '',
		'NeoPlay ('             => '' !== $company ? $company . ' (' : '',
	);
	if ( '' !== $company ) {
		$map['<strong>NeoPlay</strong>'] = '<strong>' . esc_html( $company ) . '</strong>';
	}
	if ( function_exists( 'premium_shop_legal_refresh' ) ) {
		premium_shop_legal_refresh( $map );
	}
	premium_shop_company_pages_install();
}
add_action( 'premium_shop_upgrade_2_3_16', 'premium_shop_company_update' );

/**
 * Theme update to 2.3.17 (gaming edition): kontakt@neoplay.ch replaces
 * info@neoplay.ch, in the Customizer and in every page that still shows the
 * former address (legal pages, Impressum, AGB, shipping, returns, contact,
 * including pages edited by hand). An address chosen by the shop is kept.
 */
function premium_shop_contact_email_update() {
	if ( ! function_exists( 'premium_shop_preset' ) || 'gaming' !== premium_shop_preset() ) {
		return;
	}
	$old = 'info@neoplay.ch';
	if ( strtolower( trim( (string) get_theme_mod( 'ps_contact_email', '' ) ) ) === $old ) {
		remove_theme_mod( 'ps_contact_email' );
	}
	$new = trim( (string) premium_shop_option( 'contact_email' ) );
	if ( '' === $new || strtolower( $new ) === $old ) {
		return;
	}
	if ( function_exists( 'premium_shop_legal_refresh' ) ) {
		premium_shop_legal_refresh( array( $old => $new ) );
	}

	global $wpdb;
	$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One-off update step.
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status NOT IN ('trash', 'auto-draft', 'inherit') AND post_content LIKE %s",
			'%' . $wpdb->esc_like( $old ) . '%'
		)
	);
	foreach ( $ids as $id ) {
		$content = (string) get_post_field( 'post_content', $id, 'raw' );
		$updated = str_ireplace( $old, $new, $content );
		if ( $updated !== $content ) {
			wp_update_post(
				wp_slash(
					array(
						'ID'           => (int) $id,
						'post_content' => $updated,
					)
				)
			);
		}
	}
}
add_action( 'premium_shop_upgrade_2_3_17', 'premium_shop_contact_email_update' );
