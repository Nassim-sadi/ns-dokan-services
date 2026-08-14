<?php
/**
 * Text Overrides handler.
 *
 * Intercepts gettext filters to replace Dokan strings with custom mappings
 * when the corresponding override is enabled.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Text_Overrides {

	/**
	 * Hook the gettext filters.
	 */
	public function __construct() {
		// Dashboard (new Vue-based) - dokan-lite domain
		add_filter( 'gettext', array( $this, 'override_dashboard_strings' ), 20, 3 );
		add_filter( 'gettext_with_context', array( $this, 'override_dashboard_strings' ), 20, 4 );

		// Legacy dashboard - dokan-lite domain
		add_filter( 'gettext', array( $this, 'override_legacy_dashboard_strings' ), 20, 3 );
		add_filter( 'gettext_with_context', array( $this, 'override_legacy_dashboard_strings' ), 20, 4 );

		// Filters - dokan-lite domain
		add_filter( 'gettext', array( $this, 'override_filter_strings' ), 20, 3 );
		add_filter( 'gettext_with_context', array( $this, 'override_filter_strings' ), 20, 4 );

		// Store page - dokan-lite domain
		add_filter( 'gettext', array( $this, 'override_store_page_strings' ), 20, 3 );
		add_filter( 'gettext_with_context', array( $this, 'override_store_page_strings' ), 20, 4 );
	}

	/**
	 * Override new dashboard strings (Vue-based dashboard).
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @param string $context     Context (for gettext_with_context).
	 *
	 * @return string
	 */
	public function override_dashboard_strings( $translation, $text, $domain, $context = '' ) {
		if ( ! cds_get_setting( 'override_dashboard_new', 1 ) ) {
			return $translation;
		}

		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		$custom = $this->get_custom_strings( 'custom_dashboard_strings' );

		if ( isset( $custom[ $text ] ) ) {
			return $custom[ $text ];
		}

		return $translation;
	}

	/**
	 * Override legacy dashboard strings (pre-Vue dashboard).
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @param string $context     Context (for gettext_with_context).
	 *
	 * @return string
	 */
	public function override_legacy_dashboard_strings( $translation, $text, $domain, $context = '' ) {
		if ( ! cds_get_setting( 'override_dashboard_old', 1 ) ) {
			return $translation;
		}

		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		$custom = $this->get_custom_strings( 'custom_dashboard_strings' );

		if ( isset( $custom[ $text ] ) ) {
			return $custom[ $text ];
		}

		return $translation;
	}

	/**
	 * Override filter strings (search, sort, view toggle).
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @param string $context     Context (for gettext_with_context).
	 *
	 * @return string
	 */
	public function override_filter_strings( $translation, $text, $domain, $context = '' ) {
		if ( ! cds_get_setting( 'override_filters', 1 ) ) {
			return $translation;
		}

		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		$custom = $this->get_custom_strings( 'custom_filter_strings' );

		if ( isset( $custom[ $text ] ) ) {
			return $custom[ $text ];
		}

		return $translation;
	}

	/**
	 * Override store page strings (Visit Store, Follow, etc.).
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @param string $context     Context (for gettext_with_context).
	 *
	 * @return string
	 */
	public function override_store_page_strings( $translation, $text, $domain, $context = '' ) {
		if ( ! cds_get_setting( 'override_store_page', 1 ) ) {
			return $translation;
		}

		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		$custom = $this->get_custom_strings( 'custom_store_page_strings' );

		if ( isset( $custom[ $text ] ) ) {
			return $custom[ $text ];
		}

		return $translation;
	}

	/**
	 * Get custom string mappings from settings.
	 *
	 * @param string $key Settings key.
	 *
	 * @return array
	 */
	private function get_custom_strings( $key ) {
		$json = cds_get_setting( $key, '' );

		if ( empty( $json ) ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}