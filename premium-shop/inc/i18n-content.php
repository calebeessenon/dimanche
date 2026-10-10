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
	$map    = array();
	$locale = premium_shop_locale_for( premium_shop_default_language() );
	if ( 0 === strpos( $locale, 'en_' ) ) {
		return $map;
	}
	$file = PREMIUM_SHOP_DIR . '/languages/' . $locale . '.mo';
	if ( ! is_readable( $file ) ) {
		return $map;
	}
	$mo = new MO();
	if ( ! $mo->import_from_file( $file ) ) {
		return $map;
	}
	foreach ( $mo->entries as $entry ) {
		if ( empty( $entry->translations[0] ) || $entry->is_plural || false !== strpos( $entry->singular, '%' ) ) {
			continue;
		}
		$key = premium_shop_i18n_key( $entry->translations[0] );
		// Keep the first (shortest-context) source for a given translation.
		if ( '' !== $key && ! isset( $map[ $key ] ) ) {
			$map[ $key ] = $entry->singular;
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
	$lang = premium_shop_i18n_target();
	if ( '' === $lang || '' === trim( (string) $text ) ) {
		return $text;
	}
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
	if ( '' === $lang ) {
		return $title;
	}
	$own = trim( (string) get_post_meta( $page_id, '_ps_name_' . $lang, true ) );
	return '' !== $own ? $own : premium_shop_i18n_label( $title );
}

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
