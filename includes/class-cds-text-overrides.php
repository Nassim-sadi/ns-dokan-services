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
	 * Whether we're in the admin area (to avoid recursion during option saves).
	 *
	 * @var bool
	 */
	private static $is_admin = false;

	/**
	 * Hook the gettext filters.
	 */
	public function __construct() {
		// Cache settings at init to avoid get_option() inside gettext filters.
		add_action( 'init', array( $this, 'cache_settings' ), 5 );

		// All gettext hooks are high priority; they read from cached data only.
		add_filter( 'gettext', array( $this, 'override_strings' ), 20, 3 );
		add_filter( 'gettext_with_context', array( $this, 'override_strings_with_context' ), 20, 4 );
		add_filter( 'ngettext', array( $this, 'override_plural_strings' ), 20, 5 );
	}

	private static function get_default_french_strings() {
		return array(
			'custom_dashboard_strings' => '{"Products":"Produits","Orders":"Commandes","Coupons":"Codes promo","Reports":"Rapports","Settings":"Param\u00e8tres","Log Out":"D\u00e9connexion","Profile":"Profil","Store":"Boutique","Withdraw":"Retraits","Shipping":"Exp\u00e9dition","Reviews":"Avis","Attributes":"Attributs","Add Product":"Ajouter un produit","Add New Product":"Ajouter un nouveau produit","Subscribers":"Abonn\u00e9s","Followers":"Abonn\u00e9s","Contact":"Contact","Shipping Zone":"Zone d\u0027exp\u00e9dition"}',
			'custom_filter_strings'    => '{"Filter":"Filtrer","Cancel":"Annuler","Apply":"Appliquer","Search Vendors":"Rechercher des vendeurs","Sort by":"Trier par :","Most Recent":"R\u00e9cent","Most Popular":"Populaire","Random":"Al\u00e9atoire","Total store showing: %s":"Magasin affich\u00e9 : %s","Total stores showing: %s":"Magasins affich\u00e9s : %s"}',
			'custom_store_page_strings' => '{"Visit Store":"Visiter la boutique","Add to cart":"Ajouter au panier","View cart":"Voir le panier","Checkout":"Commander","My account":"Mon compte","Logout":"D\u00e9connexion","Login":"Connexion","Price":"Prix","Availability":"Disponibilit\u00e9","In stock":"En stock","Out of stock":"Rupture de stock","Additional information":"Informations compl\u00e9mentaires","Description":"Description","Related products":"Produits similaires","Search":"Rechercher","Featured":"En vedette","Default sorting":"Tri par d\u00e9faut","Sort by popularity":"Trier par popularit\u00e9","Sort by average rating":"Trier par note moyenne","Sort by latest":"Trier par r\u00e9cent","Sort by price: low to high":"Trier par prix : du moins cher au plus cher","Sort by price: high to low":"Trier par prix : du plus cher au moins cher","Add to wishlist":"Ajouter \u00e0 la liste d\u2019envies","Browse wishlist":"Parcourir la liste d\u2019envies","The product is already in your wishlist!":"Le produit est d\u00e9j\u00e0 dans votre liste d\u2019envies !","Add to wishlist ":"Ajouter \u00e0 la liste d\u2019envies ","Wishlist added":"Liste d\u2019envies ajout\u00e9e","Wishlist removed":"Liste d\u2019envies supprim\u00e9e","Product added!":"Produit ajout\u00e9 !"}',
		);
	}

	/**
	 * Cache the settings and pre-decode the JSON string maps once.
	 *
	 * @return void
	 */
	public function cache_settings() {
		$option = get_option( CDS_SETTINGS_KEY, array() );
		$option = is_array( $option ) ? $option : array();

		// Merge in defaults so new keys exist even on existing installs.
		$defaults = array(
			'override_dashboard_new'  => 1,
			'override_dashboard_old'  => 1,
			'override_filters'        => 1,
			'override_store_page'     => 1,
			'custom_dashboard_strings' => '',
			'custom_filter_strings'    => '',
			'custom_store_page_strings' => '',
		);

		self::$settings = wp_parse_args( $option, $defaults );
		self::$is_admin = is_admin();

		$fr_defaults = ( get_locale() === 'fr_FR' ) ? self::get_default_french_strings() : array();

		$keys = array( 'custom_dashboard_strings', 'custom_filter_strings', 'custom_store_page_strings' );

		foreach ( $keys as $key ) {
			$json = isset( self::$settings[ $key ] ) ? self::$settings[ $key ] : '';
			$json = ( '' === $json && isset( $fr_defaults[ $key ] ) ) ? $fr_defaults[ $key ] : $json;
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
		if ( ! in_array( $domain, array( 'dokan-lite', 'woocommerce' ), true ) ) {
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
		if ( ! in_array( $domain, array( 'dokan-lite', 'woocommerce' ), true ) ) {
			return $translation;
		}

		$map = $this->resolve_map( $text );

		return $map !== null ? $map : $translation;
	}

	/**
	 * Override plural strings (e.g. _n("Total store showing: %s", ...)). Called
	 * on ngettext.
	 *
	 * @param string $translation Translated string (already pluralized).
	 * @param string $singular    Singular form.
	 * @param string $plural      Plural form.
	 * @param int    $number      Count.
	 * @param string $domain      Text domain.
	 *
	 * @return string
	 */
	public function override_plural_strings( $translation, $singular, $plural, $number, $domain ) {
		if ( ! in_array( $domain, array( 'dokan-lite', 'woocommerce' ), true ) ) {
			return $translation;
		}

		$text = ( $number == 1 ) ? $singular : $plural;
		$map  = $this->resolve_map( $text );

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
