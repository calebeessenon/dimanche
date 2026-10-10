<?php
/**
 * Multilingual layer.
 *
 * - Works with WPML, Polylang and TranslatePress when one of them is active
 *   (the plugin drives the language, URLs and content translation).
 * - Without a plugin, a lightweight built-in mode switches the interface
 *   language (theme + WordPress + WooCommerce strings) with a cookie.
 *   German is the default language on the first visit.
 * - Adding a language = one entry in the registry (or the
 *   `premium_shop_languages` filter) + a .po/.mo file. No architecture change.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry of every language the theme knows about.
 *
 * @return array code => [ locale, label, name ]
 */
function premium_shop_language_registry() {
	$languages = array(
		'de' => array( 'locale' => 'de_DE', 'label' => 'DE', 'name' => 'Deutsch' ),
		'fr' => array( 'locale' => 'fr_FR', 'label' => 'FR', 'name' => 'Français' ),
		'es' => array( 'locale' => 'es_ES', 'label' => 'ES', 'name' => 'Español' ),
		'en' => array( 'locale' => 'en_US', 'label' => 'EN', 'name' => 'English' ),
		'it' => array( 'locale' => 'it_IT', 'label' => 'IT', 'name' => 'Italiano' ),
		'pt' => array( 'locale' => 'pt_PT', 'label' => 'PT', 'name' => 'Português' ),
		'nl' => array( 'locale' => 'nl_NL', 'label' => 'NL', 'name' => 'Nederlands' ),
		'pl' => array( 'locale' => 'pl_PL', 'label' => 'PL', 'name' => 'Polski' ),
	);

	/**
	 * Filter the language registry. Add an entry to support a new language.
	 *
	 * @param array $languages Languages keyed by 2-letter code.
	 */
	return apply_filters( 'premium_shop_language_registry', $languages );
}

/**
 * Languages enabled in the built-in switcher, in display order.
 *
 * @return array
 */
function premium_shop_languages() {
	static $enabled = null;

	if ( null !== $enabled ) {
		return $enabled;
	}

	$registry = premium_shop_language_registry();
	$codes    = get_theme_mod( 'ps_languages_enabled', 'de,fr,es,en' );
	$codes    = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $codes ) ) ) );
	$enabled  = array();

	foreach ( $codes as $code ) {
		if ( isset( $registry[ $code ] ) ) {
			$enabled[ $code ] = $registry[ $code ];
		}
	}

	if ( empty( $enabled ) ) {
		$enabled = array( 'de' => $registry['de'] );
	}

	$enabled = apply_filters( 'premium_shop_languages', $enabled );

	return $enabled;
}

/**
 * Which multilingual plugin (if any) is active.
 *
 * @return string wpml|polylang|translatepress|''
 */
function premium_shop_multilingual_plugin() {
	if ( defined( 'ICL_SITEPRESS_VERSION' ) && ! defined( 'POLYLANG_VERSION' ) ) {
		return 'wpml';
	}
	if ( function_exists( 'pll_current_language' ) ) {
		return 'polylang';
	}
	if ( class_exists( 'TRP_Translate_Press' ) ) {
		return 'translatepress';
	}

	return '';
}

/**
 * Language mode: plugin, builtin or off.
 *
 * @return string
 */
function premium_shop_language_mode() {
	$setting = get_theme_mod( 'ps_language_mode', 'auto' );

	if ( 'off' === $setting ) {
		return 'off';
	}

	if ( premium_shop_multilingual_plugin() ) {
		return 'plugin';
	}

	return 'builtin';
}

/**
 * Default language code (German unless changed).
 *
 * @return string
 */
function premium_shop_default_language() {
	$default   = sanitize_key( get_theme_mod( 'ps_language_default', 'de' ) );
	$languages = premium_shop_languages();

	return isset( $languages[ $default ] ) ? $default : (string) key( $languages );
}

/**
 * Current language as a 2-letter code.
 *
 * @return string
 */
function premium_shop_current_language() {
	switch ( premium_shop_multilingual_plugin() ) {
		case 'wpml':
			$lang = apply_filters( 'wpml_current_language', null );
			if ( $lang ) {
				return substr( $lang, 0, 2 );
			}
			break;
		case 'polylang':
			$lang = pll_current_language( 'slug' );
			if ( $lang ) {
				return substr( $lang, 0, 2 );
			}
			break;
	}

	if ( 'builtin' === premium_shop_language_mode() ) {
		return premium_shop_builtin_language();
	}

	return substr( determine_locale(), 0, 2 );
}

/**
 * Should the built-in switcher drive the locale for this request?
 *
 * @return bool
 */
function premium_shop_builtin_applies() {
	if ( 'builtin' !== premium_shop_language_mode() ) {
		return false;
	}

	// Never touch the admin UI language (admin-ajax from the front end is fine).
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}

	return true;
}

/**
 * Language chosen by the visitor in built-in mode.
 *
 * Priority: ?lang= parameter → cookie → default language (German).
 *
 * @return string
 */
function premium_shop_builtin_language() {
	static $lang = null;

	if ( null !== $lang ) {
		return $lang;
	}

	$languages = premium_shop_languages();
	$lang      = premium_shop_default_language();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display preference.
	if ( isset( $_GET['lang'] ) ) {
		$requested = sanitize_key( wp_unslash( $_GET['lang'] ) );
		if ( isset( $languages[ $requested ] ) ) {
			$lang = $requested;
		}
	} elseif ( isset( $_COOKIE['ps_lang'] ) ) {
		$cookie = sanitize_key( wp_unslash( $_COOKIE['ps_lang'] ) );
		if ( isset( $languages[ $cookie ] ) ) {
			$lang = $cookie;
		}
	}
	// phpcs:enable

	return $lang;
}

/**
 * Locale for a language code.
 *
 * @param string $code Language code.
 * @return string
 */
function premium_shop_locale_for( $code ) {
	$registry = premium_shop_language_registry();
	return isset( $registry[ $code ]['locale'] ) ? $registry[ $code ]['locale'] : 'en_US';
}

/**
 * Built-in mode bootstrap: switch the locale as early as a theme can.
 *
 * The WordPress core text domain is loaded before the theme, so it is
 * reloaded here; WooCommerce and the theme load their translations later
 * and pick up the filtered locale automatically.
 */
function premium_shop_builtin_bootstrap() {
	if ( ! premium_shop_builtin_applies() ) {
		return;
	}

	$lang = premium_shop_builtin_language();

	// Persist an explicit choice.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['lang'] ) && ! headers_sent() ) {
		$cookie = isset( $_COOKIE['ps_lang'] ) ? sanitize_key( wp_unslash( $_COOKIE['ps_lang'] ) ) : '';
		if ( $cookie !== $lang ) {
			setcookie( 'ps_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE['ps_lang'] = $lang;
		}
	}

	$locale   = premium_shop_locale_for( $lang );
	$original = get_locale();

	add_filter(
		'locale',
		static function () use ( $locale ) {
			return $locale;
		},
		99
	);

	if ( $original === $locale ) {
		return;
	}

	// Reload core strings in the visitor language.
	unload_textdomain( 'default' );
	load_default_textdomain( $locale );

	if ( class_exists( 'WP_Locale' ) ) {
		$GLOBALS['wp_locale'] = new WP_Locale(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
premium_shop_builtin_bootstrap();

/**
 * Tell page caches that the response depends on the language cookie.
 */
add_action(
	'send_headers',
	static function () {
		if ( premium_shop_builtin_applies() && count( premium_shop_languages() ) > 1 ) {
			header( 'Vary: Cookie', false );
		}
	}
);

/**
 * Items for the language switcher, whatever the active system.
 *
 * @return array[] Each: code, label, name, url, current, locale.
 */
function premium_shop_language_switcher_items() {
	$items = array();

	switch ( premium_shop_language_mode() ) {
		case 'off':
			return array();

		case 'plugin':
			$plugin = premium_shop_multilingual_plugin();

			if ( 'polylang' === $plugin && function_exists( 'pll_the_languages' ) ) {
				$raw = pll_the_languages(
					array(
						'raw'           => 1,
						'hide_if_empty' => 0,
					)
				);
				foreach ( (array) $raw as $lang ) {
					$items[] = array(
						'code'    => $lang['slug'],
						'label'   => strtoupper( $lang['slug'] ),
						'name'    => $lang['name'],
						'url'     => $lang['url'],
						'current' => ! empty( $lang['current_lang'] ),
						'locale'  => isset( $lang['locale'] ) ? $lang['locale'] : $lang['slug'],
					);
				}
			} elseif ( 'wpml' === $plugin ) {
				$raw = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
				foreach ( (array) $raw as $lang ) {
					$items[] = array(
						'code'    => $lang['language_code'],
						'label'   => strtoupper( substr( $lang['language_code'], 0, 2 ) ),
						'name'    => $lang['native_name'],
						'url'     => $lang['url'],
						'current' => ! empty( $lang['active'] ),
						'locale'  => isset( $lang['default_locale'] ) ? $lang['default_locale'] : $lang['language_code'],
					);
				}
			} elseif ( 'translatepress' === $plugin && function_exists( 'trp_custom_language_switcher' ) ) {
				$raw     = trp_custom_language_switcher();
				$current = get_locale();
				foreach ( (array) $raw as $lang ) {
					$items[] = array(
						'code'    => $lang['short_language_name'],
						'label'   => strtoupper( $lang['short_language_name'] ),
						'name'    => $lang['language_name'],
						'url'     => $lang['current_page_url'],
						'current' => ( $lang['language_code'] === $current ),
						'locale'  => $lang['language_code'],
					);
				}
			}
			break;

		default:
			$current = premium_shop_builtin_language();
			foreach ( premium_shop_languages() as $code => $lang ) {
				$items[] = array(
					'code'    => $code,
					'label'   => $lang['label'],
					'name'    => $lang['name'],
					'url'     => premium_shop_language_url( $code ),
					'current' => ( $code === $current ),
					'locale'  => $lang['locale'],
				);
			}
	}

	return apply_filters( 'premium_shop_language_switcher_items', $items );
}

/**
 * URL of the current page in another language (built-in mode).
 *
 * @param string $code Language code.
 * @return string
 */
function premium_shop_language_url( $code ) {
	$request = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$url     = home_url( $request );

	// Drop pagination/AJAX artefacts that make no sense in another language.
	$url = remove_query_arg( array( 'add-to-cart', 'removed_item', 'undo_item', '_wpnonce', 'wc-ajax' ), $url );

	return add_query_arg( 'lang', $code, $url );
}

/**
 * Carry the language in built-in mode through the WooCommerce AJAX endpoint
 * so fragments and live search answer in the right language.
 */
add_filter(
	'woocommerce_ajax_get_endpoint',
	static function ( $url ) {
		if ( premium_shop_builtin_applies() && premium_shop_builtin_language() !== premium_shop_default_language() && false === strpos( $url, 'lang=' ) ) {
			// Appended as is: add_query_arg() would re-encode WooCommerce's
			// "%%endpoint%%" placeholder ("%25%25endpoint%25%25") and every AJAX
			// call (quick view, search, add to cart) would then fail.
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'lang=' . rawurlencode( premium_shop_builtin_language() );
		}
		return $url;
	}
);

/**
 * Register customizable texts with Polylang string translation.
 */
add_action(
	'init',
	static function () {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		foreach ( premium_shop_translatable_option_keys() as $key ) {
			$value = (string) get_theme_mod( 'ps_' . $key, '' );
			if ( '' !== trim( $value ) ) {
				pll_register_string( 'ps_' . $key, $value, 'Premium Shop', false !== strpos( $value, "\n" ) );
			}
		}
	}
);

/**
 * Register customizable texts with WPML string translation when saved.
 */
add_action(
	'customize_save_after',
	static function () {
		if ( ! has_action( 'wpml_register_single_string' ) ) {
			return;
		}

		foreach ( premium_shop_translatable_option_keys() as $key ) {
			$value = (string) get_theme_mod( 'ps_' . $key, '' );
			if ( '' !== trim( $value ) ) {
				do_action( 'wpml_register_single_string', 'Premium Shop', 'ps_' . $key, $value );
			}
		}
	}
);

/**
 * Hreflang codes for <html lang> stay correct: WordPress uses get_locale(),
 * which is filtered above in built-in mode. For the switcher we expose the
 * BCP-47 code.
 *
 * @param string $locale WordPress locale.
 * @return string
 */
function premium_shop_bcp47( $locale ) {
	return str_replace( '_', '-', $locale );
}
