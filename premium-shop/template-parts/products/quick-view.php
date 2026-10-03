<?php
/**
 * Quick view content (loaded via AJAX into the modal).
 *
 * Simple products can be added to the cart directly; other types link to
 * the product page to choose options.
 *
 * @package Premium_Shop
 *
 * @var array $args product.
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $premium_shop_product */
$premium_shop_product = $args['product'];
$premium_shop_gallery = array_slice( array_filter( array_merge( array( $premium_shop_product->get_image_id() ), $premium_shop_product->get_gallery_image_ids() ) ), 0, 5 );
?>
<div class="ps-qv">
	<div class="ps-qv__media">
		<?php premium_shop_product_badges( $premium_shop_product ); ?>
		<div class="ps-qv__slides" data-ps-qv-slides>
			<?php if ( $premium_shop_gallery ) : ?>
				<?php foreach ( $premium_shop_gallery as $premium_shop_i => $premium_shop_image ) : ?>
					<?php
					echo wp_get_attachment_image(
						(int) $premium_shop_image,
						'woocommerce_single',
						false,
						array(
							'class'   => 'ps-qv__img',
							'loading' => 0 === $premium_shop_i ? 'eager' : 'lazy',
						)
					);
					?>
				<?php endforeach; ?>
			<?php else : ?>
				<?php echo wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<?php if ( count( $premium_shop_gallery ) > 1 ) : ?>
			<div class="ps-qv__dots" aria-hidden="true">
				<?php foreach ( $premium_shop_gallery as $premium_shop_i => $premium_shop_image ) : ?>
					<span class="ps-qv__dot<?php echo 0 === $premium_shop_i ? ' is-active' : ''; ?>"></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="ps-qv__summary">
		<?php $premium_shop_cat = premium_shop_card_category( $premium_shop_product ); ?>
		<?php if ( $premium_shop_cat ) : ?>
			<p class="ps-eyebrow"><?php echo esc_html( $premium_shop_cat ); ?></p>
		<?php endif; ?>

		<h2 class="ps-qv__title" id="ps-qv-title"><?php echo esc_html( $premium_shop_product->get_name() ); ?></h2>

		<?php if ( $premium_shop_product->get_average_rating() > 0 && wc_review_ratings_enabled() ) : ?>
			<div class="ps-qv__rating">
				<?php echo wc_get_rating_html( $premium_shop_product->get_average_rating(), $premium_shop_product->get_rating_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>(<?php echo esc_html( $premium_shop_product->get_rating_count() ); ?>)</span>
			</div>
		<?php endif; ?>

		<p class="ps-qv__price price"><?php echo $premium_shop_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

		<div class="ps-qv__desc">
			<?php echo wp_kses_post( wpautop( wp_trim_words( $premium_shop_product->get_short_description() ? $premium_shop_product->get_short_description() : $premium_shop_product->get_description(), 45 ) ) ); ?>
		</div>

		<?php premium_shop_stock_label( $premium_shop_product ); ?>

		<div class="ps-qv__actions">
			<?php if ( $premium_shop_product->is_type( 'simple' ) && $premium_shop_product->is_purchasable() && $premium_shop_product->is_in_stock() ) : ?>
				<form class="cart ps-qv__form" action="<?php echo esc_url( $premium_shop_product->get_permalink() ); ?>" method="post" enctype="multipart/form-data">
					<?php
					woocommerce_quantity_input(
						array(
							'min_value'   => $premium_shop_product->get_min_purchase_quantity(),
							'max_value'   => $premium_shop_product->get_max_purchase_quantity(),
							'input_value' => $premium_shop_product->get_min_purchase_quantity(),
						),
						$premium_shop_product
					);
					?>
					<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $premium_shop_product->get_id() ); ?>" class="ps-btn ps-btn--primary single_add_to_cart_button" data-ps-qv-add="<?php echo esc_attr( $premium_shop_product->get_id() ); ?>">
						<?php premium_shop_icon( 'bag', array( 'size' => 18 ) ); ?>
						<span><?php echo esc_html( $premium_shop_product->single_add_to_cart_text() ); ?></span>
					</button>
				</form>
			<?php endif; ?>
			<a class="ps-btn ps-btn--ghost" href="<?php echo esc_url( $premium_shop_product->get_permalink() ); ?>">
				<?php echo $premium_shop_product->is_type( 'simple' ) ? esc_html__( 'View details', 'premium-shop' ) : esc_html__( 'Choose options', 'premium-shop' ); ?>
				<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
			</a>
		</div>
	</div>
</div>
