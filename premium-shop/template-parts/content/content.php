<?php
/**
 * Post card in lists.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'ps-post-card' ); ?> data-reveal>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="ps-post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'ps-banner', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="ps-post-card__body">
		<?php premium_shop_posted_on(); ?>
		<h2 class="ps-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="ps-post-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="ps-link-arrow" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read more', 'premium-shop' ); ?>
			<span class="screen-reader-text"><?php the_title(); ?></span>
			<?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?>
		</a>
	</div>
</article>
