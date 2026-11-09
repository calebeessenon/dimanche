<?php
/**
 * Homepage: campaign banner (editorial split).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_image = absint( premium_shop_option( 'campaign_image' ) );
$premium_shop_url   = premium_shop_option( 'campaign_button_url' ) ? premium_shop_option( 'campaign_button_url' ) : add_query_arg( 'on_sale', '1', premium_shop_shop_url() );

if ( ! $premium_shop_image && premium_shop_is_wc() ) {
	$premium_shop_sale = array_slice( wc_get_product_ids_on_sale(), 0, 10 );
	foreach ( $premium_shop_sale as $premium_shop_pid ) {
		$premium_shop_parent = wp_get_post_parent_id( $premium_shop_pid );
		$premium_shop_thumb  = get_post_thumbnail_id( $premium_shop_parent ? $premium_shop_parent : $premium_shop_pid );
		if ( $premium_shop_thumb ) {
			$premium_shop_image = (int) $premium_shop_thumb;
			break;
		}
	}
}
?>
<section class="ps-section ps-campaign" aria-labelledby="ps-campaign-title">
	<div class="ps-container">
		<div class="ps-campaign__card" data-reveal>
			<div class="ps-campaign__media">
				<?php
				if ( $premium_shop_image ) {
					echo wp_get_attachment_image(
						$premium_shop_image,
						'ps-banner',
						false,
						array(
							'class'   => 'ps-campaign__img',
							'loading' => 'lazy',
							'alt'     => '',
							'sizes'   => '(min-width: 1024px) 50vw, 100vw',
						)
					);
				} else {
					echo '<span class="ps-campaign__pattern" aria-hidden="true"></span>';
				}
				?>
				<span class="ps-campaign__stamp" aria-hidden="true">
					<span>%</span>
				</span>
			</div>
			<div class="ps-campaign__content">
				<p class="ps-eyebrow"><?php echo esc_html( premium_shop_text( 'campaign_eyebrow' ) ); ?></p>
				<h2 class="ps-campaign__title" id="ps-campaign-title"><?php echo esc_html( premium_shop_text( 'campaign_title' ) ); ?></h2>
				<p class="ps-campaign__text"><?php echo esc_html( premium_shop_text( 'campaign_text' ) ); ?></p>
				<a class="ps-btn ps-btn--light ps-btn--lg" href="<?php echo esc_url( $premium_shop_url ); ?>">
					<span><?php echo esc_html( premium_shop_text( 'campaign_button_text' ) ); ?></span>
					<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
				</a>
			</div>
		</div>
	</div>
</section>
