<?php
/**
 * CSV import helpers (Products → Import).
 *
 * - Columns of a WooCommerce export made in French, German or Spanish are
 *   recognised whatever the language of the admin (WooCommerce itself only
 *   recognises the admin language and English: a French export imported on a
 *   German site loses its name, prices and categories).
 * - Products left as "Import placeholder for …" by an incomplete import get a
 *   proper URL again when a later import gives them their name, and can be
 *   moved to the trash in one click.
 * - Firewood data (species, log length, stacked m³ / kg, drying, moisture) is
 *   read from the product names when the file has no "Meta: _ps_…" columns.
 *
 * @package Premium_Shop\WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize a column title: lower case, no accents, no punctuation or spaces.
 *
 * @param string $title Column title.
 * @return string
 */
function premium_shop_import_normalize( $title ) {
	$title = str_replace( array( "\xc2\xa0", '’', '`' ), array( ' ', "'", "'" ), (string) $title );
	$title = strtr( $title, array( 'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'Ä' => 'a', 'Ö' => 'o', 'Ü' => 'u', 'ß' => 'ss' ) );
	$title = strtolower( remove_accents( $title ) );
	return preg_replace( '/[^a-z0-9]+/', '', $title );
}

/**
 * Localized column titles of WooCommerce product exports.
 *
 * @return array Normalized title => importer field.
 */
function premium_shop_import_localized_columns() {
	$columns = array(
		'id'                 => array( 'ID' ),
		'type'               => array( 'Type', 'Typ', 'Tipo' ),
		'sku'                => array( 'UGS', 'Référence', 'Artikelnummer', 'SKU', 'Art.-Nr.' ),
		'name'               => array( 'Nom', 'Name', 'Nombre' ),
		'published'          => array( 'Publié', 'Veröffentlicht', 'Publicado' ),
		'featured'           => array( 'Mis en avant ?', 'Ist hervorgehoben?', 'Hervorgehoben?', '¿Está destacado?', 'Destacado' ),
		'catalog_visibility' => array( 'Visibilité dans le catalogue', 'Sichtbarkeit im Katalog', 'Katalogsichtbarkeit', 'Visibilidad en el catálogo' ),
		'short_description'  => array( 'Description courte', 'Kurzbeschreibung', 'Descripción corta' ),
		'description'        => array( 'Description', 'Beschreibung', 'Descripción' ),
		'date_on_sale_from'  => array( 'Date de début de promo', 'Datum, an dem der Angebotspreis beginnt', 'Angebotspreis gültig ab', 'Día en que empieza el precio rebajado' ),
		'date_on_sale_to'    => array( 'Date de fin de promo', 'Datum, an dem der Angebotspreis endet', 'Angebotspreis gültig bis', 'Día en que termina el precio rebajado' ),
		'tax_status'         => array( 'État de la TVA', 'Steuerstatus', 'Estado del impuesto' ),
		'tax_class'          => array( 'Classe de TVA', 'Steuerklasse', 'Clase de impuesto' ),
		'stock_status'       => array( 'En stock ?', 'Vorrätig?', 'Auf Lager?', 'Lagerstatus', '¿Existencias?', '¿En inventario?', 'En inventario' ),
		'stock_quantity'     => array( 'Stock', 'Lager', 'Bestand', 'Lagerbestand', 'Inventario' ),
		'backorders'         => array( 'Autoriser les commandes de produits en rupture ?', 'Lieferrückstände erlaubt?', 'Rückstände erlaubt?', '¿Permitir reservas de productos agotados?' ),
		'low_stock_amount'   => array( 'Montant de stock faible', 'Geringe Lagermenge', 'Geringer Lagerbestand', 'Cantidad de bajo inventario' ),
		'sold_individually'  => array( 'Vendre individuellement ?', 'Nur einzeln verkaufen?', 'Einzeln verkauft?', '¿Vendido individualmente?' ),
		'reviews_allowed'    => array( 'Autoriser les avis clients ?', 'Kundenrezensionen erlauben?', 'Kundenbewertungen erlauben?', '¿Permitir valoraciones de clientes?' ),
		'purchase_note'      => array( 'Note de commande', 'Hinweis zum Kauf', 'Kaufhinweis', 'Nota de compra' ),
		'sale_price'         => array( 'Tarif promo', 'Prix promo', 'Angebotspreis', 'Precio rebajado' ),
		'regular_price'      => array( 'Tarif régulier', 'Prix régulier', 'Regulärer Preis', 'Normaler Preis', 'Precio normal', 'Precio regular' ),
		'category_ids'       => array( 'Catégories', 'Kategorien', 'Categorías' ),
		'tag_ids'            => array( 'Étiquettes', 'Schlagwörter', 'Tags', 'Etiquetas' ),
		'shipping_class_id'  => array( 'Classe d’expédition', 'Versandklasse', 'Clase de envío' ),
		'images'             => array( 'Images', 'Bilder', 'Imágenes' ),
		'download_limit'     => array( 'Limite de téléchargement', 'Download-Limit', 'Límite de descargas' ),
		'download_expiry'    => array( 'Jours d’expiration du téléchargement', 'Ablauftage des Downloads', 'Download-Ablauf (Tage)', 'Días de caducidad de la descarga' ),
		'parent_id'          => array( 'Parent', 'Übergeordnetes Produkt', 'Eltern', 'Superior' ),
		'grouped_products'   => array( 'Groupes de produits', 'Produits groupés', 'Gruppierte Produkte', 'Productos agrupados' ),
		'upsell_ids'         => array( 'Produits suggérés', 'Montée en gamme', 'Zusatzverkäufe', 'Upsells', 'Ventas dirigidas' ),
		'cross_sell_ids'     => array( 'Ventes croisées', 'Cross-Sells', 'Cross-Sells (Querverkäufe)', 'Ventas cruzadas' ),
		'product_url'        => array( 'URL externe', 'Externe URL', 'URL externa' ),
		'button_text'        => array( 'Libellé du bouton', 'Texte du bouton', 'Button-Text', 'Texto del botón' ),
		'menu_order'         => array( 'Position', 'Posición' ),
	);

	$map = array();
	foreach ( $columns as $field => $titles ) {
		foreach ( $titles as $title ) {
			$map[ premium_shop_import_normalize( $title ) ] = $field;
		}
	}

	return apply_filters( 'premium_shop_import_localized_columns', $map );
}

/**
 * Localized "special" columns (attributes, downloads, meta), matched on the
 * column title in lower case without accents.
 *
 * @return array Regex => importer field prefix (the number or meta key follows).
 */
function premium_shop_import_localized_special_columns() {
	return apply_filters(
		'premium_shop_import_localized_special_columns',
		array(
			"/^nom de l'attribut (\d+)$/"         => 'attributes:name',
			"/^valeur\(s\) de l'attribut (\d+)$/" => 'attributes:value',
			'/^attribut (\d+) visible$/'          => 'attributes:visible',
			'/^attribut (\d+) global$/'           => 'attributes:taxonomy',
			'/^attribut (\d+) par defaut$/'       => 'attributes:default',
			'/^attribut (\d+) name$/'             => 'attributes:name',
			'/^attribut (\d+) wert\(e\)$/'        => 'attributes:value',
			'/^attribut (\d+) sichtbar$/'         => 'attributes:visible',
			'/^attribut (\d+) standard$/'         => 'attributes:default',
			'/^nombre del atributo (\d+)$/'       => 'attributes:name',
			'/^valor\(es\) del atributo (\d+)$/'  => 'attributes:value',
			'/^atributo (?:visible )?(\d+)(?: visible)?$/' => 'attributes:visible',
			'/^atributo (?:global )?(\d+)(?: global)?$/'   => 'attributes:taxonomy',
			'/^atributo (\d+) por defecto$/'      => 'attributes:default',
			'/^telechargement (\d+) id$/'         => 'downloads:id',
			'/^telechargement (\d+) nom$/'        => 'downloads:name',
			'/^telechargement (\d+) url$/'        => 'downloads:url',
			'/^download (\d+) name$/'             => 'downloads:name',
			'/^download (\d+) url$/'              => 'downloads:url',
			'/^descarga (\d+) nombre$/'           => 'downloads:name',
			'/^descarga (\d+) url$/'              => 'downloads:url',
		)
	);
}

/**
 * Map columns WooCommerce left unmapped.
 *
 * @param array $headers     Index => mapped field (WooCommerce puts the lower-cased title when it found nothing).
 * @param array $raw_headers Index => column title.
 * @return array
 */
function premium_shop_import_map_localized_columns( $headers, $raw_headers ) {
	if ( ! apply_filters( 'premium_shop_import_map_localized_columns', true ) ) {
		return $headers;
	}

	$columns = premium_shop_import_localized_columns();
	$special = premium_shop_import_localized_special_columns();
	$used    = array_flip( array_filter( array_values( $headers ), 'is_string' ) );

	foreach ( $raw_headers as $position => $title ) {
		$title = (string) $title;
		// WooCommerce indexes the result by position (mapping screen) or by title.
		$index = array_key_exists( $position, $headers ) ? $position : ( array_key_exists( $title, $headers ) ? $title : null );
		if ( null === $index || strtolower( $title ) !== $headers[ $index ] ) {
			continue; // Already mapped by WooCommerce.
		}

		// "Méta : _key" / "Meta: _key" (the key keeps its case).
		if ( preg_match( '/^\s*m(?:e|é|É|E)ta\s*:\s*(.+?)\s*$/iu', str_replace( "\xc2\xa0", ' ', $title ), $m ) ) {
			$headers[ $index ] = 'meta:' . $m[1];
			continue;
		}

		$normalized = premium_shop_import_normalize( $title );

		// Weight and dimensions whatever the unit: "Poids (kg)", "Gewicht (g)", "Longitud (cm)"…
		foreach ( array(
			'weight' => '/^(poids|gewicht|peso)/',
			'length' => '/^(longueur|lange|longitud)/',
			'width'  => '/^(largeur|breite|anchura|ancho)/',
			'height' => '/^(hauteur|hohe|altura)/',
		) as $field => $regex ) {
			if ( preg_match( $regex, $normalized ) && ! isset( $used[ $field ] ) ) {
				$headers[ $index ] = $field;
				$used[ $field ]    = true;
				continue 2;
			}
		}

		if ( isset( $columns[ $normalized ] ) && ! isset( $used[ $columns[ $normalized ] ] ) ) {
			$headers[ $index ]               = $columns[ $normalized ];
			$used[ $columns[ $normalized ] ] = true;
			continue;
		}

		$spaced = trim( preg_replace( '/\s+/', ' ', strtolower( remove_accents( str_replace( array( "\xc2\xa0", '’' ), array( ' ', "'" ), $title ) ) ) ) );
		foreach ( $special as $regex => $prefix ) {
			if ( preg_match( $regex, $spaced, $m ) ) {
				$headers[ $index ] = $prefix . $m[1];
				continue 2;
			}
		}
	}

	return $headers;
}
add_filter( 'woocommerce_csv_product_import_mapped_columns', 'premium_shop_import_map_localized_columns', 20, 2 );

/**
 * Before an imported product is saved: placeholder URL repair and firewood
 * data from the name.
 *
 * @param WC_Product $object Product about to be saved.
 * @return WC_Product
 */
function premium_shop_import_prepare_product( $object ) {
	if ( ! $object instanceof WC_Product || $object->is_type( 'variation' ) ) {
		return $object;
	}

	// A placeholder ("Import placeholder for 123") that now has its real name gets a URL from that name.
	$slug = (string) $object->get_slug( 'edit' );
	if ( 0 === strpos( $slug, 'import-placeholder-for-' ) && 0 !== stripos( $object->get_name( 'edit' ), 'Import placeholder for' ) ) {
		$object->set_slug( '' );
	}

	if ( apply_filters( 'premium_shop_import_detect_firewood', true, $object ) && function_exists( 'premium_shop_fw_is_firewood' ) && ! premium_shop_fw_is_firewood( $object ) ) {
		foreach ( premium_shop_import_firewood_from_text( $object->get_name( 'edit' ), wp_strip_all_tags( $object->get_description( 'edit' ) ) ) as $key => $value ) {
			if ( '' === (string) $object->get_meta( $key ) ) {
				$object->update_meta_data( $key, $value );
			}
		}
	}

	return $object;
}
add_filter( 'woocommerce_product_import_pre_insert_product_object', 'premium_shop_import_prepare_product', 5 );

/**
 * Read firewood data from a product name (French or German) and description.
 *
 * Examples: "Palette bois de chauffage (Chêne) - 33 cm - 3 stères",
 * "Buche Kaminholz 33 cm, 2 RM, kammergetrocknet", "Pellets – 65 sacs de 15 kg".
 *
 * @param string $name Product name.
 * @param string $text Description (plain text).
 * @return array Meta key => value.
 */
function premium_shop_import_firewood_from_text( $name, $text = '' ) {
	$n    = function_exists( 'mb_strtolower' ) ? mb_strtolower( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ) ) : strtolower( $name );
	$t    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
	$meta = array();

	$logs = ! preg_match( '/granul|pellet|briquet|brikett|densifi|presslin|b[ûu]ches? de nuit|hotrods|ecofire|compress|anz[üu]nd|allume/u', $n );

	if ( $logs ) {
		$species = array(
			'oak'      => '/ch[êe]ne|eiche/u',
			'beech'    => '/h[êe]tre|buche(?!s)/u',
			'birch'    => '/bouleau|birke/u',
			'ash'      => '/fr[êe]ne|esche/u',
			'hornbeam' => '/charme|hainbuche/u',
		);
		$found = array();
		foreach ( $species as $key => $regex ) {
			if ( preg_match( $regex, $n ) ) {
				$found[] = $key;
			}
		}
		if ( 1 === count( $found ) && ! preg_match( '/m[ée]lang|mix|misch/u', $n ) ) {
			$meta['_ps_species'] = $found[0];
		} elseif ( $found || preg_match( '/m[ée]lang|bois durs|feuillus|hartholz|laubholz|mischholz/u', $n ) ) {
			$meta['_ps_species'] = 'mixed';
		}

		if ( preg_match( '/(\d{2,3})\s*-?\s*cm/u', $n, $m ) ) {
			$meta['_ps_log_length'] = $m[1];
		}

		// "3 stères", "2,5 st", "2 RM", "1 Ster", "1 Raummeter" (not "< 6 stères").
		if ( preg_match( '/(?<![\d.,<])(?<!<\s)(\d+(?:[.,]\d+)?)\s*(?:st[èe]res?|st\b|rm\b|ster\b|raummeter)/u', $n, $m ) ) {
			$meta['_ps_unit']     = 'rm';
			$meta['_ps_unit_qty'] = (string) (float) str_replace( ',', '.', $m[1] );
		}

		if ( preg_match( '/s[ée]ch[ée]s? au four|s[ée]choir|kiln|kammergetrocknet|ofengetrocknet|technisch getrocknet/u', $n . ' ' . $t ) ) {
			$meta['_ps_drying'] = 'kiln';
		} elseif ( preg_match( '/s[ée]chage naturel|s[ée]ch[ée]e?s? (?:à|a) l.air|luftgetrocknet|naturgetrocknet/u', $n . ' ' . $t ) ) {
			$meta['_ps_drying'] = 'air';
		}

		if ( preg_match( '/(?:moins de|inf[ée]rieure? (?:à|a)|<|unter|weniger als)\s*(\d{1,2})\s*%/u', $t, $m ) ) {
			$meta['_ps_moisture'] = $m[1];
		}
	}

	if ( empty( $meta['_ps_unit'] ) ) {
		$kg = 0;
		if ( preg_match( '/(\d+)\s*(?:sacs?|s[äa]cke?)\s*(?:de|x|à|a|zu|von)?\s*(\d+)\s*kg/u', $n, $m ) ) {
			$kg = (int) $m[1] * (int) $m[2];
		} elseif ( preg_match( '/(\d+(?:[.,]\d+)?)\s*kg/u', $n, $m ) ) {
			$kg = (float) str_replace( ',', '.', $m[1] );
		} elseif ( preg_match( '/(\d+)\s*(?:tonnes?|tonne|t\b)/u', $n, $m ) ) {
			$kg = (int) $m[1] * 1000;
		}
		if ( $kg > 0 ) {
			$meta['_ps_unit']     = 'kg';
			$meta['_ps_unit_qty'] = (string) $kg;
		}
	}

	return apply_filters( 'premium_shop_import_firewood_from_text', $meta, $name, $text );
}

/**
 * Products left as "Import placeholder for …" (no name, no price).
 *
 * @return int[]
 */
function premium_shop_import_placeholder_ids() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return array_map( 'absint', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status NOT IN ('trash','auto-draft') AND post_title LIKE 'Import placeholder for %' LIMIT 1000" ) );
}

/**
 * Notice on the products screen when placeholders exist.
 */
function premium_shop_import_placeholder_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-product' !== $screen->id || ! current_user_can( 'delete_products' ) ) {
		return;
	}

	$ids = premium_shop_import_placeholder_ids();
	if ( ! $ids ) {
		return;
	}

	$url = wp_nonce_url( admin_url( 'admin-post.php?action=premium_shop_trash_placeholders' ), 'premium_shop_trash_placeholders' );
	?>
	<div class="notice notice-error">
		<p><strong>
			<?php
			/* translators: %d: number of products. */
			echo esc_html( sprintf( _n( '%d product comes from an incomplete import (“Import placeholder for …”): it has no name and no price, so it cannot be bought.', '%d products come from an incomplete import (“Import placeholder for …”): they have no name and no price, so they cannot be bought.', count( $ids ), 'premium-shop' ), count( $ids ) ) );
			?>
		</strong></p>
		<p><?php esc_html_e( 'Move them to the trash, then import your file again (Products → Import). Or import the same file again with “Update existing products” checked: they get their name, prices and categories back.', 'premium-shop' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( $url ); ?>" onclick="return window.confirm(this.getAttribute('data-confirm'));" data-confirm="<?php esc_attr_e( 'Move these products to the trash?', 'premium-shop' ); ?>"><?php esc_html_e( 'Move them to the trash', 'premium-shop' ); ?></a></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'premium_shop_import_placeholder_notice' );

/**
 * Trash the placeholders.
 */
function premium_shop_import_trash_placeholders() {
	check_admin_referer( 'premium_shop_trash_placeholders' );
	if ( ! current_user_can( 'delete_products' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'premium-shop' ), 403 );
	}

	$count = 0;
	foreach ( premium_shop_import_placeholder_ids() as $id ) {
		if ( current_user_can( 'delete_post', $id ) && wp_trash_post( $id ) ) {
			++$count;
		}
	}

	wp_safe_redirect( add_query_arg( array( 'post_type' => 'product', 'ps_trashed' => $count ), admin_url( 'edit.php' ) ) );
	exit;
}
add_action( 'admin_post_premium_shop_trash_placeholders', 'premium_shop_import_trash_placeholders' );

/**
 * Confirmation after trashing.
 */
function premium_shop_import_trashed_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['ps_trashed'] ) || ! current_user_can( 'edit_products' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$count = absint( $_GET['ps_trashed'] );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(
		/* translators: %d: number of products. */
		sprintf( _n( '%d product moved to the trash.', '%d products moved to the trash.', $count, 'premium-shop' ), $count )
	) . '</p></div>';
}
add_action( 'admin_notices', 'premium_shop_import_trashed_notice' );
