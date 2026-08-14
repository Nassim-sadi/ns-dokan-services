<?php
/**
 * Text Overrides handler.
 *
 * Intercepts gettext filters to replace Dokan strings with custom mappings
 * when the corresponding override is enabled.
 *
 * Settings are cached at init time to avoid calling get_option() (which can
 * trigger further gettext calls) inside the gettext handlers itself.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Text_Overrides {

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $settings = null;

	/**
	 * Cached custom string maps.
	 *
	 * @var array
	 */
	private static $custom_strings = array();

	/**
	 * Hook the gettext filters.
	 */
	public function __construct() {
		// Cache settings at init to avoid get_option() inside gettext filters.
		add_action( 'init', array( $this, 'cache_settings' ), 5 );

		// All gettext hooks are high priority; they read from cached data only.
		add_filter( 'gettext', array( $this, 'override_strings' ), 20, 3 );
		add_filter( 'gettext_with_context', array( $this, 'override_strings_with_context' ), 20, 4 );
	}

	/**
	 * Cache the settings and pre-decode the JSON string maps once.
	 *
	 * @return void
	 */
	public function cache_settings() {
		self::$settings = get_option( CDS_SETTINGS_KEY, array() );
		self::$settings = is_array( self::$settings ) ? self::$settings : array();

		$keys = array(
			'custom_dashboard_strings',
			'custom_filter_strings',
			'custom_store_page_strings',
		);

		foreach ( $keys as $key ) {
			$json    = isset( self::$settings[ $key ] ) ? self::$settings[ $key ] : '';
			$decoded = json_decode( (string) $json, true );

			self::$custom_strings[ $key ] = is_array( $decoded ) ? $decoded : array();
		}
	}

	/**
	 * Override strings based on enabled toggles. Called on gettext.
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 *
	 * @return string
	 */
	public function override_strings( $translation, $text, $domain ) {
		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		$map = $this->resolve_map( $text );

		return $map !== null ? $map : $translation;
	}

	/**
	 * Override strings (context-aware). Called on gettext_with_context.
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @param string $context     Context.
	 *
	 * @return string
	 */
	public function override_strings_with_context( $translation, $text, $domain, $context = '' ) {
		if ( 'dokan-lite' !== $domain ) {
			return $translation;
		}

		$map = $this->resolve_map( $text );

		return $map !== null ? $map : $translation;
	}

	/**
	 * Resolve the override map for a given text.
	 *
	 * Checks each enabled toggle and returns the replacement if found.
	 *
	 * @param string $text Original text.
	 *
	 * @return string|null Null when no override applies.
	 */
	private function resolve_map( $text ) {
		if ( self::$settings === null ) {
			return null;
		}

		// Dashboard overrides (new + legacy).
		if ( (int) ( self::$settings['override_dashboard_new'] ?? 0 )
			|| (int) ( self::$settings['override_dashboard_old'] ?? 0 ) ) {
			$map = self::$custom_strings['custom_dashboard_strings'] ?? array();

			if ( isset( $map[ $text ] ) ) {
				return $map[ $text ];
			}
		}

		// Filter overrides.
		if ( (int) ( self::$settings['override_filters'] ?? 0 ) ) {
			$map = self::$custom_strings['custom_filter_strings'] ?? array();

			if ( isset( $map[ $text ] ) ) {
				return $map[ $text ];
			}
		}

		// Store page overrides.
		if ( (int) ( self::$settings['override_store_page'] ?? 0 ) ) {
			$map = self::$custom_strings['custom_store_page_strings'] ?? array();

			if ( isset( $map[ $text ] ) ) {
				return $map[ $text ];
			}
		}

		return null;
	}
}
