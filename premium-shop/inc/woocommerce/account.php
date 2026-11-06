<?php
/**
 * My account: icons in the navigation and a visual dashboard.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon for each account endpoint.
 *
 * @param string $endpoint Endpoint.
 * @return string
 */
function premium_shop_account_icon( $endpoint ) {
	$map = array(
		'dashboard'       => 'home',
		'orders'          => 'box',
		'downloads'       => 'download',
		'edit-address'    => 'pin',
		'payment-methods' => 'card',
		'edit-account'    => 'user',
		'customer-logout' => 'logout',
	);

	return isset( $map[ $endpoint ] ) ? $map[ $endpoint ] : 'arrow';
}

/**
 * Dashboard tiles under the default welcome text.
 */
function premium_shop_account_dashboard() {
	$tiles = array();

	foreach ( wc_get_account_menu_items() as $endpoint => $label ) {
		if ( in_array( $endpoint, array( 'dashboard', 'customer-logout' ), true ) ) {
			continue;
		}
		$tiles[] = array( $endpoint, $label, wc_get_account_endpoint_url( $endpoint ) );
	}

	if ( ! $tiles ) {
		return;
	}

	$customer = new WC_Customer( get_current_user_id() );
	?>
	<div class="ps-account-tiles">
		<?php foreach ( $tiles as $tile ) : ?>
			<a class="ps-account-tile" href="<?php echo esc_url( $tile[2] ); ?>">
				<span class="ps-account-tile__icon"><?php premium_shop_icon( premium_shop_account_icon( $tile[0] ), array( 'size' => 22 ) ); ?></span>
				<span class="ps-account-tile__label"><?php echo esc_html( $tile[1] ); ?></span>
				<?php if ( 'orders' === $tile[0] ) : ?>
					<span class="ps-account-tile__meta">
						<?php
						$count = (int) $customer->get_order_count();
						/* translators: %d: number of orders. */
						echo esc_html( sprintf( _n( '%d order', '%d orders', $count, 'premium-shop' ), $count ) );
						?>
					</span>
				<?php endif; ?>
				<?php premium_shop_icon( 'arrow', array( 'size' => 16, 'class' => 'ps-account-tile__arrow' ) ); ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
add_action( 'woocommerce_account_dashboard', 'premium_shop_account_dashboard' );

/**
 * Friendlier label for the dashboard entry.
 * (Navigation icons are added in CSS from WooCommerce's endpoint classes,
 * so navigation.php does not need to be overridden.)
 *
 * @param array $items Items.
 * @return array
 */
function premium_shop_account_menu_items( $items ) {
	if ( isset( $items['dashboard'] ) ) {
		$items['dashboard'] = __( 'Overview', 'premium-shop' );
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'premium_shop_account_menu_items' );

/**
 * Greeting above the account content.
 */
function premium_shop_account_greeting() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$user = wp_get_current_user();
	$name = $user->first_name ? $user->first_name : $user->display_name;
	?>
	<div class="ps-account-hello">
		<span class="ps-account-hello__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></span>
		<div>
			<p class="ps-eyebrow"><?php esc_html_e( 'My account', 'premium-shop' ); ?></p>
			<?php /* translators: %s: customer name. */ ?>
			<p class="ps-account-hello__name"><?php echo esc_html( sprintf( __( 'Hello %s', 'premium-shop' ), $name ) ); ?></p>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_account_navigation', 'premium_shop_account_greeting', 5 );
