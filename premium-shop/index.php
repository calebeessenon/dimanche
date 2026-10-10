<?php
/**
 * Main fallback template (blog index).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="ps-page-header">
	<div class="ps-container">
		<?php premium_shop_breadcrumbs(); ?>
		<h1 class="ps-page-header__title"><?php echo esc_html( is_home() && ! is_front_page() ? premium_shop_archive_title() : __( 'Journal', 'premium-shop' ) ); ?></h1>
	</div>
</header>

<div class="ps-container ps-blog<?php echo is_active_sidebar( 'blog-sidebar' ) ? ' has-sidebar' : ''; ?>">
	<div class="ps-blog__main">
		<?php if ( have_posts() ) : ?>
			<div class="ps-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', get_post_type() );
				endwhile;
				?>
			</div>
			<?php premium_shop_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
