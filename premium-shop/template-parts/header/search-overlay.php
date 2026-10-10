<?php
/**
 * Search panel opened from the header.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( ! premium_shop_option( 'header_search' ) ) {
	return;
}
?>
<div class="ps-drawer ps-drawer--top ps-search-panel" id="ps-search" data-ps-drawer role="dialog" aria-modal="true" aria-labelledby="ps-search-title" hidden>
	<div class="ps-drawer__overlay" data-ps-close></div>
	<div class="ps-drawer__panel">
		<div class="ps-container ps-search-panel__inner">
			<div class="ps-search-panel__head">
				<p class="ps-search-panel__title" id="ps-search-title"><?php esc_html_e( 'Search', 'premium-shop' ); ?></p>
				<button type="button" class="ps-icon-btn" data-ps-close>
					<?php premium_shop_icon( 'close', array( 'size' => 22 ) ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Close search', 'premium-shop' ); ?></span>
				</button>
			</div>

			<?php get_template_part( 'template-parts/components/search-form', null, array( 'id' => 'ps-search-main', 'size' => 'large' ) ); ?>

			<?php
			if ( premium_shop_is_wc() ) :
				$premium_shop_terms = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'number'     => 8,
						'orderby'    => 'count',
						'order'      => 'DESC',
						'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
					)
				);
				if ( ! is_wp_error( $premium_shop_terms ) && $premium_shop_terms ) :
					?>
					<div class="ps-search-panel__suggest">
						<p class="ps-eyebrow"><?php esc_html_e( 'Popular categories', 'premium-shop' ); ?></p>
						<ul class="ps-chips">
							<?php foreach ( $premium_shop_terms as $premium_shop_term ) : ?>
								<li><a class="ps-chip" href="<?php echo esc_url( get_term_link( $premium_shop_term ) ); ?>"><?php echo esc_html( $premium_shop_term->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php
				endif;
			endif;
			?>
		</div>
	</div>
</div>
