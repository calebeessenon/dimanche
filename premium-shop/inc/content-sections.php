<?php
/**
 * Designed content sections usable in any page (shortcodes), filled from the
 * shop's real data: product photos, delivery time, shipping zones, enabled
 * payment methods.
 *
 * [ps_photo_collage]  Floating photos of your products.
 * [ps_features]       Four reasons to buy (icons).
 * [ps_delivery_steps] Order → preparation → delivery.
 * [ps_shipping_info]  Delivery time, free shipping, shipping zones & methods.
 * [ps_payment_methods] Enabled payment methods.
 * [ps_cta]            Call to action band (shop + contact).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * [ps_photo_collage]
 *
 * @return string
 */
function premium_shop_photo_collage_shortcode() {
	$photos = function_exists( 'premium_shop_hero_photos' ) ? premium_shop_hero_photos( 3, (int) get_the_ID() + 5 ) : array();
	if ( ! $photos ) {
		return '';
	}
	ob_start();
	?>
	<div class="ps-collage ps-phero__photos ps-phero__photos--<?php echo count( $photos ); ?>" data-reveal>
		<?php foreach ( $photos as $i => $photo ) : ?>
			<figure class="ps-phero__photo ps-phero__photo--<?php echo (int) $i + 1; ?>">
				<img src="<?php echo esc_url( $photo['src'] ); ?>" alt="<?php echo esc_attr( $photo['alt'] ); ?>" loading="lazy" decoding="async">
			</figure>
		<?php endforeach; ?>
		<span class="ps-phero__ring" aria-hidden="true"></span>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'ps_photo_collage', 'premium_shop_photo_collage_shortcode' );

/**
 * Cards with an icon, a title and a text.
 *
 * @param array  $items     array( icon, title, text ).
 * @param string $modifier  CSS modifier.
 * @return string
 */
function premium_shop_icon_cards( $items, $modifier = '' ) {
	ob_start();
	?>
	<ul class="ps-icards ps-icards--n<?php echo (int) count( $items ); ?><?php echo $modifier ? ' ps-icards--' . esc_attr( $modifier ) : ''; ?>">
		<?php foreach ( $items as $i => $item ) : ?>
			<li class="ps-icard" data-reveal style="--i:<?php echo (int) $i; ?>;--ps-delay:<?php echo (int) $i * 90; ?>ms">
				<?php if ( 'steps' === $modifier ) : ?>
					<span class="ps-icard__num"><?php echo (int) $i + 1; ?></span>
				<?php endif; ?>
				<span class="ps-icard__icon"><?php premium_shop_icon( $item[0], array( 'size' => 26 ) ); ?></span>
				<strong class="ps-icard__title"><?php echo esc_html( $item[1] ); ?></strong>
				<?php if ( ! empty( $item[2] ) ) : ?>
					<span class="ps-icard__text"><?php echo esc_html( $item[2] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	return (string) ob_get_clean();
}

/**
 * [ps_features]
 *
 * @return string
 */
function premium_shop_features_shortcode() {
	$firewood = 'firewood' === premium_shop_preset();
	$items    = array(
		$firewood ? array( 'flame', __( 'Dry, ready to burn', 'premium-shop' ), __( 'Kiln-dried wood with low residual moisture: more heat, less smoke.', 'premium-shop' ) ) : array( 'star', __( 'Carefully selected', 'premium-shop' ), __( 'Products we know and recommend.', 'premium-shop' ) ),
		array( 'truck', __( 'Delivered to your door', 'premium-shop' ), premium_shop_text( 'delivery_time' ) ),
		array( 'tag', __( 'Fair prices', 'premium-shop' ), __( 'Clear prices including VAT, no hidden costs.', 'premium-shop' ) ),
		array( 'support', __( 'Personal advice', 'premium-shop' ), __( 'Not sure what to choose? We help you find the right product.', 'premium-shop' ) ),
	);
	return premium_shop_icon_cards( apply_filters( 'premium_shop_features_items', $items ) );
}
add_shortcode( 'ps_features', 'premium_shop_features_shortcode' );

/**
 * [ps_delivery_steps]
 *
 * @return string
 */
function premium_shop_delivery_steps_shortcode() {
	$items = array(
		array( 'bag', __( 'You order', 'premium-shop' ), __( 'Online, in a few clicks. You receive a confirmation by e-mail.', 'premium-shop' ) ),
		array( 'box', __( 'We prepare', 'premium-shop' ), __( 'Your order is prepared and the delivery date is planned with you.', 'premium-shop' ) ),
		array( 'truck', __( 'We deliver', 'premium-shop' ), __( 'Delivery to your door. Follow it at any time on the order tracking page.', 'premium-shop' ) ),
	);
	return premium_shop_icon_cards( apply_filters( 'premium_shop_delivery_steps_items', $items ), 'steps' );
}
add_shortcode( 'ps_delivery_steps', 'premium_shop_delivery_steps_shortcode' );

/**
 * [ps_shipping_info]
 *
 * @return string
 */
function premium_shop_shipping_info_shortcode() {
	if ( ! premium_shop_is_wc() ) {
		return '';
	}

	$cards     = array( array( 'clock', __( 'Delivery time', 'premium-shop' ), premium_shop_text( 'delivery_time' ) ) );
	$threshold = premium_shop_free_shipping_threshold();
	if ( $threshold > 0 ) {
		/* translators: %s: amount. */
		$cards[] = array( 'gift', __( 'Free shipping', 'premium-shop' ), sprintf( __( 'From %s order value', 'premium-shop' ), premium_shop_plain_price( $threshold ) ) );
	}
	if ( premium_shop_order_tracking_url() ) {
		$cards[] = array( 'pin', __( 'Order tracking', 'premium-shop' ), __( 'Status, delivery date and tracking number online.', 'premium-shop' ) );
	}

	$zones = array();
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			$methods = array();
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'yes' !== $method->enabled ) {
					continue;
				}
				$label = $method->get_title();
				$cost  = isset( $method->instance_settings['cost'] ) ? trim( (string) $method->instance_settings['cost'] ) : '';
				if ( 'free_shipping' === $method->id ) {
					$min = isset( $method->instance_settings['min_amount'] ) ? (float) $method->instance_settings['min_amount'] : 0;
					/* translators: %s: amount. */
					$label .= $min > 0 ? ' · ' . sprintf( __( 'from %s', 'premium-shop' ), premium_shop_plain_price( $min ) ) : '';
				} elseif ( '' !== $cost && is_numeric( $cost ) ) {
					$label .= ' · ' . ( (float) $cost > 0 ? premium_shop_plain_price( (float) $cost ) : __( 'free', 'premium-shop' ) );
				}
				$methods[] = $label;
			}
			if ( $methods ) {
				$zones[] = array( $zone['zone_name'], $methods );
			}
		}
	}

	$out = premium_shop_icon_cards( $cards, 'compact' );
	if ( $zones ) {
		$out .= '<div class="ps-zones" data-reveal><table class="ps-zones__table"><thead><tr><th>' . esc_html__( 'Delivery area', 'premium-shop' ) . '</th><th>' . esc_html__( 'Shipping methods', 'premium-shop' ) . '</th></tr></thead><tbody>';
		foreach ( $zones as $zone ) {
			$out .= '<tr><td>' . esc_html( $zone[0] ) . '</td><td>' . implode( '<br>', array_map( 'esc_html', $zone[1] ) ) . '</td></tr>';
		}
		$out .= '</tbody></table></div>';
	}
	return $out;
}
add_shortcode( 'ps_shipping_info', 'premium_shop_shipping_info_shortcode' );

/**
 * [ps_payment_methods]
 *
 * @return string
 */
function premium_shop_payment_methods_shortcode() {
	if ( ! premium_shop_is_wc() || ! WC()->payment_gateways() ) {
		return '';
	}
	$items = array();
	foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
		if ( 'yes' !== $gateway->enabled ) {
			continue;
		}
		$items[] = array( 'card', wp_strip_all_tags( $gateway->get_title() ), wp_trim_words( wp_strip_all_tags( $gateway->get_description() ), 22 ) );
	}
	if ( ! $items ) {
		return '';
	}
	return premium_shop_icon_cards( $items, 'compact' ) . '<p class="ps-secure-note" data-reveal>' . premium_shop_get_icon( 'lock', array( 'size' => 16 ) ) . ' ' . esc_html( premium_shop_text( 'payment_text' ) ) . '</p>';
}
add_shortcode( 'ps_payment_methods', 'premium_shop_payment_methods_shortcode' );

/**
 * [ps_cta]
 *
 * @return string
 */
function premium_shop_cta_shortcode() {
	$shop    = premium_shop_is_wc() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$contact = get_page_by_path( 'kontakt', OBJECT, 'page' );
	ob_start();
	?>
	<div class="ps-cta-band" data-reveal>
		<div class="ps-phero__embers" aria-hidden="true">
			<?php for ( $i = 0; $i < 10; $i++ ) : ?>
				<span style="--x:<?php echo (int) ( ( $i * 41 + 7 ) % 100 ); ?>%;--d:<?php echo esc_attr( (string) ( $i * 0.9 ) ); ?>s;--t:<?php echo esc_attr( (string) ( 5 + $i % 4 ) ); ?>s;--s:<?php echo (int) ( 3 + $i % 4 ); ?>px"></span>
			<?php endfor; ?>
		</div>
		<p class="ps-cta-band__title"><?php echo esc_html( 'firewood' === premium_shop_preset() ? __( 'Ready for cosy warmth?', 'premium-shop' ) : __( 'Ready to discover our products?', 'premium-shop' ) ); ?></p>
		<p class="ps-cta-band__text"><?php esc_html_e( 'Order online in a few clicks, or ask us for advice.', 'premium-shop' ); ?></p>
		<div class="ps-cta-band__actions">
			<a class="ps-btn ps-btn--accent" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Discover the shop', 'premium-shop' ); ?> <?php premium_shop_icon( 'arrow', array( 'size' => 16 ) ); ?></a>
			<?php if ( $contact && 'publish' === $contact->post_status ) : ?>
				<a class="ps-btn ps-btn--light" href="<?php echo esc_url( get_permalink( $contact ) ); ?>"><?php esc_html_e( 'Contact us', 'premium-shop' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'ps_cta', 'premium_shop_cta_shortcode' );

/**
 * Block content of the "About us" page.
 *
 * @param callable $t Translator for the shop language.
 * @return string
 */
function premium_shop_about_page_content( $t ) {
	$p        = static function ( $text, $class = '' ) {
		return '<!-- wp:paragraph' . ( $class ? ' {"className":"' . $class . '"}' : '' ) . ' --><p' . ( $class ? ' class="' . $class . '"' : '' ) . '>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
	};
	$h        = static function ( $text ) {
		return '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $text ) . '</h2><!-- /wp:heading -->';
	};
	$s        = static function ( $code ) {
		return '<!-- wp:shortcode -->[' . $code . ']<!-- /wp:shortcode -->';
	};
	$firewood = 'firewood' === premium_shop_preset();

	return $p( $t( 'Carefully selected products, honest advice and fast delivery. We are here for you before and after your purchase.' ), 'ps-lead' )
		. '<!-- wp:columns {"verticalAlignment":"center","className":"ps-story"} --><div class="wp-block-columns are-vertically-aligned-center ps-story"><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center">'
		. $h( $t( 'Our story' ) )
		. $p( $firewood ? $t( 'Warmth, simply delivered: we supply firewood, wood briquettes and pellets of reliable quality, carefully dried and delivered to your door.' ) : $t( 'We select every product with care and deliver it quickly to your door.' ) )
		. $p( $t( 'We believe in honest advice, transparent prices and a service that does not stop after the purchase. Write to us or call us: a real person answers.' ) )
		. '</div><!-- /wp:column --><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center">' . $s( 'ps_photo_collage' ) . '</div><!-- /wp:column --></div><!-- /wp:columns -->'
		. $h( $t( 'Why shop with us?' ) )
		. $s( 'ps_features' )
		. $s( 'ps_cta' );
}

/**
 * Block content of the "Shipping & payment" page.
 *
 * @param callable $t Translator for the shop language.
 * @return string
 */
function premium_shop_shipping_page_content( $t ) {
	$p = static function ( $text, $class = '' ) {
		return '<!-- wp:paragraph' . ( $class ? ' {"className":"' . $class . '"}' : '' ) . ' --><p' . ( $class ? ' class="' . $class . '"' : '' ) . '>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
	};
	$h = static function ( $text ) {
		return '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $text ) . '</h2><!-- /wp:heading -->';
	};
	$s = static function ( $code ) {
		return '<!-- wp:shortcode -->[' . $code . ']<!-- /wp:shortcode -->';
	};

	return $p( $t( 'From your order to delivery at your door: here is everything you need to know about shipping and payment.' ), 'ps-lead' )
		. $h( $t( 'How it works' ) )
		. $s( 'ps_delivery_steps' )
		. $h( $t( 'Delivery' ) )
		. $s( 'ps_shipping_info' )
		. $h( $t( 'Payment' ) )
		. $s( 'ps_payment_methods' )
		. $s( 'ps_cta' );
}

/**
 * Replace the placeholder text of the "About us" and "Shipping & payment"
 * pages created by the setup assistant (only if it was never edited).
 */
function premium_shop_upgrade_service_content() {
	$pages  = array(
		'ueber-uns'           => array( 'Tell your story here: who you are, what drives you and why your customers can trust you.', 'premium_shop_about_page_content' ),
		'versand-und-zahlung' => array( 'Describe your shipping countries, delivery times, shipping costs and accepted payment methods here.', 'premium_shop_shipping_page_content' ),
	);
	$locale = premium_shop_locale_for( premium_shop_default_language() );
	$t      = static function ( $text ) use ( $locale ) {
		return premium_shop_translate_in( $text, $locale );
	};

	foreach ( $pages as $slug => $page ) {
		$post = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $post ) {
			continue;
		}
		$plain       = trim( wp_strip_all_tags( $post->post_content ) );
		$placeholder = false;
		foreach ( array( 'de_DE', 'fr_FR', 'es_ES', 'en_US' ) as $candidate ) {
			$needle = premium_shop_translate_in( $page[0], $candidate );
			if ( '' !== $needle && false !== strpos( $plain, $needle ) && strlen( $plain ) < strlen( $needle ) + 260 ) {
				$placeholder = true;
				break;
			}
		}
		if ( $placeholder ) {
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => call_user_func( $page[1], $t ),
				)
			);
		}
	}
}
add_action( 'premium_shop_upgrade_1_4', 'premium_shop_upgrade_service_content' );

/**
 * Placeholder texts of earlier versions: kept in the translation catalogs so
 * that premium_shop_upgrade_service_content() recognises them in any language.
 *
 * @return string[]
 */
function premium_shop_legacy_placeholder_strings() {
	return array(
		__( 'Tell your story here: who you are, what drives you and why your customers can trust you.', 'premium-shop' ),
		__( 'Describe your shipping countries, delivery times, shipping costs and accepted payment methods here.', 'premium-shop' ),
	);
}
