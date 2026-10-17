<?php
/**
 * Template tags: small, reusable rendering helpers.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site logo (custom logo or elegant text logo).
 */
function premium_shop_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	?>
	<a class="ps-logo-text" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<?php bloginfo( 'name' ); ?>
	</a>
	<?php
}

/**
 * Footer logo: the light version chosen in the Customizer, else the main
 * logo on a light card (a coloured logo stays readable on the dark footer).
 */
function premium_shop_footer_logo() {
	$light = absint( premium_shop_option( 'footer_logo' ) );
	if ( $light ) {
		?>
		<a class="ps-footer__logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php echo wp_get_attachment_image( $light, 'medium', false, array( 'class' => 'ps-footer__logo-img', 'alt' => get_bloginfo( 'name' ), 'loading' => 'lazy' ) ); ?>
		</a>
		<?php
		return;
	}
	if ( has_custom_logo() ) {
		echo '<span class="ps-footer__logo-card">';
		the_custom_logo();
		echo '</span>';
		return;
	}
	premium_shop_logo();
}

/**
 * Primary navigation (with automatic fallback when no menu is assigned).
 *
 * @param string $context desktop|mobile.
 */
function premium_shop_primary_menu( $context = 'desktop' ) {
	$menu_class = 'desktop' === $context ? 'ps-nav__list' : 'ps-mobile-nav__list';

	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => $menu_class,
				'menu_id'        => 'desktop' === $context ? 'ps-primary-menu' : 'ps-mobile-menu',
				'depth'          => 3,
				'walker'         => new Premium_Shop_Walker_Nav(),
				'fallback_cb'    => false,
				'ps_context'     => $context,
			)
		);
		return;
	}

	echo '<ul class="' . esc_attr( $menu_class ) . '">';
	foreach ( premium_shop_fallback_menu_items() as $item ) {
		$classes = array( 'menu-item' );
		if ( ! empty( $item['mega'] ) ) {
			$classes[] = 'menu-item-has-children';
			$classes[] = 'ps-has-mega';
		}
		if ( ! empty( $item['current'] ) ) {
			$classes[] = 'current-menu-item';
		}
		echo '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		printf(
			'<a href="%1$s"%3$s>%2$s</a>',
			esc_url( $item['url'] ),
			esc_html( $item['title'] ),
			! empty( $item['current'] ) ? ' aria-current="page"' : ''
		);
		if ( ! empty( $item['mega'] ) ) {
			echo premium_shop_submenu_toggle( $item['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function.
			echo premium_shop_mega_menu_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function.
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Default navigation used until a menu is created.
 *
 * @return array
 */
function premium_shop_fallback_menu_items() {
	$items = array(
		array(
			'title'   => __( 'Home', 'premium-shop' ),
			'url'     => home_url( '/' ),
			'current' => is_front_page(),
		),
	);

	if ( premium_shop_is_wc() ) {
		$items[] = array(
			'title'   => __( 'Shop', 'premium-shop' ),
			'url'     => premium_shop_shop_url(),
			'current' => is_shop() && empty( $_GET['on_sale'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$has_categories = (int) wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) ) > 1;
		if ( $has_categories ) {
			$items[] = array(
				'title' => __( 'Categories', 'premium-shop' ),
				'url'   => premium_shop_shop_url(),
				'mega'  => premium_shop_option( 'mega_categories' ),
			);
		}

		$items[] = array(
			'title'   => __( 'Offers', 'premium-shop' ),
			'url'     => add_query_arg( 'on_sale', '1', premium_shop_shop_url() ),
			'current' => is_shop() && ! empty( $_GET['on_sale'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
	}

	$about = premium_shop_page_url_by_slugs( array( 'ueber-uns', 'uber-uns', 'about-us', 'about', 'a-propos', 'sobre-nosotros' ) );
	if ( $about ) {
		$items[] = array(
			'title' => __( 'About us', 'premium-shop' ),
			'url'   => $about,
		);
	}

	$contact = premium_shop_page_url_by_slugs( array( 'kontakt', 'contact', 'contacto' ) );
	if ( $contact ) {
		$items[] = array(
			'title' => __( 'Contact', 'premium-shop' ),
			'url'   => $contact,
		);
	}

	return apply_filters( 'premium_shop_fallback_menu_items', $items );
}

/**
 * Language switcher "DE | FR | ES | EN".
 *
 * @param string $context header|drawer.
 */
function premium_shop_language_switcher( $context = 'header' ) {
	$items = premium_shop_language_switcher_items();

	if ( count( $items ) < 2 ) {
		return;
	}
	?>
	<nav class="ps-lang ps-lang--<?php echo esc_attr( $context ); ?>" aria-label="<?php esc_attr_e( 'Language', 'premium-shop' ); ?>">
		<ul class="ps-lang__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="ps-lang__item">
					<a class="ps-lang__link<?php echo $item['current'] ? ' is-current' : ''; ?>"
						href="<?php echo esc_url( $item['url'] ); ?>"
						hreflang="<?php echo esc_attr( premium_shop_bcp47( $item['locale'] ) ); ?>"
						lang="<?php echo esc_attr( premium_shop_bcp47( $item['locale'] ) ); ?>"
						title="<?php echo esc_attr( $item['name'] ); ?>"
						<?php echo $item['current'] ? 'aria-current="true"' : ''; ?>>
						<?php
						if ( 'drawer' === $context ) {
							echo premium_shop_flag( $item['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
						}
						?>
						<span aria-hidden="true"><?php echo esc_html( $item['label'] ); ?></span>
						<span class="screen-reader-text"><?php echo esc_html( $item['name'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Compact language menu for small screens: a "DE" button that opens the list
 * (native <details>, works without JavaScript).
 */
function premium_shop_language_switcher_compact() {
	$items = premium_shop_language_switcher_items();

	if ( count( $items ) < 2 ) {
		return;
	}

	$current = $items[0];
	foreach ( $items as $item ) {
		if ( $item['current'] ) {
			$current = $item;
			break;
		}
	}
	?>
	<details class="ps-lang-mini" data-ps-lang-mini>
		<summary class="ps-lang-mini__toggle">
			<?php echo premium_shop_flag( $current['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
			<span class="ps-lang-mini__current" aria-hidden="true"><?php echo esc_html( $current['label'] ); ?></span>
			<svg class="ps-lang-mini__chevron" viewBox="0 0 10 6" width="9" height="6" aria-hidden="true" focusable="false"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span class="screen-reader-text">
				<?php
				/* translators: %s: current language name. */
				echo esc_html( sprintf( __( 'Language: %s', 'premium-shop' ), $current['name'] ) );
				?>
			</span>
		</summary>
		<ul class="ps-lang-mini__list">
			<?php foreach ( $items as $i => $item ) : ?>
				<li style="--i:<?php echo (int) $i; ?>">
					<a class="ps-lang-mini__link<?php echo $item['current'] ? ' is-current' : ''; ?>"
						href="<?php echo esc_url( $item['url'] ); ?>"
						hreflang="<?php echo esc_attr( premium_shop_bcp47( $item['locale'] ) ); ?>"
						lang="<?php echo esc_attr( premium_shop_bcp47( $item['locale'] ) ); ?>"
						<?php echo $item['current'] ? 'aria-current="true"' : ''; ?>>
						<?php echo premium_shop_flag( $item['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span class="ps-lang-mini__code" aria-hidden="true"><?php echo esc_html( $item['label'] ); ?></span>
						<span class="screen-reader-text"><?php echo esc_html( $item['name'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</details>
	<?php
}

/**
 * Social network links.
 */
function premium_shop_social_links() {
	$networks = array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'tiktok'    => 'TikTok',
		'pinterest' => 'Pinterest',
		'youtube'   => 'YouTube',
		'x'         => 'X',
		'linkedin'  => 'LinkedIn',
	);

	$links = array();
	foreach ( $networks as $key => $label ) {
		$url = premium_shop_option( 'social_' . $key );
		if ( $url ) {
			$links[ $key ] = array( $url, $label );
		}
	}

	if ( ! $links ) {
		return;
	}
	?>
	<ul class="ps-social" aria-label="<?php esc_attr_e( 'Social networks', 'premium-shop' ); ?>">
		<?php foreach ( $links as $key => $link ) : ?>
			<li>
				<a href="<?php echo esc_url( $link[0] ); ?>" target="_blank" rel="noopener noreferrer me">
					<?php premium_shop_icon( $key, array( 'size' => 18 ) ); ?>
					<span class="screen-reader-text">
						<?php
						/* translators: %s: social network name. */
						echo esc_html( sprintf( __( '%s (opens in a new tab)', 'premium-shop' ), $link[1] ) );
						?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Small payment logo (inline SVG, 50 × 32), drawn by the theme.
 *
 * @param string $method Method key.
 * @param string $label  Readable name.
 * @return string SVG markup ('' when the method has no logo).
 */
function premium_shop_payment_logo( $method, $label ) {
	$font = 'font-family="Helvetica,Arial,sans-serif" font-weight="700" text-anchor="middle"';
	$art  = array(
		'visa'        => '<rect width="50" height="32" rx="5" fill="#fff"/><text x="25" y="21" ' . $font . ' font-size="14" font-style="italic" fill="#1a1f71" letter-spacing="-.5">VISA</text><rect x="9" y="24" width="32" height="2" rx="1" fill="#f7b600"/>',
		'mastercard'  => '<rect width="50" height="32" rx="5" fill="#fff"/><circle cx="20" cy="16" r="9" fill="#eb001b"/><circle cx="30" cy="16" r="9" fill="#f79e1b"/><path d="M25 8.5a9 9 0 0 1 0 15 9 9 0 0 1 0-15z" fill="#ff5f00"/>',
		'amex'        => '<rect width="50" height="32" rx="5" fill="#016fd0"/><text x="25" y="20" ' . $font . ' font-size="10.5" fill="#fff" letter-spacing=".5">AMEX</text>',
		'paypal'      => '<rect width="50" height="32" rx="5" fill="#fff"/><text x="25" y="20.5" ' . $font . ' font-size="11.5" font-style="italic"><tspan fill="#003087">Pay</tspan><tspan fill="#009cde">Pal</tspan></text>',
		'klarna'      => '<rect width="50" height="32" rx="5" fill="#ffb3c7"/><text x="25" y="20.5" ' . $font . ' font-size="11.5" fill="#17120f">Klarna.</text>',
		'applepay'    => '<rect width="50" height="32" rx="5" fill="#000"/><path fill="#fff" d="M15.6 12.3c.6-.7 1-1.7.9-2.7-.9 0-1.9.6-2.5 1.3-.6.6-1 1.6-.9 2.6 1 .1 1.9-.5 2.5-1.2zm.9 1.4c-1.4-.1-2.6.8-3.2.8-.7 0-1.7-.8-2.8-.7-1.4 0-2.7.8-3.5 2.1-1.5 2.6-.4 6.4 1.1 8.5.7 1 1.5 2.2 2.6 2.1 1-.1 1.4-.7 2.7-.7 1.2 0 1.6.7 2.7.6 1.1 0 1.8-1 2.5-2.1.8-1.2 1.1-2.3 1.1-2.4 0 0-2.2-.9-2.2-3.4 0-2.1 1.7-3.1 1.8-3.2-1-1.5-2.5-1.6-3-1.6z"/><text x="32" y="21" ' . $font . ' font-size="11" fill="#fff">Pay</text>',
		'googlepay'   => '<rect width="50" height="32" rx="5" fill="#fff"/><text x="15" y="21" ' . $font . ' font-size="13"><tspan fill="#4285f4">G</tspan></text><text x="31" y="21" ' . $font . ' font-size="11" fill="#5f6368">Pay</text>',
		'sepa'        => '<rect width="50" height="32" rx="5" fill="#fff"/><text x="23" y="20.5" ' . $font . ' font-size="11.5" font-style="italic" fill="#10298e">SEPA</text><path d="M38 11l4 5-4 5" fill="none" stroke="#ffbe00" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>',
		'sofort'      => '<rect width="50" height="32" rx="5" fill="#ef809f"/><text x="25" y="20" ' . $font . ' font-size="10.5" fill="#fff">SOFORT</text>',
		'giropay'     => '<rect width="50" height="32" rx="5" fill="#000268"/><text x="25" y="20" ' . $font . ' font-size="10" fill="#fff">giropay</text>',
		'bancontact'  => '<rect width="50" height="32" rx="5" fill="#fff"/><text x="25" y="19.5" ' . $font . ' font-size="7.6" fill="#005498">Bancontact</text>',
		'ideal'       => '<rect width="50" height="32" rx="5" fill="#fff"/><text x="25" y="21" ' . $font . ' font-size="12" fill="#cc0066">iDEAL</text>',
		'twint'       => '<rect width="50" height="32" rx="5" fill="#000"/><text x="25" y="20" ' . $font . ' font-size="10.5" fill="#fff" letter-spacing=".4">TWINT</text>',
		'postfinance' => '<rect width="50" height="32" rx="5" fill="#ffcc00"/><text x="25" y="15" ' . $font . ' font-size="7.4" fill="#000">Post</text><text x="25" y="23.5" ' . $font . ' font-size="7.4" fill="#000">Finance</text>',
		'invoice'     => '<rect width="50" height="32" rx="5" fill="#fff"/><path d="M19 7h9l5 5v13H19z" fill="#f4efe9" stroke="#8a5a36" stroke-width="1.6" stroke-linejoin="round"/><path d="M28 7v5h5M22.5 16.5h7M22.5 20h7" fill="none" stroke="#8a5a36" stroke-width="1.6" stroke-linecap="round"/>',
		'prepayment'  => '<rect width="50" height="32" rx="5" fill="#fff"/><path d="M15 13l10-5 10 5zM17 14.5v7M22 14.5v7M28 14.5v7M33 14.5v7M14.5 23.5h21" fill="none" stroke="#8a5a36" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
	);
	if ( ! isset( $art[ $method ] ) ) {
		return '';
	}
	return '<svg class="ps-payments__logo" viewBox="0 0 50 32" width="50" height="32" role="img" aria-label="' . esc_attr( $label ) . '" focusable="false">' . $art[ $method ] . '</svg>';
}

/**
 * Payment method badges: small logos drawn by the theme (methods without a
 * logo show their name).
 */
function premium_shop_payment_badges() {
	$labels = array(
		'visa'        => 'Visa',
		'mastercard'  => 'Mastercard',
		'amex'        => 'American Express',
		'paypal'      => 'PayPal',
		'klarna'      => 'Klarna',
		'applepay'    => 'Apple Pay',
		'googlepay'   => 'Google Pay',
		'sepa'        => 'SEPA',
		'sofort'      => 'Sofort',
		'giropay'     => 'giropay',
		'bancontact'  => 'Bancontact',
		'ideal'       => 'iDEAL',
		'twint'       => 'TWINT',
		'postfinance' => 'PostFinance',
		'invoice'     => __( 'Invoice', 'premium-shop' ),
		'prepayment'  => __( 'Prepayment', 'premium-shop' ),
	);

	$methods = array_filter( array_map( 'trim', explode( ',', strtolower( (string) premium_shop_option( 'footer_payments' ) ) ) ) );
	$methods = array_values( array_intersect( $methods, array_keys( $labels ) ) );

	if ( ! $methods ) {
		return;
	}
	$svg_tags = array(
		'svg'   => array( 'class' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'role' => true, 'aria-label' => true, 'focusable' => true ),
		'rect'  => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ),
		'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ),
		'path'  => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
		'text'  => array( 'x' => true, 'y' => true, 'font-family' => true, 'font-weight' => true, 'font-size' => true, 'font-style' => true, 'text-anchor' => true, 'fill' => true, 'letter-spacing' => true ),
		'tspan' => array( 'fill' => true ),
	);
	?>
	<ul class="ps-payments" aria-label="<?php esc_attr_e( 'Accepted payment methods', 'premium-shop' ); ?>">
		<?php foreach ( $methods as $i => $method ) : ?>
			<?php $logo = premium_shop_payment_logo( $method, $labels[ $method ] ); ?>
			<li class="ps-payments__item ps-payments__item--<?php echo esc_attr( $method ); ?><?php echo $logo ? ' ps-payments__item--logo' : ''; ?>" title="<?php echo esc_attr( $labels[ $method ] ); ?>" style="--ps-i:<?php echo (int) $i; ?>">
				<?php
				if ( $logo ) {
					echo wp_kses( $logo, $svg_tags );
				} else {
					echo esc_html( $labels[ $method ] );
				}
				?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Section heading (eyebrow + title + optional "view all" link).
 *
 * @param array $args title, eyebrow, link, link_text, id, tag, align.
 */
function premium_shop_section_heading( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'     => '',
			'eyebrow'   => '',
			'text'      => '',
			'link'      => '',
			'link_text' => __( 'View all', 'premium-shop' ),
			'id'        => '',
			'tag'       => 'h2',
			'align'     => 'split',
		)
	);
	$tag  = in_array( $args['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
	?>
	<header class="ps-section-head ps-section-head--<?php echo esc_attr( $args['align'] ); ?>" data-reveal>
		<div class="ps-section-head__text">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="ps-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<<?php echo esc_html( $tag ); ?> class="ps-section-title"<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?>><?php echo esc_html( $args['title'] ); ?></<?php echo esc_html( $tag ); ?>>
			<?php if ( $args['text'] ) : ?>
				<p class="ps-section-lead"><?php echo esc_html( $args['text'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $args['link'] ) : ?>
			<a class="ps-link-arrow" href="<?php echo esc_url( $args['link'] ); ?>">
				<?php echo esc_html( $args['link_text'] ); ?>
				<?php if ( $args['title'] ) : ?>
					<span class="screen-reader-text"><?php echo esc_html( ': ' . $args['title'] ); ?></span>
				<?php endif; ?>
				<?php premium_shop_icon( 'arrow', array( 'size' => 18 ) ); ?>
			</a>
		<?php endif; ?>
	</header>
	<?php
}

/**
 * Breadcrumbs: SEO plugin first, then WooCommerce, then a light fallback.
 */
function premium_shop_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	if ( function_exists( 'yoast_breadcrumb' ) && class_exists( 'WPSEO_Options' ) && WPSEO_Options::get( 'breadcrumbs-enable', false ) ) {
		yoast_breadcrumb( '<nav class="ps-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'premium-shop' ) . '">', '</nav>' );
		return;
	}

	if ( function_exists( 'rank_math_the_breadcrumbs' ) && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_breadcrumbs_enabled() ) {
		echo '<nav class="ps-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'premium-shop' ) . '">';
		rank_math_the_breadcrumbs();
		echo '</nav>';
		return;
	}

	if ( function_exists( 'woocommerce_breadcrumb' ) ) {
		woocommerce_breadcrumb();
		return;
	}

	echo '<nav class="ps-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'premium-shop' ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'premium-shop' ) . '</a>';
	echo '<span class="ps-breadcrumb__sep" aria-hidden="true">/</span>';
	echo '<span aria-current="page">' . esc_html( wp_strip_all_tags( premium_shop_archive_title() ) ) . '</span>';
	echo '</nav>';
}

/**
 * Title for archive-like views.
 *
 * @return string
 */
function premium_shop_archive_title() {
	if ( is_search() ) {
		/* translators: %s: search query. */
		return sprintf( __( 'Search results for “%s”', 'premium-shop' ), get_search_query() );
	}
	if ( is_404() ) {
		return __( 'Page not found', 'premium-shop' );
	}
	if ( is_home() && ! is_front_page() ) {
		return get_the_title( (int) get_option( 'page_for_posts' ) );
	}
	if ( is_archive() ) {
		return wp_strip_all_tags( get_the_archive_title() );
	}
	return get_the_title();
}

/**
 * Post meta line (date + author).
 */
function premium_shop_posted_on() {
	printf(
		'<p class="ps-post-meta"><time datetime="%1$s">%2$s</time><span aria-hidden="true"> · </span><span>%3$s</span></p>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() ),
		/* translators: %s: author name. */
		esc_html( sprintf( __( 'by %s', 'premium-shop' ), get_the_author() ) )
	);
}

/**
 * Numbered pagination for posts.
 */
function premium_shop_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => premium_shop_get_icon( 'arrow-left', array( 'size' => 18 ) ) . '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'premium-shop' ) . '</span>',
			'next_text'          => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'premium-shop' ) . '</span>' . premium_shop_get_icon( 'arrow', array( 'size' => 18 ) ),
			'before_page_number' => '<span class="screen-reader-text">' . esc_html__( 'Page', 'premium-shop' ) . ' </span>',
			'class'              => 'ps-pagination',
		)
	);
}

/**
 * Footer menu column with sensible fallback links.
 *
 * @param string $location Menu location.
 * @param string $title    Column title.
 */
function premium_shop_footer_menu( $location, $title ) {
	?>
	<div class="ps-footer__col">
		<h2 class="ps-footer__title"><?php echo esc_html( $title ); ?></h2>
		<?php
		if ( has_nav_menu( $location ) ) {
			wp_nav_menu(
				array(
					'theme_location' => $location,
					'container'      => false,
					'menu_class'     => 'ps-footer__menu',
					'depth'          => 1,
				)
			);
		} else {
			$links = premium_shop_footer_fallback_links( $location );
			if ( $links ) {
				echo '<ul class="ps-footer__menu">';
				foreach ( $links as $link ) {
					printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $link[0] ), esc_html( $link[1] ) );
				}
				echo '</ul>';
			}
		}
		?>
	</div>
	<?php
}

/**
 * Fallback links for footer menus.
 *
 * @param string $location Menu location.
 * @return array[] [ url, label ]
 */
function premium_shop_footer_fallback_links( $location ) {
	$links = array();

	if ( 'footer_shop' === $location ) {
		if ( premium_shop_is_wc() ) {
			$links[] = array( premium_shop_shop_url(), __( 'All products', 'premium-shop' ) );
			$links[] = array( add_query_arg( 'orderby', 'date', premium_shop_shop_url() ), __( 'New arrivals', 'premium-shop' ) );
			$links[] = array( add_query_arg( 'orderby', 'popularity', premium_shop_shop_url() ), __( 'Bestsellers', 'premium-shop' ) );
			$links[] = array( add_query_arg( 'on_sale', '1', premium_shop_shop_url() ), __( 'Offers', 'premium-shop' ) );

			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => 0,
					'hide_empty' => true,
					'number'     => 3,
					'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$links[] = array( get_term_link( $term ), $term->name );
				}
			}
		}
	} elseif ( 'footer_service' === $location ) {
		if ( premium_shop_is_wc() ) {
			$links[] = array( wc_get_page_permalink( 'myaccount' ), __( 'My account', 'premium-shop' ) );
			$links[] = array( wc_get_account_endpoint_url( 'orders' ), __( 'Track my orders', 'premium-shop' ) );
			$links[] = array( wc_get_cart_url(), __( 'Cart', 'premium-shop' ) );
		}
		$shipping = premium_shop_page_url_by_slugs( array( 'versand-und-zahlung', 'versand', 'shipping', 'livraison', 'envio' ) );
		if ( $shipping ) {
			$links[] = array( $shipping, __( 'Shipping & payment', 'premium-shop' ) );
		}
		$faq = premium_shop_page_url_by_slugs( array( 'faq', 'haeufige-fragen' ) );
		if ( $faq ) {
			$links[] = array( $faq, __( 'FAQ', 'premium-shop' ) );
		}
		$contact = premium_shop_page_url_by_slugs( array( 'kontakt', 'contact', 'contacto' ) );
		if ( $contact ) {
			$links[] = array( $contact, __( 'Contact', 'premium-shop' ) );
		}
	} elseif ( 'footer_legal' === $location ) {
		$candidates = array(
			array( array( 'impressum', 'imprint', 'mentions-legales', 'aviso-legal' ), __( 'Legal notice', 'premium-shop' ) ),
			array( array( 'agb', 'terms', 'cgv', 'terminos' ), __( 'Terms & conditions', 'premium-shop' ) ),
			array( array( 'widerrufsbelehrung', 'widerruf', 'right-of-withdrawal', 'retractation' ), __( 'Right of withdrawal', 'premium-shop' ) ),
			array( array( 'nutzungsbedingungen', 'cgu', 'terms-of-use', 'condiciones-de-uso' ), __( 'Terms of use', 'premium-shop' ) ),
		);
		foreach ( $candidates as $candidate ) {
			$url = premium_shop_page_url_by_slugs( $candidate[0] );
			if ( $url ) {
				$links[] = array( $url, $candidate[1] );
			}
		}
		$privacy = get_privacy_policy_url();
		if ( $privacy ) {
			$links[] = array( $privacy, __( 'Privacy policy', 'premium-shop' ) );
		}
	}

	return apply_filters( 'premium_shop_footer_fallback_links', $links, $location );
}

/**
 * Copyright line.
 *
 * @return string
 */
function premium_shop_copyright() {
	$company = premium_shop_option( 'company_name' );
	$company = $company ? $company : get_bloginfo( 'name' );
	$text    = premium_shop_text( 'footer_copyright' );

	if ( '' === $text ) {
		/* translators: 1: year, 2: company name. */
		$text = sprintf( __( '© %1$s %2$s. All rights reserved.', 'premium-shop' ), '{year}', '{company}' );
	}

	return str_replace( array( '{year}', '{company}' ), array( wp_date( 'Y' ), $company ), $text );
}

/**
 * Header action: account URL.
 *
 * @return string
 */
function premium_shop_account_url() {
	if ( premium_shop_is_wc() ) {
		return wc_get_page_permalink( 'myaccount' );
	}
	return wp_login_url();
}

/**
 * Wishlist page URL (page using the wishlist template or slug).
 *
 * @return string
 */
function premium_shop_wishlist_url() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'page-templates/template-wishlist.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( $pages ) {
		$id = (int) $pages[0];
		// Polylang / WPML: link to the translated wishlist page.
		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $id );
			$id         = $translated ? $translated : $id;
		} else {
			$id = (int) apply_filters( 'wpml_object_id', $id, 'page', true );
		}
		return get_permalink( $id );
	}

	return '';
}

/**
 * Number of items in the cart.
 *
 * @return int
 */
function premium_shop_cart_count() {
	if ( premium_shop_is_wc() && WC()->cart ) {
		return (int) WC()->cart->get_cart_contents_count();
	}
	return 0;
}

/**
 * Is this the minimal (distraction-free) checkout?
 *
 * @return bool
 */
function premium_shop_is_minimal_checkout() {
	return premium_shop_is_wc() && premium_shop_option( 'minimal_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' );
}

/**
 * Small round flag for a language (inline SVG, no request).
 *
 * @param string $code Language code (de, fr, es, en, it, pt, nl, pl).
 * @return string
 */
function premium_shop_flag( $code ) {
	$flags = array(
		'de' => '<rect width="24" height="8" fill="#000"/><rect y="8" width="24" height="8" fill="#dd0000"/><rect y="16" width="24" height="8" fill="#ffce00"/>',
		'fr' => '<rect width="8" height="24" fill="#002395"/><rect x="8" width="8" height="24" fill="#fff"/><rect x="16" width="8" height="24" fill="#ed2939"/>',
		'es' => '<rect width="24" height="24" fill="#aa151b"/><rect y="6" width="24" height="12" fill="#f1bf00"/>',
		'it' => '<rect width="8" height="24" fill="#009246"/><rect x="8" width="8" height="24" fill="#fff"/><rect x="16" width="8" height="24" fill="#ce2b37"/>',
		'nl' => '<rect width="24" height="8" fill="#ae1c28"/><rect y="8" width="24" height="8" fill="#fff"/><rect y="16" width="24" height="8" fill="#21468b"/>',
		'pl' => '<rect width="24" height="12" fill="#fff"/><rect y="12" width="24" height="12" fill="#dc143c"/>',
		'pt' => '<rect width="24" height="24" fill="#ff0000"/><rect width="10" height="24" fill="#006600"/><circle cx="10" cy="12" r="4" fill="#ffcc00"/>',
		'en' => '<rect width="24" height="24" fill="#012169"/><path d="M0 0l24 24M24 0L0 24" stroke="#fff" stroke-width="5"/><path d="M0 0l24 24M24 0L0 24" stroke="#c8102e" stroke-width="2"/><path d="M12 0v24M0 12h24" stroke="#fff" stroke-width="7"/><path d="M12 0v24M0 12h24" stroke="#c8102e" stroke-width="4"/>',
	);
	static $n = 0;
	++$n;
	$code = strtolower( substr( (string) $code, 0, 2 ) );
	if ( ! isset( $flags[ $code ] ) ) {
		return '<span class="ps-flag ps-flag--none" aria-hidden="true"></span>';
	}
	return '<svg class="ps-flag" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><defs><clipPath id="ps-flag-' . $code . '-' . $n . '"><circle cx="12" cy="12" r="12"/></clipPath></defs><g clip-path="url(#ps-flag-' . $code . '-' . $n . ')">' . $flags[ $code ] . '</g></svg>';
}
