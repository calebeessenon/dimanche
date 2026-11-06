<?php
/**
 * Homepage: partner brands (logos from the Customizer, or WooCommerce brands).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_brands = array();

for ( $premium_shop_i = 1; $premium_shop_i <= 8; $premium_shop_i++ ) {
	$premium_shop_logo = absint( premium_shop_option( 'brand_' . $premium_shop_i . '_logo' ) );
	if ( $premium_shop_logo ) {
		$premium_shop_brands[] = array(
			'image' => $premium_shop_logo,
			'url'   => premium_shop_option( 'brand_' . $premium_shop_i . '_url' ),
			'name'  => get_post_meta( $premium_shop_logo, '_wp_attachment_image_alt', true ),
		);
	}
}

if ( ! $premium_shop_brands && premium_shop_is_wc() && premium_shop_brand_taxonomy() ) {
	$premium_shop_terms = get_terms(
		array(
			'taxonomy'   => premium_shop_brand_taxonomy(),
			'hide_empty' => true,
			'number'     => 8,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( ! is_wp_error( $premium_shop_terms ) ) {
		foreach ( $premium_shop_terms as $premium_shop_term ) {
			$premium_shop_brands[] = array(
				'image' => (int) get_term_meta( $premium_shop_term->term_id, 'thumbnail_id', true ),
				'url'   => get_term_link( $premium_shop_term ),
				'name'  => $premium_shop_term->name,
			);
		}
	}
}

if ( ! $premium_shop_brands ) {
	if ( is_customize_preview() ) {
		echo '<section class="ps-section ps-brands"><div class="ps-container"><p class="ps-preview-hint">' . esc_html__( 'Upload partner logos in Premium Shop → Homepage — brands, or create brands in Products → Brands. (This hint is only visible in the Customizer.)', 'premium-shop' ) . '</p></div></section>';
	}
	return;
}

$premium_shop_marquee = count( $premium_shop_brands ) >= 5;
?>
<section class="ps-section ps-brands" aria-labelledby="ps-brands-title">
	<div class="ps-container">
		<h2 class="ps-brands__title" id="ps-brands-title"><?php echo esc_html( premium_shop_text( 'title_brands' ) ); ?></h2>
	</div>
	<div class="ps-brands__viewport<?php echo $premium_shop_marquee ? ' is-marquee' : ''; ?>">
		<?php for ( $premium_shop_loop = 0; $premium_shop_loop < ( $premium_shop_marquee ? 2 : 1 ); $premium_shop_loop++ ) : ?>
			<ul class="ps-brands__list"<?php echo $premium_shop_loop ? ' aria-hidden="true"' : ''; ?>>
				<?php foreach ( $premium_shop_brands as $premium_shop_brand ) : ?>
					<li class="ps-brands__item">
						<?php
						$premium_shop_inner = $premium_shop_brand['image']
							? wp_get_attachment_image(
								$premium_shop_brand['image'],
								'ps-brand',
								false,
								array(
									'class'   => 'ps-brands__logo',
									'loading' => 'lazy',
									'alt'     => $premium_shop_brand['name'],
								)
							)
							: '<span class="ps-brands__name">' . esc_html( $premium_shop_brand['name'] ) . '</span>';

						if ( $premium_shop_brand['url'] && ! is_wp_error( $premium_shop_brand['url'] ) ) {
							printf(
								'<a href="%1$s"%3$s>%2$s</a>',
								esc_url( $premium_shop_brand['url'] ),
								$premium_shop_inner, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
								$premium_shop_loop ? ' tabindex="-1"' : ''
							);
						} else {
							echo $premium_shop_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
						}
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endfor; ?>
	</div>
</section>
