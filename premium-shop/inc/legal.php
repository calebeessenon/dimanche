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
	$city    = '';
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
	$month = (int) wp_date( 'n' );
	$date  = ( 'es' === $lang ? $label[1][ $month - 1 ] . ' de ' : $label[1][ $month - 1 ] . ' ' ) . wp_date( 'Y' );

	$html = strtr(
		$html,
		array(
			'{site_url}'       => esc_url( home_url( '/' ) ),
			'{company}'        => esc_html( $company ),
			'{address}'        => implode( '<br>', array_map( 'esc_html', $lines ) ),
			'{address_inline}' => esc_html( implode( ', ', $lines ) ),
			'{email}'          => esc_html( $email ),
			'{phone_line}'     => '' !== $phone ? '<br>' . esc_html( $label[0] ) . ( 'fr' === $lang ? ' : ' : ': ' ) . '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' : '',
			'{city}'           => esc_html( '' !== $city ? $city : $company ),
			'{date}'           => esc_html( $date ),
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
