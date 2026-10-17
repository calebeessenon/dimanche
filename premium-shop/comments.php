<?php
/**
 * Comments.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="ps-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="ps-comments__title">
			<?php
			$premium_shop_count = get_comments_number();
			/* translators: %s: number of comments. */
			echo esc_html( sprintf( _n( '%s comment', '%s comments', $premium_shop_count, 'premium-shop' ), number_format_i18n( $premium_shop_count ) ) );
			?>
		</h2>
		<ol class="ps-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="ps-comments__closed"><?php esc_html_e( 'Comments are closed.', 'premium-shop' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit' => 'ps-btn ps-btn--primary',
		)
	);
	?>
</section>
