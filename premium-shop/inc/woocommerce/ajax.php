<?php
/**
 * Lightweight AJAX endpoints served through WooCommerce's fast wc-ajax
 * router (no admin-ajax overhead): live search, quick view, wishlist.
 *
 * All endpoints are read-only and return public catalog data.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Switch WPML to the requested language for AJAX queries.
 */
function premium_shop_ajax_language() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';
	if ( $lang && 'wpml' === premium_shop_multilingual_plugin() ) {
		do_action( 'wpml_switch_language', $lang );
	}
	return $lang;
}

/**
 * Live search: products (with image & price) and categories.
 */
function premium_shop_ajax_search() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
	$term = trim( mb_substr( $term, 0, 80 ) );
	$lang = premium_shop_ajax_language();

	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success( array( 'products' => array(), 'categories' => array(), 'total' => 0 ) );
	}

	$visibility = wc_get_product_visibility_term_ids();
	$args       = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		's'              => $term,
		'posts_per_page' => 6,
		'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	);

	if ( ! empty( $visibility['exclude-from-search'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array( $visibility['exclude-from-search'] ),
			'operator' => 'NOT IN',
		);
	}

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $visibility['outofstock'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => array( $visibility['outofstock'] ),
			'operator' => 'NOT IN',
		);
	}

	if ( $lang && 'polylang' === premium_shop_multilingual_plugin() ) {
		$args['lang'] = $lang;
	}

	$query = new WP_Query( apply_filters( 'premium_shop_live_search_args', $args, $term ) );
	$ids   = wp_list_pluck( $query->posts, 'ID' );

	// Exact SKU match first.
	$sku_id = wc_get_product_id_by_sku( $term );
	if ( $sku_id ) {
		$parent = wp_get_post_parent_id( $sku_id );
		$sku_id = $parent ? $parent : $sku_id;
		$ids    = array_unique( array_merge( array( $sku_id ), $ids ) );
	}

	$products = array();
	foreach ( array_slice( $ids, 0, 6 ) as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$image_id   = $product->get_image_id();
		$products[] = array(
			'id'       => $product->get_id(),
			'title'    => wp_strip_all_tags( $product->get_name() ),
			'url'      => $product->get_permalink(),
			'price'    => wp_kses_post( $product->get_price_html() ),
			'image'    => $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
			'category' => premium_shop_card_category( $product ),
			'inStock'  => $product->is_in_stock(),
		);
	}

	$terms_args = array(
		'taxonomy'   => 'product_cat',
		'name__like' => $term,
		'hide_empty' => true,
		'number'     => 4,
	);
	if ( $lang && 'polylang' === premium_shop_multilingual_plugin() ) {
		$terms_args['lang'] = $lang;
	}
	$terms      = get_terms( $terms_args );
	$categories = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $cat ) {
			$categories[] = array(
				'name'  => $cat->name,
				'url'   => get_term_link( $cat ),
				'count' => (int) $cat->count,
			);
		}
	}

	$all_url = add_query_arg(
		array(
			's'         => rawurlencode( $term ),
			'post_type' => 'product',
		),
		home_url( '/' )
	);
	if ( 'builtin' === premium_shop_language_mode() && $lang && $lang !== premium_shop_default_language() ) {
		$all_url = add_query_arg( 'lang', $lang, $all_url );
	}

	wp_send_json_success(
		array(
			'products'   => $products,
			'categories' => $categories,
			'total'      => (int) $query->found_posts,
			'allUrl'     => $all_url,
		)
	);
}
add_action( 'wc_ajax_ps_search', 'premium_shop_ajax_search' );

/**
 * Quick view HTML for one product.
 */
function premium_shop_ajax_quick_view() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
	premium_shop_ajax_language();

	$product = $id ? wc_get_product( $id ) : null;

	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() || post_password_required( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'This product is not available.', 'premium-shop' ) ), 404 );
	}

	$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['post']    = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $GLOBALS['post'] );

	ob_start();
	get_template_part( 'template-parts/products/quick-view', null, array( 'product' => $product ) );
	$html = ob_get_clean();

	wp_reset_postdata();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wc_ajax_ps_quick_view', 'premium_shop_ajax_quick_view' );

/**
 * Wishlist products as cards.
 */
function premium_shop_ajax_wishlist() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
	premium_shop_ajax_language();

	$ids = array_slice( array_filter( array_map( 'absint', explode( ',', $raw ) ) ), 0, 60 );

	if ( ! $ids ) {
		wp_send_json_success( array( 'html' => '', 'count' => 0 ) );
	}

	$query = premium_shop_product_query( 'ids', count( $ids ), array(), $ids );

	ob_start();
	premium_shop_render_products( $query, array( 'class' => 'columns-4', 'heading' => 'h2' ) );
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'  => $html,
			'count' => (int) $query->post_count,
			'ids'   => array_map( 'absint', wp_list_pluck( $query->posts, 'ID' ) ),
		)
	);
}
add_action( 'wc_ajax_ps_wishlist', 'premium_shop_ajax_wishlist' );
