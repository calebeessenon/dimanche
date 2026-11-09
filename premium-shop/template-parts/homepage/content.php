<?php
/**
 * Homepage: content of the static front page (block editor), if any.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( 'page' !== get_option( 'show_on_front' ) ) {
	return;
}

while ( have_posts() ) :
	the_post();
	if ( '' === trim( get_the_content() ) ) {
		continue;
	}
	?>
	<section class="ps-section ps-home-content">
		<div class="ps-container ps-entry-content entry-content">
			<?php the_content(); ?>
		</div>
	</section>
	<?php
endwhile;
rewind_posts();
