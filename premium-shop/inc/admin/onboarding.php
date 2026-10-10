<?php
/**
 * Setup assistant (Appearance → Shop setup).
 *
 * One click creates the pages, menus and recommended settings so the shop
 * looks finished right after activation. Nothing runs without the
 * administrator clicking a button.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remember to show the welcome notice after activation.
 */
function premium_shop_after_switch_theme() {
	update_option( 'premium_shop_welcome', 1, false );
}
add_action( 'after_switch_theme', 'premium_shop_after_switch_theme' );

/**
 * Admin page registration.
 */
function premium_shop_admin_menu() {
	add_theme_page(
		__( 'Shop setup', 'premium-shop' ),
		__( 'Shop setup', 'premium-shop' ),
		'edit_theme_options',
		'premium-shop-setup',
		'premium_shop_setup_page'
	);
}
add_action( 'admin_menu', 'premium_shop_admin_menu' );

/**
 * Welcome notice.
 */
function premium_shop_welcome_notice() {
	if ( ! get_option( 'premium_shop_welcome' ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'appearance_page_premium-shop-setup' === $screen->id ) {
		return;
	}
	$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=premium_shop_dismiss_welcome' ), 'premium_shop_dismiss_welcome' );
	?>
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Welcome to Premium Shop!', 'premium-shop' ); ?></strong>
		<?php esc_html_e( 'Set up pages, menus, languages and image settings in one click.', 'premium-shop' ); ?></p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=premium-shop-setup' ) ); ?>"><?php esc_html_e( 'Open the setup assistant', 'premium-shop' ); ?></a>
			<a class="button-link" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'premium-shop' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'premium_shop_welcome_notice' );

/**
 * Dismiss the welcome notice.
 */
function premium_shop_dismiss_welcome() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'premium-shop' ), 403 );
	}
	check_admin_referer( 'premium_shop_dismiss_welcome' );
	delete_option( 'premium_shop_welcome' );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_premium_shop_dismiss_welcome', 'premium_shop_dismiss_welcome' );

/**
 * Translate a theme string into a given locale using the bundled .mo file,
 * independently of the admin language.
 *
 * @param string $text   English source text.
 * @param string $locale Target locale.
 * @return string
 */
function premium_shop_translate_in( $text, $locale ) {
	static $catalogs = array();

	if ( 0 === strpos( $locale, 'en_' ) ) {
		return $text;
	}

	if ( ! isset( $catalogs[ $locale ] ) ) {
		$catalogs[ $locale ] = false;
		$file                = PREMIUM_SHOP_DIR . '/languages/' . $locale . '.mo';
		if ( is_readable( $file ) ) {
			$mo = new MO();
			if ( $mo->import_from_file( $file ) ) {
				$catalogs[ $locale ] = $mo;
			}
		}
	}

	return $catalogs[ $locale ] ? $catalogs[ $locale ]->translate( $text ) : $text;
}

/**
 * Setup steps status.
 *
 * @return array
 */
function premium_shop_setup_status() {
	$installed = get_available_languages();

	return array(
		'woocommerce' => premium_shop_is_wc(),
		'logo'        => has_custom_logo(),
		'pages'       => (bool) get_page_by_path( 'kontakt' ) || (bool) premium_shop_wishlist_url(),
		'menus'       => has_nav_menu( 'primary' ),
		'languages'   => ! array_diff( array( 'de_DE', 'fr_FR', 'es_ES' ), $installed ),
		'images'      => premium_shop_is_wc() && 'custom' === get_option( 'woocommerce_thumbnail_cropping' ),
		'firewood'    => premium_shop_is_wc() && function_exists( 'wc_get_product_id_by_sku' ) && (bool) wc_get_product_id_by_sku( 'FW-BEECH' ),
	);
}

/**
 * Render the setup page.
 */
function premium_shop_setup_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$status = premium_shop_setup_status();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$done = isset( $_GET['ps_done'] ) ? sanitize_key( wp_unslash( $_GET['ps_done'] ) ) : '';

	$steps = array(
		'pages'     => array(
			'title' => __( 'Create pages', 'premium-shop' ),
			'desc'  => __( 'About us, Contact, Wishlist, Shipping & payment — plus drafts of the legal pages (Legal notice, Terms & conditions, Right of withdrawal) to complete.', 'premium-shop' ),
		),
		'menus'     => array(
			'title' => __( 'Create menus', 'premium-shop' ),
			'desc'  => __( 'Main menu with categories mega menu, footer menus and legal menu.', 'premium-shop' ),
		),
		'languages' => array(
			'title' => __( 'Install languages DE / FR / ES', 'premium-shop' ),
			'desc'  => __( 'Downloads the WordPress and WooCommerce translations and sets German as the site language.', 'premium-shop' ),
		),
		'firewood'  => array(
			'title' => __( 'Create the sample firewood catalogue', 'premium-shop' ),
			'desc'  => __( 'Categories with images, attributes "Log length" and "Quantity", a "Freight" shipping class and 8 ready products (beech, oak, birch, ash, hardwood mix in 25/33/50 cm and 1–6 RM, kindling, briquettes, box) with automatic prices, data sheets and descriptions. Created as drafts: check prices, add your photos, publish.', 'premium-shop' ),
		),
		'images'    => array(
			'title' => __( 'Optimize product images', 'premium-shop' ),
			'desc'  => __( 'Portrait 4:5 product thumbnails (600 px) — the ideal format for this design.', 'premium-shop' ),
		),
	);
	?>
	<div class="wrap ps-setup">
		<h1><?php esc_html_e( 'Premium Shop — setup assistant', 'premium-shop' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Each step is optional and can be run again at any time. Existing pages and menus are never overwritten.', 'premium-shop' ); ?></p>

		<?php if ( $done ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Done!', 'premium-shop' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $status['woocommerce'] ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'WooCommerce is not active. Install and activate it to use the shop features.', 'premium-shop' ); ?>
					<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ); ?>"><?php esc_html_e( 'Install WooCommerce', 'premium-shop' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:980px;margin-top:20px">
			<tbody>
				<tr>
					<td style="width:36px"><?php echo $status['logo'] ? '✅' : '⬜'; ?></td>
					<td><strong><?php esc_html_e( 'Logo & favicon', 'premium-shop' ); ?></strong><br><span class="description"><?php esc_html_e( 'Upload your logo and site icon.', 'premium-shop' ); ?></span></td>
					<td style="text-align:right"><a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>"><?php esc_html_e( 'Open', 'premium-shop' ); ?></a></td>
				</tr>
				<?php foreach ( $steps as $key => $step ) : ?>
					<tr>
						<td><?php echo ! empty( $status[ $key ] ) ? '✅' : '⬜'; ?></td>
						<td><strong><?php echo esc_html( $step['title'] ); ?></strong><br><span class="description"><?php echo esc_html( $step['desc'] ); ?></span></td>
						<td style="text-align:right">
							<?php if ( ! in_array( $key, array( 'images', 'firewood' ), true ) || $status['woocommerce'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="premium_shop_setup" />
									<input type="hidden" name="step" value="<?php echo esc_attr( $key ); ?>" />
									<?php wp_nonce_field( 'premium_shop_setup_' . $key ); ?>
									<?php if ( 'firewood' === $key ) : ?>
										<label style="display:block;margin-bottom:6px"><input type="checkbox" name="publish" value="1" /> <?php esc_html_e( 'Publish immediately', 'premium-shop' ); ?></label>
									<?php endif; ?>
									<?php if ( 'languages' === $key ) : ?>
										<label style="display:block;margin-bottom:6px"><input type="checkbox" name="set_german" value="1" checked /> <?php esc_html_e( 'Set German as site language', 'premium-shop' ); ?></label>
									<?php endif; ?>
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Run', 'premium-shop' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<td>🎨</td>
					<td><strong><?php esc_html_e( 'Design, texts & homepage', 'premium-shop' ); ?></strong><br><span class="description"><?php esc_html_e( 'Colors, fonts, hero, sections, announcement bar, contact details, social networks.', 'premium-shop' ); ?></span></td>
					<td style="text-align:right"><a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=premium_shop' ) ); ?>"><?php esc_html_e( 'Customize', 'premium-shop' ); ?></a></td>
				</tr>
				<?php if ( $status['woocommerce'] ) : ?>
					<tr>
						<td>🛒</td>
						<td><strong><?php esc_html_e( 'Products, payments & shipping', 'premium-shop' ); ?></strong><br><span class="description"><?php esc_html_e( 'Add categories and products, then configure payments and shipping zones in WooCommerce.', 'premium-shop' ); ?></span></td>
						<td style="text-align:right">
							<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Add product', 'premium-shop' ); ?></a>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>"><?php esc_html_e( 'Payments', 'premium-shop' ); ?></a>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php esc_html_e( 'Shipping', 'premium-shop' ); ?></a>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Run a setup step.
 */
function premium_shop_run_setup_step() {
	$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked below.

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'premium-shop' ), 403 );
	}
	check_admin_referer( 'premium_shop_setup_' . $step );

	switch ( $step ) {
		case 'pages':
			premium_shop_setup_pages();
			break;
		case 'menus':
			premium_shop_setup_pages();
			premium_shop_setup_menus();
			break;
		case 'languages':
			if ( current_user_can( 'install_languages' ) ) {
				premium_shop_setup_languages( ! empty( $_POST['set_german'] ) );
			}
			break;
		case 'firewood':
			if ( premium_shop_is_wc() && current_user_can( 'manage_woocommerce' ) && function_exists( 'premium_shop_fw_create_catalogue' ) ) {
				premium_shop_fw_create_catalogue( ! empty( $_POST['publish'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
				set_theme_mod( 'ps_preset', 'firewood' );
			}
			break;
		case 'images':
			if ( premium_shop_is_wc() && current_user_can( 'manage_woocommerce' ) ) {
				update_option( 'woocommerce_thumbnail_cropping', 'custom' );
				update_option( 'woocommerce_thumbnail_cropping_custom_width', '4' );
				update_option( 'woocommerce_thumbnail_cropping_custom_height', '5' );
				update_option( 'woocommerce_thumbnail_image_width', 600 );
				update_option( 'woocommerce_single_image_width', 1000 );
			}
			break;
	}

	delete_option( 'premium_shop_welcome' );
	wp_safe_redirect( admin_url( 'themes.php?page=premium-shop-setup&ps_done=' . $step ) );
	exit;
}
add_action( 'admin_post_premium_shop_setup', 'premium_shop_run_setup_step' );

/**
 * Create the default pages (in the default shop language).
 *
 * @return array slug => page ID.
 */
function premium_shop_setup_pages() {
	$locale = premium_shop_locale_for( premium_shop_default_language() );
	$t      = static function ( $text ) use ( $locale ) {
		return premium_shop_translate_in( $text, $locale );
	};

	$pages = array(
		'ueber-uns'           => array(
			'title'   => $t( 'About us' ),
			'content' => premium_shop_about_page_content( $t ),
			'status'  => 'publish',
		),
		'kontakt'             => array(
			'title'    => $t( 'Contact' ),
			'content'  => '<!-- wp:paragraph --><p>' . esc_html( $t( 'We look forward to hearing from you. Our customer service will answer as quickly as possible.' ) ) . '</p><!-- /wp:paragraph -->',
			'status'   => 'publish',
			'template' => 'page-templates/template-contact.php',
		),
		'wunschliste'         => array(
			'title'    => $t( 'Wishlist' ),
			'content'  => '',
			'status'   => 'publish',
			'template' => 'page-templates/template-wishlist.php',
		),
		'sendungsverfolgung'  => premium_shop_is_wc() ? premium_shop_tracking_page_data( $t ) : null,
		'versand-und-zahlung' => array(
			'title'   => $t( 'Shipping & payment' ),
			'content' => premium_shop_shipping_page_content( $t ),
			'status'  => 'publish',
		),
		'impressum'           => array(
			'title'   => $t( 'Legal notice' ),
			'content' => '<!-- wp:paragraph --><p>' . esc_html( $t( 'Please complete this page with your legally required company information before publishing.' ) ) . '</p><!-- /wp:paragraph -->',
			'status'  => 'draft',
		),
		'agb'                 => array(
			'title'   => $t( 'Terms & conditions' ),
			'content' => '<!-- wp:paragraph --><p>' . esc_html( $t( 'Please complete this page with your legally required company information before publishing.' ) ) . '</p><!-- /wp:paragraph -->',
			'status'  => 'draft',
		),
		'widerrufsbelehrung'  => array(
			'title'   => $t( 'Right of withdrawal' ),
			'content' => '<!-- wp:paragraph --><p>' . esc_html( $t( 'Please complete this page with your legally required company information before publishing.' ) ) . '</p><!-- /wp:paragraph -->',
			'status'  => 'draft',
		),
	);

	$ids = array();

	foreach ( array_filter( $pages ) as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => $page['status'],
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => $page['content'],
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			if ( ! empty( $page['template'] ) ) {
				update_post_meta( $id, '_wp_page_template', $page['template'] );
			}
			$ids[ $slug ] = $id;
		}
	}

	if ( ! empty( $ids['sendungsverfolgung'] ) ) {
		update_option( 'premium_shop_tracking_page', (int) $ids['sendungsverfolgung'], false );
	}

	return $ids;
}

/**
 * Order tracking page (WooCommerce tracking form).
 *
 * @param callable $t Translation in the shop language.
 * @return array
 */
function premium_shop_tracking_page_data( $t ) {
	return array(
		'title'   => $t( 'Order tracking' ),
		'content' => '<!-- wp:paragraph --><p>' . esc_html( $t( 'Where is my order? Enter your order number and e-mail address to see its status, the planned delivery date and the tracking number.' ) ) . '</p><!-- /wp:paragraph --><!-- wp:shortcode -->[woocommerce_order_tracking]<!-- /wp:shortcode -->',
		'status'  => 'publish',
	);
}

/**
 * Contact page with form and order tracking page, also on existing sites
 * (theme update): the existing "kontakt" page keeps its text and gets the
 * contact template; the tracking page is created and added to the
 * customer service footer menu.
 */
function premium_shop_setup_service_pages() {
	$locale = premium_shop_locale_for( premium_shop_default_language() );
	$t      = static function ( $text ) use ( $locale ) {
		return premium_shop_translate_in( $text, $locale );
	};

	$contact = get_page_by_path( 'kontakt', OBJECT, 'page' );
	if ( $contact ) {
		$template = (string) get_post_meta( $contact->ID, '_wp_page_template', true );
		if ( '' === $template || 'default' === $template ) {
			update_post_meta( $contact->ID, '_wp_page_template', 'page-templates/template-contact.php' );
		}
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $t( 'Contact' ),
				'post_name'    => 'kontakt',
				'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $t( 'We look forward to hearing from you. Our customer service will answer as quickly as possible.' ) ) . '</p><!-- /wp:paragraph -->',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'page-templates/template-contact.php' );
		}
	}

	if ( ! premium_shop_is_wc() ) {
		return;
	}

	$tracking = get_page_by_path( 'sendungsverfolgung', OBJECT, 'page' );
	if ( ! $tracking ) {
		$data = premium_shop_tracking_page_data( $t );
		$id   = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $data['title'],
				'post_name'    => 'sendungsverfolgung',
				'post_content' => $data['content'],
			)
		);
		$tracking = $id && ! is_wp_error( $id ) ? get_post( $id ) : null;
	}
	if ( ! $tracking ) {
		return;
	}
	update_option( 'premium_shop_tracking_page', (int) $tracking->ID, false );

	// Add it to the customer service footer menu when that menu exists.
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menu_id   = ! empty( $locations['footer_service'] ) ? (int) $locations['footer_service'] : 0;
	if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			if ( (int) $item->object_id === (int) $tracking->ID ) {
				return;
			}
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $tracking->ID,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
}
add_action( 'premium_shop_upgrade_1_3', 'premium_shop_setup_service_pages' );

/**
 * Create and assign the default menus (never overwrites existing ones).
 */
function premium_shop_setup_menus() {
	$locale    = premium_shop_locale_for( premium_shop_default_language() );
	$t         = static function ( $text ) use ( $locale ) {
		return premium_shop_translate_in( $text, $locale );
	};
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$shop_url  = premium_shop_shop_url();

	$page_item = static function ( $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page || 'publish' !== $page->post_status ) {
			return null;
		}
		return array(
			'menu-item-object-id' => $page->ID,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		);
	};
	$link_item = static function ( $title, $url, $classes = '' ) {
		return array(
			'menu-item-title'   => $title,
			'menu-item-url'     => $url,
			'menu-item-type'    => 'custom',
			'menu-item-status'  => 'publish',
			'menu-item-classes' => $classes,
		);
	};

	$menus = array(
		'primary'        => array(
			'name'  => $t( 'Main menu' ),
			'items' => array(
				$link_item( $t( 'Home' ), home_url( '/' ) ),
				premium_shop_is_wc() ? $link_item( $t( 'Shop' ), $shop_url ) : null,
				premium_shop_is_wc() ? $link_item( $t( 'Categories' ), $shop_url, 'mega-categories' ) : null,
				premium_shop_is_wc() ? $link_item( $t( 'Offers' ), add_query_arg( 'on_sale', '1', $shop_url ) ) : null,
				$page_item( 'ueber-uns' ),
				$page_item( 'kontakt' ),
			),
		),
		'footer_shop'    => array(
			'name'  => $t( 'Footer — Shop' ),
			'items' => premium_shop_is_wc() ? array(
				$link_item( $t( 'All products' ), $shop_url ),
				$link_item( $t( 'New arrivals' ), add_query_arg( 'orderby', 'date', $shop_url ) ),
				$link_item( $t( 'Bestsellers' ), add_query_arg( 'orderby', 'popularity', $shop_url ) ),
				$link_item( $t( 'Offers' ), add_query_arg( 'on_sale', '1', $shop_url ) ),
			) : array(),
		),
		'footer_service' => array(
			'name'  => $t( 'Footer — Customer service' ),
			'items' => array(
				premium_shop_is_wc() ? $link_item( $t( 'My account' ), wc_get_page_permalink( 'myaccount' ) ) : null,
				$page_item( 'versand-und-zahlung' ),
				$page_item( 'sendungsverfolgung' ),
				$page_item( 'kontakt' ),
				$page_item( 'ueber-uns' ),
			),
		),
		'footer_legal'   => array(
			'name'  => $t( 'Footer — Legal' ),
			'items' => array(
				$page_item( 'impressum' ),
				$page_item( 'agb' ),
				$page_item( 'widerrufsbelehrung' ),
				get_option( 'wp_page_for_privacy_policy' ) && 'publish' === get_post_status( (int) get_option( 'wp_page_for_privacy_policy' ) ) ? array(
					'menu-item-object-id' => (int) get_option( 'wp_page_for_privacy_policy' ),
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				) : null,
			),
		),
	);

	foreach ( $menus as $location => $menu ) {
		if ( ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ) {
			continue;
		}

		$items = array_filter( $menu['items'] );
		if ( ! $items && 'footer_legal' !== $location ) {
			continue;
		}

		$existing = wp_get_nav_menu_object( $menu['name'] );
		$menu_id  = $existing ? $existing->term_id : wp_create_nav_menu( $menu['name'] );

		if ( is_wp_error( $menu_id ) ) {
			continue;
		}

		if ( ! $existing ) {
			foreach ( $items as $item ) {
				wp_update_nav_menu_item( $menu_id, 0, $item );
			}
		}

		$locations[ $location ] = $menu_id;
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Install core + plugin language packs and optionally set German.
 *
 * @param bool $set_german Switch the site language to German.
 */
function premium_shop_setup_languages( $set_german ) {
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	if ( ! wp_can_install_language_pack() ) {
		return;
	}

	foreach ( premium_shop_languages() as $lang ) {
		if ( 'en_US' !== $lang['locale'] ) {
			wp_download_language_pack( $lang['locale'] );
		}
	}

	// Fetch plugin/theme translations (WooCommerce…) for the installed locales.
	wp_clean_update_cache();
	wp_update_plugins();
	wp_update_themes();

	$updates = wp_get_translation_updates();
	if ( $updates ) {
		$upgrader = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin() );
		$upgrader->bulk_upgrade( $updates );
	}

	if ( $set_german && in_array( 'de_DE', get_available_languages(), true ) ) {
		update_option( 'WPLANG', 'de_DE' );
	}
}
