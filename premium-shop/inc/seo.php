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
 * Meta description fallback — only when no SEO plugin is active.
 */
function premium_shop_meta_description() {
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
		return;
	}

	$description = '';

	if ( is_front_page() ) {
		$description = get_bloginfo( 'description' );
	} elseif ( is_singular() ) {
		$post        = get_queried_object();
		$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28, '…' );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$description = term_description();
	}

	$description = trim( wp_strip_all_tags( (string) $description ) );

	if ( $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $description, 160, '…' ) ) );
	}
}
add_action( 'wp_head', 'premium_shop_meta_description', 1 );

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
