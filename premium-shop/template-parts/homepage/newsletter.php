<?php
/**
 * Homepage: newsletter.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="ps-section ps-newsletter" id="ps-newsletter" aria-labelledby="ps-newsletter-title">
	<div class="ps-container">
		<div class="ps-newsletter__card" data-reveal>
			<div class="ps-newsletter__intro">
				<span class="ps-newsletter__icon" aria-hidden="true"><?php premium_shop_icon( 'mail', array( 'size' => 26 ) ); ?></span>
				<h2 class="ps-newsletter__title" id="ps-newsletter-title"><?php echo esc_html( premium_shop_text( 'newsletter_title' ) ); ?></h2>
				<p class="ps-newsletter__text"><?php echo esc_html( premium_shop_text( 'newsletter_text' ) ); ?></p>
			</div>
			<?php get_template_part( 'template-parts/components/newsletter-form', null, array( 'id' => 'ps-home-newsletter' ) ); ?>
		</div>
	</div>
</section>
