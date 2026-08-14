<?php
/**
 * Shared helper functions.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get a plugin setting with a default fallback.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 *
 * @return mixed
 */
function cds_get_setting( $key, $default = '' ) {
	$settings = get_option( CDS_SETTINGS_KEY, array() );
	$settings = is_array( $settings ) ? $settings : array();

	if ( isset( $settings[ $key ] ) ) {
		return $settings[ $key ];
	}

	return $default;
}

/**
 * Get the vendor type for a user. Always one of 'store' or 'service'.
 *
 * @param int $user_id User ID.
 *
 * @return string
 */
function cds_get_vendor_type( $user_id ) {
	$type = get_user_meta( $user_id, CDS_VENDOR_TYPE_KEY, true );

	return in_array( $type, array( 'store', 'service' ), true ) ? $type : 'store';
}

/**
 * The ID of the page assigned as the services shops listing page.
 *
 * @return int
 */
function cds_get_services_listing_page_id() {
	return (int) cds_get_setting( 'services_listing_page', 0 );
}

/**
 * Whether the given post ID (or the current page) is the assigned
 * services shops listing page. Polylang-aware: any translation of the
 * assigned page counts.
 *
 * @param int|null $page_id Page ID to test. Defaults to the current page.
 *
 * @return bool
 */
function cds_is_services_listing( $page_id = null ) {
	$assigned = cds_get_services_listing_page_id();

	if ( ! $assigned ) {
		return false;
	}

	if ( null === $page_id ) {
		$page_id = get_the_ID();
	}

	$page_id = (int) $page_id;

	if ( $page_id === $assigned ) {
		return true;
	}

	// Polylang: the current page may be a translation of the assigned one.
	if ( function_exists( 'pll_get_post_translations' ) ) {
		$translations = pll_get_post_translations( $assigned );

		if ( is_array( $translations ) && in_array( $page_id, array_map( 'intval', $translations ), true ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Render the Products vendor type filter in the admin.
 *
 * Adds a dropdown to the Products list table: All, Products, Services.
 *
 * @return void
 */
function cds_products_vendor_filter() {
	// Only show on Products post type screen
	if ( ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) {
		return;
	}

	// Determine the current selection
	$current = isset( $_GET['cds_products_vendor'] ) ? sanitize_text_field( wp_unslash( $_GET['cds_products_vendor'] ) ) : 'all';

	// Build the dropdown options
	$options = array(
		'all'      => __( 'All', 'camalg-services' ),
		'products' => __( 'Products', 'camalg-services' ),
		'services' => __( 'Services', 'camalg-services' ),
	);

	// Build option HTML
	$html = '<select name="cds_products_vendor" class="postform">';
	foreach ( $options as $value => $label ) {
		$selected = selected( $current, $value, false );
		$html .= '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
	}
	$html .= '</select>';

	// Add action hook
	echo '<div class="alignleft actions">'
		. '<label class="screen-reader-text" for="post_type">' . __( 'Products vendor type', 'camalg-services' ) . '</label>'
		. $html
		. '</div>';
}

/**
 * Filter the admin Products query by vendor type.
 *
 * The vendor type is stored as user meta, so we resolve the selected type to
 * the list of vendor user IDs and restrict the query by post author.
 *
 * @param array    $clauses Query clauses (where, join, etc.).
 * @param WP_Query $query   The WP_Query instance.
 *
 * @return array
 */
function cds_products_vendor_query( $clauses, $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return $clauses;
	}

	global $pagenow;

	if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) {
		return $clauses;
	}

	if ( empty( $_GET['cds_products_vendor'] ) || 'all' === $_GET['cds_products_vendor'] ) {
		return $clauses;
	}

	$selected = sanitize_text_field( wp_unslash( $_GET['cds_products_vendor'] ) );

	if ( ! in_array( $selected, array( 'products', 'services' ), true ) ) {
		return $clauses;
	}

	// Map the filter value to the vendor type stored in user meta.
	$vendor_type = ( 'services' === $selected ) ? 'service' : 'store';

	$vendor_ids = get_users(
		array(
			'meta_key'   => CDS_VENDOR_TYPE_KEY,
			'meta_value' => $vendor_type,
			'fields'     => 'ID',
			'number'     => -1,
		)
	);

	if ( empty( $vendor_ids ) ) {
		$clauses['where'] .= ' AND 1=0';
	} else {
		$ids = implode( ',', array_map( 'intval', $vendor_ids ) );
		$clauses['where'] .= " AND wp_posts.post_author IN ({$ids})";
	}

	return $clauses;
}

add_action( 'restrict_manage_posts', 'cds_products_vendor_filter' );
add_filter( 'posts_clauses', 'cds_products_vendor_query', 20, 2 );
