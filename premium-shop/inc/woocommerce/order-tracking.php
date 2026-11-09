<?php
/**
 * Order tracking: status timeline, delivery date and tracking number.
 *
 * - Order screen (admin): box "Delivery & tracking" — planned delivery date,
 *   carrier, tracking number (the tracking link is built automatically).
 * - Customer: the "Order tracking" page (WooCommerce [woocommerce_order_tracking]:
 *   order number + e-mail), My account → Orders and the order e-mails show a
 *   timeline (received → in preparation → on its way → delivered) and the
 *   delivery details.
 *
 * HPOS compatible (order meta through the WC_Order API only).
 *
 * @package Premium_Shop\WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Carriers with their tracking URL (%s = tracking number).
 *
 * @return array key => [ label, url ].
 */
function premium_shop_tracking_carriers() {
	return apply_filters(
		'premium_shop_tracking_carriers',
		array(
			'own'   => array( __( 'Our own delivery', 'premium-shop' ), '' ),
			'post'  => array( 'Die Post / La Poste (CH)', 'https://service.post.ch/ekp-web/ui/entry/search/%s' ),
			'dhl'   => array( 'DHL', 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode=%s' ),
			'dpd'   => array( 'DPD', 'https://tracking.dpd.de/status/de_DE/parcel/%s' ),
			'gls'   => array( 'GLS', 'https://gls-group.com/DE/de/paketverfolgung?match=%s' ),
			'ups'   => array( 'UPS', 'https://www.ups.com/track?tracknum=%s' ),
			'other' => array( __( 'Other carrier', 'premium-shop' ), '' ),
		)
	);
}

/**
 * URL of the order tracking page ('' when there is none).
 *
 * @return string
 */
function premium_shop_order_tracking_url() {
	$id = (int) get_option( 'premium_shop_tracking_page' );
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		$page = get_page_by_path( 'sendungsverfolgung', OBJECT, 'page' );
		$id   = $page && 'publish' === $page->post_status ? $page->ID : 0;
	}
	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Whether a page is the order tracking page.
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function premium_shop_is_tracking_page( $page_id ) {
	$page_id = (int) $page_id;
	if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
		return false;
	}
	if ( (int) get_option( 'premium_shop_tracking_page' ) === $page_id || 'sendungsverfolgung' === get_post_field( 'post_name', $page_id ) ) {
		return true;
	}
	return has_shortcode( (string) get_post_field( 'post_content', $page_id ), 'woocommerce_order_tracking' );
}

/**
 * Delivery data of an order.
 *
 * @param WC_Order $order Order.
 * @return array carrier, carrier_label, number, url, date (Y-m-d), note.
 */
function premium_shop_order_delivery( $order ) {
	$carriers = premium_shop_tracking_carriers();
	$carrier  = (string) $order->get_meta( '_ps_carrier' );
	$number   = (string) $order->get_meta( '_ps_tracking_number' );
	$url      = (string) $order->get_meta( '_ps_tracking_url' );

	if ( '' === $url && '' !== $number && isset( $carriers[ $carrier ] ) && $carriers[ $carrier ][1] ) {
		$url = sprintf( $carriers[ $carrier ][1], rawurlencode( $number ) );
	}

	return array(
		'carrier'       => $carrier,
		'carrier_label' => isset( $carriers[ $carrier ] ) && 'other' !== $carrier ? $carriers[ $carrier ][0] : '',
		'number'        => $number,
		'url'           => $url,
		'date'          => (string) $order->get_meta( '_ps_delivery_date' ),
		'note'          => (string) $order->get_meta( '_ps_delivery_note' ),
	);
}

/* --------------------------------------------------------------------------
 * Admin: "Delivery & tracking" box on the order screen.
 * ----------------------------------------------------------------------- */

/**
 * Register the box (classic orders and HPOS).
 */
function premium_shop_tracking_meta_box() {
	$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box( 'ps-order-tracking', __( 'Delivery & tracking', 'premium-shop' ), 'premium_shop_tracking_meta_box_html', $screen, 'side', 'high' );
}
add_action( 'add_meta_boxes', 'premium_shop_tracking_meta_box' );

/**
 * Box content.
 *
 * @param WP_Post|WC_Order $post_or_order Post (classic) or order (HPOS).
 */
function premium_shop_tracking_meta_box_html( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	$d = premium_shop_order_delivery( $order );
	wp_nonce_field( 'premium_shop_tracking', 'ps_tracking_nonce' );
	?>
	<p>
		<label for="ps_delivery_date"><strong><?php esc_html_e( 'Planned delivery date', 'premium-shop' ); ?></strong></label><br>
		<input type="date" id="ps_delivery_date" name="ps_delivery_date" value="<?php echo esc_attr( $d['date'] ); ?>" style="width:100%">
	</p>
	<p>
		<label for="ps_carrier"><strong><?php esc_html_e( 'Carrier', 'premium-shop' ); ?></strong></label><br>
		<select id="ps_carrier" name="ps_carrier" style="width:100%">
			<option value=""><?php esc_html_e( '— None —', 'premium-shop' ); ?></option>
			<?php foreach ( premium_shop_tracking_carriers() as $key => $carrier ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $d['carrier'], $key ); ?>><?php echo esc_html( $carrier[0] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="ps_tracking_number"><strong><?php esc_html_e( 'Tracking number', 'premium-shop' ); ?></strong></label><br>
		<input type="text" id="ps_tracking_number" name="ps_tracking_number" value="<?php echo esc_attr( $d['number'] ); ?>" style="width:100%">
	</p>
	<p>
		<label for="ps_tracking_url"><?php esc_html_e( 'Tracking link (only for “Other carrier”)', 'premium-shop' ); ?></label><br>
		<input type="url" id="ps_tracking_url" name="ps_tracking_url" value="<?php echo esc_attr( (string) $order->get_meta( '_ps_tracking_url' ) ); ?>" placeholder="https://" style="width:100%">
	</p>
	<p>
		<label for="ps_delivery_note"><?php esc_html_e( 'Note for the customer (e.g. delivery time window)', 'premium-shop' ); ?></label><br>
		<textarea id="ps_delivery_note" name="ps_delivery_note" rows="2" style="width:100%"><?php echo esc_textarea( $d['note'] ); ?></textarea>
	</p>
	<p class="description"><?php esc_html_e( 'Shown to the customer on the order tracking page, in their account and in the order e-mails.', 'premium-shop' ); ?></p>
	<?php
}

/**
 * Save the box.
 *
 * @param int $order_id Order ID.
 */
function premium_shop_tracking_save( $order_id ) {
	if ( ! isset( $_POST['ps_tracking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ps_tracking_nonce'] ) ), 'premium_shop_tracking' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order || ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}

	$date    = isset( $_POST['ps_delivery_date'] ) ? sanitize_text_field( wp_unslash( $_POST['ps_delivery_date'] ) ) : '';
	$carrier = isset( $_POST['ps_carrier'] ) ? sanitize_key( wp_unslash( $_POST['ps_carrier'] ) ) : '';

	$order->update_meta_data( '_ps_delivery_date', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '' );
	$order->update_meta_data( '_ps_carrier', array_key_exists( $carrier, premium_shop_tracking_carriers() ) ? $carrier : '' );
	$order->update_meta_data( '_ps_tracking_number', isset( $_POST['ps_tracking_number'] ) ? sanitize_text_field( wp_unslash( $_POST['ps_tracking_number'] ) ) : '' );
	$order->update_meta_data( '_ps_tracking_url', isset( $_POST['ps_tracking_url'] ) ? esc_url_raw( wp_unslash( $_POST['ps_tracking_url'] ) ) : '' );
	$order->update_meta_data( '_ps_delivery_note', isset( $_POST['ps_delivery_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ps_delivery_note'] ) ) : '' );
	$order->save_meta_data();
}
add_action( 'woocommerce_process_shop_order_meta', 'premium_shop_tracking_save', 20 );

/* --------------------------------------------------------------------------
 * Customer: timeline + delivery card (tracking page and My account).
 * ----------------------------------------------------------------------- */

/**
 * Timeline steps for an order.
 *
 * @param WC_Order $order Order.
 * @return array|null Steps [ label, icon, state ] or null for cancelled/failed/refunded orders.
 */
function premium_shop_order_steps( $order ) {
	$status = $order->get_status();
	if ( in_array( $status, array( 'cancelled', 'failed', 'refunded', 'trash' ), true ) ) {
		return null;
	}

	$d         = premium_shop_order_delivery( $order );
	$completed = 'completed' === $status;
	$paid      = $completed || $order->is_paid() || 'processing' === $status;
	$shipped   = $completed || '' !== $d['number'] || '' !== $d['date'];

	$current = $completed ? 4 : ( $shipped ? 3 : ( $paid ? 2 : 1 ) );

	$steps = array(
		array( __( 'Order received', 'premium-shop' ), 'check' ),
		array( 'on-hold' === $status || 'pending' === $status ? __( 'Waiting for payment', 'premium-shop' ) : __( 'In preparation', 'premium-shop' ), 'box' ),
		array( '' !== $d['date'] && '' === $d['number'] ? __( 'Delivery scheduled', 'premium-shop' ) : __( 'On its way', 'premium-shop' ), 'truck' ),
		array( __( 'Delivered', 'premium-shop' ), 'home' ),
	);

	foreach ( $steps as $i => $step ) {
		$n               = $i + 1;
		$steps[ $i ][2] = $n < $current || ( 4 === $n && $completed ) ? 'done' : ( $n === $current ? 'current' : 'todo' );
	}

	return apply_filters( 'premium_shop_order_steps', $steps, $order );
}

/**
 * Timeline and delivery details.
 *
 * @param int $order_id Order ID.
 */
function premium_shop_order_tracking_details( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$steps = premium_shop_order_steps( $order );
	$d     = premium_shop_order_delivery( $order );
	?>
	<section class="ps-order-track" aria-label="<?php esc_attr_e( 'Order status', 'premium-shop' ); ?>">
		<?php if ( null === $steps ) : ?>
			<p class="ps-notice ps-notice--error">
				<?php
				/* translators: %s: order status, e.g. Cancelled. */
				echo esc_html( sprintf( __( 'This order is %s. Please contact us if you have any questions.', 'premium-shop' ), wc_get_order_status_name( $order->get_status() ) ) );
				?>
			</p>
		<?php else : ?>
			<ol class="ps-order-steps">
				<?php foreach ( $steps as $step ) : ?>
					<li class="ps-order-steps__step is-<?php echo esc_attr( $step[2] ); ?>"<?php echo 'current' === $step[2] ? ' aria-current="step"' : ''; ?>>
						<span class="ps-order-steps__dot" aria-hidden="true"><?php premium_shop_icon( 'done' === $step[2] ? 'check' : $step[1], array( 'size' => 18 ) ); ?></span>
						<span class="ps-order-steps__label"><?php echo esc_html( $step[0] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<?php if ( $d['date'] || $d['number'] || $d['note'] ) : ?>
			<div class="ps-order-delivery">
				<?php if ( $d['date'] ) : ?>
					<p class="ps-order-delivery__row">
						<?php premium_shop_icon( 'clock', array( 'size' => 18 ) ); ?>
						<span><?php esc_html_e( 'Planned delivery date', 'premium-shop' ); ?>: <strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['date'] . ' 12:00:00' ) ) ); ?></strong></span>
					</p>
				<?php endif; ?>
				<?php if ( $d['number'] ) : ?>
					<p class="ps-order-delivery__row">
						<?php premium_shop_icon( 'truck', array( 'size' => 18 ) ); ?>
						<span>
							<?php echo $d['carrier_label'] ? esc_html( $d['carrier_label'] ) . ' — ' : ''; ?>
							<?php esc_html_e( 'Tracking number', 'premium-shop' ); ?>: <strong><?php echo esc_html( $d['number'] ); ?></strong>
						</span>
					</p>
					<?php if ( $d['url'] ) : ?>
						<p><a class="ps-btn ps-btn--primary" href="<?php echo esc_url( $d['url'] ); ?>" target="_blank" rel="noopener"><span><?php esc_html_e( 'Track my parcel', 'premium-shop' ); ?></span><?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?></a></p>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( $d['note'] ) : ?>
					<p class="ps-order-delivery__note"><?php echo esc_html( $d['note'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
}
add_action( 'woocommerce_view_order', 'premium_shop_order_tracking_details', 5 );

/**
 * Delivery details in the customer e-mails.
 *
 * @param WC_Order $order         Order.
 * @param bool     $sent_to_admin Sent to the shop.
 * @param bool     $plain_text    Plain-text e-mail.
 */
function premium_shop_order_tracking_email( $order, $sent_to_admin, $plain_text ) {
	if ( $sent_to_admin || ! $order instanceof WC_Order ) {
		return;
	}
	$d = premium_shop_order_delivery( $order );
	if ( ! $d['date'] && ! $d['number'] && ! $d['note'] ) {
		return;
	}

	$lines = array();
	if ( $d['date'] ) {
		$lines[] = __( 'Planned delivery date', 'premium-shop' ) . ': ' . date_i18n( get_option( 'date_format' ), strtotime( $d['date'] . ' 12:00:00' ) );
	}
	if ( $d['number'] ) {
		$lines[] = ( $d['carrier_label'] ? $d['carrier_label'] . ' — ' : '' ) . __( 'Tracking number', 'premium-shop' ) . ': ' . $d['number'];
	}
	if ( $d['note'] ) {
		$lines[] = $d['note'];
	}

	if ( $plain_text ) {
		echo "\n" . esc_html( strtoupper( __( 'Delivery', 'premium-shop' ) ) ) . "\n" . esc_html( implode( "\n", $lines ) ) . ( $d['url'] ? "\n" . esc_url( $d['url'] ) : '' ) . "\n\n";
		return;
	}

	echo '<h2>' . esc_html__( 'Delivery', 'premium-shop' ) . '</h2><p>' . wp_kses_post( implode( '<br>', array_map( 'esc_html', $lines ) ) ) . '</p>';
	if ( $d['url'] ) {
		echo '<p><a href="' . esc_url( $d['url'] ) . '">' . esc_html__( 'Track my parcel', 'premium-shop' ) . '</a></p>';
	}
}
add_action( 'woocommerce_email_order_meta', 'premium_shop_order_tracking_email', 20, 3 );
