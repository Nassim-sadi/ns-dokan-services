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
			'custom_dashboard_strings' => '{"Products":"Produits","Services":"Services","Orders":"Commandes","Coupons":"Codes promo","Reports":"Rapports","Settings":"Paramètres","Log Out":"Déconnexion","Profile":"Profil","Store":"Boutique","Withdraw":"Retraits","Shipping":"Expédition","Reviews":"Avis","Attributes":"Attributs","Add Product":"Ajouter un produit","Add New Product":"Ajouter un nouveau produit","Subscribers":"Abonnés","Followers":"Abonnés","Contact":"Contact","Shipping Zone":"Zone d\'expédition"," You Earn : ":" Vous gagnez : ","No withdraw method is available. Please contact site admin.":"Aucun moyen de retrait n\'est disponible. Veuillez contacter l\'administrateur du site.","Invalid withdraw method. Please contact site admin":"Méthode de retrait invalide. Veuillez contacter l\'administrateur du site","Your Balance:":"Votre solde :","You do not have any approved withdraw yet.":"Vous n\'avez pas encore de retrait approuvé.","Payment Methods":"Méthodes de paiement","Show email address in store":"Afficher l\'adresse e-mail dans la boutique","Store has open close time":"La boutique a des horaires d\'ouverture et de fermeture","Marketplace Commission":"Commission de la place de marché","Total Earning":"Revenu total","Marketplace Discount":"Remise de la place de marché","Store Discount":"Remise du vendeur","Digital Product Options":"Options de produit numérique","Sale Price":"Prix soldé","Create Schedule for Discount":"Créer un planning de remise","Brands":"Marques","Select product brands":"Sélectionner les marques du produit","Categories":"Catégories","Select product categories":"Sélectionner les catégories du produit","Feature Image":"Image à la une","Select product image":"Sélectionner l\'image du produit","Gallery Image":"Image de galerie","Select product gallery images":"Sélectionner les images de la galerie du produit","Manage stock?":"Gérer le stock ?","Manage stock level (quantity)":"Gérer le niveau de stock (quantité)","Manage inventory for this product":"Gérer l\'inventaire de ce produit","SKU":"UGS","Stock Keeping Unit":"Unité de gestion des stocks","Enter product SKU":"Saisir l\'UGS du produit","SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.":"L\'UGS désigne une unité de gestion des stocks, un identifiant unique pour chaque produit et service distinct pouvant être acheté.","GTIN, UPC, EAN, or ISBN":"GTIN, UPC, EAN ou ISBN","Product Identifiers":"Identifiants de produit","Enter code":"Saisir le code","Enter a barcode or any other identifier unique to this product. It can help you list this product on other channels or marketplaces.":"Saisissez un code-barres ou tout autre identifiant unique à ce produit. Il peut vous aider à référencer ce produit sur d\'autres canaux ou places de marché.","Permalink":"Permalien","Enter product slug...":"Saisir le slug du produit...","Enter product title...":"Saisir le titre du produit...","Enter product description":"Saisir la description du produit","Enter product short description":"Saisir la description courte du produit","Choose product type.":"Choisissez le type de produit.","Enabled":"Activé","Downloadable Files":"Fichiers téléchargeables","Downloadable products give access to a file upon purchase.":"Les produits téléchargeables donnent accès à un fichier après l\'achat.","Virtual products are intangible and are not shipped.":"Les produits virtuels sont immatériels et ne sont pas expédiés.","Leave blank for unlimited re-downloads.":"Laissez vide pour des téléchargements illimités.","Enter the number of days before a download link expires, or leave blank.":"Saisissez le nombre de jours avant l\'expiration du lien de téléchargement, ou laissez vide.","Pick downloadable files from upload directory which is approved by the store admin.":"Sélectionnez des fichiers téléchargeables depuis le répertoire de téléchargement approuvé par l\'administrateur du site.","Upload files that customers can download after purchase.":"Téléversez les fichiers que les clients pourront télécharger après l\'achat.","Allow only one quantity of this product to be bought in a single order.":"Autoriser uniquement l\'achat d\'une seule quantité de ce produit par commande.","Check to let customers to purchase only 1 item in a single order. This is particularly useful for items that have limited quantity, for example art or handmade goods.":"Cochez cette case pour permettre aux clients d\'acheter un seul article par commande. C\'est particulièrement utile pour les articles en quantité limitée, par exemple les œuvres d\'art ou les produits artisanaux.","Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.":"Quantité en stock. S\'il s\'agit d\'un produit variable, cette valeur servira à contrôler le stock de toutes les variations, sauf si vous définissez le stock au niveau de la variation.","When product stock reaches this amount you will be notified by email. It is possible to define different values for each variation individually.":"Lorsque le stock du produit atteint cette quantité, vous êtes averti par e-mail. Il est possible de définir des valeurs différentes pour chaque variation.","If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.":"Si la gestion du stock est activée, ce paramètre contrôle si les commandes en attente (backorders) sont autorisées. Si activé, la quantité en stock peut passer sous 0.","Store-wide threshold (%d)":"Seuil général du site (%d)","Sale price must be less than the regular price.":"Le prix soldé doit être inférieur au prix normal.","Customer will get this in order email.":"Le client recevra ceci dans l\'e-mail de commande.","Invalid field type: %1$s and id: %2$s":"Type de champ invalide : %1$s et id : %2$s","Invalid field variant: %1$s and id: %2$s":"Variante de champ invalide : %1$s et id : %2$s","Missing required attribute \"%1$s\" on field: %2$s":"Attribut requis \"%1$s\" manquant sur le champ : %2$s"}',
			'custom_filter_strings'    => '{"Filter":"Filtrer","Cancel":"Annuler","Apply":"Appliquer","Search Vendors":"Rechercher des vendeurs","Sort by":"Trier par","Most Recent":"Récent","Most Popular":"Populaire","Random":"Aléatoire","Total store showing: %s":"Magasin affiché : %s","Total stores showing: %s":"Magasins affichés : %s"}',
			'custom_store_page_strings' => '{"Visit Store":"Visiter la boutique","Add to cart":"Ajouter au panier","View cart":"Voir le panier","Checkout":"Commander","My account":"Mon compte","Logout":"Déconnexion","Login":"Connexion","Price":"Prix","Availability":"Disponibilité","In stock":"En stock","Out of stock":"Rupture de stock","Additional information":"Informations complémentaires","Description":"Description","Related products":"Produits similaires","Search":"Rechercher","Featured":"En vedette","Default sorting":"Tri par défaut","Sort by popularity":"Trier par popularité","Sort by average rating":"Trier par note moyenne","Sort by latest":"Trier par récent","Sort by price: low to high":"Trier par prix : du moins cher au plus cher","Sort by price: high to low":"Trier par prix : du plus cher au moins cher","Add to wishlist":"Ajouter à la liste d’envies","Browse wishlist":"Parcourir la liste d’envies","The product is already in your wishlist!":"Le produit est déjà dans votre liste d’envies !","Product added!":"Produit ajouté !","Store Product Category":"Catégorie de produits","Contact Vendor":"Contacter le vendeur","Your Name":"Votre nom","you@example.com":"vous@example.com","Type your message...":"Tapez votre message...","Send Message":"Envoyer le message"}',
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

		// One-time repair: WP's options.php wp_unslash() strips the backslash
		// from \uXXXX JSON escapes before the sanitize callback runs, so
		// stored values could be corrupted (e.g. "Paramu00e8tres" instead of
		// "Paramètres"). Recover the intended characters as plain UTF-8.
		if ( ! get_option( 'cds_strings_repaired' ) ) {
			$option = $this->repair_corrupted_json( $option );
			update_option( CDS_SETTINGS_KEY, $option );
			update_option( 'cds_strings_repaired', 1 );
		}

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
	 * Repair custom string JSON whose hex escapes were corrupted by
	 * wp_unslash() (e.g. a stripped escape like "u00e8" is rebuilt as the
	 * intended "è" character).
	 *
	 * @param array $option Raw settings array.
	 *
	 * @return array
	 */
	private function repair_corrupted_json( $option ) {
		$keys = array( 'custom_dashboard_strings', 'custom_filter_strings', 'custom_store_page_strings' );

		foreach ( $keys as $key ) {
			if ( empty( $option[ $key ] ) || ! is_string( $option[ $key ] ) ) {
				continue;
			}

			$json = $option[ $key ];

			// Only touch values that show the corruption pattern: a uXXXX
			// hex sequence whose backslash was stripped off.
			if ( ! preg_match( '/(?<!\\\\)u[0-9a-f]{4}/i', $json ) ) {
				continue;
			}

			// Re-add the missing backslash, then decode and re-encode as
			// plain UTF-8 so future saves can't corrupt it again.
			$repaired = preg_replace_callback(
				'/(?<!\\\\)u([0-9a-f]{4})/i',
				function ( $m ) {
					return '\\u' . $m[1];
				},
				$json
			);

			$decoded = json_decode( $repaired, true );

			if ( is_array( $decoded ) ) {
				$option[ $key ] = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE );
			}
		}

		return $option;
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

	// Service vendors get service wording everywhere on their dashboard.
	if ( $this->is_service_vendor() ) {
		$service = $this->service_product_strings();

		if ( isset( $service[ $text ] ) ) {
			return $service[ $text ];
		}
	}

	$map = $this->resolve_map( $text );

	return $map !== null ? $map : $translation;
}

/**
 * Whether the current user is a service vendor on the front-end.
 *
 * @return bool
 */
private function is_service_vendor() {
	if ( is_admin() ) {
		return false;
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}

	return 'service' === cds_get_vendor_type( $user_id );
}

/**
 * Strings shown only to service vendors in the Dokan dashboard / product
 * form, where "product" wording must become "service" wording.
 *
 * @return array<string, string>
 */
private function service_product_strings() {
	$map = array(
		'Products' => 'Services',
		'Product'  => 'Service',
	);

	// Only relabel the product-form / dashboard strings when the legacy
	// dashboard overrides are enabled, so admins can opt out.
	if ( self::$settings !== null && ! (int) ( self::$settings['override_dashboard_old'] ?? 0 ) ) {
		return $map;
	}

	$map += array(
		'Add Product'                      => 'Ajouter un service',
		'Add New Product'                  => 'Ajouter un nouveau service',
		'Add new product'                  => 'Ajouter un nouveau service',
		'+ Add new product'                => '+ Ajouter un nouveau service',
		'Create Product'                   => 'Créer le service',
		'Create & Add New'                 => 'Créer et ajouter un nouveau service',
		'Edit Product'                     => 'Modifier le service',
		'Save Product'                     => 'Enregistrer le service',
		'View Product'                     => 'Voir le service',
		'View Product &rarr;'               => 'Voir le service &rarr;',
		'New Product'                      => 'Nouveau service',
		'No Products Found!'               => 'Aucun service trouvé !',
		'No product found'                 => 'Aucun service trouvé',
		'The product has been saved successfully.' => 'Le service a bien été enregistré.',
	);

	return $map;
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

		if ( $this->is_service_vendor() ) {
			$service = $this->service_product_strings();

			if ( isset( $service[ $text ] ) ) {
				return $service[ $text ];
			}
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
