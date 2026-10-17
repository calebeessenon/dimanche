<?php
/**
 * Site footer.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<?php
if ( premium_shop_is_minimal_checkout() ) {
	get_template_part( 'template-parts/footer/footer', 'checkout' );
} else {
	get_template_part( 'template-parts/footer/site-footer' );
}

if ( premium_shop_is_wc() ) {
	get_template_part( 'template-parts/products/quick-view-modal' );
}
?>

<div class="ps-toast" data-ps-toast role="status" aria-live="polite" hidden></div>

<?php wp_footer(); ?>
</body>
</html>
