<?php
/**
 * Template Name: Wishlist
 *
 * Products saved with the heart button (stored in the visitor's browser,
 * no account needed). Compatible with YITH Wishlist: its shortcode in the
 * page content is displayed instead.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ps-page ps-wishlist-page' ); ?>>
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
		<div class="ps-container">
			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="ps-entry-content entry-content"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( premium_shop_is_wc() ) : ?>
				<div class="ps-wishlist" data-ps-wishlist-page aria-live="polite">
					<div class="ps-wishlist__loading"><span class="ps-spinner" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Loading…', 'premium-shop' ); ?></span></div>
				</div>
				<template data-ps-wishlist-empty>
					<div class="ps-empty">
						<span class="ps-empty__icon" aria-hidden="true"><?php premium_shop_icon( 'heart', array( 'size' => 28 ) ); ?></span>
						<h2 class="ps-empty__title"><?php esc_html_e( 'Your wishlist is empty', 'premium-shop' ); ?></h2>
						<p><?php esc_html_e( 'Tap the heart on any product to save it here for later.', 'premium-shop' ); ?></p>
						<a class="ps-btn ps-btn--primary" href="<?php echo esc_url( premium_shop_shop_url() ); ?>"><span><?php esc_html_e( 'Discover products', 'premium-shop' ); ?></span><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></a>
					</div>
				</template>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
