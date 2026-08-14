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
