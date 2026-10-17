<?php
/**
 * Search form with live product suggestions.
 *
 * @package Premium_Shop
 *
 * @var array $args id, autofocus, live.
 */

defined( 'ABSPATH' ) || exit;

$premium_shop_args = wp_parse_args(
	isset( $args ) ? $args : array(),
	array(
		'id'   => 'ps-search-field',
		'live' => true,
		'size' => 'default',
	)
);

$premium_shop_id    = sanitize_html_class( $premium_shop_args['id'] );
$premium_shop_live  = $premium_shop_args['live'] && premium_shop_is_wc();
$premium_shop_label = premium_shop_is_wc() ? __( 'Search products', 'premium-shop' ) : __( 'Search', 'premium-shop' );
?>
<form role="search" method="get" class="ps-search-form ps-search-form--<?php echo esc_attr( $premium_shop_args['size'] ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $premium_shop_live ? ' data-ps-live-search' : ''; ?>>
	<label class="screen-reader-text" for="<?php echo esc_attr( $premium_shop_id ); ?>"><?php echo esc_html( $premium_shop_label ); ?></label>
	<span class="ps-search-form__icon" aria-hidden="true"><?php premium_shop_icon( 'search', array( 'size' => 20 ) ); ?></span>
	<input type="search"
		id="<?php echo esc_attr( $premium_shop_id ); ?>"
		class="ps-search-form__input"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'What are you looking for?', 'premium-shop' ); ?>"
		autocomplete="off"
		<?php if ( $premium_shop_live ) : ?>
		role="combobox"
		aria-autocomplete="list"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $premium_shop_id ); ?>-results"
		<?php endif; ?>
	/>
	<?php if ( premium_shop_is_wc() ) : ?>
		<input type="hidden" name="post_type" value="product" />
	<?php endif; ?>
	<?php if ( 'builtin' === premium_shop_language_mode() && premium_shop_current_language() !== premium_shop_default_language() ) : ?>
		<input type="hidden" name="lang" value="<?php echo esc_attr( premium_shop_current_language() ); ?>" />
	<?php endif; ?>
	<button type="submit" class="ps-search-form__submit">
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'premium-shop' ); ?></span>
		<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
	</button>
	<?php if ( $premium_shop_live ) : ?>
		<div class="ps-live-results" id="<?php echo esc_attr( $premium_shop_id ); ?>-results" role="listbox" aria-label="<?php esc_attr_e( 'Suggestions', 'premium-shop' ); ?>" hidden></div>
		<p class="screen-reader-text" aria-live="polite" data-ps-live-status></p>
	<?php endif; ?>
</form>
