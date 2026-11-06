<?php
/**
 * 404 page — helps the visitor back into the shop.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="ps-404">
	<div class="ps-container ps-container--narrow ps-404__inner">
		<p class="ps-404__code" aria-hidden="true">404</p>
		<h1 class="ps-404__title"><?php esc_html_e( 'This page could not be found', 'premium-shop' ); ?></h1>
		<p class="ps-404__text"><?php esc_html_e( 'The page may have moved or no longer exists. Try a search or discover our products.', 'premium-shop' ); ?></p>
		<div class="ps-404__search"><?php get_search_form(); ?></div>
		<div class="ps-404__actions">
			<a class="ps-btn ps-btn--primary" href="<?php echo esc_url( premium_shop_is_wc() ? premium_shop_shop_url() : home_url( '/' ) ); ?>">
				<span><?php echo premium_shop_is_wc() ? esc_html__( 'Go to the shop', 'premium-shop' ) : esc_html__( 'Back to the homepage', 'premium-shop' ); ?></span>
				<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
			</a>
		</div>
	</div>
</section>

<?php
if ( premium_shop_is_wc() ) {
	get_template_part(
		'template-parts/homepage/products',
		null,
		array(
			'type'    => 'bestsellers',
			'title'   => 'title_bestsellers',
			'eyebrow' => __( 'Most loved', 'premium-shop' ),
			'link'    => add_query_arg( 'orderby', 'popularity', premium_shop_shop_url() ),
		)
	);
}

get_footer();
