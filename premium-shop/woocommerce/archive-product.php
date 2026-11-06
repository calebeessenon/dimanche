<?php
/**
 * The Template for displaying product archives, including the main shop page.
 *
 * Premium Shop layout: editorial header, sub-category chips, filter sidebar
 * (off-canvas on mobile), toolbar and AJAX-refreshable results.
 * All WooCommerce hooks of the original template are preserved.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Premium_Shop\WooCommerce
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked Premium Shop wrapper (opens .ps-wc-main) - 10
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

$premium_shop_filters = premium_shop_option( 'shop_filters' );
$premium_shop_subcats = array();

if ( is_product_category() || is_shop() ) {
	$premium_shop_parent  = is_product_category() ? get_queried_object_id() : 0;
	$premium_shop_subcats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $premium_shop_parent,
			'hide_empty' => true,
			'number'     => 12,
			'orderby'    => 'menu_order',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( is_wp_error( $premium_shop_subcats ) ) {
		$premium_shop_subcats = array();
	}
}
?>
<header class="ps-shop-header woocommerce-products-header">
	<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
		<h1 class="ps-shop-header__title woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_archive_description.
	 *
	 * @hooked woocommerce_taxonomy_archive_description - 10
	 * @hooked woocommerce_product_archive_description - 10
	 */
	do_action( 'woocommerce_archive_description' );
	?>

	<?php if ( $premium_shop_subcats ) : ?>
		<nav class="ps-subcats" aria-label="<?php esc_attr_e( 'Categories', 'premium-shop' ); ?>">
			<ul class="ps-chips ps-chips--scroll">
				<?php foreach ( $premium_shop_subcats as $premium_shop_term ) : ?>
					<li><a class="ps-chip" href="<?php echo esc_url( get_term_link( $premium_shop_term ) ); ?>"><?php echo esc_html( $premium_shop_term->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
</header>

<div class="ps-shop<?php echo $premium_shop_filters ? ' has-filters' : ''; ?>">
	<?php if ( $premium_shop_filters ) : ?>
		<aside class="ps-shop__sidebar ps-drawer ps-drawer--left ps-drawer--desktop-static" id="ps-shop-filters" data-ps-drawer aria-labelledby="ps-filters-title">
			<div class="ps-drawer__overlay" data-ps-close></div>
			<div class="ps-drawer__panel">
				<div class="ps-drawer__head">
					<h2 class="ps-drawer__title" id="ps-filters-title"><?php esc_html_e( 'Filters', 'premium-shop' ); ?></h2>
					<button type="button" class="ps-icon-btn ps-drawer__close" data-ps-close>
						<?php premium_shop_icon( 'close', array( 'size' => 22 ) ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Close filters', 'premium-shop' ); ?></span>
					</button>
				</div>
				<div class="ps-drawer__body" data-ps-region="filters">
					<?php premium_shop_shop_filters(); ?>
					<?php if ( is_active_sidebar( 'shop-sidebar' ) ) : ?>
						<div class="ps-shop__widgets"><?php dynamic_sidebar( 'shop-sidebar' ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</aside>
	<?php endif; ?>

	<div class="ps-shop__main" data-ps-region="results" aria-live="polite" aria-busy="false">
		<?php
		premium_shop_shop_toolbar();

		if ( woocommerce_product_loop() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @hooked woocommerce_output_all_notices - 10
			 * (result count & ordering are rendered in the toolbar)
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();

					/**
					 * Hook: woocommerce_shop_loop.
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			/**
			 * Hook: woocommerce_after_shop_loop.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );
		}
		?>
	</div>
</div>
<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked Premium Shop wrapper end - 10
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 * (the theme shows filters instead of the default sidebar)
 */
do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
