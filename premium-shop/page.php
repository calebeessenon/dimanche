<?php
/**
 * Pages (including WooCommerce cart, checkout and account pages).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();

$premium_shop_wc_page = premium_shop_is_wc() && ( is_cart() || is_checkout() || is_account_page() );

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $premium_shop_wc_page ? 'ps-wc-page' : 'ps-page' ); ?>>
		<?php if ( premium_shop_page_hero_enabled() && premium_shop_is_wc() && is_wc_endpoint_url( 'order-received' ) ) : ?>
			<?php premium_shop_thanks_hero(); ?>
		<?php elseif ( premium_shop_page_hero_enabled() ) : ?>
			<?php premium_shop_page_hero(); ?>
		<?php elseif ( $premium_shop_wc_page && ( is_cart() || is_checkout() ) ) : ?>
			<div class="ps-container ps-wc-page__head">
				<?php premium_shop_checkout_steps(); ?>
				<h1 class="ps-wc-page__title"><?php the_title(); ?></h1>
			</div>
		<?php else : ?>
			<header class="ps-page-header<?php echo $premium_shop_wc_page ? ' ps-page-header--compact' : ''; ?>">
				<div class="ps-container">
					<?php premium_shop_breadcrumbs(); ?>
					<h1 class="ps-page-header__title"><?php the_title(); ?></h1>
				</div>
			</header>
			<?php if ( has_post_thumbnail() && ! $premium_shop_wc_page ) : ?>
				<div class="ps-container ps-page__cover">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="ps-container <?php echo $premium_shop_wc_page ? 'ps-wc-page__content' : 'ps-entry-content entry-content'; ?>">
			<?php
			the_content();
			wp_link_pages(
				array(
					'before' => '<nav class="ps-page-links" aria-label="' . esc_attr__( 'Page', 'premium-shop' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>

		<?php if ( ! $premium_shop_wc_page && ( comments_open() || get_comments_number() ) ) : ?>
			<div class="ps-container ps-entry-content">
				<?php comments_template(); ?>
			</div>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
