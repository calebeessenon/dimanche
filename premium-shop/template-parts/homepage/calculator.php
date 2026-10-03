<?php
/**
 * Homepage: firewood calculator.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="ps-section ps-calc-section" id="ps-calculator" aria-labelledby="ps-calculator-title">
	<div class="ps-container">
		<?php
		premium_shop_section_heading(
			array(
				'eyebrow' => __( 'Plan ahead', 'premium-shop' ),
				'title'   => premium_shop_text( 'title_calculator' ),
				'text'    => __( 'Estimate your firewood needs for the season in a few seconds and convert between stacked, loose and solid cubic metres.', 'premium-shop' ),
				'id'      => 'ps-calculator-title',
			)
		);
		?>
		<div data-reveal><?php premium_shop_firewood_calculator(); ?></div>
	</div>
</section>
