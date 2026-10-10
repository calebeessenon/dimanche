<?php
/**
 * Homepage hero.
 *
 * Editorial split layout (text + arched image) or full-width cover.
 * Without an uploaded image, a featured product image is used, otherwise
 * an abstract artwork keeps the hero beautiful from day one.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_layout   = premium_shop_option( 'hero_layout' );
$premium_shop_image_id = absint( premium_shop_option( 'hero_image' ) );

$premium_shop_bundled = ( ! $premium_shop_image_id && 'firewood' === premium_shop_preset() ) ? PREMIUM_SHOP_URI . '/assets/images/firewood/hero.jpg' : '';

if ( ! $premium_shop_image_id && ! $premium_shop_bundled && premium_shop_is_wc() ) {
	$premium_shop_featured = wc_get_featured_product_ids();
	foreach ( array_slice( $premium_shop_featured, 0, 5 ) as $premium_shop_fid ) {
		$premium_shop_thumb = get_post_thumbnail_id( $premium_shop_fid );
		if ( $premium_shop_thumb ) {
			$premium_shop_image_id = (int) $premium_shop_thumb;
			break;
		}
	}
}

if ( 'cover' === $premium_shop_layout && ! $premium_shop_image_id ) {
	$premium_shop_layout = 'split';
}

$premium_shop_btn_url  = premium_shop_option( 'hero_button_url' ) ? premium_shop_option( 'hero_button_url' ) : premium_shop_shop_url();
$premium_shop_btn2_url = premium_shop_option( 'hero_button2_url' ) ? premium_shop_option( 'hero_button2_url' ) : add_query_arg( 'on_sale', '1', premium_shop_shop_url() );
$premium_shop_btn2     = premium_shop_text( 'hero_button2_text' );
$premium_shop_overlay  = absint( premium_shop_option( 'hero_overlay' ) ) / 100;
$premium_shop_days     = absint( premium_shop_option( 'returns_days' ) );
$premium_shop_free     = premium_shop_free_shipping_threshold();
$premium_shop_title    = premium_shop_text( 'hero_title' );
?>
<section class="ps-hero ps-hero--<?php echo esc_attr( $premium_shop_layout ); ?>" aria-labelledby="ps-hero-title">
	<?php if ( 'cover' === $premium_shop_layout ) : ?>
		<div class="ps-hero__bg">
			<?php
			echo wp_get_attachment_image(
				$premium_shop_image_id,
				'full',
				false,
				array(
					'class'         => 'ps-hero__bg-img',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'sizes'         => '100vw',
					'alt'           => '',
				)
			);
			?>
			<span class="ps-hero__shade" style="--ps-overlay:<?php echo esc_attr( $premium_shop_overlay ); ?>"></span>
		</div>
	<?php endif; ?>

	<div class="ps-container ps-hero__inner">
		<div class="ps-hero__content">
			<p class="ps-eyebrow ps-hero__eyebrow ps-anim" style="--ps-delay:0ms"><?php echo esc_html( premium_shop_text( 'hero_eyebrow' ) ); ?></p>
			<h1 class="ps-hero__title ps-anim" id="ps-hero-title" style="--ps-delay:90ms"><?php echo nl2br( esc_html( $premium_shop_title ) ); ?></h1>
			<p class="ps-hero__subtitle ps-anim" style="--ps-delay:180ms"><?php echo esc_html( premium_shop_text( 'hero_subtitle' ) ); ?></p>
			<div class="ps-hero__actions ps-anim" style="--ps-delay:270ms">
				<a class="ps-btn ps-btn--primary ps-btn--lg" href="<?php echo esc_url( $premium_shop_btn_url ); ?>">
					<span><?php echo esc_html( premium_shop_text( 'hero_button_text' ) ); ?></span>
					<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
				</a>
				<?php if ( $premium_shop_btn2 ) : ?>
					<a class="ps-btn ps-btn--ghost ps-btn--lg" href="<?php echo esc_url( $premium_shop_btn2_url ); ?>">
						<span><?php echo esc_html( $premium_shop_btn2 ); ?></span>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( premium_shop_option( 'hero_badges' ) && ( $premium_shop_free > 0 || $premium_shop_days ) ) : ?>
				<ul class="ps-hero__facts ps-anim" style="--ps-delay:360ms">
					<?php if ( $premium_shop_free > 0 ) : ?>
						<li><?php premium_shop_icon( 'truck', array( 'size' => 18 ) ); ?>
							<?php
							/* translators: %s: amount. */
							echo esc_html( sprintf( __( 'Free shipping from %s', 'premium-shop' ), premium_shop_plain_price( $premium_shop_free ) ) );
							?>
						</li>
					<?php endif; ?>
					<?php if ( $premium_shop_days ) : ?>
						<li><?php premium_shop_icon( 'return', array( 'size' => 18 ) ); ?>
							<?php
							/* translators: %d: number of days. */
							echo esc_html( sprintf( __( '%d-day free returns', 'premium-shop' ), $premium_shop_days ) );
							?>
						</li>
					<?php endif; ?>
					<li><?php premium_shop_icon( 'lock', array( 'size' => 18 ) ); ?><?php esc_html_e( 'Secure payment', 'premium-shop' ); ?></li>
				</ul>
			<?php endif; ?>
		</div>

		<?php
		// No image chosen: the shop's own product photos, floating (instead of the illustration).
		$premium_shop_collage = ( 'split' === $premium_shop_layout && ! absint( premium_shop_option( 'hero_image' ) ) && function_exists( 'premium_shop_hero_photo_pool' ) && count( premium_shop_hero_photo_pool() ) >= 3 ) ? premium_shop_hero_photos( 3, 3 ) : array();
		?>
		<?php if ( $premium_shop_collage ) : ?>
			<div class="ps-hero__visual ps-hero__visual--photos ps-anim" style="--ps-delay:120ms" aria-hidden="true">
				<div class="ps-phero__photos ps-phero__photos--3 ps-hero__collage">
					<?php foreach ( $premium_shop_collage as $premium_shop_i => $premium_shop_photo ) : ?>
						<figure class="ps-phero__photo ps-phero__photo--<?php echo (int) $premium_shop_i + 1; ?>">
							<img src="<?php echo esc_url( $premium_shop_photo['src'] ); ?>" alt="" <?php echo 0 === $premium_shop_i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
						</figure>
					<?php endforeach; ?>
					<span class="ps-phero__ring"></span>
				</div>
				<span class="ps-hero__orb ps-hero__orb--1"></span>
				<span class="ps-hero__orb ps-hero__orb--2"></span>
			</div>
		<?php elseif ( 'split' === $premium_shop_layout ) : ?>
			<div class="ps-hero__visual ps-anim" style="--ps-delay:120ms" aria-hidden="true">
				<div class="ps-hero__arch">
					<?php if ( $premium_shop_image_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$premium_shop_image_id,
							'ps-hero',
							false,
							array(
								'class'         => 'ps-hero__img',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'sizes'         => '(min-width: 1024px) 45vw, 90vw',
								'alt'           => '',
							)
						);
						?>
					<?php elseif ( $premium_shop_bundled ) : ?>
						<img class="ps-hero__img" src="<?php echo esc_url( $premium_shop_bundled ); ?>" width="1050" height="1200" alt="" fetchpriority="high" decoding="async" />
					<?php else : ?>
						<?php get_template_part( 'template-parts/components/hero-art' ); ?>
					<?php endif; ?>
				</div>
				<span class="ps-hero__orb ps-hero__orb--1"></span>
				<span class="ps-hero__orb ps-hero__orb--2"></span>
				<span class="ps-hero__ring"></span>
			</div>
		<?php endif; ?>
	</div>
</section>
