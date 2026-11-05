<?php
/**
 * Holiday season (gaming style): gift finder by budget and the countdown
 * to Christmas.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_shop   = premium_shop_shop_url();
$premium_shop_symbol = premium_shop_is_wc() ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '';
$premium_shop_ranges = array(
	/* translators: 1: amount, 2: currency. */
	array( 0, 100, sprintf( __( 'Under %1$s %2$s', 'premium-shop' ), '100', $premium_shop_symbol ), __( 'Gift cards, games, mini speakers', 'premium-shop' ), 'g-gift--red' ),
	array( 100, 300, sprintf( '100 – 300 %s', $premium_shop_symbol ), __( 'Controllers, JBL speakers, headsets', 'premium-shop' ), 'g-gift--green' ),
	array( 300, 600, sprintf( '300 – 600 %s', $premium_shop_symbol ), __( 'PS5 Slim, Nintendo Switch', 'premium-shop' ), 'g-gift--violet' ),
	/* translators: 1: amount, 2: currency. */
	array( 600, 0, sprintf( __( '%1$s %2$s and more', 'premium-shop' ), '600', $premium_shop_symbol ), __( 'PS5 Pro, graphics cards', 'premium-shop' ), 'g-gift--gold' ),
);
?>
<section class="ps-section g-gifts" aria-labelledby="g-gifts-title">
	<div class="ps-container">
		<div class="g-gifts__head" data-reveal>
			<p class="ps-eyebrow"><?php esc_html_e( 'Gift finder', 'premium-shop' ); ?></p>
			<h2 class="ps-section-title" id="g-gifts-title"><?php esc_html_e( 'Gift ideas for every budget', 'premium-shop' ); ?></h2>
		</div>
		<div class="g-gifts__grid">
			<?php foreach ( $premium_shop_ranges as $premium_shop_i => $premium_shop_range ) : ?>
				<?php
				$premium_shop_args = array( 'min_price' => $premium_shop_range[0] );
				if ( $premium_shop_range[1] ) {
					$premium_shop_args['max_price'] = $premium_shop_range[1];
				}
				?>
				<a class="g-gift <?php echo esc_attr( $premium_shop_range[4] ); ?>" href="<?php echo esc_url( add_query_arg( $premium_shop_args, $premium_shop_shop ) ); ?>" data-reveal style="--ps-delay:<?php echo (int) $premium_shop_i * 90; ?>ms">
					<span class="g-gift__box" aria-hidden="true"><span class="g-gift__lid"></span><span class="g-gift__bow"></span></span>
					<span class="g-gift__price"><?php echo esc_html( $premium_shop_range[2] ); ?></span>
					<span class="g-gift__label"><?php echo esc_html( $premium_shop_range[3] ); ?></span>
					<span class="g-gift__go"><?php esc_html_e( 'See the gifts', 'premium-shop' ); ?> <?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="g-xmas" data-reveal>
			<div class="g-xmas__text">
				<p class="g-xmas__title"><span aria-hidden="true">🎄</span> <?php esc_html_e( 'Christmas is coming', 'premium-shop' ); ?></p>
				<p class="g-xmas__note"><?php esc_html_e( 'Order by 20 December for delivery before Christmas.', 'premium-shop' ); ?></p>
			</div>
			<div class="g-xmas__count" data-g-xmas role="timer" aria-live="off">
				<?php foreach ( array( __( 'days', 'premium-shop' ), __( 'hrs', 'premium-shop' ), __( 'min', 'premium-shop' ), __( 'sec', 'premium-shop' ) ) as $premium_shop_unit ) : ?>
					<span class="g-xmas__cell"><b>00</b><small><?php echo esc_html( $premium_shop_unit ); ?></small></span>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
