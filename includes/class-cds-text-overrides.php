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
			'custom_dashboard_strings' => '{"Products":"Produits","Services":"Services","Orders":"Commandes","Coupons":"Codes promo","Reports":"Rapports","Settings":"Param\u00e8tres","Log Out":"D\u00e9connexion","Profile":"Profil","Store":"Boutique","Withdraw":"Retraits","Shipping":"Exp\u00e9dition","Reviews":"Avis","Attributes":"Attributs","Add Product":"Ajouter un produit","Add New Product":"Ajouter un nouveau produit","Subscribers":"Abonn\u00e9s","Followers":"Abonn\u00e9s","Contact":"Contact","Shipping Zone":"Zone d\u0027exp\u00e9dition","No withdraw method is available. Please contact site admin.":"Aucun moyen de retrait n\u0027est disponible. Veuillez contacter l\u0027administrateur du site.","Invalid withdraw method. Please contact site admin":"M\u00e9thode de retrait invalide. Veuillez contacter l\u0027administrateur du site","Your Balance:":"Votre solde :","You do not have any approved withdraw yet.":"Vous n\u0027avez pas encore de retrait approuv\u00e9.","Payment Methods":"M\u00e9thodes de paiement","Show email address in store":"Afficher l\u0027adresse e-mail dans la boutique","Store has open close time":"La boutique a des horaires d\u0027ouverture et de fermeture","Marketplace Commission":"Commission de la place de march\u00e9","Total Earning":"Revenu total","Marketplace Discount":"Remise de la place de march\u00e9","Store Discount":"Remise du vendeur","Digital Product Options":"Options de produit num\u00e9rique","Sale Price":"Prix sold\u00e9","Create Schedule for Discount":"Cr\u00e9er un planning de remise","Brands":"Marques","Select product brands":"S\u00e9lectionner les marques du produit","Categories":"Cat\u00e9gories","Select product categories":"S\u00e9lectionner les cat\u00e9gories du produit","Feature Image":"Image \u00e0 la une","Select product image":"S\u00e9lectionner l\u0027image du produit","Gallery Image":"Image de galerie","Select product gallery images":"S\u00e9lectionner les images de la galerie du produit","Manage stock?":"G\u00e9rer le stock ?","Manage stock level (quantity)":"G\u00e9rer le niveau de stock (quantit\u00e9)","Manage inventory for this product":"G\u00e9rer l\u0027inventaire de ce produit","SKU":"UGS","Stock Keeping Unit":"Unit\u00e9 de gestion des stocks","Enter product SKU":"Saisir l\u0027UGS du produit","SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.":"L\u0027UGS d\u00e9signe une unit\u00e9 de gestion des stocks, un identifiant unique pour chaque produit et service distinct pouvant \u00eatre achet\u00e9.","GTIN, UPC, EAN, or ISBN":"GTIN, UPC, EAN ou ISBN","Product Identifiers":"Identifiants de produit","Enter code":"Saisir le code","Enter a barcode or any other identifier unique to this product. It can help you list this product on other channels or marketplaces.":"Saisissez un code-barres ou tout autre identifiant unique \u00e0 ce produit. Il peut vous aider \u00e0 r\u00e9f\u00e9rencer ce produit sur d\u0027autres canaux ou places de march\u00e9.","Permalink":"Permalien","Enter product slug...":"Saisir le slug du produit...","Enter product title...":"Saisir le titre du produit...","Enter product description":"Saisir la description du produit","Enter product short description":"Saisir la description courte du produit","Choose product type.":"Choisissez le type de produit.","Enabled":"Activ\u00e9","Downloadable Files":"Fichiers t\u00e9l\u00e9chargeables","Downloadable products give access to a file upon purchase.":"Les produits t\u00e9l\u00e9chargeables donnent acc\u00e8s \u00e0 un fichier apr\u00e8s l\u0027achat.","Virtual products are intangible and are not shipped.":"Les produits virtuels sont immat\u00e9riels et ne sont pas exp\u00e9di\u00e9s.","Leave blank for unlimited re-downloads.":"Laissez vide pour des t\u00e9l\u00e9chargements illimit\u00e9s.","Enter the number of days before a download link expires, or leave blank.":"Saisissez le nombre de jours avant l\u0027expiration du lien de t\u00e9l\u00e9chargement, ou laissez vide.","Pick downloadable files from upload directory which is approved by the store admin.":"S\u00e9lectionnez des fichiers t\u00e9l\u00e9chargeables depuis le r\u00e9pertoire de t\u00e9l\u00e9chargement approuv\u00e9 par l\u0027administrateur du site.","Upload files that customers can download after purchase.":"T\u00e9l\u00e9versez les fichiers que les clients pourront t\u00e9l\u00e9charger apr\u00e8s l\u0027achat.","Allow only one quantity of this product to be bought in a single order.":"Autoriser uniquement l\u0027achat d\u0027une seule quantit\u00e9 de ce produit par commande.","Check to let customers to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, for example art or handmade goods.":"Cochez cette case pour permettre aux clients d\u0027acheter un seul article par commande. C\u0027est particuli\u00e8rement utile pour les articles en quantit\u00e9 limit\u00e9e, par exemple les \u0153uvres d\u0027art ou les produits artisanaux.","Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.":"Quantit\u00e9 en stock. S\u0027il s\u0027agit d\u0027un produit variable, cette valeur servira \u00e0 contr\u00f4ler le stock de toutes les variations, sauf si vous d\u00e9finissez le stock au niveau de la variation.","When product stock reaches this amount you will be notified by email. It is possible to define different values for each variation individually.":"Lorsque le stock du produit atteint cette quantit\u00e9, vous \u00eates averti par e-mail. Il est possible de d\u00e9finir des valeurs diff\u00e9rentes pour chaque variation.","If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.":"Si la gestion du stock est activ\u00e9e, ce param\u00e8tre contr\u00f4le si les commandes en attente (backorders) sont autoris\u00e9es. Si activ\u00e9, la quantit\u00e9 en stock peut passer sous 0.","Store-wide threshold (%d)":"Seuil g\u00e9n\u00e9ral du site (%d)","Sale price must be less than the regular price.":"Le prix sold\u00e9 doit \u00eatre inf\u00e9rieur au prix normal.","Customer will get this in order email.":"Le client recevra ceci dans l\u0027e-mail de commande.","Invalid field type: %1$s and id: %2$s":"Type de champ invalide : %1$s et id : %2$s","Invalid field variant: %1$s and id: %2$s":"Variante de champ invalide : %1$s et id : %2$s","Missing required attribute \"%1$s\" on field: %2$s":"Attribut requis \"%1$s\" manquant sur le champ : %2$s"}',
			'custom_filter_strings'    => '{"Filter":"Filtrer","Cancel":"Annuler","Apply":"Appliquer","Search Vendors":"Rechercher des vendeurs","Sort by":"Trier par","Most Recent":"R\u00e9cent","Most Popular":"Populaire","Random":"Al\u00e9atoire","Total store showing: %s":"Magasin affich\u00e9 : %s","Total stores showing: %s":"Magasins affich\u00e9s : %s"}',
			'custom_store_page_strings' => '{"Visit Store":"Visiter la boutique","Add to cart":"Ajouter au panier","View cart":"Voir le panier","Checkout":"Commander","My account":"Mon compte","Logout":"D\u00e9connexion","Login":"Connexion","Price":"Prix","Availability":"Disponibilit\u00e9","In stock":"En stock","Out of stock":"Rupture de stock","Additional information":"Informations compl\u00e9mentaires","Description":"Description","Related products":"Produits similaires","Search":"Rechercher","Featured":"En vedette","Default sorting":"Tri par d\u00e9faut","Sort by popularity":"Trier par popularit\u00e9","Sort by average rating":"Trier par note moyenne","Sort by latest":"Trier par r\u00e9cent","Sort by price: low to high":"Trier par prix : du moins cher au plus cher","Sort by price: high to low":"Trier par prix : du plus cher au moins cher","Add to wishlist":"Ajouter \u00e0 la liste d\u2019envies","Browse wishlist":"Parcourir la liste d\u2019envies","The product is already in your wishlist!":"Le produit est d\u00e9j\u00e0 dans votre liste d\u2019envies !","Product added!":"Produit ajout\u00e9 !","Store Product Category":"Catégorie de produits","Contact Vendor":"Contacter le vendeur","Your Name":"Votre nom","you@example.com":"vous@example.com","Type your message...":"Tapez votre message...","Send Message":"Envoyer le message"}',
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
			$json    = isset( self::$settings[ $key ] ) ? self::$settings[ $key ] : '';
			$decoded = json_decode( (string) $json, true );
			$decoded = is_array( $decoded ) ? $decoded : array();

			// Merge the French defaults in so new keys (e.g. the withdraw
			// warning strings) reach older installs; stored values win.
			if ( ! empty( $fr_defaults[ $key ] ) ) {
				$fr = json_decode( $fr_defaults[ $key ], true );

				if ( is_array( $fr ) ) {
					$decoded = array_merge( $fr, $decoded );
				}
			}

			self::$custom_strings[ $key ] = $decoded;
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

	// Check if this is a service vendor and text is "Products" — show "Services" instead
	if ( $this->is_service_vendor_and_text( $text ) ) {
		return 'Services';
	}

	$map = $this->resolve_map( $text );

	return $map !== null ? $map : $translation;
}

/**
 * Check if current user is a service vendor and the text matches "Products".
 *
 * @param string $text Original text.
 *
 * @return bool
 */
private function is_service_vendor_and_text( $text ) {
	// Only check on frontend, not admin
	if ( is_admin() ) {
		return false;
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}

	$vendor_type = cds_get_vendor_type( $user_id );
	if ( 'service' !== $vendor_type ) {
		return false;
	}

	return 'Products' === $text || 'Product' === $text;
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
