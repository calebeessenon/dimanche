<?php
/**
 * Single blog post.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ps-single' ); ?>>
		<header class="ps-page-header ps-page-header--center">
			<div class="ps-container ps-container--narrow">
				<?php premium_shop_breadcrumbs(); ?>
				<?php
				$premium_shop_cats = get_the_category_list( ', ' );
				if ( $premium_shop_cats ) {
					echo '<p class="ps-eyebrow">' . wp_kses_post( $premium_shop_cats ) . '</p>';
				}
				?>
				<h1 class="ps-page-header__title"><?php the_title(); ?></h1>
				<?php premium_shop_posted_on(); ?>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="ps-container ps-single__cover">
				<?php the_post_thumbnail( 'full', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1280px) 1200px, 100vw' ) ); ?>
			</figure>
		<?php endif; ?>

		<div class="ps-container ps-container--narrow ps-entry-content entry-content">
			<?php
			the_content();
			wp_link_pages(
				array(
					'before' => '<nav class="ps-page-links" aria-label="' . esc_attr__( 'Page', 'premium-shop' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
			<?php the_tags( '<p class="ps-single__tags">', '', '</p>' ); ?>
		</div>

		<div class="ps-container ps-container--narrow">
			<?php
			the_post_navigation(
				array(
					'prev_text' => '<span class="ps-eyebrow">' . esc_html__( 'Previous', 'premium-shop' ) . '</span><span>%title</span>',
					'next_text' => '<span class="ps-eyebrow">' . esc_html__( 'Next', 'premium-shop' ) . '</span><span>%title</span>',
				)
			);

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
