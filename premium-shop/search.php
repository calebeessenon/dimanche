<?php
/**
 * Search results (products are shown as product cards).
 *
 * Product-only searches (post_type=product) use the WooCommerce archive
 * template with filters; this template handles global searches.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="ps-page-header">
	<div class="ps-container">
		<?php premium_shop_breadcrumbs(); ?>
		<h1 class="ps-page-header__title"><?php echo esc_html( premium_shop_archive_title() ); ?></h1>
		<div class="ps-page-header__search"><?php get_search_form(); ?></div>
	</div>
</header>

<div class="ps-container ps-search-results">
	<?php if ( have_posts() ) : ?>
		<?php
		$premium_shop_products = array();
		$premium_shop_others   = array();
		while ( have_posts() ) {
			the_post();
			if ( 'product' === get_post_type() ) {
				$premium_shop_products[] = get_the_ID();
			} else {
				$premium_shop_others[] = get_the_ID();
			}
		}
		rewind_posts();
		?>

		<?php if ( $premium_shop_products && premium_shop_is_wc() ) : ?>
			<section class="ps-section ps-section--tight" aria-labelledby="ps-search-products">
				<h2 class="ps-section-title" id="ps-search-products"><?php esc_html_e( 'Products', 'premium-shop' ); ?></h2>
				<?php premium_shop_render_products( premium_shop_product_query( 'ids', count( $premium_shop_products ), array(), $premium_shop_products ), array( 'class' => 'columns-4' ) ); ?>
			</section>
		<?php endif; ?>

		<?php if ( $premium_shop_others ) : ?>
			<section class="ps-section ps-section--tight" aria-labelledby="ps-search-content">
				<h2 class="ps-section-title" id="ps-search-content"><?php esc_html_e( 'Pages & articles', 'premium-shop' ); ?></h2>
				<div class="ps-post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						if ( 'product' === get_post_type() ) {
							continue;
						}
						get_template_part( 'template-parts/content/content', 'search' );
					endwhile;
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php premium_shop_pagination(); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
