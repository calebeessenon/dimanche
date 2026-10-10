<?php
/**
 * Contact form (page template "Contact" and [ps_contact_form]).
 *
 * - Sent by e-mail to the shop (Customizer → Contact → Email, or the site
 *   e-mail) with the customer as Reply-To.
 * - Every message is also stored in the admin (Contact messages), so nothing
 *   is lost when the hosting's e-mail sending fails. Messages older than one
 *   year are deleted automatically (GDPR: storage limitation).
 * - Spam protection without third-party service: invisible honeypot field,
 *   minimum filling time, nonce and a limit per visitor.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Storage for received messages (admin only).
 */
function premium_shop_contact_register_type() {
	register_post_type(
		'ps_message',
		array(
			'labels'          => array(
				'name'          => __( 'Contact messages', 'premium-shop' ),
				'singular_name' => __( 'Contact message', 'premium-shop' ),
				'menu_name'     => __( 'Contact messages', 'premium-shop' ),
				'edit_item'     => __( 'Contact message', 'premium-shop' ),
				'search_items'  => __( 'Search messages', 'premium-shop' ),
				'not_found'     => __( 'No messages yet.', 'premium-shop' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 26,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'rewrite'         => false,
			'query_var'       => false,
		)
	);
}
add_action( 'init', 'premium_shop_contact_register_type' );

/**
 * Subjects offered in the form.
 *
 * @return array key => label.
 */
function premium_shop_contact_subjects() {
	return apply_filters(
		'premium_shop_contact_subjects',
		array(
			'question' => __( 'General question', 'premium-shop' ),
			'order'    => __( 'My order / delivery', 'premium-shop' ),
			'advice'   => __( 'Product advice', 'premium-shop' ),
			'quote'    => __( 'Quote for a large quantity', 'premium-shop' ),
			'other'    => __( 'Other', 'premium-shop' ),
		)
	);
}

/**
 * Recipient of contact messages.
 *
 * @return string
 */
function premium_shop_contact_recipient() {
	$email = sanitize_email( (string) premium_shop_option( 'contact_email' ) );
	return (string) apply_filters( 'premium_shop_contact_recipient', is_email( $email ) ? $email : get_option( 'admin_email' ) );
}

/**
 * Render the contact form.
 */
function premium_shop_contact_form() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status message after redirect.
	$status   = isset( $_GET['ps_contact'] ) ? sanitize_key( wp_unslash( $_GET['ps_contact'] ) ) : '';
	$user     = wp_get_current_user();
	$privacy  = get_privacy_policy_url();
	$subjects = premium_shop_contact_subjects();
	$uid      = 'ps-contact-' . wp_rand( 100, 999 );
	?>
	<div class="ps-contact-form-wrap" id="ps-contact-form">
		<div class="ps-contact-form__status" role="status" aria-live="polite" data-ps-contact-status>
			<?php if ( 'sent' === $status ) : ?>
				<p class="ps-notice ps-notice--success"><?php esc_html_e( 'Thank you! Your message has been sent. We will get back to you as soon as possible.', 'premium-shop' ); ?></p>
			<?php elseif ( $status ) : ?>
				<p class="ps-notice ps-notice--error"><?php echo esc_html( premium_shop_contact_error_message( $status ) ); ?></p>
			<?php endif; ?>
		</div>

		<form class="ps-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ps-contact-form novalidate>
			<input type="hidden" name="action" value="premium_shop_contact">
			<input type="hidden" name="ps_back" value="<?php echo esc_url( get_permalink() ? get_permalink() : home_url( '/' ) ); ?>">
			<input type="hidden" name="ps_time" value="<?php echo esc_attr( time() ); ?>">
			<input type="hidden" name="ps_lang" value="<?php echo esc_attr( premium_shop_current_language() ); ?>">
			<?php wp_nonce_field( 'premium_shop_contact', 'ps_contact_nonce' ); ?>

			<div class="ps-contact-form__hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $uid ); ?>-website">Website</label>
				<input type="text" id="<?php echo esc_attr( $uid ); ?>-website" name="ps_website" tabindex="-1" autocomplete="off">
			</div>

			<div class="ps-contact-form__grid">
				<p class="ps-field">
					<label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Name', 'premium-shop' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="ps_name" required maxlength="100" autocomplete="name" value="<?php echo esc_attr( $user->exists() ? $user->display_name : '' ); ?>">
				</p>
				<p class="ps-field">
					<label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email address', 'premium-shop' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input type="email" id="<?php echo esc_attr( $uid ); ?>-email" name="ps_email" required maxlength="190" autocomplete="email" value="<?php echo esc_attr( $user->exists() ? $user->user_email : '' ); ?>">
				</p>
				<p class="ps-field">
					<label for="<?php echo esc_attr( $uid ); ?>-phone"><?php esc_html_e( 'Phone', 'premium-shop' ); ?> <span class="ps-field__optional"><?php esc_html_e( '(optional)', 'premium-shop' ); ?></span></label>
					<input type="tel" id="<?php echo esc_attr( $uid ); ?>-phone" name="ps_phone" maxlength="40" autocomplete="tel">
				</p>
				<p class="ps-field">
					<label for="<?php echo esc_attr( $uid ); ?>-order"><?php esc_html_e( 'Order number', 'premium-shop' ); ?> <span class="ps-field__optional"><?php esc_html_e( '(optional)', 'premium-shop' ); ?></span></label>
					<input type="text" id="<?php echo esc_attr( $uid ); ?>-order" name="ps_order" maxlength="40" inputmode="numeric">
				</p>
				<p class="ps-field ps-field--full">
					<label for="<?php echo esc_attr( $uid ); ?>-subject"><?php esc_html_e( 'Subject', 'premium-shop' ); ?></label>
					<select id="<?php echo esc_attr( $uid ); ?>-subject" name="ps_subject">
						<?php foreach ( $subjects as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="ps-field ps-field--full">
					<label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Message', 'premium-shop' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<textarea id="<?php echo esc_attr( $uid ); ?>-message" name="ps_message" rows="6" required minlength="10" maxlength="5000"></textarea>
				</p>
			</div>

			<p class="ps-field ps-field--check">
				<label>
					<input type="checkbox" name="ps_consent" value="1" required>
					<span>
						<?php
						if ( $privacy ) {
							printf(
								/* translators: %s: link to the privacy policy. */
								esc_html__( 'I agree that my details are used to answer my request. More in the %s.', 'premium-shop' ),
								'<a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">' . esc_html__( 'privacy policy', 'premium-shop' ) . '</a>'
							);
						} else {
							esc_html_e( 'I agree that my details are used to answer my request.', 'premium-shop' );
						}
						?>
						<span class="required" aria-hidden="true">*</span>
					</span>
				</label>
			</p>

			<button type="submit" class="ps-btn ps-btn--primary ps-contact-form__submit">
				<span><?php esc_html_e( 'Send message', 'premium-shop' ); ?></span>
				<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
			</button>
		</form>
	</div>
	<?php
}

/**
 * Shortcode [ps_contact_form].
 *
 * @return string
 */
function premium_shop_contact_shortcode() {
	ob_start();
	premium_shop_contact_form();
	return ob_get_clean();
}
add_shortcode( 'ps_contact_form', 'premium_shop_contact_shortcode' );

/**
 * Error messages.
 *
 * @param string $code Error code.
 * @return string
 */
function premium_shop_contact_error_message( $code ) {
	$messages = array(
		'fields'  => __( 'Please fill in your name, a valid e-mail address and your message (at least 10 characters).', 'premium-shop' ),
		'consent' => __( 'Please confirm that we may use your details to answer your request.', 'premium-shop' ),
		'limit'   => __( 'You have sent several messages in a short time. Please try again later or call us.', 'premium-shop' ),
		'expired' => __( 'The form has expired. Please reload the page and try again.', 'premium-shop' ),
	);
	return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Something went wrong. Please try again.', 'premium-shop' );
}

/**
 * Handle a submission (classic POST or fetch()).
 */
function premium_shop_contact_submit() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified below.
	$ajax = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'fetch' === strtolower( sanitize_key( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) );
	$back = isset( $_POST['ps_back'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['ps_back'] ) ), home_url( '/' ) ) : home_url( '/' );

	// Answer in the language the visitor uses on the site (the e-mail to the shop stays in the site language).
	$lang   = isset( $_POST['ps_lang'] ) ? sanitize_key( wp_unslash( $_POST['ps_lang'] ) ) : '';
	$locale = $lang && function_exists( 'premium_shop_languages' ) && isset( premium_shop_languages()[ $lang ] ) ? premium_shop_locale_for( $lang ) : '';

	$finish = static function ( $code ) use ( $ajax, $back, $locale ) {
		if ( $ajax ) {
			if ( $locale && determine_locale() !== $locale ) {
				unload_textdomain( 'premium-shop' );
				load_textdomain( 'premium-shop', PREMIUM_SHOP_DIR . '/languages/' . $locale . '.mo', $locale );
			}
			if ( 'sent' === $code ) {
				wp_send_json_success( array( 'message' => __( 'Thank you! Your message has been sent. We will get back to you as soon as possible.', 'premium-shop' ) ) );
			}
			wp_send_json_error( array( 'message' => premium_shop_contact_error_message( $code ) ), 400 );
		}
		wp_safe_redirect( add_query_arg( 'ps_contact', $code, $back ) . '#ps-contact-form' );
		exit;
	};

	if ( ! isset( $_POST['ps_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ps_contact_nonce'] ) ), 'premium_shop_contact' ) ) {
		$finish( 'expired' );
	}

	// Bots: honeypot filled or form sent faster than a human can type → pretend it worked.
	$elapsed = time() - ( isset( $_POST['ps_time'] ) ? absint( $_POST['ps_time'] ) : 0 );
	if ( ! empty( $_POST['ps_website'] ) || $elapsed < 3 ) {
		$finish( 'sent' );
	}

	$name    = isset( $_POST['ps_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ps_name'] ) ) : '';
	$email   = isset( $_POST['ps_email'] ) ? sanitize_email( wp_unslash( $_POST['ps_email'] ) ) : '';
	$phone   = isset( $_POST['ps_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ps_phone'] ) ) : '';
	$order   = isset( $_POST['ps_order'] ) ? sanitize_text_field( wp_unslash( $_POST['ps_order'] ) ) : '';
	$message = isset( $_POST['ps_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ps_message'] ) ) : '';
	$subject = isset( $_POST['ps_subject'] ) ? sanitize_key( wp_unslash( $_POST['ps_subject'] ) ) : '';
	// phpcs:enable

	$subjects = premium_shop_contact_subjects();
	$subject  = isset( $subjects[ $subject ] ) ? $subject : 'question';

	if ( '' === $name || ! is_email( $email ) || mb_strlen( $message ) < 10 ) {
		$finish( 'fields' );
	}
	if ( empty( $_POST['ps_consent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$finish( 'consent' );
	}

	// At most 5 messages per hour from the same visitor (IP address stored only as a hash).
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key     = 'ps_contact_' . substr( wp_hash( $ip ), 0, 20 );
	$counter = (int) get_transient( $key );
	if ( $counter >= (int) apply_filters( 'premium_shop_contact_limit', 5 ) ) {
		$finish( 'limit' );
	}
	set_transient( $key, $counter + 1, HOUR_IN_SECONDS );

	$subject_label = $subjects[ $subject ];
	$site          = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	// Keep a copy in the admin.
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'ps_message',
			'post_status'  => 'private',
			'post_title'   => wp_trim_words( $name . ' — ' . $subject_label, 20 ),
			'post_content' => $message,
		),
		true
	);
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_ps_name', $name );
		update_post_meta( $post_id, '_ps_email', $email );
		update_post_meta( $post_id, '_ps_phone', $phone );
		update_post_meta( $post_id, '_ps_order', $order );
		update_post_meta( $post_id, '_ps_subject', $subject_label );
		update_post_meta( $post_id, '_ps_lang', $lang );
	}
	premium_shop_contact_cleanup();

	$lines = array(
		__( 'Name', 'premium-shop' ) . ': ' . $name,
		__( 'Email address', 'premium-shop' ) . ': ' . $email,
	);
	if ( $phone ) {
		$lines[] = __( 'Phone', 'premium-shop' ) . ': ' . $phone;
	}
	if ( $order ) {
		$lines[] = __( 'Order number', 'premium-shop' ) . ': ' . $order;
	}
	$lines[] = __( 'Subject', 'premium-shop' ) . ': ' . $subject_label;
	$lines[] = __( 'Language', 'premium-shop' ) . ': ' . strtoupper( $lang );
	$lines[] = '';
	$lines[] = $message;

	$sent = wp_mail(
		premium_shop_contact_recipient(),
		/* translators: 1: site name, 2: subject. */
		sprintf( __( '[%1$s] Contact request: %2$s', 'premium-shop' ), $site, $subject_label ),
		implode( "\n", $lines ),
		array( 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>', ',' ), '', $name ) . ' <' . $email . '>' )
	);

	if ( ! $sent && $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_ps_mail_failed', 1 );
	}

	do_action( 'premium_shop_contact_received', $post_id, compact( 'name', 'email', 'phone', 'order', 'subject_label', 'message' ) );

	$finish( 'sent' );
}
add_action( 'admin_post_premium_shop_contact', 'premium_shop_contact_submit' );
add_action( 'admin_post_nopriv_premium_shop_contact', 'premium_shop_contact_submit' );

/**
 * Delete stored messages older than one year (filter premium_shop_contact_keep_days).
 */
function premium_shop_contact_cleanup() {
	$days = absint( apply_filters( 'premium_shop_contact_keep_days', 365 ) );
	if ( ! $days ) {
		return;
	}
	$old = get_posts(
		array(
			'post_type'      => 'ps_message',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 50,
			'date_query'     => array( array( 'before' => gmdate( 'Y-m-d', time() - $days * DAY_IN_SECONDS ) ) ),
		)
	);
	foreach ( $old as $id ) {
		wp_delete_post( $id, true );
	}
}

/**
 * Admin list columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function premium_shop_contact_columns( $columns ) {
	return array(
		'cb'         => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'      => __( 'Message', 'premium-shop' ),
		'ps_email'   => __( 'Email address', 'premium-shop' ),
		'ps_phone'   => __( 'Phone', 'premium-shop' ),
		'ps_order'   => __( 'Order number', 'premium-shop' ),
		'date'       => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'premium-shop' ),
	);
}
add_filter( 'manage_ps_message_posts_columns', 'premium_shop_contact_columns' );

/**
 * Admin list cells.
 *
 * @param string $column  Column.
 * @param int    $post_id Message ID.
 */
function premium_shop_contact_column( $column, $post_id ) {
	if ( 'ps_email' === $column ) {
		$email = (string) get_post_meta( $post_id, '_ps_email', true );
		echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		if ( get_post_meta( $post_id, '_ps_mail_failed', true ) ) {
			echo '<br><span style="color:#b32d2e">' . esc_html__( 'E-mail could not be sent — answer from here.', 'premium-shop' ) . '</span>';
		}
	} elseif ( 'ps_phone' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_ps_phone', true ) );
	} elseif ( 'ps_order' === $column ) {
		$order = (string) get_post_meta( $post_id, '_ps_order', true );
		if ( $order && function_exists( 'wc_get_order' ) && ctype_digit( $order ) && wc_get_order( (int) $order ) ) {
			echo '<a href="' . esc_url( wc_get_order( (int) $order )->get_edit_order_url() ) . '">#' . esc_html( $order ) . '</a>';
		} else {
			echo esc_html( $order );
		}
	}
}
add_action( 'manage_ps_message_posts_custom_column', 'premium_shop_contact_column', 10, 2 );

/**
 * Read-only view of a message in the editor.
 *
 * @param WP_Post $post Message.
 */
function premium_shop_contact_meta_box( $post ) {
	$email = (string) get_post_meta( $post->ID, '_ps_email', true );
	$rows  = array(
		__( 'Name', 'premium-shop' )          => get_post_meta( $post->ID, '_ps_name', true ),
		__( 'Email address', 'premium-shop' ) => $email,
		__( 'Phone', 'premium-shop' )         => get_post_meta( $post->ID, '_ps_phone', true ),
		__( 'Order number', 'premium-shop' )  => get_post_meta( $post->ID, '_ps_order', true ),
		__( 'Subject', 'premium-shop' )       => get_post_meta( $post->ID, '_ps_subject', true ),
		__( 'Language', 'premium-shop' )      => strtoupper( (string) get_post_meta( $post->ID, '_ps_lang', true ) ),
	);
	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $label => $value ) {
		if ( '' !== (string) $value ) {
			echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
	}
	echo '</tbody></table>';
	echo '<div style="margin-top:16px;padding:16px;background:#f6f7f7;white-space:pre-wrap">' . esc_html( $post->post_content ) . '</div>';
	if ( is_email( $email ) ) {
		echo '<p><a class="button button-primary" href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( 'Re: ' . get_the_title( $post ) ) . '">' . esc_html__( 'Reply by e-mail', 'premium-shop' ) . '</a></p>';
	}
}

/**
 * Register the message view.
 */
function premium_shop_contact_meta_boxes() {
	add_meta_box( 'ps-message', __( 'Contact message', 'premium-shop' ), 'premium_shop_contact_meta_box', 'ps_message', 'normal', 'high' );
	remove_meta_box( 'submitdiv', 'ps_message', 'side' );
}
add_action( 'add_meta_boxes_ps_message', 'premium_shop_contact_meta_boxes' );

/**
 * Contact details block shown next to the form.
 */
function premium_shop_contact_details() {
	$items = array();
	$phone = (string) premium_shop_option( 'contact_phone' );
	$email = (string) premium_shop_option( 'contact_email' );
	$wa    = preg_replace( '/[^0-9]/', '', (string) premium_shop_option( 'contact_whatsapp' ) );

	if ( premium_shop_option( 'contact_address' ) ) {
		$items[] = array( 'pin', __( 'Address', 'premium-shop' ), nl2br( esc_html( (string) premium_shop_option( 'contact_address' ) ) ) );
	}
	if ( $phone ) {
		$items[] = array( 'phone', __( 'Phone', 'premium-shop' ), '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' );
	}
	if ( $email ) {
		$items[] = array( 'mail', __( 'Email', 'premium-shop' ), '<a href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a>' );
	}
	if ( $wa ) {
		$items[] = array( 'whatsapp', 'WhatsApp', '<a href="https://wa.me/' . esc_attr( $wa ) . '" target="_blank" rel="noopener">' . esc_html__( 'Write to us on WhatsApp', 'premium-shop' ) . '</a>' );
	}
	if ( premium_shop_text( 'contact_hours' ) ) {
		$items[] = array( 'clock', __( 'Opening hours', 'premium-shop' ), esc_html( premium_shop_text( 'contact_hours' ) ) );
	}

	if ( ! $items ) {
		return;
	}
	?>
	<ul class="ps-contact-details">
		<?php foreach ( $items as $item ) : ?>
			<li class="ps-contact-details__item">
				<span class="ps-contact-details__icon" aria-hidden="true"><?php premium_shop_icon( $item[0], array( 'size' => 20 ) ); ?></span>
				<span>
					<span class="ps-contact-details__label"><?php echo esc_html( $item[1] ); ?></span>
					<span class="ps-contact-details__value"><?php echo wp_kses_post( $item[2] ); ?></span>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
