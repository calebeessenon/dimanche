<?php
/**
 * Built-in language mode: everything in the visitor's language.
 *
 * - Menu items (custom links and pages) and page titles written in the shop
 *   language are translated through the theme catalogue (e.g. "Startseite"
 *   → "Accueil", "Versand & Zahlung" → "Livraison & paiement").
 * - Labels can also hold their own versions: "[:de]Angebote[:fr]Promos".
 * - Pages created by the setup assistant get their title and text in every
 *   language (Translations box under the page editor).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Visitor language when it differs from the shop language ('' otherwise).
 *
 * @return string
 */
function premium_shop_i18n_target() {
	// E-mails and invoices switched to the customer's language.
	if ( ! empty( $GLOBALS['premium_shop_switched_language'] ) ) {
		$switched = $GLOBALS['premium_shop_switched_language'];
		return $switched === premium_shop_default_language() ? '' : $switched;
	}
	static $lang = null;
	if ( null !== $lang ) {
		return $lang;
	}
	$lang = '';
	if ( function_exists( 'premium_shop_builtin_applies' ) && premium_shop_builtin_applies() ) {
		$current = premium_shop_builtin_language();
		if ( $current && $current !== premium_shop_default_language() ) {
			$lang = $current;
		}
	}
	return $lang;
}

/**
 * Shop-language text → English source text, from the theme catalogue.
 *
 * @return array
 */
function premium_shop_i18n_reverse_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array();
	// Shop language first, then the other languages: a label typed in any
	// of them (e.g. "Livraison express" on a German shop) is recognised.
	$codes = array_unique( array_merge( array( premium_shop_default_language() ), array_keys( premium_shop_languages() ) ) );
	foreach ( $codes as $code ) {
		$locale = premium_shop_locale_for( $code );
		$file   = PREMIUM_SHOP_DIR . '/languages/' . $locale . '.mo';
		if ( 0 === strpos( $locale, 'en_' ) || ! is_readable( $file ) ) {
			continue;
		}
		$mo = new MO();
		if ( ! $mo->import_from_file( $file ) ) {
			continue;
		}
		foreach ( $mo->entries as $entry ) {
			if ( empty( $entry->translations[0] ) || $entry->is_plural || false !== strpos( $entry->singular, '%' ) ) {
				continue;
			}
			foreach ( array( $entry->translations[0], $entry->singular ) as $text ) {
				$key = premium_shop_i18n_key( $text );
				// Keep the first (shop-language) source for a given text.
				if ( '' !== $key && ! isset( $map[ $key ] ) ) {
					$map[ $key ] = $entry->singular;
				}
			}
		}
	}
	return $map;
}

/**
 * Normalised lookup key.
 *
 * @param string $text Text.
 * @return string
 */
function premium_shop_i18n_key( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	$text = str_replace( array( "\xC2\xA0", '–', '—' ), array( ' ', '-', '-' ), $text );
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Translate a short label written in the shop language.
 *
 * @param string $text Label (may contain HTML entities).
 * @return string Translated label, or the original when unknown.
 */
function premium_shop_i18n_label( $text ) {
	if ( '' === trim( (string) $text ) ) {
		return $text;
	}
	// Shop language too: a label typed in another language is brought back to it.
	$lang = premium_shop_i18n_target();
	$lang = '' !== $lang ? $lang : premium_shop_default_language();
	if ( false !== strpos( $text, '[:' ) && function_exists( 'premium_shop_pick_language_block' ) ) {
		return premium_shop_pick_language_block( $text );
	}
	$map = premium_shop_i18n_reverse_map();
	$key = premium_shop_i18n_key( $text );
	if ( ! isset( $map[ $key ] ) ) {
		return $text;
	}
	$source = $map[ $key ];
	return 'en' === $lang ? $source : premium_shop_translate_in( $source, premium_shop_locale_for( $lang ) );
}

/**
 * Translated title of a page: its own translation, else the catalogue.
 *
 * @param int    $page_id Page ID.
 * @param string $title   Current title.
 * @return string
 */
function premium_shop_i18n_page_title( $page_id, $title ) {
	$lang = premium_shop_i18n_target();
	if ( '' !== $lang ) {
		$own = trim( (string) get_post_meta( $page_id, '_ps_name_' . $lang, true ) );
		if ( '' !== $own ) {
			return $own;
		}
	}
	// Shop, cart, checkout, account… created by WooCommerce / WordPress in
	// the language WordPress was installed in ("Validation de la commande"
	// on a German shop): shown in the visitor's language.
	$system = premium_shop_system_page_title( (int) $page_id, $title, '' !== $lang ? $lang : premium_shop_default_language() );
	if ( '' !== $system ) {
		return $system;
	}
	return premium_shop_i18n_label( $title );
}

/**
 * Default titles of the pages created by WooCommerce and WordPress, in every
 * language WordPress or WooCommerce may have used when creating them.
 *
 * @return array role => array( 'de' => …, 'fr' => …, 'en' => …, 'es' => …, 'other' => string[] )
 */
function premium_shop_system_page_titles() {
	static $titles = null;
	if ( null !== $titles ) {
		return $titles;
	}
	$titles = array(
		'shop'      => array( 'de' => 'Shop', 'fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda', 'other' => array( 'Produkte', 'Magasin', 'Products', 'Productos' ) ),
		'cart'      => array( 'de' => 'Warenkorb', 'fr' => 'Panier', 'en' => 'Cart', 'es' => 'Carrito', 'other' => array( 'Basket', 'Cesta' ) ),
		'checkout'  => array( 'de' => 'Kasse', 'fr' => 'Commande', 'en' => 'Checkout', 'es' => 'Finalizar compra', 'other' => array( 'Validation de la commande', 'Validation de commande', 'Paiement', 'Commander', 'Caisse', 'Bestellung', 'Zur Kasse', 'Pago', 'Finalizar pedido' ) ),
		'myaccount' => array( 'de' => 'Mein Konto', 'fr' => 'Mon compte', 'en' => 'My account', 'es' => 'Mi cuenta', 'other' => array( 'My Account', 'Kundenkonto' ) ),
		'refund'    => array( 'de' => 'Rückerstattung und Rückgaben', 'fr' => 'Politique de remboursement et de retour', 'en' => 'Refund and Returns Policy', 'es' => 'Política de reembolso y devoluciones', 'other' => array( 'Rückerstattungs- und Rückgaberichtlinie', 'Richtlinie für Erstattungen und Rückgaben', 'Politique de remboursements et de retours', 'Politique en matière de remboursements et de retours', 'Política de devoluciones y reembolsos' ) ),
		'privacy'   => array( 'de' => 'Datenschutzerklärung', 'fr' => 'Politique de confidentialité', 'en' => 'Privacy policy', 'es' => 'Política de privacidad', 'other' => array( 'Privacy Policy', 'Datenschutz' ) ),
		'terms'     => array( 'de' => 'AGB', 'fr' => 'Conditions générales de vente', 'en' => 'Terms and conditions', 'es' => 'Términos y condiciones', 'other' => array( 'Allgemeine Geschäftsbedingungen', 'CGV', 'Terms & conditions' ) ),
	);
	// Titles WooCommerce / WordPress actually used, read from the language
	// packs installed on the site (they change between versions, and the site
	// may have been installed in yet another language, e.g. Italian).
	foreach ( premium_shop_installed_default_texts() as $role => $versions ) {
		if ( isset( $titles[ $role ] ) ) {
			$titles[ $role ]['other'] = array_values( array_unique( array_merge( $titles[ $role ]['other'], array_values( $versions ) ) ) );
		}
	}
	return $titles;
}

/**
 * Texts WordPress and WooCommerce write into the database when they are
 * installed (page titles, example content, default categories), in every
 * language whose language pack is installed: role => array( code => text ).
 * Cached; refreshed after language packs are updated.
 *
 * @return array
 */
function premium_shop_installed_default_texts() {
	static $texts = null;
	if ( null !== $texts ) {
		return $texts;
	}
	$texts = get_transient( 'premium_shop_default_texts' );
	if ( is_array( $texts ) ) {
		return $texts;
	}
	// role => array( language file, context, English text ).
	$sources = array(
		'shop'        => array( 'woocommerce', 'Page title', 'Shop' ),
		'cart'        => array( 'woocommerce', 'Page title', 'Cart' ),
		'checkout'    => array( 'woocommerce', 'Page title', 'Checkout' ),
		'myaccount'   => array( 'woocommerce', 'Page title', 'My account' ),
		'refund'      => array( 'woocommerce', 'Page title', 'Refund and Returns Policy' ),
		'product_cat' => array( 'woocommerce', 'Default category slug', 'Uncategorized' ),
		'privacy'     => array( 'core', null, 'Privacy Policy' ),
		'category'    => array( 'core', null, 'Uncategorized' ),
		'sample'      => array( 'admin', null, 'Sample Page' ),
		'hello'       => array( 'admin', null, 'Hello world!' ),
	);
	$texts = array();
	foreach ( $sources as $role => $source ) {
		$texts[ $role ] = array( 'en' => $source[2] );
	}
	foreach ( premium_shop_language_registry() as $code => $language ) {
		$locale = $language['locale'];
		if ( 0 === strpos( $locale, 'en_' ) ) {
			continue;
		}
		$files = array(
			'woocommerce' => WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo',
			'core'        => WP_LANG_DIR . '/' . $locale . '.mo',
			'admin'       => WP_LANG_DIR . '/admin-' . $locale . '.mo',
		);
		$catalogs = array();
		foreach ( $sources as $role => $source ) {
			if ( ! isset( $catalogs[ $source[0] ] ) ) {
				$catalogs[ $source[0] ] = false;
				if ( is_readable( $files[ $source[0] ] ) ) {
					$mo = new MO();
					if ( $mo->import_from_file( $files[ $source[0] ] ) ) {
						$catalogs[ $source[0] ] = $mo;
					}
				}
			}
			if ( $catalogs[ $source[0] ] ) {
				$translation = (string) $catalogs[ $source[0] ]->translate( $source[2], $source[1] );
				if ( '' !== $translation && $translation !== $source[2] ) {
					$texts[ $role ][ $code ] = $translation;
				}
			}
		}
	}
	set_transient( 'premium_shop_default_texts', $texts, WEEK_IN_SECONDS );
	return $texts;
}

/**
 * Language packs updated: read the default texts again.
 */
function premium_shop_forget_default_texts() {
	delete_transient( 'premium_shop_default_texts' );
}
add_action( 'upgrader_process_complete', 'premium_shop_forget_default_texts' );

/**
 * Text in a given language, for a text WordPress / WooCommerce wrote when it
 * was installed: the shop's version ($names), else the installed language
 * pack's, else English.
 *
 * @param string $role  Role in premium_shop_installed_default_texts().
 * @param array  $names Preferred versions (code => text).
 * @param string $text  Stored text.
 * @param string $lang  Wanted language.
 * @return string '' when $text is not one of the default texts.
 */
function premium_shop_default_text_in( $role, $names, $text, $lang ) {
	$all      = premium_shop_installed_default_texts();
	$versions = isset( $all[ $role ] ) ? array_merge( $all[ $role ], $names ) : $names;
	$key      = premium_shop_i18n_key( $text );
	foreach ( array_merge( array_values( $names ), isset( $all[ $role ] ) ? array_values( $all[ $role ] ) : array() ) as $candidate ) {
		if ( premium_shop_i18n_key( $candidate ) === $key ) {
			return isset( $versions[ $lang ] ) ? $versions[ $lang ] : ( isset( $versions['en'] ) ? $versions['en'] : $text );
		}
	}
	return '';
}

/**
 * Role of a page created by WooCommerce / WordPress ('' for other pages).
 *
 * @param int $page_id Page ID.
 * @return string
 */
function premium_shop_system_page_role( $page_id ) {
	static $roles = null;
	if ( null === $roles ) {
		$roles = array();
		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( array( 'shop', 'cart', 'checkout', 'myaccount', 'terms' ) as $role ) {
				$id = (int) wc_get_page_id( $role );
				if ( $id > 0 ) {
					$roles[ $id ] = $role;
				}
			}
			$refund = (int) get_option( 'woocommerce_refund_returns_page_id' );
			if ( $refund && ! isset( $roles[ $refund ] ) && 'rueckgabe-und-erstattung' !== get_post_field( 'post_name', $refund ) ) {
				$roles[ $refund ] = 'refund';
			}
		}
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy && ! isset( $roles[ $privacy ] ) ) {
			$roles[ $privacy ] = 'privacy';
		}
	}
	return isset( $roles[ $page_id ] ) ? $roles[ $page_id ] : '';
}

/**
 * Title of a WooCommerce / WordPress page in a language, when its stored
 * title is still one of the default titles (a title you chose is kept).
 *
 * @param int    $page_id Page ID.
 * @param string $title   Stored or current title.
 * @param string $lang    Language code.
 * @return string '' when the page is not concerned.
 */
function premium_shop_system_page_title( $page_id, $title, $lang, $role = '' ) {
	$role = '' !== $role ? $role : premium_shop_system_page_role( $page_id );
	if ( '' === $role ) {
		return '';
	}
	$set   = premium_shop_system_page_titles()[ $role ];
	$known = array_merge( array( $set['de'], $set['fr'], $set['en'], $set['es'] ), $set['other'] );
	$key   = premium_shop_i18n_key( wp_strip_all_tags( html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ) ) );
	foreach ( $known as $candidate ) {
		if ( premium_shop_i18n_key( $candidate ) === $key ) {
			return isset( $set[ $lang ] ) ? $set[ $lang ] : $set['en'];
		}
	}
	return '';
}

/**
 * Theme update: store the WooCommerce / WordPress page titles in the shop
 * language and their translations (also seen in the admin, menus, e-mails).
 */
function premium_shop_fix_system_page_titles() {
	delete_transient( 'premium_shop_default_texts' );
	$default = premium_shop_default_language();
	$ids     = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount', 'terms' ) as $role ) {
			$ids[ (int) wc_get_page_id( $role ) ] = '';
		}
		$ids[ (int) get_option( 'woocommerce_refund_returns_page_id' ) ] = '';
	}
	$ids[ (int) get_option( 'wp_page_for_privacy_policy' ) ] = '';
	// WooCommerce's refund policy draft left over when the theme's returns
	// page took its place ("Politique en matière de remboursements et de
	// retours" in the page list of a German shop).
	$refund = premium_shop_system_page_titles()['refund'];
	$titles = array_map( 'esc_html', array_unique( array_merge( array( $refund['de'], $refund['fr'], $refund['en'], $refund['es'] ), $refund['other'] ) ) );
	global $wpdb;
	$found = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status IN ( 'publish', 'draft', 'pending', 'private' ) AND post_title IN ( " . implode( ', ', array_fill( 0, count( $titles ), '%s' ) ) . ' ) LIMIT 20', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$titles
		)
	);
	foreach ( array_map( 'intval', $found ) as $id ) {
		if ( ! isset( $ids[ $id ] ) && 'rueckgabe-und-erstattung' !== get_post_field( 'post_name', $id ) ) {
			$ids[ $id ] = 'refund';
		}
	}
	unset( $ids[0] );
	foreach ( $ids as $id => $role ) {
		$post = get_post( $id );
		if ( ! $post || 'page' !== $post->post_type ) {
			continue;
		}
		$title = premium_shop_system_page_title( $id, $post->post_title, $default, $role );
		if ( '' === $title ) {
			continue;
		}
		if ( $title !== $post->post_title ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => $title,
				)
			);
		}
		foreach ( array( 'de', 'fr', 'en', 'es' ) as $lang ) {
			if ( $lang === $default ) {
				continue;
			}
			$current = trim( (string) get_post_meta( $id, '_ps_name_' . $lang, true ) );
			if ( '' === $current || '' !== premium_shop_system_page_title( $id, $current, $lang, $role ) ) {
				update_post_meta( $id, '_ps_name_' . $lang, premium_shop_system_page_title( $id, $title, $lang, $role ) );
			}
		}
	}
}
add_action( 'premium_shop_upgrade_2_3_12', 'premium_shop_fix_system_page_titles' );

/**
 * Default names of the categories WordPress and WooCommerce create.
 *
 * @param string $taxonomy category|product_cat.
 * @return array code => name.
 */
function premium_shop_default_category_names( $taxonomy ) {
	if ( 'category' === $taxonomy ) {
		return array( 'de' => 'Allgemein', 'fr' => 'Non classé', 'en' => 'Uncategorized', 'es' => 'Sin categoría' );
	}
	return array( 'de' => 'Unkategorisiert', 'fr' => 'Non classé', 'en' => 'Uncategorized', 'es' => 'Sin categorizar' );
}

/**
 * A text WordPress / WooCommerce saved in the language the site was
 * installed in, in another language: theme catalogue, then WooCommerce's own
 * translations ('' when the text is not known, e.g. typed by you).
 *
 * @param string $text Text.
 * @param string $lang Language code.
 * @return string
 */
function premium_shop_known_text_in( $text, $lang ) {
	if ( '' === trim( (string) $text ) || false !== strpos( $text, '[:' ) ) {
		return '';
	}
	$map = premium_shop_i18n_reverse_map();
	$key = premium_shop_i18n_key( $text );
	if ( isset( $map[ $key ] ) ) {
		return 'en' === $lang ? $map[ $key ] : premium_shop_translate_in( $map[ $key ], premium_shop_locale_for( $lang ) );
	}
	return function_exists( 'premium_shop_wc_catalogue_translate' ) ? premium_shop_wc_catalogue_translate( $text, $lang ) : '';
}

/**
 * WooCommerce texts saved in its settings when WooCommerce was installed or a
 * method was added (payment method titles and descriptions, "Livraison
 * gratuite", the privacy texts of the checkout and the registration form…):
 * rewritten in the shop language. Texts you typed yourself are kept.
 */
function premium_shop_fix_wc_settings_language() {
	global $wpdb;
	// Lookups cached by an earlier version (texts it could not translate).
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_ps\\_wct\\_%' OR option_name LIKE '\\_transient\\_timeout\\_ps\\_wct\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	wp_cache_delete( 'alloptions', 'options' );
	$default = premium_shop_default_language();
	$texts   = array( 'woocommerce_checkout_privacy_policy_text', 'woocommerce_registration_privacy_policy_text', 'woocommerce_checkout_terms_and_conditions_checkbox_text', 'woocommerce_demo_store_notice' );
	// Raw values: the front end reads them translated into the visitor's language.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ( '" . implode( "', '", $texts ) . "' ) OR ( option_name LIKE 'woocommerce\\_%\\_settings' )" );
	foreach ( $rows as $row ) {
		$value   = maybe_unserialize( $row->option_value );
		$changed = false;
		if ( is_string( $value ) && in_array( $row->option_name, $texts, true ) ) {
			$new = premium_shop_known_text_in( $value, $default );
			if ( '' !== $new && $new !== $value ) {
				$value   = $new;
				$changed = true;
			}
		} elseif ( is_array( $value ) ) {
			foreach ( array( 'title', 'description', 'instructions' ) as $field ) {
				if ( empty( $value[ $field ] ) || ! is_string( $value[ $field ] ) ) {
					continue;
				}
				$new = premium_shop_known_text_in( $value[ $field ], $default );
				if ( '' !== $new && $new !== $value[ $field ] ) {
					$value[ $field ] = $new;
					$changed         = true;
				}
			}
		}
		if ( $changed ) {
			// Written directly: update_option() compares with the translated
			// value and could skip the change.
			$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $value ) ), array( 'option_name' => $row->option_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( $row->option_name, 'options' );
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'notoptions', 'options' );
		}
	}
}
add_action( 'premium_shop_upgrade_2_3_13', 'premium_shop_fix_wc_settings_language' );

/**
 * Example content and default categories WordPress / WooCommerce created in
 * the language the site was installed in:
 *
 * - "Non classé" categories (posts and products) renamed in the shop language
 *   (the address stays the same);
 * - WordPress' untouched example page and first post ("Page d'exemple",
 *   "Bonjour tout le monde !", a mechanic who wants to be an actor…) taken
 *   offline (draft, title in the shop language): they are not part of a shop.
 *   Edited or later republished, they are left alone.
 */
function premium_shop_fix_install_language_content() {
	global $wpdb;
	delete_transient( 'premium_shop_default_texts' );
	$default = premium_shop_default_language();
	$all     = premium_shop_installed_default_texts();

	foreach ( array( 'category' => 'default_category', 'product_cat' => 'default_product_cat' ) as $taxonomy => $option ) {
		$term_id = (int) get_option( $option );
		// Stored name (get_term() gives it in the visitor's language).
		$raw = (string) $wpdb->get_var( $wpdb->prepare( "SELECT t.name FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE t.term_id = %d AND tt.taxonomy = %s", $term_id, $taxonomy ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( '' === $raw ) {
			continue;
		}
		$name = premium_shop_default_text_in( $taxonomy, premium_shop_default_category_names( $taxonomy ), html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ), $default );
		if ( '' !== $name && premium_shop_i18n_key( $name ) !== premium_shop_i18n_key( $raw ) ) {
			wp_update_term( $term_id, $taxonomy, array( 'name' => $name ) );
		}
	}

	$examples = array(
		'sample' => array(
			'type'  => 'page',
			'names' => array( 'de' => 'Beispielseite', 'fr' => 'Page d’exemple', 'en' => 'Sample Page', 'es' => 'Página de ejemplo' ),
		),
		'hello'  => array(
			'type'  => 'post',
			'names' => array( 'de' => 'Hallo Welt!', 'fr' => 'Bonjour tout le monde !', 'en' => 'Hello world!', 'es' => '¡Hola, mundo!' ),
		),
	);
	foreach ( $examples as $role => $example ) {
		$titles = array_unique( array_merge( array_values( $example['names'] ), isset( $all[ $role ] ) ? array_values( $all[ $role ] ) : array() ) );
		$posts  = get_posts(
			array(
				'post_type'        => $example['type'],
				'post_status'      => 'publish',
				'numberposts'      => 5,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
		foreach ( $posts as $post ) {
			// Only WordPress' own example: one of its default titles, never edited.
			if ( ! in_array( html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ), $titles, true ) || $post->post_modified_gmt !== $post->post_date_gmt ) {
				continue;
			}
			$title = premium_shop_default_text_in( $role, $example['names'], $post->post_title, $default );
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'draft',
					'post_title'  => '' !== $title ? $title : $post->post_title,
					'post_name'   => $post->post_name,
				)
			);
		}
	}
}
add_action( 'premium_shop_upgrade_2_3_13', 'premium_shop_fix_install_language_content' );

/**
 * Page titles (front end).
 *
 * @param string $title   Title.
 * @param int    $post_id Post ID.
 * @return string
 */
function premium_shop_i18n_the_title( $title, $post_id = 0 ) {
	if ( ! $post_id || is_admin() || 'page' !== get_post_type( $post_id ) ) {
		return $title;
	}
	$translated = premium_shop_i18n_page_title( (int) $post_id, $title );
	return $translated === $title ? $title : esc_html( $translated );
}
add_filter( 'the_title', 'premium_shop_i18n_the_title', 12, 2 );

/**
 * Document title of pages.
 *
 * @param string  $title Title.
 * @param WP_Post $post  Post.
 * @return string
 */
function premium_shop_i18n_single_title( $title, $post = null ) {
	if ( $post instanceof WP_Post && 'page' === $post->post_type && ! is_admin() ) {
		return premium_shop_i18n_page_title( $post->ID, $title );
	}
	return $title;
}
add_filter( 'single_post_title', 'premium_shop_i18n_single_title', 12, 2 );

/**
 * Menu items: page items take the page translation, other labels go
 * through the catalogue (or their own "[:de]..[:fr].." versions).
 *
 * @param array $items Menu items.
 * @return array
 */
function premium_shop_i18n_menu_items( $items ) {
	if ( '' === premium_shop_i18n_target() || is_admin() ) {
		return $items;
	}
	foreach ( $items as $item ) {
		$title = (string) $item->title;
		if ( 'post_type' === $item->type && 'page' === $item->object ) {
			$raw = (string) get_post_field( 'post_title', (int) $item->object_id );
			if ( premium_shop_i18n_key( $title ) === premium_shop_i18n_key( $raw ) || premium_shop_i18n_key( $title ) === premium_shop_i18n_key( get_the_title( (int) $item->object_id ) ) ) {
				$item->title = esc_html( premium_shop_i18n_page_title( (int) $item->object_id, $raw ) );
				continue;
			}
		}
		$translated = premium_shop_i18n_label( $title );
		if ( $translated !== $title ) {
			$item->title = esc_html( $translated );
		}
		if ( ! empty( $item->attr_title ) ) {
			$item->attr_title = premium_shop_i18n_label( $item->attr_title );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'premium_shop_i18n_menu_items', 20 );

/**
 * Breadcrumbs: WooCommerce prints the stored title of the account page on
 * its sub-pages ("Mein Konto / Mot de passe perdu" in French).
 *
 * @param array $crumbs Crumbs (text, URL).
 * @return array
 */
function premium_shop_i18n_breadcrumb( $crumbs ) {
	if ( is_admin() || ! is_array( $crumbs ) ) {
		return $crumbs;
	}
	foreach ( $crumbs as $i => $crumb ) {
		if ( empty( $crumb[1] ) || ! is_string( $crumb[0] ) ) {
			continue;
		}
		$id = url_to_postid( $crumb[1] );
		if ( $id && 'page' === get_post_type( $id ) && premium_shop_i18n_key( $crumb[0] ) === premium_shop_i18n_key( get_post_field( 'post_title', $id ) ) ) {
			$crumbs[ $i ][0] = premium_shop_i18n_page_title( $id, $crumb[0] );
		}
	}
	return $crumbs;
}
add_filter( 'woocommerce_get_breadcrumb', 'premium_shop_i18n_breadcrumb', 20 );

/**
 * Product category names without their own translation (e.g. "Zubehör")
 * go through the catalogue too.
 *
 * @param WP_Term|mixed $term Term.
 * @return WP_Term|mixed
 */
function premium_shop_i18n_term( $term ) {
	if ( ! $term instanceof WP_Term || is_admin() || '' === premium_shop_i18n_target() || ! in_array( $term->taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
		return $term;
	}
	if ( '' !== trim( (string) get_term_meta( $term->term_id, '_ps_name_' . premium_shop_i18n_target(), true ) ) ) {
		return $term; // Handled by the product translations.
	}
	$term->name = premium_shop_i18n_label( $term->name );
	return $term;
}
add_filter( 'get_term', 'premium_shop_i18n_term', 20 );

/**
 * Give the pages created by the setup assistant their title and text in
 * every enabled language, when they still hold the generated text (pages
 * you edited keep only what you wrote; translate them in their Translations
 * box).
 */
function premium_shop_seed_page_translations() {
	if ( ! function_exists( 'premium_shop_setup_pages_data' ) ) {
		return;
	}
	$default = premium_shop_default_language();
	$tr      = static function ( $code ) {
		$locale = premium_shop_locale_for( $code );
		return static function ( $text ) use ( $locale ) {
			return premium_shop_translate_in( $text, $locale );
		};
	};
	$base = array_filter( premium_shop_setup_pages_data( $tr( $default ) ) );

	foreach ( array_keys( premium_shop_languages() ) as $code ) {
		if ( $code === $default ) {
			continue;
		}
		$data = array_filter( premium_shop_setup_pages_data( $tr( $code ) ) );
		foreach ( $base as $slug => $page ) {
			$post = get_page_by_path( $slug, OBJECT, 'page' );
			if ( ! $post || empty( $data[ $slug ] ) ) {
				continue;
			}
			if ( '' === (string) get_post_meta( $post->ID, '_ps_name_' . $code, true ) && premium_shop_i18n_key( $post->post_title ) === premium_shop_i18n_key( $page['title'] ) ) {
				update_post_meta( $post->ID, '_ps_name_' . $code, $data[ $slug ]['title'] );
			}
			if ( '' !== trim( $page['content'] ) && '' === (string) get_post_meta( $post->ID, '_ps_desc_' . $code, true ) && trim( $post->post_content ) === trim( $page['content'] ) ) {
				update_post_meta( $post->ID, '_ps_desc_' . $code, wp_slash( $data[ $slug ]['content'] ) );
			}
		}
	}
}
add_action( 'premium_shop_upgrade_1_7', 'premium_shop_seed_page_translations' );

/**
 * Widget titles written in the shop language.
 *
 * @param string $title Title.
 * @return string
 */
function premium_shop_i18n_widget_title( $title ) {
	return '' === premium_shop_i18n_target() ? $title : esc_html( premium_shop_i18n_label( $title ) );
}
add_filter( 'widget_title', 'premium_shop_i18n_widget_title', 20 );

/**
 * WooCommerce texts typed in its settings (price suffix such as
 * "inkl. MwSt.", store notice): catalogue or "[:de]..[:fr].." versions.
 *
 * @param string $html HTML.
 * @return string
 */
function premium_shop_i18n_setting_html( $html ) {
	if ( '' === premium_shop_i18n_target() ) {
		return $html;
	}
	return preg_replace_callback(
		'/>([^<>]+)</u',
		static function ( $m ) {
			$text = trim( $m[1] );
			if ( '' === $text ) {
				return $m[0];
			}
			$translated = premium_shop_i18n_label( $text );
			return $translated === $text ? $m[0] : '>' . str_replace( $text, esc_html( $translated ), $m[1] ) . '<';
		},
		$html
	);
}
add_filter( 'woocommerce_get_price_suffix', 'premium_shop_i18n_setting_html', 20 );
add_filter( 'woocommerce_demo_store', 'premium_shop_i18n_setting_html', 20 );

/**
 * Common texts typed in WooCommerce settings, kept in the catalogue so that
 * they are recognised in the shop language and translated.
 *
 * @return string[]
 */
function premium_shop_i18n_setting_strings() {
	return array(
		__( 'incl. VAT', 'premium-shop' ),
		__( 'excl. VAT', 'premium-shop' ),
		__( 'plus shipping', 'premium-shop' ),
		__( 'incl. VAT, plus shipping', 'premium-shop' ),
	);
}

/**
 * Site slogan in the visitor's language: "[:de]…[:fr]…" versions, the
 * catalogue, else the (translated) small line of the homepage hero.
 *
 * @param string $value Slogan.
 * @return string
 */
function premium_shop_i18n_slogan( $value ) {
	if ( is_admin() || is_customize_preview() || '' === trim( (string) $value ) ) {
		return $value;
	}
	if ( false !== strpos( $value, '[:' ) && function_exists( 'premium_shop_pick_language_block' ) ) {
		return premium_shop_pick_language_block( $value );
	}
	if ( '' === premium_shop_i18n_target() ) {
		return $value;
	}
	$translated = premium_shop_i18n_label( $value );
	if ( $translated !== $value ) {
		return $translated;
	}
	$eyebrow = premium_shop_text( 'hero_eyebrow' );
	return $eyebrow ? $eyebrow : $value;
}
add_filter( 'option_blogdescription', 'premium_shop_i18n_slogan', 20 );

/**
 * Date format in the visitor's (or e-mail's) language when the site keeps
 * one of WordPress' standard formats: "5 novembre 2026", "5 de noviembre de
 * 2026", "November 5, 2026", "5. November 2026" instead of the format of the
 * shop language ("novembre 5, 2026"), shop language included.
 *
 * @param string $format Date format.
 * @return string
 */
function premium_shop_i18n_date_format( $format ) {
	$formats = array(
		'de' => 'j. F Y',
		'fr' => 'j F Y',
		'es' => 'j \d\e F \d\e Y',
		'en' => 'F j, Y',
	);
	if ( ! in_array( $format, $formats, true ) || ( is_admin() && ! wp_doing_ajax() && empty( $GLOBALS['premium_shop_switched_language'] ) ) ) {
		return $format;
	}
	$lang = premium_shop_i18n_target();
	if ( '' === $lang && ( ! empty( $GLOBALS['premium_shop_switched_language'] ) || ( function_exists( 'premium_shop_builtin_applies' ) && premium_shop_builtin_applies() ) ) ) {
		// Shop language too: a site installed in English and then switched to
		// German (setup assistant) keeps "F j, Y" ("November 5, 2026").
		$lang = premium_shop_default_language();
	}
	return '' !== $lang && isset( $formats[ $lang ] ) ? $formats[ $lang ] : $format;
}
add_filter( 'option_date_format', 'premium_shop_i18n_date_format', 20 );

/**
 * Default categories ("Non classé" on a site installed in French: products
 * and posts) in the visitor's language.
 *
 * @param WP_Term $term     Term.
 * @param string  $taxonomy Taxonomy.
 * @return WP_Term
 */
function premium_shop_i18n_default_category( $term, $taxonomy ) {
	$options = array(
		'product_cat' => 'default_product_cat',
		'category'    => 'default_category',
	);
	if ( ! isset( $options[ $taxonomy ] ) || ! $term instanceof WP_Term || is_admin() || (int) get_option( $options[ $taxonomy ] ) !== (int) $term->term_id ) {
		return $term;
	}
	$lang = premium_shop_i18n_target();
	$lang = '' !== $lang ? $lang : premium_shop_default_language();
	$name = premium_shop_default_text_in( $taxonomy, premium_shop_default_category_names( $taxonomy ), html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ), $lang );
	if ( '' !== $name ) {
		$term       = clone $term;
		$term->name = $name;
	}
	return $term;
}
add_filter( 'get_term', 'premium_shop_i18n_default_category', 20, 2 );
