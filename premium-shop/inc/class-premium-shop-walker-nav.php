<?php
/**
 * Navigation walker: accessible dropdown toggles + categories mega menu.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main navigation walker.
 */
class Premium_Shop_Walker_Nav extends Walker_Nav_Menu {

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string   $output Used to append additional content (passed by reference).
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<ul class="sub-menu ps-dropdown ps-dropdown--depth-' . absint( $depth ) . '">';
	}

	/**
	 * Ends the list.
	 *
	 * @param string   $output Used to append additional content (passed by reference).
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	/**
	 * Starts the element output.
	 *
	 * @param string   $output            Used to append additional content (passed by reference).
	 * @param WP_Post  $data_object       Menu item data object.
	 * @param int      $depth             Depth of menu item.
	 * @param stdClass $args              An object of wp_nav_menu() arguments.
	 * @param int      $current_object_id Optional. ID of the current menu item.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$is_mega = ( 0 === $depth && in_array( 'mega-categories', $classes, true ) && premium_shop_option( 'mega_categories' ) && premium_shop_is_wc() );

		if ( $is_mega ) {
			$classes[]     = 'menu-item-has-children';
			$classes[]     = 'ps-has-mega';
			$item->classes = $classes;
		}

		parent::start_el( $output, $item, $depth, $args, $current_object_id );

		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$output .= premium_shop_submenu_toggle( $item->title );
		}

		if ( $is_mega ) {
			$output .= premium_shop_mega_menu_html();
		}
	}
}

/**
 * Toggle button placed after a parent menu link (keyboard & touch access).
 *
 * @param string $title Parent item title.
 * @return string
 */
function premium_shop_submenu_toggle( $title ) {
	return sprintf(
		'<button type="button" class="ps-nav__toggle" aria-expanded="false" aria-label="%1$s">%2$s</button>',
		/* translators: %s: menu item title. */
		esc_attr( sprintf( __( 'Show submenu for %s', 'premium-shop' ), wp_strip_all_tags( $title ) ) ),
		premium_shop_get_icon( 'chevron', array( 'size' => 16 ) )
	);
}

/**
 * Mega menu panel listing product categories (cached per language).
 *
 * @return string
 */
function premium_shop_mega_menu_html() {
	$cache_key = 'ps_mega_' . premium_shop_current_language() . '_' . PREMIUM_SHOP_VERSION;
	$html      = get_transient( $cache_key );

	if ( false !== $html && ! is_customize_preview() ) {
		return $html;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 8,
			'orderby'    => 'menu_order',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="ps-mega" role="group">
		<div class="ps-mega__inner ps-container">
			<ul class="ps-mega__grid sub-menu">
				<?php
				foreach ( $terms as $term ) :
					$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
					$children = get_terms(
						array(
							'taxonomy'   => 'product_cat',
							'parent'     => $term->term_id,
							'hide_empty' => true,
							'number'     => 5,
							'orderby'    => 'menu_order',
						)
					);
					?>
					<li class="ps-mega__item menu-item">
						<a class="ps-mega__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
							<span class="ps-mega__thumb" aria-hidden="true">
								<?php
								if ( $thumb_id ) {
									echo wp_get_attachment_image( $thumb_id, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) );
								} else {
									echo '<span class="ps-mega__initial">' . esc_html( mb_substr( $term->name, 0, 1 ) ) . '</span>';
								}
								?>
							</span>
							<span class="ps-mega__name"><?php echo esc_html( $term->name ); ?></span>
						</a>
						<?php if ( ! is_wp_error( $children ) && $children ) : ?>
							<ul class="ps-mega__children">
								<?php foreach ( $children as $child ) : ?>
									<li><a href="<?php echo esc_url( get_term_link( $child ) ); ?>"><?php echo esc_html( $child->name ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<a class="ps-mega__promo" href="<?php echo esc_url( add_query_arg( 'on_sale', '1', premium_shop_shop_url() ) ); ?>">
				<span class="ps-mega__promo-eyebrow"><?php esc_html_e( 'Limited time', 'premium-shop' ); ?></span>
				<span class="ps-mega__promo-title"><?php esc_html_e( 'Discover our offers', 'premium-shop' ); ?></span>
				<span class="ps-mega__promo-cta"><?php esc_html_e( 'Shop the sale', 'premium-shop' ); ?> <?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?></span>
			</a>
		</div>
	</div>
	<?php
	$html = (string) ob_get_clean();

	set_transient( $cache_key, $html, 6 * HOUR_IN_SECONDS );

	return $html;
}

/**
 * Flush the mega menu cache when categories change.
 */
function premium_shop_flush_mega_cache() {
	foreach ( array_keys( premium_shop_language_registry() ) as $code ) {
		delete_transient( 'ps_mega_' . $code . '_' . PREMIUM_SHOP_VERSION );
	}
}
add_action( 'created_product_cat', 'premium_shop_flush_mega_cache' );
add_action( 'edited_product_cat', 'premium_shop_flush_mega_cache' );
add_action( 'delete_product_cat', 'premium_shop_flush_mega_cache' );
add_action( 'customize_save_after', 'premium_shop_flush_mega_cache' );

/**
 * Remove duplicate IDs when the menu is printed a second time (mobile drawer).
 *
 * @param string   $id   Item ID.
 * @param WP_Post  $item Item.
 * @param stdClass $args Menu args.
 * @return string
 */
function premium_shop_nav_item_id( $id, $item, $args ) {
	if ( isset( $args->ps_context ) && 'mobile' === $args->ps_context ) {
		return '';
	}
	return $id;
}
add_filter( 'nav_menu_item_id', 'premium_shop_nav_item_id', 10, 3 );
