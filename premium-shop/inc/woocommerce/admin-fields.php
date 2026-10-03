<?php
/**
 * Admin fields: product video URL and attribute swatch colors.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product video field (General tab of the product data box).
 */
function premium_shop_product_video_field() {
	woocommerce_wp_text_input(
		array(
			'id'          => '_ps_video_url',
			'label'       => __( 'Product video', 'premium-shop' ),
			'placeholder' => 'https://www.youtube.com/watch?v=…',
			'desc_tip'    => true,
			'description' => __( 'YouTube or Vimeo link, or a video file URL (MP4). Shown as a "Watch the video" button on the product gallery.', 'premium-shop' ),
			'type'        => 'url',
		)
	);
	wp_nonce_field( 'premium_shop_product_video', 'premium_shop_product_video_nonce' );
}
add_action( 'woocommerce_product_options_general_product_data', 'premium_shop_product_video_field' );

/**
 * Save the product video field (HPOS-agnostic: uses the product object).
 *
 * @param WC_Product $product Product being saved.
 */
function premium_shop_save_product_video( $product ) {
	if ( ! isset( $_POST['premium_shop_product_video_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['premium_shop_product_video_nonce'] ) ), 'premium_shop_product_video' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
		return;
	}

	$url = isset( $_POST['_ps_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['_ps_video_url'] ) ) : '';
	$product->update_meta_data( '_ps_video_url', $url );
}
add_action( 'woocommerce_admin_process_product_object', 'premium_shop_save_product_video' );

/**
 * Register swatch color fields for every attribute taxonomy.
 */
function premium_shop_register_swatch_fields() {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return;
	}
	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
		add_action( $taxonomy . '_add_form_fields', 'premium_shop_swatch_add_field' );
		add_action( $taxonomy . '_edit_form_fields', 'premium_shop_swatch_edit_field' );
		add_action( 'created_' . $taxonomy, 'premium_shop_swatch_save' );
		add_action( 'edited_' . $taxonomy, 'premium_shop_swatch_save' );
	}
}
add_action( 'admin_init', 'premium_shop_register_swatch_fields' );

/**
 * Swatch field on "Add term".
 */
function premium_shop_swatch_add_field() {
	wp_nonce_field( 'premium_shop_swatch', 'premium_shop_swatch_nonce' );
	?>
	<div class="form-field">
		<label for="ps_swatch"><?php esc_html_e( 'Swatch color (optional)', 'premium-shop' ); ?></label>
		<input type="color" name="ps_swatch" id="ps_swatch" value="#ffffff" />
		<label><input type="checkbox" name="ps_swatch_enable" value="1" /> <?php esc_html_e( 'Use as color swatch in shop filters', 'premium-shop' ); ?></label>
	</div>
	<?php
}

/**
 * Swatch field on "Edit term".
 *
 * @param WP_Term $term Term.
 */
function premium_shop_swatch_edit_field( $term ) {
	$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'ps_swatch', true ) );
	wp_nonce_field( 'premium_shop_swatch', 'premium_shop_swatch_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ps_swatch"><?php esc_html_e( 'Swatch color (optional)', 'premium-shop' ); ?></label></th>
		<td>
			<input type="color" name="ps_swatch" id="ps_swatch" value="<?php echo esc_attr( $color ? $color : '#ffffff' ); ?>" />
			<label><input type="checkbox" name="ps_swatch_enable" value="1" <?php checked( (bool) $color ); ?> /> <?php esc_html_e( 'Use as color swatch in shop filters', 'premium-shop' ); ?></label>
		</td>
	</tr>
	<?php
}

/**
 * Save swatch color.
 *
 * @param int $term_id Term ID.
 */
function premium_shop_swatch_save( $term_id ) {
	if ( ! isset( $_POST['premium_shop_swatch_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['premium_shop_swatch_nonce'] ) ), 'premium_shop_swatch' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}

	$color = isset( $_POST['ps_swatch'] ) ? sanitize_hex_color( wp_unslash( $_POST['ps_swatch'] ) ) : '';

	if ( ! empty( $_POST['ps_swatch_enable'] ) && $color ) {
		update_term_meta( $term_id, 'ps_swatch', $color );
	} else {
		delete_term_meta( $term_id, 'ps_swatch' );
	}
}
