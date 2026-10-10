<?php
/**
 * Built-in newsletter sign-up with double opt-in (GDPR / DSGVO).
 *
 * Used only when no newsletter plugin shortcode is configured. Subscribers are
 * stored as a private post type (Tools → Newsletter) and can be exported to CSV.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the private subscriber post type.
 */
function premium_shop_register_subscriber_cpt() {
	$cap = 'manage_options';

	register_post_type(
		'ps_subscriber',
		array(
			'labels'              => array(
				'name'          => __( 'Newsletter', 'premium-shop' ),
				'singular_name' => __( 'Subscriber', 'premium-shop' ),
				'menu_name'     => __( 'Newsletter', 'premium-shop' ),
				'all_items'     => __( 'Newsletter subscribers', 'premium-shop' ),
				'not_found'     => __( 'No subscribers yet.', 'premium-shop' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'tools.php',
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'supports'            => array( 'title' ),
			'map_meta_cap'        => false,
			'capabilities'        => array(
				'edit_post'          => $cap,
				'read_post'          => $cap,
				'delete_post'        => $cap,
				'edit_posts'         => $cap,
				'edit_others_posts'  => $cap,
				'delete_posts'       => $cap,
				'publish_posts'      => $cap,
				'read_private_posts' => $cap,
				'create_posts'       => 'do_not_allow',
			),
		)
	);
}
add_action( 'init', 'premium_shop_register_subscriber_cpt' );

/**
 * Process a sign-up (shared by the AJAX and the no-JS handlers).
 *
 * @return array [ bool success, string message ]
 */
function premium_shop_newsletter_process() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified below.
	$nonce = isset( $_POST['ps_newsletter_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ps_newsletter_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'premium_shop_newsletter' ) ) {
		return array( false, __( 'Your session has expired. Please reload the page and try again.', 'premium-shop' ) );
	}

	// Honeypot: bots fill every field.
	if ( ! empty( $_POST['ps_website'] ) ) {
		return array( true, __( 'Almost done! Please check your inbox to confirm your subscription.', 'premium-shop' ) );
	}

	$email   = isset( $_POST['ps_email'] ) ? sanitize_email( wp_unslash( $_POST['ps_email'] ) ) : '';
	$consent = ! empty( $_POST['ps_consent'] );
	$lang    = isset( $_POST['ps_lang'] ) ? sanitize_key( wp_unslash( $_POST['ps_lang'] ) ) : premium_shop_default_language();
	// phpcs:enable

	if ( ! is_email( $email ) ) {
		return array( false, __( 'Please enter a valid email address.', 'premium-shop' ) );
	}
	if ( ! $consent ) {
		return array( false, __( 'Please accept the privacy conditions.', 'premium-shop' ) );
	}

	// Basic rate limiting per IP (hashed, not stored in clear).
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rl_key  = 'ps_nl_rl_' . md5( $ip . wp_salt( 'nonce' ) );
	$attempt = (int) get_transient( $rl_key );
	if ( $attempt >= 5 ) {
		return array( false, __( 'Too many attempts. Please try again later.', 'premium-shop' ) );
	}
	set_transient( $rl_key, $attempt + 1, HOUR_IN_SECONDS );

	$existing = get_posts(
		array(
			'post_type'      => 'ps_subscriber',
			'post_status'    => array( 'publish', 'pending' ),
			'title'          => $email,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( $existing && 'publish' === get_post_status( $existing[0] ) ) {
		return array( true, __( 'You are already subscribed. Thank you!', 'premium-shop' ) );
	}

	$token = wp_generate_password( 32, false, false );

	if ( $existing ) {
		$id = (int) $existing[0];
	} else {
		$id = wp_insert_post(
			array(
				'post_type'   => 'ps_subscriber',
				'post_status' => 'pending',
				'post_title'  => $email,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return array( false, __( 'Something went wrong. Please try again.', 'premium-shop' ) );
		}
	}

	update_post_meta( $id, '_ps_token', wp_hash_password( $token ) );
	update_post_meta( $id, '_ps_lang', $lang );
	update_post_meta( $id, '_ps_consent_at', gmdate( 'c' ) );

	premium_shop_newsletter_send_confirmation( $id, $email, $token );

	return array( true, __( 'Almost done! Please check your inbox to confirm your subscription.', 'premium-shop' ) );
}

/**
 * Send the double opt-in email.
 *
 * @param int    $id    Subscriber ID.
 * @param string $email Email.
 * @param string $token Clear token.
 */
function premium_shop_newsletter_send_confirmation( $id, $email, $token ) {
	$link = add_query_arg(
		array(
			'ps_nl_confirm' => $id,
			'ps_nl_token'   => $token,
		),
		home_url( '/' )
	);

	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	/* translators: %s: site name. */
	$subject = sprintf( __( 'Please confirm your subscription to %s', 'premium-shop' ), $site );
	$message = sprintf(
		/* translators: %s: site name. */
		__( 'Hello, thank you for your interest in the %s newsletter. Please confirm your subscription by clicking the link below:', 'premium-shop' ),
		$site
	);
	$message .= "\n\n" . $link . "\n\n";
	$message .= __( 'If you did not request this, simply ignore this email — you will not be subscribed.', 'premium-shop' ) . "\n\n";
	$message .= __( 'Best regards,', 'premium-shop' ) . "\n" . $site;

	wp_mail( $email, $subject, $message );
}

/**
 * AJAX handler.
 */
function premium_shop_newsletter_ajax() {
	list( $ok, $message ) = premium_shop_newsletter_process();

	if ( $ok ) {
		wp_send_json_success( array( 'message' => $message ) );
	}
	wp_send_json_error( array( 'message' => $message ), 400 );
}
add_action( 'wp_ajax_premium_shop_newsletter', 'premium_shop_newsletter_ajax' );
add_action( 'wp_ajax_nopriv_premium_shop_newsletter', 'premium_shop_newsletter_ajax' );

/**
 * No-JS handler (admin-post.php).
 */
function premium_shop_newsletter_post() {
	list( $ok, $message ) = premium_shop_newsletter_process();

	$msg_id   = wp_generate_password( 12, false, false );
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$redirect = add_query_arg(
		array(
			'ps_nl'    => $ok ? 'ok' : 'error',
			'ps_nl_id' => $msg_id,
		),
		remove_query_arg( array( 'ps_nl', 'ps_nl_id' ), $redirect )
	);
	set_transient( 'ps_nl_msg_' . $msg_id, $message, 120 );

	wp_safe_redirect( $redirect . '#ps-newsletter' );
	exit;
}
add_action( 'admin_post_premium_shop_newsletter', 'premium_shop_newsletter_post' );
add_action( 'admin_post_nopriv_premium_shop_newsletter', 'premium_shop_newsletter_post' );

/**
 * Confirmation link handler.
 */
function premium_shop_newsletter_confirm() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Token verified instead (email link).
	if ( empty( $_GET['ps_nl_confirm'] ) || empty( $_GET['ps_nl_token'] ) ) {
		return;
	}

	$id    = absint( $_GET['ps_nl_confirm'] );
	$token = sanitize_text_field( wp_unslash( $_GET['ps_nl_token'] ) );
	// phpcs:enable

	$hash = (string) get_post_meta( $id, '_ps_token', true );
	$ok   = $hash && 'ps_subscriber' === get_post_type( $id ) && wp_check_password( $token, $hash );

	if ( $ok ) {
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $id, '_ps_confirmed_at', gmdate( 'c' ) );
		delete_post_meta( $id, '_ps_token' );
	}

	wp_safe_redirect( add_query_arg( 'ps_nl', $ok ? 'confirmed' : 'invalid', home_url( '/' ) ) );
	exit;
}
add_action( 'template_redirect', 'premium_shop_newsletter_confirm', 1 );

/**
 * Notice after confirmation / no-JS submit.
 */
function premium_shop_newsletter_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$state = isset( $_GET['ps_nl'] ) ? sanitize_key( wp_unslash( $_GET['ps_nl'] ) ) : '';
	if ( ! $state ) {
		return;
	}

	$messages = array(
		'confirmed' => __( 'Thank you! Your subscription is confirmed.', 'premium-shop' ),
		'invalid'   => __( 'This confirmation link is invalid or has already been used.', 'premium-shop' ),
	);

	$message = isset( $messages[ $state ] ) ? $messages[ $state ] : '';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$msg_id = isset( $_GET['ps_nl_id'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET['ps_nl_id'] ) ) ) : '';
	if ( ! $message && $msg_id && in_array( $state, array( 'ok', 'error' ), true ) ) {
		$message = (string) get_transient( 'ps_nl_msg_' . $msg_id );
		delete_transient( 'ps_nl_msg_' . $msg_id );
	}

	if ( $message ) {
		printf( '<div class="ps-flash" role="status"><div class="ps-container">%s</div></div>', esc_html( $message ) );
	}
}
add_action( 'wp_body_open', 'premium_shop_newsletter_notice', 20 );

/**
 * Admin list columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function premium_shop_subscriber_columns( $columns ) {
	return array(
		'cb'        => $columns['cb'],
		'title'     => __( 'Email', 'premium-shop' ),
		'ps_status' => __( 'Status', 'premium-shop' ),
		'ps_lang'   => __( 'Language', 'premium-shop' ),
		'date'      => __( 'Date', 'premium-shop' ),
	);
}
add_filter( 'manage_ps_subscriber_posts_columns', 'premium_shop_subscriber_columns' );

/**
 * Admin list column values.
 *
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function premium_shop_subscriber_column_values( $column, $post_id ) {
	if ( 'ps_status' === $column ) {
		echo 'publish' === get_post_status( $post_id ) ? esc_html__( 'Confirmed', 'premium-shop' ) : esc_html__( 'Awaiting confirmation', 'premium-shop' );
	} elseif ( 'ps_lang' === $column ) {
		echo esc_html( strtoupper( (string) get_post_meta( $post_id, '_ps_lang', true ) ) );
	}
}
add_action( 'manage_ps_subscriber_posts_custom_column', 'premium_shop_subscriber_column_values', 10, 2 );

/**
 * "Export CSV" button above the subscriber list.
 *
 * @param string $which top|bottom.
 */
function premium_shop_subscriber_export_button( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'edit-ps_subscriber' !== $screen->id ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=premium_shop_export_subscribers' ), 'premium_shop_export_subscribers' );
	printf( '<div class="alignleft actions"><a class="button" href="%s">%s</a></div>', esc_url( $url ), esc_html__( 'Export confirmed subscribers (CSV)', 'premium-shop' ) );
}
add_action( 'manage_posts_extra_tablenav', 'premium_shop_subscriber_export_button' );

/**
 * CSV export of confirmed subscribers.
 */
function premium_shop_export_subscribers() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'premium-shop' ), 403 );
	}
	check_admin_referer( 'premium_shop_export_subscribers' );

	$ids = get_posts(
		array(
			'post_type'      => 'ps_subscriber',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=newsletter-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	fputcsv( $out, array( 'email', 'language', 'consent_at', 'confirmed_at' ) );
	foreach ( $ids as $id ) {
		$email = get_the_title( $id );
		// Neutralise spreadsheet formulas.
		if ( preg_match( '/^[=+\-@]/', $email ) ) {
			$email = "'" . $email;
		}
		fputcsv(
			$out,
			array(
				$email,
				get_post_meta( $id, '_ps_lang', true ),
				get_post_meta( $id, '_ps_consent_at', true ),
				get_post_meta( $id, '_ps_confirmed_at', true ),
			)
		);
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_premium_shop_export_subscribers', 'premium_shop_export_subscribers' );
