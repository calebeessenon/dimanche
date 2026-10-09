<?php
/**
 * Styles & scripts — loaded conditionally, deferred, no heavy libraries.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asset URL, using the minified build unless SCRIPT_DEBUG is on.
 *
 * @param string $path Path relative to /assets, without extension (e.g. "css/main").
 * @param string $ext  css|js.
 * @return string
 */
function premium_shop_asset( $path, $ext ) {
	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	if ( $min && ! file_exists( PREMIUM_SHOP_DIR . '/assets/' . $path . $min . '.' . $ext ) ) {
		$min = '';
	}

	return PREMIUM_SHOP_URI . '/assets/' . $path . $min . '.' . $ext;
}

/**
 * Is the current request a shop listing (shop, category, tag, attribute, product search)?
 *
 * @return bool
 */
function premium_shop_is_shop_listing() {
	if ( ! premium_shop_is_wc() ) {
		return false;
	}

	return is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
}

/**
 * Enqueue front-end assets.
 */
function premium_shop_enqueue_assets() {
	$version = PREMIUM_SHOP_VERSION;

	wp_enqueue_style( 'premium-shop', premium_shop_asset( 'css/main', 'css' ), array(), $version );
	wp_add_inline_style( 'premium-shop', premium_shop_dynamic_css() );

	if ( premium_shop_is_wc() ) {
		wp_enqueue_style( 'premium-shop-woocommerce', premium_shop_asset( 'css/woocommerce', 'css' ), array( 'premium-shop' ), $version );
		// Keep the header cart count and the side cart in sync on every page.
		wp_enqueue_script( 'wc-cart-fragments' );
		// Quick view: variation form of variable products (WooCommerce's own script).
		if ( premium_shop_option( 'card_quick_view' ) && ! is_cart() && ! is_checkout() ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}
	}

	wp_enqueue_script(
		'premium-shop',
		premium_shop_asset( 'js/theme', 'js' ),
		array(),
		$version,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	$wc_ajax = premium_shop_is_wc() && class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '';

	wp_localize_script(
		'premium-shop',
		'premiumShop',
		array(
			'wcAjax'      => $wc_ajax,
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'lang'        => premium_shop_current_language(),
			'cartDrawer'  => (bool) premium_shop_option( 'cart_drawer' ),
			'wishlist'    => (bool) premium_shop_option( 'card_wishlist' ),
			'reducedMotion' => ! premium_shop_option( 'animations' ),
			'newsletterNonce' => wp_create_nonce( 'premium_shop_newsletter' ),
			'i18n'        => array(
				'searching'      => __( 'Searching…', 'premium-shop' ),
				'noResults'      => __( 'No products found.', 'premium-shop' ),
				'viewAll'        => __( 'View all results', 'premium-shop' ),
				'products'       => __( 'Products', 'premium-shop' ),
				'categories'     => __( 'Categories', 'premium-shop' ),
				'addedCart'      => __( 'Added to your cart', 'premium-shop' ),
				'addedWishlist'  => __( 'Added to your wishlist', 'premium-shop' ),
				'removedWishlist'=> __( 'Removed from your wishlist', 'premium-shop' ),
				'wishlistEmpty'  => __( 'Your wishlist is empty.', 'premium-shop' ),
				'loading'        => __( 'Loading…', 'premium-shop' ),
				'error'          => __( 'Something went wrong. Please try again.', 'premium-shop' ),
				'resultsUpdated' => __( 'Results updated', 'premium-shop' ),
				'close'          => __( 'Close', 'premium-shop' ),
			),
		)
	);

	if ( premium_shop_is_shop_listing() ) {
		wp_enqueue_script(
			'premium-shop-shop',
			premium_shop_asset( 'js/shop', 'js' ),
			array( 'premium-shop' ),
			$version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'premium-shop-shop',
			'premiumShopShop',
			array(
				'ajax'        => (bool) premium_shop_option( 'shop_ajax' ),
				'defaultView' => premium_shop_option( 'shop_view' ),
			)
		);
	}

	if ( premium_shop_is_wc() && is_product() ) {
		wp_enqueue_script(
			'premium-shop-product',
			premium_shop_asset( 'js/product', 'js' ),
			array( 'premium-shop' ),
			$version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) && ! ( premium_shop_is_wc() && is_product() ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'premium_shop_enqueue_assets' );

/**
 * Preload the fonts actually used (they are tiny variable WOFF2 files,
 * self-hosted: no request to Google, GDPR-friendly).
 */
function premium_shop_preload_fonts() {
	$fonts = array();

	if ( 'fraunces' === premium_shop_option( 'font_heading' ) ) {
		$fonts[] = 'fraunces-var-latin.woff2';
	}
	if ( 'inter' === premium_shop_option( 'font_body' ) || 'inter' === premium_shop_option( 'font_heading' ) ) {
		$fonts[] = 'inter-var-latin.woff2';
	}

	foreach ( array_unique( $fonts ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( PREMIUM_SHOP_URI . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'premium_shop_preload_fonts', 2 );

/**
 * Mark "no-js" → "js" as early as possible to avoid layout shifts.
 */
function premium_shop_js_detection() {
	echo "<script>document.documentElement.classList.replace('no-js','js');</script>\n";
}
add_action( 'wp_head', 'premium_shop_js_detection', 0 );

/**
 * Block editor: palette-aware editor styles.
 */
function premium_shop_block_editor_assets() {
	wp_add_inline_style( 'wp-edit-blocks', premium_shop_dynamic_css() );
}
add_action( 'enqueue_block_editor_assets', 'premium_shop_block_editor_assets' );
