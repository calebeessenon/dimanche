<?php
/**
 * Firewood calculator (annual need + unit converter). Calculated in the browser.
 *
 * @package Premium_Shop
 *
 * @var array $args compact (bool), heading (h2|h3).
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_compact = ! empty( $args['compact'] );
$premium_shop_tag     = isset( $args['heading'] ) && in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h3';
$premium_shop_uid     = wp_unique_id( 'ps-calc-' );
$premium_shop_species = premium_shop_calc_species();
?>
<div class="ps-calc<?php echo $premium_shop_compact ? ' ps-calc--compact' : ''; ?>" data-ps-calc>
	<div class="ps-calc__panel">
		<<?php echo esc_html( $premium_shop_tag ); ?> class="ps-calc__title"><?php premium_shop_icon( 'calculator', array( 'size' => 22 ) ); ?> <?php esc_html_e( 'Annual need', 'premium-shop' ); ?></<?php echo esc_html( $premium_shop_tag ); ?>>
		<div class="ps-calc__grid">
			<label>
				<span><?php esc_html_e( 'Wood species', 'premium-shop' ); ?></span>
				<select data-ps-calc-species>
					<?php foreach ( $premium_shop_species as $premium_shop_key => $premium_shop_s ) : ?>
						<option value="<?php echo esc_attr( $premium_shop_s[1] ); ?>" <?php selected( 'beech', $premium_shop_key ); ?>><?php echo esc_html( $premium_shop_s[0] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( 'Stove output (kW)', 'premium-shop' ); ?></span>
				<input type="number" min="2" max="40" step="0.5" value="7" inputmode="decimal" data-ps-calc-kw />
			</label>
			<label>
				<span><?php esc_html_e( 'Hours of use per day', 'premium-shop' ); ?></span>
				<input type="number" min="1" max="24" step="0.5" value="4" inputmode="decimal" data-ps-calc-hours />
			</label>
			<label>
				<span><?php esc_html_e( 'Heating days per season', 'premium-shop' ); ?></span>
				<input type="number" min="1" max="365" step="1" value="120" inputmode="numeric" data-ps-calc-days />
			</label>
		</div>
		<div class="ps-calc__result" aria-live="polite">
			<p class="ps-calc__big"><span data-ps-calc-rm>—</span> <small data-ps-calc-unit><?php esc_html_e( 'stacked m³ per season', 'premium-shop' ); ?></small></p>
			<p class="ps-calc__eq" data-ps-calc-eq></p>
		</div>
		<p class="ps-calc__note"><?php esc_html_e( 'Indicative values for air-dry wood (< 20% moisture) and a stove efficiency of about 78%. Actual consumption depends on insulation, stove and habits.', 'premium-shop' ); ?></p>
		<?php if ( function_exists( 'premium_shop_shop_url' ) && premium_shop_is_wc() && ! $premium_shop_compact ) : ?>
			<a class="ps-btn ps-btn--accent" href="<?php echo esc_url( premium_shop_shop_url() ); ?>"><span><?php esc_html_e( 'Order firewood', 'premium-shop' ); ?></span><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></a>
		<?php endif; ?>
	</div>

	<div class="ps-calc__panel ps-calc__panel--soft">
		<<?php echo esc_html( $premium_shop_tag ); ?> class="ps-calc__title"><?php premium_shop_icon( 'logs', array( 'size' => 22 ) ); ?> <?php esc_html_e( 'Unit converter', 'premium-shop' ); ?></<?php echo esc_html( $premium_shop_tag ); ?>>
		<div class="ps-calc__grid ps-calc__grid--2">
			<label>
				<span><?php esc_html_e( 'Quantity', 'premium-shop' ); ?></span>
				<input type="number" min="0" step="0.1" value="1" inputmode="decimal" data-ps-conv-value id="<?php echo esc_attr( $premium_shop_uid ); ?>-value" />
			</label>
			<label>
				<span><?php esc_html_e( 'Unit', 'premium-shop' ); ?></span>
				<select data-ps-conv-unit>
					<option value="rm"><?php esc_html_e( 'Stacked cubic metre (RM)', 'premium-shop' ); ?></option>
					<option value="srm"><?php esc_html_e( 'Loose cubic metre (SRM)', 'premium-shop' ); ?></option>
					<option value="fm"><?php esc_html_e( 'Solid cubic metre (FM)', 'premium-shop' ); ?></option>
				</select>
			</label>
		</div>
		<ul class="ps-calc__conv" aria-live="polite">
			<li><strong data-ps-conv-rm>1</strong> <?php esc_html_e( 'stacked m³', 'premium-shop' ); ?></li>
			<li><strong data-ps-conv-srm>1,4</strong> <?php esc_html_e( 'loose m³', 'premium-shop' ); ?></li>
			<li><strong data-ps-conv-fm>0,7</strong> <?php esc_html_e( 'solid m³', 'premium-shop' ); ?></li>
		</ul>
		<p class="ps-calc__note"><?php esc_html_e( 'Rule of thumb for split logs (33 cm): 1 solid m³ ≈ 1.4 stacked m³ ≈ 2 loose m³.', 'premium-shop' ); ?></p>
	</div>
</div>
