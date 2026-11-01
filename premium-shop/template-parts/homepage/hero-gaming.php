<?php
/**
 * Homepage hero of the "Gaming & tech" style: particle field, rotating
 * platform names, animated counters and a 3D stage of floating products.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_btn_url  = premium_shop_option( 'hero_button_url' ) ? premium_shop_option( 'hero_button_url' ) : premium_shop_shop_url();
$premium_shop_btn2_url = premium_shop_option( 'hero_button2_url' ) ? premium_shop_option( 'hero_button2_url' ) : add_query_arg( 'on_sale', '1', premium_shop_shop_url() );
$premium_shop_btn2     = premium_shop_text( 'hero_button2_text' );
$premium_shop_words    = apply_filters( 'premium_shop_gaming_hero_words', array( 'Nintendo Switch 2', 'PlayStation 5', 'GeForce RTX', 'Radeon RX' ) );
$premium_shop_stage    = premium_shop_gaming_stage_images();
$premium_shop_days     = absint( premium_shop_option( 'returns_days' ) );
$premium_shop_stats    = array(
	array( 24, __( 'h', 'premium-shop' ), __( 'Shipping', 'premium-shop' ) ),
	array( 100, '%', __( 'Original', 'premium-shop' ) ),
	array( 2, __( 'years', 'premium-shop' ), __( 'Warranty', 'premium-shop' ) ),
);
if ( $premium_shop_days ) {
	$premium_shop_stats[] = array( $premium_shop_days, __( 'days', 'premium-shop' ), __( 'Returns', 'premium-shop' ) );
}
?>
<section class="ps-ghero" aria-labelledby="ps-hero-title" data-ps-ghero>
	<canvas class="ps-ghero__canvas" data-ps-particles aria-hidden="true"></canvas>
	<div class="ps-ghero__aurora" aria-hidden="true"><span></span><span></span><span></span></div>
	<div class="ps-ghero__grid" aria-hidden="true"></div>

	<div class="ps-container ps-ghero__inner">
		<div class="ps-ghero__content">
			<p class="ps-ghero__badge ps-g-in" style="--d:0ms"><span class="ps-ghero__live" aria-hidden="true"></span><?php echo esc_html( premium_shop_text( 'hero_eyebrow' ) ); ?></p>
			<h1 class="ps-ghero__title ps-g-in" id="ps-hero-title" style="--d:90ms"><?php echo esc_html( premium_shop_text( 'hero_title' ) ); ?></h1>
			<p class="ps-ghero__typed ps-g-in" style="--d:180ms">
				<span class="ps-ghero__typed-label"><?php esc_html_e( 'Now in stock:', 'premium-shop' ); ?></span>
				<span class="ps-ghero__word" data-ps-typed="<?php echo esc_attr( wp_json_encode( array_values( $premium_shop_words ) ) ); ?>"><?php echo esc_html( reset( $premium_shop_words ) ); ?></span><span class="ps-ghero__caret" aria-hidden="true"></span>
			</p>
			<p class="ps-ghero__subtitle ps-g-in" style="--d:260ms"><?php echo esc_html( premium_shop_text( 'hero_subtitle' ) ); ?></p>
			<div class="ps-ghero__actions ps-g-in" style="--d:340ms">
				<a class="ps-gbtn ps-gbtn--primary" href="<?php echo esc_url( $premium_shop_btn_url ); ?>" data-ps-magnetic>
					<span><?php echo esc_html( premium_shop_text( 'hero_button_text' ) ); ?></span>
					<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
				</a>
				<?php if ( $premium_shop_btn2 ) : ?>
					<a class="ps-gbtn ps-gbtn--ghost" href="<?php echo esc_url( $premium_shop_btn2_url ); ?>" data-ps-magnetic>
						<?php premium_shop_icon( 'tag', array( 'size' => 18 ) ); ?>
						<span><?php echo esc_html( $premium_shop_btn2 ); ?></span>
					</a>
				<?php endif; ?>
			</div>
			<ul class="ps-ghero__stats ps-g-in" style="--d:420ms">
				<?php foreach ( $premium_shop_stats as $premium_shop_stat ) : ?>
					<li>
						<strong><span data-ps-count="<?php echo (int) $premium_shop_stat[0]; ?>"><?php echo (int) $premium_shop_stat[0]; ?></span><small><?php echo esc_html( $premium_shop_stat[1] ); ?></small></strong>
						<span><?php echo esc_html( $premium_shop_stat[2] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="ps-ghero__stage" data-ps-stage aria-hidden="true">
			<span class="ps-ghero__ring ps-ghero__ring--1"></span>
			<span class="ps-ghero__ring ps-ghero__ring--2"></span>
			<span class="ps-ghero__floor"></span>
			<?php foreach ( $premium_shop_stage as $premium_shop_i => $premium_shop_src ) : ?>
				<figure class="ps-ghero__item ps-ghero__item--<?php echo (int) $premium_shop_i + 1; ?>" data-depth="<?php echo esc_attr( array( '0.9', '0.5', '1.3', '0.7' )[ $premium_shop_i % 4 ] ); ?>">
					<img src="<?php echo esc_url( $premium_shop_src ); ?>" alt="" <?php echo 0 === $premium_shop_i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
				</figure>
			<?php endforeach; ?>
			<span class="ps-ghero__spark ps-ghero__spark--1"></span>
			<span class="ps-ghero__spark ps-ghero__spark--2"></span>
			<span class="ps-ghero__spark ps-ghero__spark--3"></span>
		</div>
	</div>

	<a class="ps-ghero__scroll" href="#ps-g-next" aria-label="<?php esc_attr_e( 'Scroll down', 'premium-shop' ); ?>"><span></span></a>
</section>
<span id="ps-g-next"></span>
