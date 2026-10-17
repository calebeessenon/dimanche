<?php
/**
 * Blog sidebar.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_active_sidebar( 'blog-sidebar' ) ) {
	return;
}
?>
<aside class="ps-blog__sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'premium-shop' ); ?>">
	<?php dynamic_sidebar( 'blog-sidebar' ); ?>
</aside>
