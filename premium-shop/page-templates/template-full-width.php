<?php
/**
 * Template Name: Full width (no title)
 *
 * Ideal for landing pages built with blocks.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'ps-page ps-page--full' ); ?>>
		<h1 class="screen-reader-text"><?php the_title(); ?></h1>
		<div class="ps-entry-content ps-entry-content--full entry-content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
