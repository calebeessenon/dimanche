<?php
/**
 * WooCommerce integration: supports, wrappers, styles, fragments.
 *
 * The theme relies on WooCommerce hooks rather than template overrides,
 * so WooCommerce updates stay painless. Only two templates are overridden
 * (archive-product.php and content-product.php).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce theme supports.
 */
function premium_shop_wc_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width'         => 600,
			'gallery_thumbnail_image_width' => 160,
			'single_image_width'            => 1000,
			'product_grid'                  => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'max_rows'        => 12,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'premium_shop_wc_setup' );

/**
 * Replace the default WooCommerce stylesheets with the theme's own design
 * (assets/css/woocommerce.css). Block styles (cart/checkout blocks) are kept.
 *
 * @param array $styles Styles.
 * @return array
 */
function premium_shop_wc_styles( $styles ) {
	unset( $styles['woocommerce-general'], $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'] );
	return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'premium_shop_wc_styles' );

/**
 * Content wrappers.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

add_action(
	'woocommerce_before_main_content',
	static function () {
		echo '<div class="ps-container ps-wc-main">';
	},
	10
);
add_action(
	'woocommerce_after_main_content',
	static function () {
		echo '</div>';
	},
	10
);

/**
 * Breadcrumb markup.
 *
 * @param array $defaults Defaults.
 * @return array
 */
function premium_shop_wc_breadcrumb_defaults( $defaults ) {
	$defaults['delimiter']   = '<span class="ps-breadcrumb__sep" aria-hidden="true">/</span>';
	$defaults['wrap_before'] = '<nav class="ps-breadcrumb woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'premium-shop' ) . '">';
	$defaults['wrap_after']  = '</nav>';
	$defaults['home']        = _x( 'Home', 'breadcrumb', 'premium-shop' );
	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'premium_shop_wc_breadcrumb_defaults' );

/**
 * Columns & products per page from the Customizer.
 */
add_filter(
	'loop_shop_columns',
	static function () {
		return absint( premium_shop_option( 'shop_columns' ) );
	}
);
add_filter(
	'loop_shop_per_page',
	static function () {
		return absint( premium_shop_option( 'shop_per_page' ) );
	},
	20
);

/**
 * Related & up-sell products: 4 items.
 *
 * @param array $args Args.
 * @return array
 */
function premium_shop_related_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'premium_shop_related_args' );
add_filter( 'woocommerce_upsell_display_args', 'premium_shop_related_args' );

/**
 * Pagination with icons.
 *
 * @param array $args Args.
 * @return array
 */
function premium_shop_wc_pagination_args( $args ) {
	$args['prev_text'] = premium_shop_get_icon( 'arrow-left', array( 'size' => 18 ) ) . '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'premium-shop' ) . '</span>';
	$args['next_text'] = '<span class="screen-reader-text">' . esc_html__( 'Next page', 'premium-shop' ) . '</span>' . premium_shop_get_icon( 'arrow', array( 'size' => 18 ) );
	$args['mid_size']  = 1;
	return $args;
}
add_filter( 'woocommerce_pagination_args', 'premium_shop_wc_pagination_args' );

/**
 * Cart count bubble.
 *
 * @param string $variant header|inline.
 */
function premium_shop_cart_count_html( $variant = 'header' ) {
	$count = premium_shop_cart_count();
	printf(
		'<span class="ps-cart-count ps-cart-count--%1$s" data-count="%2$d"%3$s>%2$d</span>',
		esc_attr( 'inline' === $variant || 'ps-cart-count--inline' === $variant ? 'inline' : 'header' ),
		absint( $count ),
		$count ? '' : ' hidden'
	);
}

/**
 * Free-shipping progress bar.
 */
function premium_shop_free_shipping_bar() {
	$threshold = premium_shop_free_shipping_threshold();

	if ( $threshold <= 0 || ! WC()->cart || WC()->cart->is_empty() || ! WC()->cart->needs_shipping() ) {
		echo '<div class="ps-free-shipping" hidden></div>';
		return;
	}

	$subtotal  = (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total();
	if ( WC()->cart->display_prices_including_tax() ) {
		$subtotal -= (float) WC()->cart->get_discount_tax();
	}
	$remaining = max( 0, $threshold - $subtotal );
	$percent   = min( 100, round( ( $subtotal / $threshold ) * 100 ) );
	?>
	<div class="ps-free-shipping<?php echo $remaining <= 0 ? ' is-complete' : ''; ?>">
		<p class="ps-free-shipping__text">
			<?php premium_shop_icon( 'truck', array( 'size' => 18 ) ); ?>
			<span>
			<?php
			if ( $remaining > 0 ) {
				printf(
					/* translators: %s: remaining amount. */
					esc_html__( 'Only %s left until free shipping', 'premium-shop' ),
					'<strong>' . wp_kses_post( wc_price( $remaining ) ) . '</strong>'
				);
			} else {
				esc_html_e( 'Congratulations! Your order ships for free.', 'premium-shop' );
			}
			?>
			</span>
		</p>
		<div class="ps-free-shipping__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $percent ); ?>" aria-label="<?php esc_attr_e( 'Progress towards free shipping', 'premium-shop' ); ?>">
			<span style="width:<?php echo esc_attr( $percent ); ?>%"></span>
		</div>
	</div>
	<?php
}

/**
 * Cart fragments: header count, drawer count, free-shipping bar.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function premium_shop_cart_fragments( $fragments ) {
	ob_start();
	premium_shop_cart_count_html( 'header' );
	$fragments['span.ps-cart-count--header'] = ob_get_clean();

	ob_start();
	premium_shop_cart_count_html( 'inline' );
	$fragments['span.ps-cart-count--inline'] = ob_get_clean();

	ob_start();
	premium_shop_free_shipping_bar();
	$fragments['div.ps-free-shipping'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'premium_shop_cart_fragments' );

/**
 * Show the free-shipping bar above the cart (classic cart).
 */
add_action( 'woocommerce_before_cart', 'premium_shop_free_shipping_bar', 5 );

/**
 * Placeholder image matching the design.
 *
 * @return string
 */
function premium_shop_wc_placeholder() {
	return PREMIUM_SHOP_URI . '/assets/images/placeholder.svg';
}
add_filter( 'woocommerce_placeholder_img_src', 'premium_shop_wc_placeholder' );

/**
 * Products in "on sale" listings, cached (for the homepage and filters).
 *
 * @return int[]
 */
function premium_shop_on_sale_ids() {
	$ids = wc_get_product_ids_on_sale();
	return $ids ? array_map( 'absint', $ids ) : array( 0 );
}

/**
 * Query products for homepage sections and recommendations.
 *
 * @param string $type    popular|new|sale|bestsellers|featured|ids.
 * @param int    $limit   Number of products.
 * @param array  $exclude Product IDs to exclude.
 * @param array  $include Product IDs to include (type "ids").
 * @return WP_Query
 */
function premium_shop_product_query( $type, $limit = 8, $exclude = array(), $include = array() ) {
	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => absint( $limit ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'tax_query'           => WC()->query->get_tax_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		'meta_query'          => WC()->query->get_meta_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	);

	if ( $exclude ) {
		$args['post__not_in'] = array_map( 'absint', $exclude );
	}

	switch ( $type ) {
		case 'new':
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;

		case 'sale':
			$args['post__in'] = premium_shop_on_sale_ids();
			$args['orderby']  = 'rand';
			break;

		case 'bestsellers':
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			);
			break;

		case 'popular':
			// Featured products first, then the best rated.
			$featured = wc_get_featured_product_ids();
			if ( count( $featured ) >= min( 4, $limit ) ) {
				$args['post__in'] = array_map( 'absint', $featured );
				$args['orderby']  = 'post__in';
			} else {
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = array(
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				);
			}
			break;

		case 'ids':
			$args['post__in'] = $include ? array_map( 'absint', $include ) : array( 0 );
			$args['orderby']  = 'post__in';
			break;
	}

	$args = apply_filters( 'premium_shop_product_query_args', $args, $type );

	return new WP_Query( $args );
}

/**
 * Render a list of product cards (homepage rails, recommendations, wishlist).
 *
 * @param WP_Query $query   Query.
 * @param array    $options class, heading (h2|h3).
 */
function premium_shop_render_products( $query, $options = array() ) {
	$options = wp_parse_args(
		$options,
		array(
			'class'   => '',
			'heading' => 'h3',
		)
	);

	if ( ! $query->have_posts() ) {
		return;
	}

	wc_set_loop_prop( 'ps_heading', $options['heading'] );
	wc_set_loop_prop( 'name', 'premium_shop' );

	echo '<ul class="products ps-products ' . esc_attr( $options['class'] ) . '">';
	while ( $query->have_posts() ) {
		$query->the_post();
		wc_get_template_part( 'content', 'product' );
	}
	echo '</ul>';

	wp_reset_postdata();
	wc_reset_loop();
}
