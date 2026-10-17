<?php
/**
 * Homepage: product rail (popular, new, sale, bestsellers).
 *
 * @package Premium_Shop
 *
 * @var array $args type, title (option key), eyebrow, link.
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_type  = $args['type'];
$premium_shop_query = premium_shop_product_query( $premium_shop_type, absint( premium_shop_option( 'home_products_count' ) ) );

if ( ! $premium_shop_query->have_posts() ) {
	return;
}

$premium_shop_id = 'ps-products-' . sanitize_html_class( $premium_shop_type );
?>
<section class="ps-section ps-rail-section ps-rail-section--<?php echo esc_attr( $premium_shop_type ); ?>" aria-labelledby="<?php echo esc_attr( $premium_shop_id ); ?>">
	<div class="ps-container">
		<div class="ps-rail-head">
			<?php
			premium_shop_section_heading(
				array(
					'eyebrow' => $args['eyebrow'],
					'title'   => premium_shop_text( $args['title'] ),
					'id'      => $premium_shop_id,
					'link'    => $args['link'],
				)
			);
			?>
			<div class="ps-rail-nav" aria-hidden="true">
				<button type="button" class="ps-icon-btn ps-rail-nav__btn" data-ps-rail-prev tabindex="-1"><?php premium_shop_icon( 'arrow-left', array( 'size' => 18 ) ); ?></button>
				<button type="button" class="ps-icon-btn ps-rail-nav__btn" data-ps-rail-next tabindex="-1"><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></button>
			</div>
		</div>
		<div class="ps-rail" data-ps-rail>
			<?php premium_shop_render_products( $premium_shop_query, array( 'class' => 'ps-rail__track' ) ); ?>
		</div>
	</div>
</section>
