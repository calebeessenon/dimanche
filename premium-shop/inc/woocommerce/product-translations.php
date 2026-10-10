<?php
/**
 * Product texts in several languages — without a translation plugin.
 *
 * Each product has a "Translations" tab (name, short description, description
 * per language of the language switcher). The visitor sees the version of the
 * language chosen at the top of the page; empty fields fall back to the main
 * product texts. Category and tag names can be translated on their edit
 * screen. CSV import/export: columns "Meta: _ps_name_de", "Meta: _ps_short_de",
 * "Meta: _ps_desc_de" (fr, es, en…).
 *
 * Only active in the built-in language mode: with WPML, Polylang or
 * TranslatePress, translate with the plugin.
 *
 * @package Premium_Shop\WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Translatable product fields.
 *
 * @return array field => meta prefix.
 */
function premium_shop_pt_fields() {
	return array(
		'name'  => '_ps_name_',
		'short' => '_ps_short_',
		'desc'  => '_ps_desc_',
	);
}

/**
 * Should product texts be swapped for this request?
 *
 * @return string Language code, or '' when nothing should be swapped.
 */
function premium_shop_pt_language() {
	static $lang = null;
	if ( null !== $lang ) {
		return $lang;
	}

	$lang = '';
	if ( ! function_exists( 'premium_shop_builtin_applies' ) || ! premium_shop_builtin_applies() ) {
		return $lang;
	}
	// AJAX / REST calls made from admin screens (order editor, block editor,
	// WooCommerce admin…) keep the main texts.
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$rest = false !== strpos( $uri, '/' . rest_get_url_prefix() . '/' ) || isset( $_GET['rest_route'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ( wp_doing_ajax() || $rest ) && false !== strpos( (string) wp_get_referer(), '/wp-admin/' ) ) {
		return $lang;
	}

	$lang = premium_shop_builtin_language();
	return $lang;
}

/**
 * Post types whose title and content can be translated (products, pages).
 *
 * @return string[]
 */
function premium_shop_pt_post_types() {
	return (array) apply_filters( 'premium_shop_translatable_post_types', array( 'product', 'page' ) );
}

/**
 * Translated text of a product.
 *
 * @param int    $product_id Product ID.
 * @param string $field      name|short|desc.
 * @param string $lang       Language code (default: current).
 * @return string '' when there is no translation.
 */
function premium_shop_pt_get( $product_id, $field, $lang = '' ) {
	$lang   = $lang ? $lang : premium_shop_pt_language();
	$fields = premium_shop_pt_fields();
	if ( ! $lang || ! $product_id || ! isset( $fields[ $field ] ) ) {
		return '';
	}
	$value = get_post_meta( $product_id, $fields[ $field ] . $lang, true );
	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Product name.
 *
 * @param string     $name    Name.
 * @param WC_Product $product Product.
 * @return string
 */
function premium_shop_pt_name( $name, $product ) {
	$translated = premium_shop_pt_get( $product->get_id(), 'name' );
	return '' !== $translated ? $translated : $name;
}
add_filter( 'woocommerce_product_get_name', 'premium_shop_pt_name', 10, 2 );

/**
 * Variation name: "Parent name - attributes" with the translated parent name.
 *
 * @param string               $name      Name.
 * @param WC_Product_Variation $variation Variation.
 * @return string
 */
function premium_shop_pt_variation_name( $name, $variation ) {
	$parent_id  = $variation->get_parent_id();
	$translated = premium_shop_pt_get( $parent_id, 'name' );
	if ( '' === $translated ) {
		return $name;
	}
	$original = (string) get_post_field( 'post_title', $parent_id );
	if ( '' !== $original && 0 === strpos( $name, $original ) ) {
		return $translated . substr( $name, strlen( $original ) );
	}
	return $name;
}
add_filter( 'woocommerce_product_variation_get_name', 'premium_shop_pt_variation_name', 10, 2 );

/**
 * Short description (product getter).
 *
 * @param string     $text    Text.
 * @param WC_Product $product Product.
 * @return string
 */
function premium_shop_pt_short( $text, $product ) {
	$translated = premium_shop_pt_get( $product->get_id(), 'short' );
	return '' !== $translated ? $translated : $text;
}
add_filter( 'woocommerce_product_get_short_description', 'premium_shop_pt_short', 10, 2 );

/**
 * Description (product getter).
 *
 * @param string     $text    Text.
 * @param WC_Product $product Product.
 * @return string
 */
function premium_shop_pt_desc( $text, $product ) {
	$translated = premium_shop_pt_get( $product->get_id(), 'desc' );
	return '' !== $translated ? $translated : $text;
}
add_filter( 'woocommerce_product_get_description', 'premium_shop_pt_desc', 10, 2 );

/**
 * Titles printed with the_title() / single_post_title() (product page, <title>).
 *
 * @param string   $title   Title.
 * @param int|null $post_id Post ID.
 * @return string
 */
function premium_shop_pt_the_title( $title, $post_id = null ) {
	if ( ! $post_id || ! in_array( get_post_type( $post_id ), premium_shop_pt_post_types(), true ) ) {
		return $title;
	}
	$translated = premium_shop_pt_get( (int) $post_id, 'name' );
	return '' !== $translated ? esc_html( $translated ) : $title;
}
add_filter( 'the_title', 'premium_shop_pt_the_title', 10, 2 );

/**
 * Document title of a product page.
 *
 * @param string  $title Title.
 * @param WP_Post $post  Post.
 * @return string
 */
function premium_shop_pt_single_title( $title, $post = null ) {
	if ( $post instanceof WP_Post && in_array( $post->post_type, premium_shop_pt_post_types(), true ) ) {
		$translated = premium_shop_pt_get( $post->ID, 'name' );
		return '' !== $translated ? $translated : $title;
	}
	return $title;
}
add_filter( 'single_post_title', 'premium_shop_pt_single_title', 10, 2 );

/**
 * Product page description tab (the_content) and short description.
 *
 * @param string $content Content.
 * @return string
 */
function premium_shop_pt_content( $content ) {
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, premium_shop_pt_post_types(), true ) || ! in_the_loop() ) {
		return $content;
	}
	$translated = premium_shop_pt_get( $post->ID, 'desc' );
	return '' !== $translated ? $translated : $content;
}
add_filter( 'the_content', 'premium_shop_pt_content', 1 );

/**
 * Short description on the product page.
 *
 * @param string $text Text.
 * @return string
 */
function premium_shop_pt_short_description( $text ) {
	$post = get_post();
	if ( ! $post || 'product' !== $post->post_type ) {
		return $text;
	}
	$translated = premium_shop_pt_get( $post->ID, 'short' );
	return '' !== $translated ? $translated : $text;
}
add_filter( 'woocommerce_short_description', 'premium_shop_pt_short_description', 1 );

/**
 * Search also finds translated names and short descriptions.
 *
 * @param string   $search Search SQL.
 * @param WP_Query $query  Query.
 * @return string
 */
function premium_shop_pt_search( $search, $query ) {
	global $wpdb;
	$lang = premium_shop_pt_language();
	if ( ! $lang || '' === $search || ! $query->is_search() ) {
		return $search;
	}
	$types = (array) $query->get( 'post_type' );
	if ( ! in_array( 'product', $types, true ) ) {
		return $search;
	}
	$term = trim( (string) $query->get( 's' ) );
	if ( '' === $term ) {
		return $search;
	}
	$like = '%' . $wpdb->esc_like( $term ) . '%';
	$sub  = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND meta_value LIKE %s", '_ps_name_' . $lang, '_ps_short_' . $lang, $like );
	$body = preg_replace( '/^\s*AND\s+/i', '', $search );
	return " AND ( {$wpdb->posts}.ID IN ( {$sub} ) OR ( {$body} ) ) ";
}
add_filter( 'posts_search', 'premium_shop_pt_search', 20, 2 );

/* --------------------------------------------------------------------------
 * Category / tag names.
 * ----------------------------------------------------------------------- */

/**
 * Taxonomies whose terms can be translated.
 *
 * @return string[]
 */
function premium_shop_pt_taxonomies() {
	return apply_filters( 'premium_shop_pt_taxonomies', array( 'product_cat', 'product_tag' ) );
}

/**
 * Swap a term's name/description.
 *
 * @param mixed $term Term.
 * @return mixed
 */
function premium_shop_pt_term( $term ) {
	if ( ! $term instanceof WP_Term || ! in_array( $term->taxonomy, premium_shop_pt_taxonomies(), true ) ) {
		return $term;
	}
	$lang = premium_shop_pt_language();
	if ( ! $lang ) {
		return $term;
	}
	$name = get_term_meta( $term->term_id, '_ps_name_' . $lang, true );
	if ( is_string( $name ) && '' !== trim( $name ) ) {
		$term->name = trim( $name );
	}
	$desc = get_term_meta( $term->term_id, '_ps_desc_' . $lang, true );
	if ( is_string( $desc ) && '' !== trim( $desc ) ) {
		$term->description = trim( $desc );
	}
	return $term;
}
add_filter( 'get_term', 'premium_shop_pt_term' );

/**
 * Swap names in term lists.
 *
 * @param array $terms Terms.
 * @return array
 */
function premium_shop_pt_terms( $terms ) {
	if ( ! is_array( $terms ) || ! premium_shop_pt_language() ) {
		return $terms;
	}
	foreach ( $terms as $i => $term ) {
		if ( $term instanceof WP_Term ) {
			$terms[ $i ] = premium_shop_pt_term( $term );
		}
	}
	return $terms;
}
add_filter( 'get_terms', 'premium_shop_pt_terms' );
add_filter( 'get_the_terms', 'premium_shop_pt_terms' );

/**
 * Known names of usual firewood shop categories, used to pre-fill category
 * translations (only empty fields are filled).
 *
 * @return array[] Each: language => name.
 */
function premium_shop_pt_known_terms() {
	return apply_filters(
		'premium_shop_pt_known_terms',
		array(
			array( 'de' => 'Brennholz', 'fr' => 'Bois de chauffage', 'es' => 'Leña', 'en' => 'Firewood' ),
			array( 'de' => 'Kaminholz', 'fr' => 'Bois de cheminée', 'es' => 'Leña para chimenea', 'en' => 'Fireplace logs' ),
			array( 'de' => 'Holzbriketts', 'fr' => 'Bûches compressées', 'es' => 'Briquetas de madera', 'en' => 'Wood briquettes' ),
			array( 'de' => 'Holzpellets', 'fr' => 'Granulés et pellets', 'es' => 'Pellets de madera', 'en' => 'Wood pellets' ),
			array( 'de' => 'Pellets', 'fr' => 'Granulés', 'es' => 'Pellets', 'en' => 'Pellets' ),
			array( 'de' => 'Anzündholz', 'fr' => 'Bois d’allumage', 'es' => 'Astillas para encender', 'en' => 'Kindling' ),
			array( 'de' => 'Zubehör', 'fr' => 'Accessoires', 'es' => 'Accesorios', 'en' => 'Accessories' ),
			array( 'de' => 'Kaminholz-Boxen', 'fr' => 'Box de bois', 'es' => 'Cajas de leña', 'en' => 'Firewood boxes' ),
		)
	);
}

/**
 * Pre-fill translations of a category whose name is a known one.
 *
 * @param int $term_id Term ID.
 */
function premium_shop_pt_seed_term( $term_id ) {
	$term = get_term( $term_id );
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	$raw = get_term_field( 'name', $term_id, '', 'raw' );
	$key = premium_shop_import_normalize_name( is_string( $raw ) ? $raw : $term->name );
	foreach ( premium_shop_pt_known_terms() as $names ) {
		foreach ( $names as $name ) {
			if ( premium_shop_import_normalize_name( $name ) === $key ) {
				foreach ( $names as $lang => $translated ) {
					if ( isset( premium_shop_languages()[ $lang ] ) && '' === (string) get_term_meta( $term_id, '_ps_name_' . $lang, true ) ) {
						update_term_meta( $term_id, '_ps_name_' . $lang, $translated );
					}
				}
				return;
			}
		}
	}
}

/**
 * Normalize a name for comparison.
 *
 * @param string $name Name.
 * @return string
 */
function premium_shop_import_normalize_name( $name ) {
	return strtolower( preg_replace( '/[^a-z0-9]+/i', '', remove_accents( html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ) ) ) );
}

/**
 * Seed all product categories (theme update and after each import).
 */
function premium_shop_pt_seed_terms() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $term_id ) {
		premium_shop_pt_seed_term( (int) $term_id );
	}
}
add_action( 'premium_shop_upgrade_1_3', 'premium_shop_pt_seed_terms' );
add_action( 'created_product_cat', 'premium_shop_pt_seed_term' );

/* --------------------------------------------------------------------------
 * Admin.
 * ----------------------------------------------------------------------- */

/**
 * "Translations" tab in the product data box.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function premium_shop_pt_tab( $tabs ) {
	$tabs['ps_translations'] = array(
		'label'    => __( 'Translations', 'premium-shop' ),
		'target'   => 'ps_translations_data',
		'class'    => array(),
		'priority' => 85,
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'premium_shop_pt_tab' );

/**
 * Tab content.
 */
function premium_shop_pt_panel() {
	global $post;
	$languages = premium_shop_languages();
	?>
	<div id="ps_translations_data" class="panel woocommerce_options_panel hidden">
		<?php if ( 'builtin' !== premium_shop_language_mode() ) : ?>
			<p class="form-field"><?php esc_html_e( 'A translation plugin (WPML, Polylang, TranslatePress) is active or the language switcher is turned off: translate your products with the plugin.', 'premium-shop' ); ?></p>
		<?php endif; ?>
		<p class="form-field" style="padding-right:20px"><?php esc_html_e( 'Text shown to visitors who chose this language at the top of the page. Leave a field empty to show the main product text.', 'premium-shop' ); ?></p>
		<?php foreach ( $languages as $code => $language ) : ?>
			<div class="options_group">
				<h4 style="margin:12px 12px 4px"><?php echo esc_html( $language['name'] . ' (' . $language['label'] . ')' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'    => '_ps_name_' . $code,
						'label' => __( 'Product name', 'premium-shop' ),
						'value' => (string) get_post_meta( $post->ID, '_ps_name_' . $code, true ),
					)
				);
				woocommerce_wp_textarea_input(
					array(
						'id'    => '_ps_short_' . $code,
						'label' => __( 'Short description', 'premium-shop' ),
						'value' => (string) get_post_meta( $post->ID, '_ps_short_' . $code, true ),
						'rows'  => 3,
					)
				);
				woocommerce_wp_textarea_input(
					array(
						'id'          => '_ps_desc_' . $code,
						'label'       => __( 'Description', 'premium-shop' ),
						'value'       => (string) get_post_meta( $post->ID, '_ps_desc_' . $code, true ),
						'rows'        => 8,
						'desc_tip'    => true,
						'description' => __( 'HTML allowed (paragraphs, lists, tables…).', 'premium-shop' ),
					)
				);
				?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'premium_shop_pt_panel' );

/**
 * Save the tab.
 *
 * @param WC_Product $product Product.
 */
function premium_shop_pt_save( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product form nonce.
	foreach ( array_keys( premium_shop_languages() ) as $code ) {
		if ( isset( $_POST[ '_ps_name_' . $code ] ) ) {
			$product->update_meta_data( '_ps_name_' . $code, sanitize_text_field( wp_unslash( $_POST[ '_ps_name_' . $code ] ) ) );
		}
		if ( isset( $_POST[ '_ps_short_' . $code ] ) ) {
			$product->update_meta_data( '_ps_short_' . $code, wp_kses_post( wp_unslash( $_POST[ '_ps_short_' . $code ] ) ) );
		}
		if ( isset( $_POST[ '_ps_desc_' . $code ] ) ) {
			$product->update_meta_data( '_ps_desc_' . $code, wp_kses_post( wp_unslash( $_POST[ '_ps_desc_' . $code ] ) ) );
		}
	}
	// phpcs:enable
}
add_action( 'woocommerce_admin_process_product_object', 'premium_shop_pt_save' );

/**
 * Translation fields on the category / tag edit screen.
 *
 * @param WP_Term $term Term.
 */
function premium_shop_pt_term_fields( $term ) {
	foreach ( premium_shop_languages() as $code => $language ) {
		?>
		<tr class="form-field">
			<th scope="row"><label for="ps_term_name_<?php echo esc_attr( $code ); ?>">
				<?php
				/* translators: %s: language name. */
				echo esc_html( sprintf( __( 'Name — %s', 'premium-shop' ), $language['name'] ) );
				?>
			</label></th>
			<td>
				<input type="text" id="ps_term_name_<?php echo esc_attr( $code ); ?>" name="ps_term_name[<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( (string) get_term_meta( $term->term_id, '_ps_name_' . $code, true ) ); ?>">
			</td>
		</tr>
		<?php
	}
	?>
	<tr class="form-field"><th></th><td><p class="description"><?php esc_html_e( 'Name shown to visitors who chose this language. Leave empty to show the main name.', 'premium-shop' ); ?></p><?php wp_nonce_field( 'premium_shop_term_translations', 'ps_term_nonce' ); ?></td></tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'premium_shop_pt_term_fields' );
add_action( 'product_tag_edit_form_fields', 'premium_shop_pt_term_fields' );

/**
 * Save term translations.
 *
 * @param int $term_id Term ID.
 */
function premium_shop_pt_term_save( $term_id ) {
	if ( ! isset( $_POST['ps_term_nonce'], $_POST['ps_term_name'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ps_term_nonce'] ) ), 'premium_shop_term_translations' ) || ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}
	$names = (array) wp_unslash( $_POST['ps_term_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
	foreach ( array_keys( premium_shop_languages() ) as $code ) {
		if ( isset( $names[ $code ] ) && is_scalar( $names[ $code ] ) ) {
			update_term_meta( $term_id, '_ps_name_' . $code, sanitize_text_field( (string) $names[ $code ] ) );
		}
	}
}
add_action( 'edited_product_cat', 'premium_shop_pt_term_save' );
add_action( 'edited_product_tag', 'premium_shop_pt_term_save' );

/**
 * After an import: pre-fill known category translations.
 */
function premium_shop_pt_after_import() {
	premium_shop_pt_seed_terms();
}
add_action( 'woocommerce_product_import_inserted_product_object', 'premium_shop_pt_after_import_once' );

/**
 * Seed terms once per import request.
 */
function premium_shop_pt_after_import_once() {
	static $done = false;
	if ( ! $done ) {
		$done = true;
		add_action( 'shutdown', 'premium_shop_pt_after_import' );
	}
}

/**
 * Page translations: a box under the page editor (title and text per language).
 */
function premium_shop_pt_page_box() {
	add_meta_box( 'premium-shop-page-translations', __( 'Translations', 'premium-shop' ), 'premium_shop_pt_page_box_html', 'page', 'normal', 'low' );
}
add_action( 'add_meta_boxes', 'premium_shop_pt_page_box' );

/**
 * Page translations box.
 *
 * @param WP_Post $post Page.
 */
function premium_shop_pt_page_box_html( $post ) {
	wp_nonce_field( 'premium_shop_page_translations', 'premium_shop_page_translations_nonce' );
	$default = function_exists( 'premium_shop_default_language' ) ? premium_shop_default_language() : '';
	echo '<p class="description">' . esc_html__( 'Text shown to visitors who chose this language at the top of the page. Leave a field empty to show the main text.', 'premium-shop' ) . ' ' . esc_html__( 'HTML and blocks are allowed.', 'premium-shop' ) . '</p>';
	foreach ( premium_shop_builtin_languages_list() as $code => $label ) {
		if ( $code === $default ) {
			continue;
		}
		$title = (string) get_post_meta( $post->ID, '_ps_name_' . $code, true );
		$text  = (string) get_post_meta( $post->ID, '_ps_desc_' . $code, true );
		printf(
			'<details style="margin:10px 0"%5$s><summary style="cursor:pointer;font-weight:600">%1$s</summary><p><label>%2$s<br><input type="text" class="widefat" name="ps_pt_page[%3$s][name]" value="%4$s"></label></p>',
			esc_html( $label ),
			esc_html__( 'Title', 'premium-shop' ),
			esc_attr( $code ),
			esc_attr( $title ),
			'' !== $title ? ' open' : ''
		);
		printf(
			'<p><label>%1$s<br><textarea class="widefat code" rows="10" name="ps_pt_page[%2$s][desc]">%3$s</textarea></label></p></details>',
			esc_html__( 'Text', 'premium-shop' ),
			esc_attr( $code ),
			esc_textarea( $text )
		);
	}
}

/**
 * Languages offered by the built-in switcher.
 *
 * @return array code => label.
 */
function premium_shop_builtin_languages_list() {
	return array(
		'de' => 'Deutsch',
		'fr' => 'Français',
		'en' => 'English',
		'es' => 'Español',
	);
}

/**
 * Save the page translations.
 *
 * @param int $post_id Page ID.
 */
function premium_shop_pt_page_save( $post_id ) {
	if ( ! isset( $_POST['premium_shop_page_translations_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['premium_shop_page_translations_nonce'] ) ), 'premium_shop_page_translations' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
	$data = isset( $_POST['ps_pt_page'] ) && is_array( $_POST['ps_pt_page'] ) ? wp_unslash( $_POST['ps_pt_page'] ) : array();
	foreach ( array_keys( premium_shop_builtin_languages_list() ) as $code ) {
		if ( ! isset( $data[ $code ] ) || ! is_array( $data[ $code ] ) ) {
			continue;
		}
		$name = isset( $data[ $code ]['name'] ) ? sanitize_text_field( $data[ $code ]['name'] ) : '';
		$desc = isset( $data[ $code ]['desc'] ) ? wp_kses_post( $data[ $code ]['desc'] ) : '';
		'' !== $name ? update_post_meta( $post_id, '_ps_name_' . $code, $name ) : delete_post_meta( $post_id, '_ps_name_' . $code );
		'' !== $desc ? update_post_meta( $post_id, '_ps_desc_' . $code, wp_slash( $desc ) ) : delete_post_meta( $post_id, '_ps_desc_' . $code );
	}
}
add_action( 'save_post_page', 'premium_shop_pt_page_save' );
