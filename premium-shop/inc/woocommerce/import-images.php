<?php
/**
 * Product CSV import: never lose a product because of an image.
 *
 * WooCommerce cancels the whole product when one image URL cannot be
 * downloaded (old shop offline, hotlink protection, typo…). The theme now
 * downloads external images first: images that work are stored once and
 * reused by WooCommerce; images that fail are skipped, the product is
 * imported anyway and the skipped URLs are listed in a notice.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Attachment already imported from this URL?
 *
 * @param string $url Image URL.
 * @return int
 */
function premium_shop_import_existing_image( $url ) {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => '_wc_attachment_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Download one external image; return false when it cannot be used.
 *
 * @param string $url Image URL.
 * @return bool
 */
function premium_shop_import_prefetch_image( $url ) {
	if ( false === strpos( $url, '://' ) || premium_shop_import_existing_image( $url ) ) {
		return true; // Local file name or already imported: WooCommerce handles it.
	}

	$upload = wc_rest_upload_image_from_url( $url );
	if ( is_wp_error( $upload ) ) {
		return false;
	}

	$id = wc_rest_set_uploaded_image_as_attachment( $upload, 0 );
	if ( ! $id || is_wp_error( $id ) || ! wp_attachment_is_image( $id ) ) {
		if ( $id && ! is_wp_error( $id ) ) {
			wp_delete_attachment( $id, true );
		}
		return false;
	}

	update_post_meta( $id, '_wc_attachment_source', $url );
	return true;
}

/**
 * Remove unusable image URLs from an import row before WooCommerce uses them.
 *
 * @param array $data Parsed row.
 * @return array
 */
function premium_shop_import_filter_images( $data ) {
	if ( ! apply_filters( 'premium_shop_import_skip_broken_images', true ) ) {
		return $data;
	}

	$skipped = array();

	if ( ! empty( $data['raw_image_id'] ) && ! premium_shop_import_prefetch_image( $data['raw_image_id'] ) ) {
		$skipped[] = $data['raw_image_id'];
		unset( $data['raw_image_id'] );
	}

	if ( ! empty( $data['raw_gallery_image_ids'] ) && is_array( $data['raw_gallery_image_ids'] ) ) {
		foreach ( $data['raw_gallery_image_ids'] as $key => $url ) {
			if ( ! premium_shop_import_prefetch_image( $url ) ) {
				$skipped[] = $url;
				unset( $data['raw_gallery_image_ids'][ $key ] );
			}
		}
		$data['raw_gallery_image_ids'] = array_values( $data['raw_gallery_image_ids'] );
	}

	if ( $skipped ) {
		$log = (array) get_option( 'premium_shop_import_skipped_images', array() );
		foreach ( $skipped as $url ) {
			$log[] = array(
				'url'     => esc_url_raw( $url ),
				'product' => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			);
		}
		update_option( 'premium_shop_import_skipped_images', array_slice( $log, -300 ), false );
	}

	return $data;
}
add_filter( 'woocommerce_product_import_process_item_data', 'premium_shop_import_filter_images' );

/**
 * Notice listing the images that could not be imported.
 */
function premium_shop_import_images_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->post_type || ! current_user_can( 'edit_products' ) ) {
		return;
	}

	$log = (array) get_option( 'premium_shop_import_skipped_images', array() );
	if ( ! $log ) {
		return;
	}

	$clear = wp_nonce_url( admin_url( 'admin-post.php?action=premium_shop_clear_import_images' ), 'premium_shop_clear_import_images' );
	?>
	<div class="notice notice-warning">
		<p><strong>
			<?php
			/* translators: %d: number of images. */
			echo esc_html( sprintf( _n( '%d image could not be downloaded during the import.', '%d images could not be downloaded during the import.', count( $log ), 'premium-shop' ), count( $log ) ) );
			?>
		</strong>
		<?php esc_html_e( 'The products were imported without these images. Add them in the product, or make the image addresses reachable and import again with "Update existing products".', 'premium-shop' ); ?></p>
		<ul style="list-style:disc;margin-left:20px;max-height:180px;overflow:auto">
			<?php foreach ( array_slice( $log, 0, 50 ) as $item ) : ?>
				<li><?php echo esc_html( $item['product'] ); ?> — <code><?php echo esc_html( $item['url'] ); ?></code></li>
			<?php endforeach; ?>
		</ul>
		<p><a class="button" href="<?php echo esc_url( $clear ); ?>"><?php esc_html_e( 'Hide this list', 'premium-shop' ); ?></a></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'premium_shop_import_images_notice' );

/**
 * Clear the list.
 */
function premium_shop_clear_import_images() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'premium-shop' ), 403 );
	}
	check_admin_referer( 'premium_shop_clear_import_images' );
	delete_option( 'premium_shop_import_skipped_images' );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=product' ) );
	exit;
}
add_action( 'admin_post_premium_shop_clear_import_images', 'premium_shop_clear_import_images' );
