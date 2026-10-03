<?php
/**
 * Homepage: categories as an editorial bento grid.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( ! premium_shop_is_wc() ) {
	return;
}

$premium_shop_ids   = array_filter( array_map( 'absint', explode( ',', (string) premium_shop_option( 'home_categories_ids' ) ) ) );
$premium_shop_count = absint( premium_shop_option( 'home_categories_count' ) );

$premium_shop_args = array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'number'     => $premium_shop_count,
);

if ( $premium_shop_ids ) {
	$premium_shop_args['include'] = $premium_shop_ids;
	$premium_shop_args['orderby'] = 'include';
} else {
	$premium_shop_args['parent']  = 0;
	$premium_shop_args['orderby'] = 'menu_order';
	$premium_shop_args['exclude'] = array( (int) get_option( 'default_product_cat' ) );
}

$premium_shop_terms = get_terms( $premium_shop_args );

if ( is_wp_error( $premium_shop_terms ) || ! $premium_shop_terms ) {
	return;
}
?>
<section class="ps-section ps-categories" aria-labelledby="ps-categories-title">
	<div class="ps-container">
		<?php
		premium_shop_section_heading(
			array(
				'eyebrow' => __( 'Explore', 'premium-shop' ),
				'title'   => premium_shop_text( 'title_categories' ),
				'id'      => 'ps-categories-title',
				'link'    => premium_shop_shop_url(),
				'link_text' => __( 'All products', 'premium-shop' ),
			)
		);
		?>
		<ul class="ps-categories__grid ps-categories__grid--<?php echo esc_attr( min( 6, count( $premium_shop_terms ) ) ); ?>">
			<?php foreach ( $premium_shop_terms as $premium_shop_i => $premium_shop_term ) : ?>
				<?php $premium_shop_thumb = (int) get_term_meta( $premium_shop_term->term_id, 'thumbnail_id', true ); ?>
				<li class="ps-cat-tile<?php echo 0 === $premium_shop_i ? ' ps-cat-tile--featured' : ''; ?>" data-reveal style="--ps-delay:<?php echo esc_attr( $premium_shop_i * 70 ); ?>ms">
					<a href="<?php echo esc_url( get_term_link( $premium_shop_term ) ); ?>" class="ps-cat-tile__link">
						<span class="ps-cat-tile__media">
							<?php
							if ( $premium_shop_thumb ) {
								echo wp_get_attachment_image(
									$premium_shop_thumb,
									0 === $premium_shop_i ? 'ps-banner' : 'ps-category',
									false,
									array(
										'class'   => 'ps-cat-tile__img',
										'loading' => 'lazy',
										'alt'     => '',
										'sizes'   => 0 === $premium_shop_i ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 1024px) 25vw, 50vw',
									)
								);
							} else {
								echo '<span class="ps-cat-tile__placeholder" aria-hidden="true">' . esc_html( mb_substr( $premium_shop_term->name, 0, 1 ) ) . '</span>';
							}
							?>
						</span>
						<span class="ps-cat-tile__body">
							<span class="ps-cat-tile__name"><?php echo esc_html( $premium_shop_term->name ); ?></span>
							<span class="ps-cat-tile__count">
								<?php
								/* translators: %d: number of products. */
								echo esc_html( sprintf( _n( '%d product', '%d products', $premium_shop_term->count, 'premium-shop' ), $premium_shop_term->count ) );
								?>
							</span>
						</span>
						<span class="ps-cat-tile__arrow" aria-hidden="true"><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
