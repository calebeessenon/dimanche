<?php
/**
 * "Gaming & tech" style: band of platform names scrolling in two directions.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_rows = apply_filters(
	'premium_shop_gaming_marquee',
	array(
		array( 'PlayStation 5 Slim', 'Nintendo Switch 2', 'DualSense', 'JBL Flip 6', 'Mario Kart World', 'GeForce RTX' ),
		array( __( 'Consoles', 'premium-shop' ), __( 'Controllers', 'premium-shop' ), __( 'Speakers', 'premium-shop' ), __( 'Gift cards', 'premium-shop' ), __( 'Games', 'premium-shop' ), __( 'Graphics cards', 'premium-shop' ) ),
	)
);
?>
<section class="ps-gmarquee" aria-hidden="true">
	<?php foreach ( $premium_shop_rows as $premium_shop_r => $premium_shop_row ) : ?>
		<div class="ps-gmarquee__row<?php echo 1 === $premium_shop_r ? ' ps-gmarquee__row--reverse ps-gmarquee__row--outline' : ''; ?>">
			<div class="ps-gmarquee__track">
				<?php for ( $premium_shop_k = 0; $premium_shop_k < 2; $premium_shop_k++ ) : ?>
					<?php foreach ( $premium_shop_row as $premium_shop_word ) : ?>
						<span class="ps-gmarquee__item"><?php echo esc_html( $premium_shop_word ); ?></span><span class="ps-gmarquee__star">✦</span>
					<?php endforeach; ?>
				<?php endfor; ?>
			</div>
		</div>
	<?php endforeach; ?>
</section>
