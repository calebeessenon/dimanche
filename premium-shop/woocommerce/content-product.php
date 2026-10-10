<?php
/**
 * The template for displaying product content within loops.
 *
 * Premium Shop card: two images, badges, quick view, wishlist, rating,
 * stock and add-to-cart. All WooCommerce loop hooks are preserved.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Premium_Shop\WooCommerce
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$premium_shop_heading = wc_get_loop_prop( 'ps_heading' );
$premium_shop_heading = in_array( $premium_shop_heading, array( 'h2', 'h3' ), true ) ? $premium_shop_heading : 'h2';
$premium_shop_link    = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
$premium_shop_cat     = premium_shop_option( 'card_category' ) ? premium_shop_card_category( $product ) : '';
$premium_shop_button  = 'hover' === premium_shop_option( 'card_button' ) ? 'hover' : 'visible';
?>
<li <?php wc_product_class( 'ps-card', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

	<div class="ps-card__media">
		<a class="ps-card__image" href="<?php echo esc_url( $premium_shop_link ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core image markup.
			echo $product->get_image(
				'woocommerce_thumbnail',
				array(
					'class'    => 'ps-card__img ps-card__img--main',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			premium_shop_card_hover_image( $product );
			?>
		</a>

		<?php premium_shop_product_badges( $product ); ?>
		<?php premium_shop_card_actions( $product ); ?>

		<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>

		<?php if ( 'hover' === $premium_shop_button ) : ?>
			<div class="ps-card__cta">
				<?php woocommerce_template_loop_add_to_cart(); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="ps-card__body">
		<?php if ( $premium_shop_cat ) : ?>
			<p class="ps-card__cat"><?php echo esc_html( $premium_shop_cat ); ?></p>
		<?php endif; ?>

		<<?php echo esc_html( $premium_shop_heading ); ?> class="ps-card__title woocommerce-loop-product__title">
			<a href="<?php echo esc_url( $premium_shop_link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</<?php echo esc_html( $premium_shop_heading ); ?>>

		<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>

		<?php if ( $product->get_average_rating() > 0 && wc_review_ratings_enabled() ) : ?>
			<div class="ps-card__rating">
				<?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce escapes. ?>
				<span class="ps-card__rating-count">(<?php echo esc_html( $product->get_rating_count() ); ?>)</span>
			</div>
		<?php endif; ?>

		<div class="ps-card__price price">
			<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price markup. ?>
		</div>

		<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>

		<?php
		if ( premium_shop_option( 'card_stock' ) ) {
			premium_shop_stock_label( $product );
		}
		?>

		<div class="ps-card__excerpt">
			<?php echo wp_kses_post( wp_trim_words( $product->get_short_description(), 22 ) ); ?>
		</div>

		<?php if ( 'visible' === $premium_shop_button ) : ?>
			<div class="ps-card__cta ps-card__cta--static">
				<?php woocommerce_template_loop_add_to_cart(); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
</li>
