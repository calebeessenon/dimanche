<?php
/**
 * Homepage: FAQ.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_items = premium_shop_faq_items();
if ( ! $premium_shop_items ) {
	return;
}
?>
<section class="ps-section ps-faq" id="ps-faq" aria-labelledby="ps-faq-title">
	<div class="ps-container ps-faq__inner">
		<?php
		premium_shop_section_heading(
			array(
				'eyebrow' => __( 'Good to know', 'premium-shop' ),
				'title'   => premium_shop_text( 'title_faq' ),
				'id'      => 'ps-faq-title',
				'align'   => 'center',
			)
		);
		?>
		<div data-reveal><?php premium_shop_faq_list( $premium_shop_items ); ?></div>
	</div>
</section>
