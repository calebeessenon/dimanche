<?php
/**
 * Technical SEO foundations.
 *
 * The theme does not replace SEO plugins (Yoast SEO, Rank Math): it outputs
 * clean semantic HTML, a single H1 per page, correct image alt texts, and
 * leaves meta tags and structured data to WooCommerce and the SEO plugin.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Missing alt text on product images → product name.
 *
 * @param array        $attr       Attributes.
 * @param WP_Post      $attachment Attachment.
 * @return array
 */
function premium_shop_image_alt_fallback( $attr, $attachment ) {
	if ( ! empty( $attr['alt'] ) ) {
		return $attr;
	}

	$parent = $attachment->post_parent ? get_post( $attachment->post_parent ) : null;

	if ( $parent && 'product' === $parent->post_type ) {
		$attr['alt'] = get_the_title( $parent );
	} elseif ( in_the_loop() && get_the_title() ) {
		$attr['alt'] = get_the_title();
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'premium_shop_image_alt_fallback', 10, 2 );

/**
 * Is an SEO plugin handling meta tags?
 *
 * @return bool
 */
function premium_shop_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

/**
 * Description of the current page (meta description and link previews).
 *
 * @return string
 */
function premium_shop_page_description() {
	$description = '';

	if ( is_front_page() ) {
		$description = get_bloginfo( 'description' );
		// Slogan empty or just the shop name (e.g. "Warmeholz – Warmeholz"): use the homepage text.
		if ( '' === trim( $description ) || get_bloginfo( 'name' ) === trim( $description ) || 'Just another WordPress site' === $description ) {
			$description = premium_shop_text( 'hero_subtitle' );
		}
	} elseif ( is_singular() ) {
		$post        = get_queried_object();
		$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28, '…' );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$description = term_description();
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$description = premium_shop_text( 'hero_subtitle' );
	}

	return wp_html_excerpt( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $description ) ) ), 160, '…' );
}

/**
 * Meta description fallback — only when no SEO plugin is active.
 */
function premium_shop_meta_description() {
	if ( premium_shop_seo_plugin_active() ) {
		return;
	}
	$description = premium_shop_page_description();
	if ( $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
}
add_action( 'wp_head', 'premium_shop_meta_description', 1 );

/**
 * Link previews (WhatsApp, Facebook, Messenger, LinkedIn, X…): title,
 * description and image — only when no SEO plugin is active.
 */
function premium_shop_open_graph() {
	if ( premium_shop_seo_plugin_active() || is_404() ) {
		return;
	}

	$name  = get_bloginfo( 'name' );
	$title = wp_strip_all_tags( html_entity_decode( wp_get_document_title(), ENT_QUOTES, 'UTF-8' ) );
	$type  = 'website';
	$image = '';

	if ( is_singular() ) {
		$thumb = get_post_thumbnail_id( get_queried_object_id() );
		if ( $thumb ) {
			$image = wp_get_attachment_image_url( $thumb, 'large' );
		}
		if ( ! is_front_page() ) {
			$type = 'article';
		}
	} elseif ( is_tax( 'product_cat' ) ) {
		$thumb = (int) get_term_meta( get_queried_object_id(), 'thumbnail_id', true );
		if ( $thumb ) {
			$image = wp_get_attachment_image_url( $thumb, 'large' );
		}
	}
	if ( ! $image ) {
		$image = premium_shop_share_image();
	}

	$tags = array(
		'og:type'        => $type,
		'og:site_name'   => $name,
		'og:title'       => $title,
		'og:description' => premium_shop_page_description(),
		'og:url'         => is_singular() ? get_permalink() : home_url( add_query_arg( array() ) ),
		'og:locale'      => get_locale(),
		'og:image'       => $image,
	);
	foreach ( array_filter( $tags ) as $property => $content ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), 'og:url' === $property || 'og:image' === $property ? esc_url( $content ) : esc_attr( $content ) );
	}
	printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
}
add_action( 'wp_head', 'premium_shop_open_graph', 2 );

/**
 * Default preview image: Customizer image, else the bundled share image
 * (firewood preset), else the site icon or logo.
 *
 * @return string URL ('' when none).
 */
function premium_shop_share_image() {
	$custom = absint( premium_shop_option( 'share_image' ) );
	if ( $custom ) {
		return (string) wp_get_attachment_image_url( $custom, 'large' );
	}
	if ( 'firewood' === premium_shop_preset() && file_exists( PREMIUM_SHOP_DIR . '/assets/images/firewood/share.jpg' ) ) {
		return PREMIUM_SHOP_URI . '/assets/images/firewood/share.jpg';
	}
	$hero = absint( premium_shop_option( 'hero_image' ) );
	if ( $hero ) {
		return (string) wp_get_attachment_image_url( $hero, 'large' );
	}
	if ( has_custom_logo() ) {
		return (string) wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
	}
	return (string) get_site_icon_url( 512 );
}

/**
 * Filtered shop URLs (?filter_*, ?min_price…) should not be indexed when no
 * SEO plugin handles it — avoids thousands of near-duplicate pages.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function premium_shop_robots_filtered( $robots ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$params = array_keys( $_GET );
	// phpcs:enable

	foreach ( $params as $param ) {
		if ( 0 === strpos( $param, 'filter_' ) || in_array( $param, array( 'min_price', 'max_price', 'rating_filter', 'stock', 'ps_brand', 'ps_view' ), true ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			break;
		}
	}

	return $robots;
}
add_filter( 'wp_robots', 'premium_shop_robots_filtered' );

/**
 * Homepage title: no "Shop – Shop" when the slogan repeats the name.
 *
 * @param array $parts Title parts.
 * @return array
 */
function premium_shop_document_title_parts( $parts ) {
	if ( isset( $parts['tagline'] ) && ( '' === trim( $parts['tagline'] ) || trim( $parts['tagline'] ) === trim( (string) $parts['title'] ) ) ) {
		$slogan = premium_shop_text( 'hero_eyebrow' );
		if ( $slogan ) {
			$parts['tagline'] = $slogan;
		} else {
			unset( $parts['tagline'] );
		}
	}
	return $parts;
}
add_filter( 'document_title_parts', 'premium_shop_document_title_parts' );
