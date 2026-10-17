<?php
/**
 * Sample firewood catalogue, created in one click from Appearance → Shop setup.
 *
 * Creates (in the shop's default language): categories with images,
 * the attributes "Log length" and "Quantity", a "Freight" shipping class
 * and ready-to-sell products with variations, automatic prices, data sheets
 * and descriptions. Products are drafts by default: check the prices,
 * replace the illustrations with your photos and publish.
 *
 * Running it again never duplicates anything (products are matched by SKU).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catalogue definition (English source texts, translated on creation).
 *
 * @return array
 */
function premium_shop_fw_catalogue() {
	return array(
		'categories' => array(
			'firewood'   => array( 'Firewood', 'buche.jpg', 'Kiln-dried split logs for stoves, fireplaces and boilers.' ),
			'kindling'   => array( 'Kindling', 'anzuendholz.jpg', 'Dry kindling to light your fire quickly and cleanly.' ),
			'briquettes' => array( 'Wood briquettes', 'briketts.jpg', 'Compressed wood briquettes with a long burning time.' ),
			'boxes'      => array( 'Firewood boxes', 'box.jpg', 'Handy boxes of firewood — ideal for apartments and small storage spaces.' ),
		),
		'products'   => array(
			array( 'sku' => 'FW-BEECH', 'name' => 'Beech firewood, kiln-dried', 'species' => 'beech', 'cat' => 'firewood', 'image' => 'buche.jpg', 'price' => 159 ),
			array( 'sku' => 'FW-OAK', 'name' => 'Oak firewood, kiln-dried', 'species' => 'oak', 'cat' => 'firewood', 'image' => 'eiche.jpg', 'price' => 149 ),
			array( 'sku' => 'FW-BIRCH', 'name' => 'Birch firewood, kiln-dried', 'species' => 'birch', 'cat' => 'firewood', 'image' => 'birke.jpg', 'price' => 169 ),
			array( 'sku' => 'FW-ASH', 'name' => 'Ash firewood, kiln-dried', 'species' => 'ash', 'cat' => 'firewood', 'image' => 'esche.jpg', 'price' => 155 ),
			array( 'sku' => 'FW-MIX', 'name' => 'Hardwood mix, kiln-dried', 'species' => 'mixed', 'cat' => 'firewood', 'image' => 'mischholz.jpg', 'price' => 139 ),
			array( 'sku' => 'FW-KINDLING', 'name' => 'Kindling, 10 kg sack', 'species' => 'spruce', 'cat' => 'kindling', 'image' => 'anzuendholz.jpg', 'simple' => 14.9, 'unit' => 'kg', 'qty' => 10, 'length' => '20' ),
			array( 'sku' => 'FW-BRIQUETTES', 'name' => 'Beech wood briquettes, 10 kg pack', 'species' => 'beech', 'cat' => 'briquettes', 'image' => 'briketts.jpg', 'simple' => 6.9, 'unit' => 'kg', 'qty' => 10, 'length' => '' ),
			array( 'sku' => 'FW-BOX', 'name' => 'Beech firewood box, approx. 30 litres', 'species' => 'beech', 'cat' => 'boxes', 'image' => 'box.jpg', 'simple' => 24.9, 'unit' => 'liter', 'qty' => 30, 'length' => '25' ),
		),
		'lengths'    => array( '25', '33', '50' ),
		'quantities' => array( 1, 2, 3, 6 ),
	);
}

/**
 * Source strings of the catalogue (only for the translation tools).
 *
 * @return array
 */
function premium_shop_fw_catalogue_i18n() {
	return array(
		__( 'Firewood', 'premium-shop' ),
		__( 'Kindling', 'premium-shop' ),
		__( 'Wood briquettes', 'premium-shop' ),
		__( 'Firewood boxes', 'premium-shop' ),
		__( 'Kiln-dried split logs for stoves, fireplaces and boilers.', 'premium-shop' ),
		__( 'Dry kindling to light your fire quickly and cleanly.', 'premium-shop' ),
		__( 'Compressed wood briquettes with a long burning time.', 'premium-shop' ),
		__( 'Handy boxes of firewood — ideal for apartments and small storage spaces.', 'premium-shop' ),
		__( 'Beech firewood, kiln-dried', 'premium-shop' ),
		__( 'Oak firewood, kiln-dried', 'premium-shop' ),
		__( 'Birch firewood, kiln-dried', 'premium-shop' ),
		__( 'Ash firewood, kiln-dried', 'premium-shop' ),
		__( 'Hardwood mix, kiln-dried', 'premium-shop' ),
		__( 'Kindling, 10 kg sack', 'premium-shop' ),
		__( 'Beech wood briquettes, 10 kg pack', 'premium-shop' ),
		__( 'Beech firewood box, approx. 30 litres', 'premium-shop' ),
		__( 'Log length', 'premium-shop' ),
		__( 'Quantity', 'premium-shop' ),
		__( 'Freight', 'premium-shop' ),
		__( 'Delivery by truck (pallets, crates, loose).', 'premium-shop' ),
	);
}

/**
 * Import a bundled image into the media library (once).
 *
 * @param string $file  File name in assets/images/firewood.
 * @param string $title Title / alt text.
 * @return int Attachment ID (0 on failure).
 */
function premium_shop_fw_import_image( $file, $title ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'meta_key'       => '_ps_bundled_image', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$source = PREMIUM_SHOP_DIR . '/assets/images/firewood/' . basename( $file );
	if ( ! is_readable( $source ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$upload = wp_upload_bits( 'ps-' . basename( $file ), null, file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	update_post_meta( $id, '_ps_bundled_image', $file );

	return (int) $id;
}

/**
 * Get or create a global attribute with terms.
 *
 * @param string $name  Attribute name.
 * @param array  $terms Term names.
 * @return array [ attribute_id, taxonomy, term ids by name ]
 */
function premium_shop_fw_attribute( $name, array $terms ) {
	$slug = substr( sanitize_title( remove_accents( $name, premium_shop_locale_for( premium_shop_default_language() ) ) ), 0, 27 );
	$id   = wc_attribute_taxonomy_id_by_name( $slug );

	if ( ! $id ) {
		$id = wc_create_attribute(
			array(
				'name'         => $name,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( is_wp_error( $id ) ) {
			return array( 0, '', array() );
		}
	}

	$taxonomy = wc_attribute_taxonomy_name( $slug );
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, array( 'product', 'product_variation' ), array( 'hierarchical' => false ) );
	}

	$ids = array();
	foreach ( $terms as $order => $term_name ) {
		$term = term_exists( $term_name, $taxonomy );
		if ( ! $term ) {
			$term = wp_insert_term( $term_name, $taxonomy );
		}
		if ( ! is_wp_error( $term ) ) {
			$ids[ $term_name ] = (int) $term['term_id'];
			update_term_meta( (int) $term['term_id'], 'order', $order );
		}
	}

	return array( (int) $id, $taxonomy, $ids );
}

/**
 * Create the catalogue.
 *
 * @param bool $publish Publish products immediately (otherwise drafts).
 * @return int Number of products created.
 */
function premium_shop_fw_create_catalogue( $publish = false ) {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
	}

	$t      = 'premium_shop_fw_t';
	$data   = premium_shop_fw_catalogue();
	$unit   = premium_shop_fw_unit_label_in_shop_language( 'rm' );
	$status = $publish ? 'publish' : 'draft';

	// Shipping class "Freight".
	$freight = get_term_by( 'slug', 'spedition', 'product_shipping_class' );
	if ( ! $freight ) {
		$created = wp_insert_term( $t( 'Freight' ), 'product_shipping_class', array( 'slug' => 'spedition', 'description' => $t( 'Delivery by truck (pallets, crates, loose).' ) ) );
		$freight = is_wp_error( $created ) ? null : get_term( $created['term_id'], 'product_shipping_class' );
	}

	// Categories.
	$cats  = array();
	$order = 0;
	foreach ( $data['categories'] as $key => $cat ) {
		$name = $t( $cat[0] );
		$term = term_exists( $name, 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'product_cat', array( 'description' => $t( $cat[2] ) ) );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$cats[ $key ] = (int) $term['term_id'];
		update_term_meta( $cats[ $key ], 'order', $order++ );
		if ( ! get_term_meta( $cats[ $key ], 'thumbnail_id', true ) ) {
			$image = premium_shop_fw_import_image( $cat[1], $name );
			if ( $image ) {
				update_term_meta( $cats[ $key ], 'thumbnail_id', $image );
			}
		}
	}

	// Attributes.
	$length_terms = array();
	foreach ( $data['lengths'] as $length ) {
		$length_terms[] = $length . ' cm';
	}
	$qty_terms = array();
	foreach ( $data['quantities'] as $qty ) {
		$qty_terms[] = number_format_i18n( $qty ) . ' ' . $unit;
	}
	list( $length_attr_id, $length_tax ) = premium_shop_fw_attribute( $t( 'Log length' ), $length_terms );
	list( $qty_attr_id, $qty_tax )       = premium_shop_fw_attribute( $t( 'Quantity' ), $qty_terms );

	$created = 0;

	foreach ( $data['products'] as $def ) {
		if ( wc_get_product_id_by_sku( $def['sku'] ) ) {
			continue;
		}

		$name  = $t( $def['name'] );
		$image = premium_shop_fw_import_image( $def['image'], $name );

		if ( isset( $def['simple'] ) ) {
			$product = new WC_Product_Simple();
			$product->set_regular_price( (string) $def['simple'] );
			$product->update_meta_data( '_ps_unit', $def['unit'] );
			$product->update_meta_data( '_ps_unit_qty', (string) $def['qty'] );
			$product->update_meta_data( '_ps_log_length', $def['length'] );
		} else {
			$product = new WC_Product_Variable();
			$product->update_meta_data( '_ps_unit', 'rm' );
			$product->update_meta_data( '_ps_log_length', '25/33/50' );
			$product->update_meta_data( '_ps_price_per_unit', (string) $def['price'] );
			$product->update_meta_data( '_ps_auto_prices', 'yes' );
			$product->update_meta_data( '_ps_disc1_qty', '3' );
			$product->update_meta_data( '_ps_disc1_pct', '5' );
			$product->update_meta_data( '_ps_disc2_qty', '6' );
			$product->update_meta_data( '_ps_disc2_pct', '10' );

			$attributes = array();
			foreach ( array( array( $length_attr_id, $length_tax ), array( $qty_attr_id, $qty_tax ) ) as $position => $attr_def ) {
				if ( ! $attr_def[0] ) {
					continue;
				}
				$attribute = new WC_Product_Attribute();
				$attribute->set_id( $attr_def[0] );
				$attribute->set_name( $attr_def[1] );
				$attribute->set_options( get_terms( array( 'taxonomy' => $attr_def[1], 'fields' => 'ids', 'hide_empty' => false ) ) );
				$attribute->set_position( $position );
				$attribute->set_visible( true );
				$attribute->set_variation( true );
				$attributes[] = $attribute;
			}
			$product->set_attributes( $attributes );
		}

		$product->set_name( $name );
		$product->set_sku( $def['sku'] );
		$product->set_status( $status );
		$product->set_catalog_visibility( 'visible' );
		$product->set_category_ids( isset( $cats[ $def['cat'] ] ) ? array( $cats[ $def['cat'] ] ) : array() );
		if ( $image ) {
			$product->set_image_id( $image );
		}
		if ( $freight ) {
			$product->set_shipping_class_id( $freight->term_id );
		}
		$product->update_meta_data( '_ps_species', $def['species'] );
		$product->update_meta_data( '_ps_moisture', '20' );
		$product->update_meta_data( '_ps_drying', 'kiln' );

		premium_shop_fw_auto_texts( $product );
		$product_id = $product->save();

		if ( $product->is_type( 'variable' ) && $length_tax && $qty_tax ) {
			$menu_order = 0;
			foreach ( get_terms( array( 'taxonomy' => $length_tax, 'hide_empty' => false ) ) as $length_term ) {
				foreach ( $data['quantities'] as $qty ) {
					$qty_term = get_term_by( 'name', number_format_i18n( $qty ) . ' ' . $unit, $qty_tax );
					if ( ! $qty_term ) {
						continue;
					}
					$variation = new WC_Product_Variation();
					$variation->set_parent_id( $product_id );
					$variation->set_attributes(
						array(
							$length_tax => $length_term->slug,
							$qty_tax    => $qty_term->slug,
						)
					);
					$variation->set_menu_order( $menu_order++ );
					$variation->set_regular_price( (string) premium_shop_fw_price_for( $product, (float) $qty ) );
					$variation->set_stock_status( 'instock' );
					$variation->save();
				}
			}
			WC_Product_Variable::sync( $product_id );
			wc_delete_product_transients( $product_id );
		}

		$created++;
	}

	return $created;
}

/**
 * Unit label in the shop's default language.
 *
 * @param string $unit Unit key.
 * @return string
 */
function premium_shop_fw_unit_label_in_shop_language( $unit ) {
	$source = array(
		'rm'  => 'stacked m³',
		'srm' => 'loose m³',
		'fm'  => 'solid m³',
	);
	return isset( $source[ $unit ] ) ? premium_shop_fw_t( $source[ $unit ] ) : $unit;
}
