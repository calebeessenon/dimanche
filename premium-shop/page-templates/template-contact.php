<?php
/**
 * Template Name: Contact
 *
 * Page text and contact details (Customizer → Premium Shop → Contact) next to
 * a ready-to-use contact form.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ps-page ps-contact-page' ); ?>>
		<?php if ( premium_shop_page_hero_enabled() ) : ?>
			<?php premium_shop_page_hero(); ?>
		<?php else : ?>
			<header class="ps-page-header">
				<div class="ps-container">
					<?php premium_shop_breadcrumbs(); ?>
					<h1 class="ps-page-header__title"><?php the_title(); ?></h1>
				</div>
			</header>
		<?php endif; ?>

		<div class="ps-container ps-contact">
			<div class="ps-contact__info">
				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="ps-entry-content entry-content"><?php the_content(); ?></div>
				<?php else : ?>
					<p class="ps-contact__lead"><?php esc_html_e( 'We look forward to hearing from you. Our customer service will answer as quickly as possible.', 'premium-shop' ); ?></p>
				<?php endif; ?>

				<?php premium_shop_contact_details(); ?>

				<?php if ( premium_shop_is_wc() && premium_shop_order_tracking_url() ) : ?>
					<a class="ps-contact__tracking" href="<?php echo esc_url( premium_shop_order_tracking_url() ); ?>">
						<?php premium_shop_icon( 'truck', array( 'size' => 20 ) ); ?>
						<span><?php esc_html_e( 'Where is my order? Track it here', 'premium-shop' ); ?></span>
						<?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="ps-contact__form">
				<h2 class="ps-contact__title"><?php esc_html_e( 'Send us a message', 'premium-shop' ); ?></h2>
				<?php premium_shop_contact_form(); ?>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
