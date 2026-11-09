<?php
/**
 * Legal pages: privacy policy and terms of use, in the four shop languages,
 * filled with the shop's contact details (Customizer → Premium Shop → Contact).
 *
 * The main text (shop language) is the page content; the other languages are
 * stored as page translations (shown by the language switcher).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Legal documents.
 *
 * @return array
 */
function premium_shop_legal_docs() {
	return array(
		'privacy' => array(
			'slug'   => 'datenschutzerklaerung',
			'titles' => array(
				'de' => 'Datenschutzerklärung',
				'fr' => 'Politique de confidentialité',
				'en' => 'Privacy policy',
				'es' => 'Política de privacidad',
			),
		),
		'terms'   => array(
			'slug'   => 'nutzungsbedingungen',
			'titles' => array(
				'de' => 'Nutzungsbedingungen',
				'fr' => 'Conditions générales d’utilisation',
				'en' => 'Terms of use',
				'es' => 'Condiciones de uso',
			),
		),
	);
}

/**
 * Text of a legal document with the shop's details.
 *
 * @param string $doc  privacy|terms.
 * @param string $lang de|fr|en|es.
 * @return string Block markup ('' when the file is missing).
 */
function premium_shop_legal_content( $doc, $lang ) {
	$file = PREMIUM_SHOP_DIR . '/inc/legal/' . sanitize_key( $doc ) . '-' . sanitize_key( $lang ) . '.html';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	$html = (string) file_get_contents( $file );

	$company = trim( (string) premium_shop_option( 'company_name' ) );
	$company = '' !== $company ? $company : get_bloginfo( 'name' );
	$email   = trim( (string) premium_shop_option( 'contact_email' ) );
	$email   = is_email( $email ) ? $email : get_option( 'admin_email' );
	$phone   = trim( (string) premium_shop_option( 'contact_phone' ) );
	$lines   = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) premium_shop_option( 'contact_address' ) ) ) ) );
	$uid       = trim( (string) premium_shop_option( 'company_uid' ) );
	$uid_label = 'fr' === $lang ? 'IDE' : 'UID';
	$city      = '';
	if ( $lines ) {
		$last = preg_replace( '/^\s*[A-Z]{0,2}-?\d{4,5}\s+/', '', end( $lines ) );
		$city = trim( strtok( (string) $last, ',' ) );
	}
	$labels = array(
		'de' => array( 'Telefon', array( 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ) ),
		'fr' => array( 'Téléphone', array( 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' ) ),
		'en' => array( 'Phone', array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ) ),
		'es' => array( 'Teléfono', array( 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre' ) ),
	);
	$label = isset( $labels[ $lang ] ) ? $labels[ $lang ] : $labels['en'];
	// Example of small differences between photo and product, per shop type.
	$examples = function_exists( 'premium_shop_is_gaming' ) && premium_shop_is_gaming()
		? array(
			'de' => 'z. B. bei Farbe, Verpackung oder Zubehör je nach Lieferserie des Herstellers',
			'fr' => 'par exemple de couleur, d’emballage ou d’accessoires selon la série de livraison du fabricant',
			'en' => 'for example in the colour, packaging or accessories depending on the manufacturer’s production batch',
			'es' => 'por ejemplo, en el color, el embalaje o los accesorios según la serie de fabricación',
		)
		: array(
			'de' => 'z. B. bei Farbe oder Aussehen des Holzes als Naturprodukt',
			'fr' => 'par exemple de couleur ou d’aspect du bois, produit naturel',
			'en' => 'for example in the colour or appearance of wood, a natural product',
			'es' => 'por ejemplo, en el color o aspecto de la madera, producto natural',
		);
	$month = (int) wp_date( 'n' );
	$date  = ( 'es' === $lang ? $label[1][ $month - 1 ] . ' de ' : $label[1][ $month - 1 ] . ' ' ) . wp_date( 'Y' );

	$html = strtr(
		$html,
		array(
			'{site_url}'          => esc_url( home_url( '/' ) ),
			'{company}'           => esc_html( $company ),
			'{address}'           => implode( '<br>', array_map( 'esc_html', $lines ) ),
			'{address_inline}'    => esc_html( implode( ', ', $lines ) ),
			'{email}'             => esc_html( $email ),
			'{uid_line}'          => '' !== $uid ? '<br>' . esc_html( $uid_label . ( 'fr' === $lang ? ' : ' : ': ' ) . $uid ) : '',
			'{company_line}'      => esc_html( implode( ', ', array_filter( array_merge( array( $company ), $lines, '' !== $uid ? array( $uid_label . ' ' . $uid ) : array() ) ) ) ) . ' – ',
			'{phone_line}'        => '' !== $phone ? '<br>' . esc_html( $label[0] ) . ( 'fr' === $lang ? ' : ' : ': ' ) . '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' : '',
			'{city}'              => esc_html( '' !== $city ? $city : $company ),
			'{date}'              => esc_html( $date ),
			'{variation_example}' => esc_html( isset( $examples[ $lang ] ) ? $examples[ $lang ] : $examples['en'] ),
		)
	);
	// No address entered: drop the empty separators.
	$html = str_replace( array( '<br><br>', ', .' ), array( '<br>', '.' ), $html );

	return premium_shop_html_to_blocks( $html );
}

/**
 * Wrap simple HTML (p, h2, h3, ul) in block comments so the block editor
 * shows editable paragraphs, headings and lists.
 *
 * @param string $html HTML.
 * @return string
 */
function premium_shop_html_to_blocks( $html ) {
	preg_match_all( '#<(p|h2|h3|ul)\b[^>]*>.*?</\1>#s', $html, $matches, PREG_SET_ORDER );
	$out = '';
	foreach ( $matches as $match ) {
		switch ( $match[1] ) {
			case 'h2':
				$out .= '<!-- wp:heading -->' . str_replace( '<h2>', '<h2 class="wp-block-heading">', $match[0] ) . '<!-- /wp:heading -->' . "\n\n";
				break;
			case 'h3':
				$out .= '<!-- wp:heading {"level":3} -->' . str_replace( '<h3>', '<h3 class="wp-block-heading">', $match[0] ) . '<!-- /wp:heading -->' . "\n\n";
				break;
			case 'ul':
				$items = preg_replace( '#<li>(.*?)</li>#s', '<!-- wp:list-item --><li>$1</li><!-- /wp:list-item -->', $match[0] );
				$out  .= '<!-- wp:list -->' . str_replace( '<ul>', '<ul class="wp-block-list">', $items ) . '<!-- /wp:list -->' . "\n\n";
				break;
			default:
				$out .= '<!-- wp:paragraph -->' . $match[0] . '<!-- /wp:paragraph -->' . "\n\n";
		}
	}
	return trim( $out );
}

/**
 * Whether a privacy page still holds WordPress' default (example) text.
 *
 * @param WP_Post $post Page.
 * @return bool
 */
function premium_shop_is_default_privacy_page( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	return 'publish' !== $post->post_status
		|| false !== strpos( $post->post_content, 'privacy-policy-tutorial' )
		|| '' === trim( wp_strip_all_tags( $post->post_content ) );
}

/**
 * Create (or complete) the legal pages and link them in the footer.
 *
 * - Privacy policy: replaces WordPress' default draft; a privacy page you
 *   wrote yourself and published is never touched.
 * - Terms of use: created when there is no page with that address yet.
 */
function premium_shop_legal_install() {
	$default = function_exists( 'premium_shop_default_language' ) ? premium_shop_default_language() : 'de';
	$langs   = array( 'de', 'fr', 'en', 'es' );
	if ( ! in_array( $default, $langs, true ) ) {
		$default = 'en';
	}
	$created = array();

	foreach ( premium_shop_legal_docs() as $doc => $data ) {
		$page = null;
		if ( 'privacy' === $doc ) {
			$current = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) );
			if ( $current && 'trash' !== $current->post_status ) {
				if ( ! premium_shop_is_default_privacy_page( $current ) ) {
					continue; // The shop's own published privacy policy.
				}
				$page = $current;
			}
		}
		if ( ! $page ) {
			$existing = get_page_by_path( $data['slug'], OBJECT, 'page' );
			if ( $existing && 'trash' !== $existing->post_status ) {
				if ( 'terms' === $doc || ! premium_shop_is_default_privacy_page( $existing ) ) {
					if ( 'privacy' === $doc ) {
						update_option( 'wp_page_for_privacy_policy', $existing->ID );
					}
					continue;
				}
				$page = $existing;
			}
		}

		$postarr = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $data['titles'][ $default ],
			'post_name'    => $data['slug'],
			'post_content' => premium_shop_legal_content( $doc, $default ),
		);
		if ( $page ) {
			$postarr['ID'] = $page->ID;
			$id            = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$id = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}

		update_post_meta( $id, '_ps_legal_doc', $doc );
		foreach ( $langs as $lang ) {
			if ( $lang === $default ) {
				continue;
			}
			update_post_meta( $id, '_ps_name_' . $lang, $data['titles'][ $lang ] );
			update_post_meta( $id, '_ps_desc_' . $lang, wp_slash( premium_shop_legal_content( $doc, $lang ) ) );
		}
		if ( 'privacy' === $doc ) {
			update_option( 'wp_page_for_privacy_policy', $id );
		}
		$created[] = (int) $id;
	}

	premium_shop_legal_menu_sync();
}
add_action( 'premium_shop_upgrade_1_5', 'premium_shop_legal_install' );

/**
 * Make sure every published legal page (legal notice, terms, withdrawal,
 * terms of use, privacy policy) is linked in the footer "Legal" menu when a
 * menu is assigned to it (without a menu the footer lists them by itself).
 */
function premium_shop_legal_menu_sync() {
	$locations = get_nav_menu_locations();
	if ( empty( $locations['footer_legal'] ) || ! wp_get_nav_menu_object( $locations['footer_legal'] ) ) {
		return;
	}
	$menu_id = (int) $locations['footer_legal'];

	$pages = array();
	foreach ( array( 'impressum', 'agb', 'widerrufsbelehrung', 'nutzungsbedingungen' ) as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page && 'publish' === $page->post_status ) {
			$pages[] = (int) $page->ID;
		}
	}
	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $privacy && 'publish' === get_post_status( $privacy ) ) {
		$pages[] = $privacy;
	}

	$linked = array_map( 'intval', wp_list_pluck( (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) ), 'object_id' ) );
	foreach ( array_unique( $pages ) as $id ) {
		if ( in_array( $id, $linked, true ) ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
}
add_action( 'premium_shop_upgrade_1_6_1', 'premium_shop_legal_menu_sync' );

/**
 * Link a legal page in the footer as soon as it is published (the legal
 * notice, terms and withdrawal pages are created as drafts to be completed).
 *
 * @param string  $new_status New status.
 * @param string  $old_status Old status.
 * @param WP_Post $post       Page.
 */
function premium_shop_legal_menu_on_publish( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status || 'page' !== $post->post_type ) {
		return;
	}
	$legal = in_array( $post->post_name, array( 'impressum', 'agb', 'widerrufsbelehrung', 'nutzungsbedingungen' ), true )
		|| (int) get_option( 'wp_page_for_privacy_policy' ) === (int) $post->ID;
	if ( $legal ) {
		premium_shop_legal_menu_sync();
	}
}
add_action( 'transition_post_status', 'premium_shop_legal_menu_on_publish', 10, 3 );

/**
 * Block-level pieces (paragraphs, headings, lists) of a legal text, as plain
 * text, in order.
 *
 * @param string $html HTML or block markup.
 * @return string[]
 */
function premium_shop_legal_pieces( $html ) {
	preg_match_all( '#<(p|h2|h3|ul)\b[^>]*>.*?</\1>#s', (string) $html, $matches );
	$out = array();
	foreach ( $matches[0] as $piece ) {
		$text  = html_entity_decode( wp_strip_all_tags( $piece ), ENT_QUOTES, 'UTF-8' );
		$out[] = trim( preg_replace( '/\s+/u', ' ', $text ) );
	}
	return $out;
}

/**
 * New text for a legal page written by the theme, with the current contact
 * details.
 *
 * The stored text is compared with the theme's text block by block: when the
 * only differences are in the blocks that hold contact details (company,
 * address, e-mail, phone, city, date), the page was not edited by hand and is
 * rewritten. A page edited by hand is left alone (null).
 *
 * @param string $content Stored text.
 * @param string $doc     privacy|terms.
 * @param string $lang    Language of the stored text, '' to detect it.
 * @return array|null array( new text, language ) or null; new text is '' when nothing changed.
 */
function premium_shop_legal_refreshed( $content, $doc, $lang = '' ) {
	$current = premium_shop_legal_pieces( $content );
	if ( ! $current ) {
		return null;
	}
	foreach ( $lang ? array( $lang ) : array( 'de', 'fr', 'en', 'es' ) as $code ) {
		$file = PREMIUM_SHOP_DIR . '/inc/legal/' . $doc . '-' . $code . '.html';
		if ( ! is_readable( $file ) ) {
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		preg_match_all( '#<(p|h2|h3|ul)\b[^>]*>.*?</\1>#s', (string) file_get_contents( $file ), $tpl );
		$fresh_html = premium_shop_legal_content( $doc, $code );
		$fresh      = premium_shop_legal_pieces( $fresh_html );
		if ( count( $fresh ) !== count( $current ) || count( $tpl[0] ) !== count( $current ) ) {
			continue;
		}
		$ok      = true;
		$changed = false;
		foreach ( $current as $i => $text ) {
			if ( $text === $fresh[ $i ] ) {
				continue;
			}
			if ( false === strpos( $tpl[0][ $i ], '{' ) ) {
				$ok = false; // A block without contact details was changed by hand.
				break;
			}
			// Only the "last updated" date differs: nothing to update.
			if ( preg_match_all( '/\{[a-z_]+\}/', $tpl[0][ $i ], $ph ) && array( '{date}' ) === array_values( array_unique( $ph[0] ) ) ) {
				continue;
			}
			$changed = true;
		}
		if ( $ok ) {
			return array( $changed ? $fresh_html : '', $code );
		}
	}
	return null;
}

/**
 * Legal pages written by the theme (privacy policy, terms of use).
 *
 * @return array page ID => privacy|terms
 */
function premium_shop_legal_pages() {
	$pages = array();
	$ids   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'meta_key'       => '_ps_legal_doc', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $ids as $id ) {
		$pages[ (int) $id ] = (string) get_post_meta( $id, '_ps_legal_doc', true );
	}
	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $privacy && ! isset( $pages[ $privacy ] ) && get_post( $privacy ) ) {
		$pages[ $privacy ] = 'privacy';
	}
	foreach ( premium_shop_legal_docs() as $doc => $data ) {
		$page = get_page_by_path( $data['slug'], OBJECT, 'page' );
		if ( $page && ! isset( $pages[ $page->ID ] ) ) {
			$pages[ (int) $page->ID ] = $doc;
		}
	}
	return array_filter(
		$pages,
		static function ( $doc ) {
			return in_array( $doc, array( 'privacy', 'terms' ), true );
		}
	);
}

/**
 * Put the current contact details (company, address, e-mail, phone, city)
 * into the privacy policy and the terms of use, in every language.
 *
 * Pages written by the theme are rewritten; in pages edited by hand the old
 * e-mail address and phone number given in $replace are swapped for the new
 * ones, the rest of the text is kept.
 *
 * @param array $replace Old value => new value, for pages edited by hand.
 */
function premium_shop_legal_refresh( $replace = array() ) {
	$replace = array_filter(
		(array) $replace,
		static function ( $new, $old ) {
			return '' !== trim( (string) $old ) && '' !== trim( (string) $new ) && $old !== $new;
		},
		ARRAY_FILTER_USE_BOTH
	);

	foreach ( premium_shop_legal_pages() as $id => $doc ) {
		$post = get_post( $id );
		if ( ! $post || 'trash' === $post->post_status ) {
			continue;
		}

		$result = premium_shop_legal_refreshed( $post->post_content, $doc );
		$new    = null;
		if ( $result ) {
			$new = $result[0];
		} elseif ( $replace ) {
			$new = strtr( $post->post_content, $replace );
			$new = $new === $post->post_content ? '' : $new;
		}
		if ( $new ) {
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $id,
						'post_content' => $new,
					)
				)
			);
		}
		update_post_meta( $id, '_ps_legal_doc', $doc );

		foreach ( array( 'de', 'fr', 'en', 'es' ) as $lang ) {
			$meta = (string) get_post_meta( $id, '_ps_desc_' . $lang, true );
			if ( '' === $meta ) {
				continue;
			}
			$result = premium_shop_legal_refreshed( $meta, $doc, $lang );
			if ( $result ) {
				$new = $result[0];
			} else {
				$new = $replace ? strtr( $meta, $replace ) : '';
				$new = $new === $meta ? '' : $new;
			}
			if ( $new ) {
				update_post_meta( $id, '_ps_desc_' . $lang, wp_slash( $new ) );
			}
		}
	}
}

/**
 * Contact details before a Customizer save (to replace them in pages edited
 * by hand).
 */
function premium_shop_legal_remember_contact() {
	$GLOBALS['premium_shop_old_contact'] = array(
		'email'   => trim( (string) premium_shop_option( 'contact_email' ) ),
		'phone'   => trim( (string) premium_shop_option( 'contact_phone' ) ),
		'company' => trim( (string) premium_shop_option( 'company_name' ) ),
	);
}
add_action( 'customize_save', 'premium_shop_legal_remember_contact' );

/**
 * After a Customizer save: the legal pages follow the new contact details.
 */
function premium_shop_legal_refresh_after_save() {
	$old = isset( $GLOBALS['premium_shop_old_contact'] ) ? $GLOBALS['premium_shop_old_contact'] : array();
	$map = array();
	foreach ( array( 'email' => 'contact_email', 'phone' => 'contact_phone', 'company' => 'company_name' ) as $k => $key ) {
		if ( ! empty( $old[ $k ] ) ) {
			$map[ $old[ $k ] ] = trim( (string) premium_shop_option( $key ) );
		}
	}
	premium_shop_legal_refresh( $map );
}
add_action( 'customize_save_after', 'premium_shop_legal_refresh_after_save' );

/**
 * Theme update: legal pages with the current contact details. On the gaming
 * edition, contact details copied from the firewood shop are replaced.
 */
function premium_shop_legal_refresh_on_upgrade() {
	$map = array();
	if ( function_exists( 'premium_shop_preset' ) && 'gaming' === premium_shop_preset() ) {
		$email = strtolower( trim( (string) get_theme_mod( 'ps_contact_email', '' ) ) );
		if ( false !== strpos( $email, 'warmeholz' ) || false !== strpos( $email, 'mirop-bois' ) ) {
			remove_theme_mod( 'ps_contact_email' ); // Back to the gaming default (kontakt@neoplay.ch).
		}
		if ( false !== stripos( (string) get_theme_mod( 'ps_company_name', '' ), 'warmeholz' ) ) {
			remove_theme_mod( 'ps_company_name' );
		}
		$new = trim( (string) premium_shop_option( 'contact_email' ) );
		foreach ( array( 'info@warmeholz.ch', 'info@mirop-bois.ch', (string) get_option( 'admin_email' ) ) as $old ) {
			$map[ $old ] = $new;
		}
	}
	premium_shop_legal_refresh( $map );
}
add_action( 'premium_shop_upgrade_2_3_3', 'premium_shop_legal_refresh_on_upgrade' );
// 2.3.10: terms of use without the firewood example on the gaming edition.
add_action( 'premium_shop_upgrade_2_3_10', 'premium_shop_legal_refresh_on_upgrade' );
