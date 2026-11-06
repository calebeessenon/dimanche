<?php
/**
 * Search form (used by get_search_form()).
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/components/search-form',
	null,
	array(
		'id'   => wp_unique_id( 'ps-search-form-' ),
		'live' => true,
	)
);
