<?php
/**
 * Homepage: sections assembled from the Customizer
 * (Premium Shop → Homepage — sections & order).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();

$premium_shop_product_sections = array(
	'popular'     => array( 'title' => 'title_popular', 'eyebrow' => __( 'Customer favourites', 'premium-shop' ), 'link' => add_query_arg( 'orderby', 'rating', premium_shop_shop_url() ) ),
	'new'         => array( 'title' => 'title_new', 'eyebrow' => __( 'Just arrived', 'premium-shop' ), 'link' => add_query_arg( 'orderby', 'date', premium_shop_shop_url() ) ),
	'sale'        => array( 'title' => 'title_sale', 'eyebrow' => __( 'Limited time', 'premium-shop' ), 'link' => add_query_arg( 'on_sale', '1', premium_shop_shop_url() ) ),
	'bestsellers' => array( 'title' => 'title_bestsellers', 'eyebrow' => __( 'Most loved', 'premium-shop' ), 'link' => add_query_arg( 'orderby', 'popularity', premium_shop_shop_url() ) ),
);

foreach ( premium_shop_home_sections() as $premium_shop_section ) {
	if ( isset( $premium_shop_product_sections[ $premium_shop_section ] ) ) {
		if ( premium_shop_is_wc() ) {
			get_template_part(
				'template-parts/homepage/products',
				null,
				array_merge( $premium_shop_product_sections[ $premium_shop_section ], array( 'type' => $premium_shop_section ) )
			);
		}
		continue;
	}

	get_template_part( 'template-parts/homepage/' . $premium_shop_section );
}

get_footer();
