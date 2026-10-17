<?php
/**
 * Site header.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="ps-skip-link screen-reader-text" href="#ps-main"><?php esc_html_e( 'Skip to content', 'premium-shop' ); ?></a>

<?php
if ( premium_shop_is_minimal_checkout() ) {
	get_template_part( 'template-parts/header/header', 'checkout' );
} else {
	get_template_part( 'template-parts/header/promo-bar' );
	get_template_part( 'template-parts/header/site-header' );
	get_template_part( 'template-parts/header/mobile-menu' );
	get_template_part( 'template-parts/header/search-overlay' );
	if ( premium_shop_is_wc() ) {
		get_template_part( 'template-parts/header/cart-drawer' );
	}
}
?>

<main id="ps-main" class="ps-main" tabindex="-1">
