<?php
/**
 * Products → Firewood prices: change all prices in one screen.
 *
 * - Edit the price per unit of every firewood product in a single table.
 * - Raise or lower all prices by a percentage (seasonal price changes).
 * - Every variation price is recalculated automatically.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu entry.
 */
function premium_shop_fw_price_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Firewood prices', 'premium-shop' ),
		__( 'Firewood prices', 'premium-shop' ),
		'manage_woocommerce',
		'premium-shop-prices',
		'premium_shop_fw_price_page'
	);
}
add_action( 'admin_menu', 'premium_shop_fw_price_menu', 60 );

/**
 * Firewood products (products with firewood data).
 *
 * @return WC_Product[]
 */
function premium_shop_fw_products() {
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_ps_unit',
					'value'   => '',
					'compare' => '!=',
				),
				array(
					'key'     => '_ps_species',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	return array_filter( array_map( 'wc_get_product', $ids ) );
}

/**
 * Admin page.
 */
function premium_shop_fw_price_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$products = premium_shop_fw_products();
	$currency = get_woocommerce_currency_symbol();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$updated = isset( $_GET['ps_updated'] ) ? absint( $_GET['ps_updated'] ) : null;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Firewood prices', 'premium-shop' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Set the price per unit once — the prices of all quantities (variations) are calculated automatically, including quantity discounts. Ideal for seasonal price changes.', 'premium-shop' ); ?></p>

		<?php if ( null !== $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				/* translators: %d: number of prices. */
				echo esc_html( sprintf( _n( '%d price updated.', '%d prices updated.', $updated, 'premium-shop' ), $updated ) );
				?>
			</p></div>
		<?php endif; ?>

		<?php if ( ! $products ) : ?>
			<div class="notice notice-info inline"><p>
				<?php esc_html_e( 'No firewood products yet. Fill the "Firewood" tab of your products, or create the sample catalogue in Appearance → Shop setup.', 'premium-shop' ); ?>
			</p></div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="premium_shop_fw_prices" />
				<?php wp_nonce_field( 'premium_shop_fw_prices' ); ?>

				<table class="widefat striped" style="max-width:1100px;margin-top:16px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'premium-shop' ); ?></th>
							<th><?php esc_html_e( 'Status', 'premium-shop' ); ?></th>
							<th><?php esc_html_e( 'Sales unit', 'premium-shop' ); ?></th>
							<th><?php echo esc_html( __( 'Price per unit', 'premium-shop' ) . ' (' . $currency . ')' ); ?></th>
							<th><?php esc_html_e( 'Automatic', 'premium-shop' ); ?></th>
							<th><?php esc_html_e( 'Current prices', 'premium-shop' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $products as $product ) : ?>
							<?php $id = $product->get_id(); ?>
							<tr>
								<td><strong><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></strong></td>
								<td><?php echo esc_html( get_post_status_object( $product->get_status() ) ? get_post_status_object( $product->get_status() )->label : $product->get_status() ); ?></td>
								<td><?php echo esc_html( premium_shop_fw_unit_label( premium_shop_fw_unit( $product ) ) ); ?></td>
								<td><input type="text" class="short wc_input_price" name="ps_price[<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_attr( wc_format_localized_price( $product->get_meta( '_ps_price_per_unit' ) ) ); ?>" style="width:110px" /></td>
								<td><input type="checkbox" name="ps_auto[<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( 'yes', $product->get_meta( '_ps_auto_prices' ) ); ?> /></td>
								<td><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p style="margin-top:18px">
					<label>
						<?php esc_html_e( 'Change all prices per unit by', 'premium-shop' ); ?>
						<input type="number" name="ps_percent" step="0.1" value="" placeholder="0" style="width:80px" /> %
					</label>
					<span class="description"><?php esc_html_e( '(e.g. 5 = +5%, -3 = −3%; leave empty to keep the values above)', 'premium-shop' ); ?></span>
				</p>
				<p>
					<label><input type="checkbox" name="ps_round" value="1" checked /> <?php esc_html_e( 'Round prices per unit to whole amounts', 'premium-shop' ); ?></label>
				</p>
				<?php submit_button( __( 'Save & recalculate all prices', 'premium-shop' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save the price table.
 */
function premium_shop_fw_save_prices() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'premium-shop' ), 403 );
	}
	check_admin_referer( 'premium_shop_fw_prices' );

	$prices  = isset( $_POST['ps_price'] ) && is_array( $_POST['ps_price'] ) ? wp_unslash( $_POST['ps_price'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per item below.
	$auto    = isset( $_POST['ps_auto'] ) && is_array( $_POST['ps_auto'] ) ? array_map( 'absint', array_keys( wp_unslash( $_POST['ps_auto'] ) ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$percent = isset( $_POST['ps_percent'] ) ? (float) premium_shop_fw_number( sanitize_text_field( wp_unslash( $_POST['ps_percent'] ) ) ) : 0.0;
	$round   = ! empty( $_POST['ps_round'] );
	$count   = 0;

	foreach ( $prices as $id => $raw ) {
		$product = wc_get_product( absint( $id ) );
		if ( ! $product || ! current_user_can( 'edit_post', $product->get_id() ) ) {
			continue;
		}

		$price = (float) wc_format_decimal( sanitize_text_field( $raw ) );
		if ( $percent && $price > 0 ) {
			$price = $price * ( 1 + $percent / 100 );
		}
		if ( $price > 0 ) {
			$price = $round ? round( $price ) : round( $price, wc_get_price_decimals() );
		}

		$product->update_meta_data( '_ps_price_per_unit', $price > 0 ? (string) $price : '' );
		$product->update_meta_data( '_ps_auto_prices', in_array( $product->get_id(), $auto, true ) ? 'yes' : '' );
		$product->save();

		$count += premium_shop_fw_recalculate( $product->get_id() );
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=premium-shop-prices&ps_updated=' . $count ) );
	exit;
}
add_action( 'admin_post_premium_shop_fw_prices', 'premium_shop_fw_save_prices' );
