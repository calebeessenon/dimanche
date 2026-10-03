<?php
/**
 * Shop filters: categories, price, availability, offers, attributes, brands,
 * rating and search.
 *
 * Filters use WooCommerce's native query parameters (min_price, max_price,
 * filter_{attribute}, rating_filter) plus three theme parameters (stock,
 * on_sale, ps_brand). They work without JavaScript; shop.js turns them into
 * instant AJAX filtering.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize array parameters (name="filter_color[]") into WooCommerce's
 * comma-separated format before WooCommerce reads them.
 */
function premium_shop_normalize_filter_params() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only catalog filters.
	foreach ( $_GET as $key => $value ) {
		if ( is_array( $value ) && ( 0 === strpos( $key, 'filter_' ) || in_array( $key, array( 'ps_brand', 'rating_filter' ), true ) ) ) {
			$clean        = array_filter( array_map( 'sanitize_title', wp_unslash( $value ) ) );
			$_GET[ $key ] = implode( ',', $clean );
			if ( '' === $_GET[ $key ] ) {
				unset( $_GET[ $key ] );
			}
		}
	}
	foreach ( array( 'min_price', 'max_price' ) as $key ) {
		if ( isset( $_GET[ $key ] ) && '' === $_GET[ $key ] ) {
			unset( $_GET[ $key ] );
		}
	}
	// phpcs:enable
}
add_action( 'init', 'premium_shop_normalize_filter_params', 1 );

/**
 * Apply the theme filters to the main product query.
 *
 * @param WP_Query $q Query.
 */
function premium_shop_product_query_filters( $q ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$tax_query = (array) $q->get( 'tax_query' );

	if ( ! empty( $_GET['stock'] ) && 'instock' === sanitize_key( wp_unslash( $_GET['stock'] ) ) ) {
		$terms = wc_get_product_visibility_term_ids();
		if ( ! empty( $terms['outofstock'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( $terms['outofstock'] ),
				'operator' => 'NOT IN',
			);
		}
	}

	if ( ! empty( $_GET['ps_brand'] ) && premium_shop_brand_taxonomy() ) {
		$brands = array_filter( array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET['ps_brand'] ) ) ) ) );
		if ( $brands ) {
			$tax_query[] = array(
				'taxonomy' => premium_shop_brand_taxonomy(),
				'field'    => 'slug',
				'terms'    => $brands,
				'operator' => 'IN',
			);
		}
	}

	$q->set( 'tax_query', $tax_query );

	if ( ! empty( $_GET['on_sale'] ) ) {
		$ids      = premium_shop_on_sale_ids();
		$existing = (array) $q->get( 'post__in' );
		if ( $existing ) {
			$ids = array_values( array_intersect( array_map( 'absint', $existing ), $ids ) );
			$ids = $ids ? $ids : array( 0 );
		}
		$q->set( 'post__in', $ids );
	}
	// phpcs:enable
}
add_action( 'woocommerce_product_query', 'premium_shop_product_query_filters' );

/**
 * Brand taxonomy (WooCommerce Brands, or popular brand plugins).
 *
 * @return string Empty when none.
 */
function premium_shop_brand_taxonomy() {
	foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand' ) as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			return $taxonomy;
		}
	}
	return '';
}

/**
 * Catalog price range (cached).
 *
 * @return array [ min, max ]
 */
function premium_shop_price_range() {
	$range = get_transient( 'ps_price_range' );

	if ( false === $range ) {
		global $wpdb;
		$row   = $wpdb->get_row( "SELECT MIN( min_price ) AS min_price, MAX( max_price ) AS max_price FROM {$wpdb->wc_product_meta_lookup} WHERE max_price > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$range = array(
			$row ? (int) floor( (float) $row->min_price ) : 0,
			$row ? (int) ceil( (float) $row->max_price ) : 0,
		);
		set_transient( 'ps_price_range', $range, 12 * HOUR_IN_SECONDS );
	}

	return $range;
}

/**
 * Clear cached price range when products change.
 */
function premium_shop_flush_price_range() {
	delete_transient( 'ps_price_range' );
}
add_action( 'woocommerce_update_product', 'premium_shop_flush_price_range' );
add_action( 'woocommerce_new_product', 'premium_shop_flush_price_range' );
add_action( 'woocommerce_delete_product', 'premium_shop_flush_price_range' );

/**
 * Base URL of the current listing, without filter parameters.
 *
 * @return string
 */
function premium_shop_listing_base_url() {
	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? premium_shop_shop_url() : $link;
	}
	return premium_shop_shop_url();
}

/**
 * Current filter parameters (sanitized), for links and chips.
 *
 * @return array
 */
function premium_shop_current_filters() {
	$filters = array();
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	foreach ( $_GET as $key => $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			continue;
		}
		$key = sanitize_key( $key );
		if ( 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'query_type_' ) || in_array( $key, array( 'min_price', 'max_price', 'rating_filter', 'stock', 'on_sale', 'ps_brand', 'orderby', 's', 'post_type', 'lang' ), true ) ) {
			$filters[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}
	}
	// phpcs:enable
	return $filters;
}

/**
 * Render the filter panel.
 */
function premium_shop_shop_filters() {
	$current  = premium_shop_current_filters();
	$base_url = premium_shop_listing_base_url();
	$form_id  = 'ps-filters-form';
	?>
	<form class="ps-filters" id="<?php echo esc_attr( $form_id ); ?>" method="get" action="<?php echo esc_url( $base_url ); ?>" data-ps-filters>
		<?php if ( isset( $current['orderby'] ) ) : ?>
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $current['orderby'] ); ?>" />
		<?php endif; ?>
		<?php if ( isset( $current['lang'] ) ) : ?>
			<input type="hidden" name="lang" value="<?php echo esc_attr( $current['lang'] ); ?>" />
		<?php endif; ?>
		<input type="hidden" name="post_type" value="product" />

		<?php // Search inside the shop. ?>
		<div class="ps-filter ps-filter--search">
			<label class="screen-reader-text" for="ps-filter-search"><?php esc_html_e( 'Search in the shop', 'premium-shop' ); ?></label>
			<span class="ps-filter__search-icon" aria-hidden="true"><?php premium_shop_icon( 'search', array( 'size' => 18 ) ); ?></span>
			<input type="search" id="ps-filter-search" name="s" value="<?php echo esc_attr( isset( $current['s'] ) ? $current['s'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Search in the shop', 'premium-shop' ); ?>" data-ps-filter-search />
		</div>

		<?php premium_shop_filter_categories(); ?>
		<?php premium_shop_filter_price( $current ); ?>

		<details class="ps-filter" open>
			<summary class="ps-filter__title"><?php esc_html_e( 'Availability', 'premium-shop' ); ?><?php premium_shop_icon( 'chevron', array( 'size' => 16 ) ); ?></summary>
			<div class="ps-filter__body">
				<label class="ps-check">
					<input type="checkbox" name="stock" value="instock" <?php checked( isset( $current['stock'] ) && 'instock' === $current['stock'] ); ?> />
					<span><?php esc_html_e( 'In stock only', 'premium-shop' ); ?></span>
				</label>
				<label class="ps-check">
					<input type="checkbox" name="on_sale" value="1" <?php checked( ! empty( $current['on_sale'] ) ); ?> />
					<span><?php esc_html_e( 'Offers only', 'premium-shop' ); ?></span>
				</label>
			</div>
		</details>

		<?php premium_shop_filter_brands( $current ); ?>
		<?php premium_shop_filter_attributes( $current ); ?>
		<?php premium_shop_filter_rating( $current ); ?>

		<div class="ps-filters__actions">
			<button type="submit" class="ps-btn ps-btn--primary ps-btn--block" data-ps-filters-submit><?php esc_html_e( 'Apply filters', 'premium-shop' ); ?></button>
			<?php if ( premium_shop_has_active_filters() ) : ?>
				<a class="ps-btn ps-btn--ghost ps-btn--block" href="<?php echo esc_url( $base_url ); ?>" data-ps-filter-link><?php esc_html_e( 'Reset all', 'premium-shop' ); ?></a>
			<?php endif; ?>
		</div>
	</form>
	<?php
}

/**
 * Category navigation inside the filters.
 */
function premium_shop_filter_categories() {
	$current_term = is_product_category() ? get_queried_object() : null;
	$parent       = 0;

	if ( $current_term ) {
		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $current_term->term_id,
				'hide_empty' => true,
				'fields'     => 'ids',
			)
		);
		$parent   = ( ! is_wp_error( $children ) && $children ) ? $current_term->term_id : $current_term->parent;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent,
			'hide_empty' => true,
			'orderby'    => 'menu_order',
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return;
	}

	$keep = premium_shop_current_filters();
	unset( $keep['s'], $keep['post_type'] );
	?>
	<details class="ps-filter" open>
		<summary class="ps-filter__title"><?php esc_html_e( 'Categories', 'premium-shop' ); ?><?php premium_shop_icon( 'chevron', array( 'size' => 16 ) ); ?></summary>
		<div class="ps-filter__body">
			<ul class="ps-filter__cats">
				<?php if ( $parent ) : ?>
					<?php $parent_term = get_term( $parent, 'product_cat' ); ?>
					<li>
						<a class="ps-filter__back" href="<?php echo esc_url( $parent_term->parent ? get_term_link( (int) $parent_term->parent, 'product_cat' ) : premium_shop_shop_url() ); ?>" data-ps-filter-link>
							<?php premium_shop_icon( 'arrow-left', array( 'size' => 14 ) ); ?>
							<?php echo esc_html( $parent_term->name ); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php foreach ( $terms as $term ) : ?>
					<?php $is_current = $current_term && (int) $current_term->term_id === (int) $term->term_id; ?>
					<li>
						<a class="ps-filter__cat<?php echo $is_current ? ' is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( $keep, get_term_link( $term ) ) ); ?>" <?php echo $is_current ? 'aria-current="page"' : ''; ?> data-ps-filter-link>
							<span><?php echo esc_html( $term->name ); ?></span>
							<span class="ps-filter__count"><?php echo esc_html( $term->count ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</details>
	<?php
}

/**
 * Price range filter (two linked range sliders + inputs).
 *
 * @param array $current Current filters.
 */
function premium_shop_filter_price( $current ) {
	list( $min, $max ) = premium_shop_price_range();

	if ( $max <= $min ) {
		return;
	}

	$from     = isset( $current['min_price'] ) ? max( $min, (int) $current['min_price'] ) : $min;
	$to       = isset( $current['max_price'] ) ? min( $max, (int) $current['max_price'] ) : $max;
	$currency = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
	?>
	<details class="ps-filter" open>
		<summary class="ps-filter__title"><?php esc_html_e( 'Price', 'premium-shop' ); ?><?php premium_shop_icon( 'chevron', array( 'size' => 16 ) ); ?></summary>
		<div class="ps-filter__body">
			<div class="ps-range" data-ps-range data-min="<?php echo esc_attr( $min ); ?>" data-max="<?php echo esc_attr( $max ); ?>">
				<div class="ps-range__track"><span class="ps-range__fill" data-ps-range-fill></span></div>
				<input type="range" class="ps-range__slider" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" value="<?php echo esc_attr( $from ); ?>" step="1" aria-label="<?php esc_attr_e( 'Minimum price', 'premium-shop' ); ?>" data-ps-range-from />
				<input type="range" class="ps-range__slider" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" value="<?php echo esc_attr( $to ); ?>" step="1" aria-label="<?php esc_attr_e( 'Maximum price', 'premium-shop' ); ?>" data-ps-range-to />
			</div>
			<div class="ps-range__inputs">
				<label>
					<span><?php esc_html_e( 'From', 'premium-shop' ); ?></span>
					<span class="ps-range__field">
						<input type="number" name="min_price" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" value="<?php echo esc_attr( isset( $current['min_price'] ) ? $from : '' ); ?>" placeholder="<?php echo esc_attr( $min ); ?>" inputmode="numeric" data-ps-range-min />
						<span aria-hidden="true"><?php echo esc_html( $currency ); ?></span>
					</span>
				</label>
				<label>
					<span><?php esc_html_e( 'To', 'premium-shop' ); ?></span>
					<span class="ps-range__field">
						<input type="number" name="max_price" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" value="<?php echo esc_attr( isset( $current['max_price'] ) ? $to : '' ); ?>" placeholder="<?php echo esc_attr( $max ); ?>" inputmode="numeric" data-ps-range-max />
						<span aria-hidden="true"><?php echo esc_html( $currency ); ?></span>
					</span>
				</label>
			</div>
		</div>
	</details>
	<?php
}

/**
 * Brand filter.
 *
 * @param array $current Current filters.
 */
function premium_shop_filter_brands( $current ) {
	$taxonomy = premium_shop_brand_taxonomy();
	if ( ! $taxonomy ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'number'     => 30,
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return;
	}

	$selected = isset( $current['ps_brand'] ) ? explode( ',', $current['ps_brand'] ) : array();
	?>
	<details class="ps-filter"<?php echo $selected ? ' open' : ''; ?>>
		<summary class="ps-filter__title"><?php esc_html_e( 'Brands', 'premium-shop' ); ?><?php premium_shop_icon( 'chevron', array( 'size' => 16 ) ); ?></summary>
		<div class="ps-filter__body ps-filter__body--scroll">
			<?php foreach ( $terms as $term ) : ?>
				<label class="ps-check">
					<input type="checkbox" name="ps_brand[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $selected, true ) ); ?> />
					<span><?php echo esc_html( $term->name ); ?></span>
					<span class="ps-filter__count"><?php echo esc_html( $term->count ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</details>
	<?php
}

/**
 * Attribute filters (color swatches when a swatch color is set on terms).
 *
 * @param array $current Current filters.
 */
function premium_shop_filter_attributes( $current ) {
	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'number'     => 40,
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			continue;
		}

		$param    = 'filter_' . $attribute->attribute_name;
		$selected = isset( $current[ $param ] ) ? explode( ',', $current[ $param ] ) : array();
		$swatches = false;
		foreach ( $terms as $term ) {
			if ( get_term_meta( $term->term_id, 'ps_swatch', true ) ) {
				$swatches = true;
				break;
			}
		}
		?>
		<details class="ps-filter"<?php echo $selected || $swatches ? ' open' : ''; ?>>
			<summary class="ps-filter__title"><?php echo esc_html( wc_attribute_label( $taxonomy ) ); ?><?php premium_shop_icon( 'chevron', array( 'size' => 16 ) ); ?></summary>
			<div class="ps-filter__body<?php echo $swatches ? ' ps-filter__body--swatches' : ' ps-filter__body--chips'; ?>">
				<?php
				foreach ( $terms as $term ) :
					$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'ps_swatch', true ) );
					?>
					<label class="<?php echo $swatches ? 'ps-swatch' : 'ps-chip-check'; ?>" title="<?php echo esc_attr( $term->name ); ?>">
						<input type="checkbox" name="<?php echo esc_attr( $param ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $selected, true ) ); ?> />
						<?php if ( $swatches ) : ?>
							<span class="ps-swatch__dot" style="<?php echo $color ? 'background:' . esc_attr( $color ) : ''; ?>" aria-hidden="true"></span>
							<span class="screen-reader-text"><?php echo esc_html( $term->name ); ?></span>
						<?php else : ?>
							<span><?php echo esc_html( $term->name ); ?></span>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
		</details>
		<?php
	}
}

/**
 * Rating filter (minimum stars).
 *
 * @param array $current Current filters.
 */
function premium_shop_filter_rating( $current ) {
	if ( ! wc_review_ratings_enabled() ) {
		return;
	}

	$selected = isset( $current['rating_filter'] ) ? $current['rating_filter'] : '';
	$options  = array(
		'4,5'     => 4,
		'3,4,5'   => 3,
		'2,3,4,5' => 2,
	);
	?>
	<details class="ps-filter"<?php echo $selected ? ' open' : ''; ?>>
		<summary class="ps-filter__title"><?php esc_html_e( 'Rating', 'premium-shop' ); ?><?php premium_shop_icon( 'chevron', array( 'size' => 16 ) ); ?></summary>
		<div class="ps-filter__body">
			<label class="ps-check ps-check--radio">
				<input type="radio" name="rating_filter" value="" <?php checked( '', $selected ); ?> />
				<span><?php esc_html_e( 'All ratings', 'premium-shop' ); ?></span>
			</label>
			<?php foreach ( $options as $value => $stars ) : ?>
				<label class="ps-check ps-check--radio">
					<input type="radio" name="rating_filter" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $selected ); ?> />
					<span class="ps-stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $stars ) . str_repeat( '☆', 5 - $stars ) ); ?></span>
					<span>
						<?php
						/* translators: %d: number of stars. */
						echo esc_html( sprintf( __( '%d stars & up', 'premium-shop' ), $stars ) );
						?>
					</span>
				</label>
			<?php endforeach; ?>
		</div>
	</details>
	<?php
}

/**
 * Are any filters active?
 *
 * @return bool
 */
function premium_shop_has_active_filters() {
	return (bool) premium_shop_active_filter_chips();
}

/**
 * Removable chips for active filters.
 *
 * @return array[] [ label, url ]
 */
function premium_shop_active_filter_chips() {
	$current = premium_shop_current_filters();
	$base    = premium_shop_listing_base_url();
	$chips   = array();

	$remove = static function ( $key, $value = null ) use ( $current, $base ) {
		$args = $current;
		if ( null === $value || ! isset( $args[ $key ] ) ) {
			unset( $args[ $key ] );
		} else {
			$values = array_diff( explode( ',', $args[ $key ] ), array( $value ) );
			if ( $values ) {
				$args[ $key ] = implode( ',', $values );
			} else {
				unset( $args[ $key ] );
			}
		}
		if ( 's' === $key ) {
			unset( $args['post_type'] );
		}
		return add_query_arg( array_map( 'rawurlencode', $args ), $base );
	};

	foreach ( $current as $key => $value ) {
		if ( 'min_price' === $key ) {
			/* translators: %s: price. */
			$chips[] = array( sprintf( __( 'From %s', 'premium-shop' ), wp_strip_all_tags( wc_price( (float) $value, array( 'decimals' => 0 ) ) ) ), $remove( $key ) );
		} elseif ( 'max_price' === $key ) {
			/* translators: %s: price. */
			$chips[] = array( sprintf( __( 'Up to %s', 'premium-shop' ), wp_strip_all_tags( wc_price( (float) $value, array( 'decimals' => 0 ) ) ) ), $remove( $key ) );
		} elseif ( 'stock' === $key ) {
			$chips[] = array( __( 'In stock only', 'premium-shop' ), $remove( $key ) );
		} elseif ( 'on_sale' === $key ) {
			$chips[] = array( __( 'Offers only', 'premium-shop' ), $remove( $key ) );
		} elseif ( 'rating_filter' === $key ) {
			$min = min( array_map( 'absint', explode( ',', $value ) ) );
			/* translators: %d: number of stars. */
			$chips[] = array( sprintf( __( '%d stars & up', 'premium-shop' ), $min ), $remove( $key ) );
		} elseif ( 's' === $key ) {
			$chips[] = array( '“' . $value . '”', $remove( $key ) );
		} elseif ( 'ps_brand' === $key || 0 === strpos( $key, 'filter_' ) ) {
			$taxonomy = 'ps_brand' === $key ? premium_shop_brand_taxonomy() : wc_attribute_taxonomy_name( substr( $key, 7 ) );
			foreach ( explode( ',', $value ) as $slug ) {
				$term    = $taxonomy ? get_term_by( 'slug', $slug, $taxonomy ) : null;
				$chips[] = array( $term ? $term->name : $slug, $remove( $key, $slug ) );
			}
		}
	}

	return $chips;
}

/**
 * Toolbar: filter button (mobile), result count, chips, sorting, view switch.
 */
function premium_shop_shop_toolbar() {
	$chips = premium_shop_active_filter_chips();
	?>
	<div class="ps-toolbar">
		<div class="ps-toolbar__left">
			<?php if ( premium_shop_option( 'shop_filters' ) ) : ?>
				<button type="button" class="ps-btn ps-btn--ghost ps-btn--sm ps-toolbar__filters" data-ps-open="ps-shop-filters" aria-controls="ps-shop-filters" aria-expanded="false">
					<?php premium_shop_icon( 'filter', array( 'size' => 18 ) ); ?>
					<span><?php esc_html_e( 'Filters', 'premium-shop' ); ?></span>
					<?php if ( $chips ) : ?>
						<span class="ps-badge-count ps-badge-count--inline"><?php echo esc_html( count( $chips ) ); ?></span>
					<?php endif; ?>
				</button>
			<?php endif; ?>
			<?php woocommerce_result_count(); ?>
		</div>
		<div class="ps-toolbar__right">
			<?php woocommerce_catalog_ordering(); ?>
			<div class="ps-view-switch" role="group" aria-label="<?php esc_attr_e( 'Display', 'premium-shop' ); ?>">
				<button type="button" class="ps-icon-btn" data-ps-view="grid" aria-pressed="true">
					<?php premium_shop_icon( 'grid', array( 'size' => 18 ) ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Grid view', 'premium-shop' ); ?></span>
				</button>
				<button type="button" class="ps-icon-btn" data-ps-view="list" aria-pressed="false">
					<?php premium_shop_icon( 'list', array( 'size' => 18 ) ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'List view', 'premium-shop' ); ?></span>
				</button>
			</div>
		</div>
	</div>
	<?php if ( $chips ) : ?>
		<ul class="ps-active-filters" aria-label="<?php esc_attr_e( 'Active filters', 'premium-shop' ); ?>">
			<?php foreach ( $chips as $chip ) : ?>
				<li>
					<a class="ps-chip ps-chip--active" href="<?php echo esc_url( $chip[1] ); ?>" data-ps-filter-link>
						<?php echo esc_html( $chip[0] ); ?>
						<?php premium_shop_icon( 'close', array( 'size' => 14 ) ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Remove filter', 'premium-shop' ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
			<li><a class="ps-link-reset" href="<?php echo esc_url( premium_shop_listing_base_url() ); ?>" data-ps-filter-link><?php esc_html_e( 'Reset all', 'premium-shop' ); ?></a></li>
		</ul>
	<?php endif; ?>
	<?php
}

// The toolbar replaces the default count & ordering positions.
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

