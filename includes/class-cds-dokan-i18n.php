<?php
/**
 * Dokan French translation installer + JS overrides.
 *
 * Bundles the official Dokan Lite French translations (.mo/.po for PHP
 * strings and JED .json files for the React dashboard) inside the plugin so
 * they are never lost, and exposes an admin button to (re)install them into
 * wp-content/languages/plugins. Also applies small `wp.i18n` overrides on the
 * vendor dashboard for strings the official French translation leaves
 * untranslated (lowercase "tout", withdraw/orders/charts labels, and
 * "Products" -> "Services" for service vendors).
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Dokan_I18n {

	/**
	 * Files bundled in /languages/dokan and installed into the languages dir.
	 *
	 * @var string[]
	 */
	private static function bundled_files() {
		return array(
			'dokan-lite-fr_FR.mo',
			'dokan-lite-fr_FR.po',
			// dokan-vendor-dashboard -> assets/js/vendor-dashboard/layout/index.js
			'dokan-lite-fr_FR-77bfd47a4a4a61d39981c82e6b84f2b2.json',
			// dokan-react-frontend -> assets/js/frontend.js
			'dokan-lite-fr_FR-9b50bfb64bd2792ebdca1ffb3c249a70.json',
			// vendor_analytics_script -> assets/js/vendor-dashboard/reports/index.js
			'dokan-lite-fr_FR-01fcff6242b7adf9cd40845c482957f6.json',
		);
	}

	/**
	 * Hook admin handlers and footer overrides.
	 */
	public function __construct() {
		add_action( 'admin_post_cds_install_dokan_translations', array( $this, 'handle_install' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_js_overrides' ), 100 );
	}

	/**
	 * Directory where the bundled translation files live.
	 *
	 * @return string
	 */
	public static function source_dir() {
		return CDS_PLUGIN_DIR . 'languages/dokan';
	}

	/**
	 * Directory where translations are installed.
	 *
	 * @return string
	 */
	public static function target_dir() {
		return WP_LANG_DIR . '/plugins';
	}

	/**
	 * Whether Dokan (Lite/Pro) is present.
	 *
	 * @return bool
	 */
	public static function is_dokan_active() {
		return class_exists( 'WeDevs\Dokan\Vendor\Vendor' ) || defined( 'DOKAN_PLUGIN_VERSION' );
	}

	/**
	 * Copy the bundled translation files into the WordPress languages dir.
	 *
	 * Best-effort; never throws.
	 *
	 * @return array{installed:string[],failed:string[]}
	 */
	public static function install_translations() {
		$result = array(
			'installed' => array(),
			'failed'    => array(),
		);

		if ( ! self::is_dokan_active() ) {
			$result['failed'][] = 'dokan';

			return $result;
		}

		$src = self::source_dir();
		$dst = self::target_dir();

		if ( ! wp_mkdir_p( $dst ) || ! is_dir( $dst ) ) {
			$result['failed'][] = 'dir';

			return $result;
		}

		foreach ( self::bundled_files() as $file ) {
			$from = $src . '/' . $file;
			$to   = $dst . '/' . $file;

			if ( ! file_exists( $from ) ) {
				$result['failed'][] = $file;

				continue;
			}

			if ( @copy( $from, $to ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$result['installed'][] = $file;
			} else {
				$result['failed'][] = $file;
			}
		}

		return $result;
	}

	/**
	 * Bundled files currently present in the target languages dir.
	 *
	 * @return string[]
	 */
	public static function installed_files() {
		$present = array();

		foreach ( self::bundled_files() as $file ) {
			if ( file_exists( self::target_dir() . '/' . $file ) ) {
				$present[] = $file;
			}
		}

		return $present;
	}

	/**
	 * Whether all bundled files are installed.
	 *
	 * @return bool
	 */
	public static function all_installed() {
		return count( self::installed_files() ) === count( self::bundled_files() );
	}

	/**
	 * Path to the bundled French translations for the @wordpress/components
	 * ("default" domain) strings used by the dashboard bundles.
	 *
	 * Extracted from the WordPress core fr_FR package files shipped in
	 * wp-content/languages/, so the wording matches the admin.
	 *
	 * @return string
	 */
	public static function wp_core_default_file() {
		return CDS_PLUGIN_DIR . 'languages/wp-core-fr-default.json';
	}

	/**
	 * The "default"-domain (wp.i18n / @wordpress/components) overrides.
	 *
	 * The core-fr translations bundled in wp-core-fr-default.json plus a few
	 * Dokan-specific default-domain strings core does not ship.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function default_domain_overrides() {
		$map = array(
			'No data found'                                          => array( 'Aucune donnée trouvée' ),
			'Notifications'                                          => array( 'Notifications' ),
			'Actions'                                                => array( 'Actions' ),
			'Conditions'                                             => array( 'Conditions' ),
			'Date'                                                   => array( 'Date' ),
			'Min.'                                                   => array( 'Min.' ),
			'Max.'                                                   => array( 'Max.' ),
			'Remove filter'                                          => array( 'Supprimer le filtre' ),
			'Add Filter'                                             => array( 'Ajouter un filtre' ),
			'Scroll tabs left'                                       => array( 'Faire défiler les onglets vers la gauche' ),
			'Scroll tabs right'                                      => array( 'Faire défiler les onglets vers la droite' ),
			'Are you sure? This action cannot be undone.'            => array( 'Êtes-vous sûr ? Cette action est irréversible.' ),
			'Use this media'                                         => array( 'Utiliser ce média' ),
			'Is any'                                                 => array( 'Est n\'importe lequel' ),
			'Is none'                                                => array( 'N\'est aucun' ),
			'Is all'                                                 => array( 'Est tous' ),
			'Is not all'                                             => array( 'N\'est pas tous' ),
			'<Name>%1$s is any: </Name><Value>%2$s</Value>'          => array( '<Name>%1$s est n\'importe lequel : </Name><Value>%2$s</Value>' ),
			'<Name>%1$s is none: </Name><Value>%2$s</Value>'         => array( '<Name>%1$s n\'est aucun : </Name><Value>%2$s</Value>' ),
			'<Name>%1$s is all: </Name><Value>%2$s</Value>'          => array( '<Name>%1$s est tous : </Name><Value>%2$s</Value>' ),
			'<Name>%1$s is not all: </Name><Value>%2$s</Value>'      => array( '<Name>%1$s n\'est pas tous : </Name><Value>%2$s</Value>' ),
			'<Name>%1$s is over: </Name><Value>%2$s</Value> ago'     => array( '<Name>%1$s est supérieur à : </Name><Value>%2$s</Value> il y a' ),
			'Valid'                                                  => array( 'Valide' ),
		);

		$file = self::wp_core_default_file();

		if ( file_exists( $file ) ) {
			$core = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( is_array( $core ) ) {
				$map = array_merge( $core, $map );
			}
		}

		return $map;
	}

	/**
	 * Render the install status + button (settings field).
	 *
	 * @return void
	 */
	public static function render_status() {
		if ( ! self::is_dokan_active() ) {
			echo '<p class="description">' . esc_html__( 'Dokan is not active. Install and activate Dokan first, then this button will install the French translations.', 'camalg-services' ) . '</p>';

			return;
		}

		$installed = self::installed_files();
		$missing   = array_diff( self::bundled_files(), $installed );

		echo '<p class="description">' . esc_html__( 'Installs the official Dokan French translation (.mo + .po for PHP strings, JSON for the React dashboard) into wp-content/languages/plugins/.', 'camalg-services' ) . '</p>';

		echo '<ul style="margin:8px 0 0 18px;list-style:disc">';
		foreach ( self::bundled_files() as $file ) {
			$ok = in_array( $file, $installed, true );
			printf(
				'<li>%1$s <span style="color:%2$s">%3$s</span></li>',
				esc_html( $file ),
				$ok ? '#00a32a' : '#d63638',
				$ok ? esc_html__( 'installed', 'camalg-services' ) : esc_html__( 'missing', 'camalg-services' )
			);
		}
		echo '</ul>';

		if ( ! empty( $missing ) ) {
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
				<input type="hidden" name="action" value="cds_install_dokan_translations" />
				<?php wp_nonce_field( 'cds_install_dokan_translations' ); ?>
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Install Dokan French translation', 'camalg-services' ); ?></button>
			</form>
			<?php
		}
	}

	/**
	 * Handle the install form POST (admin-post.php).
	 *
	 * @return void
	 */
	public function handle_install() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'camalg-services' ) );
		}

		check_admin_referer( 'cds_install_dokan_translations' );

		$result = self::install_translations();
		$notice = empty( $result['failed'] ) ? 'cds_dokan_i18n_ok' : 'cds_dokan_i18n_failed';

		wp_safe_redirect( add_query_arg( array( 'page' => 'camalg-services', 'cds_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Show a one-time notice after (re)installing the translations.
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( ! isset( $_GET['page'] ) || 'camalg-services' !== $_GET['page'] || empty( $_GET['cds_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$notice = sanitize_key( wp_unslash( $_GET['cds_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'cds_dokan_i18n_ok' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Dokan French translation installed successfully.', 'camalg-services' ) . '</p></div>';
		} elseif ( 'cds_dokan_i18n_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Some Dokan translation files could not be installed. Check that wp-content/languages/plugins/ is writable.', 'camalg-services' ) . '</p></div>';
		}
	}

	/**
	 * Register small `wp.i18n` overrides as inline-before scripts on Dokan's
	 * React dashboard bundles.
	 *
	 * WordPress prints a script's translations inline first, then its
	 * inline-before scripts, then the bundle itself. Adding the overrides as
	 * inline-before therefore runs them after the official French JED data and
	 * before the bundle executes, so the merged locale data wins even for
	 * strings read once at module load time (e.g. page/route titles).
	 *
	 * @return void
	 */
	public function register_js_overrides() {
		if ( ! self::is_dokan_active() || ! function_exists( 'dokan_is_seller_dashboard' ) || ! dokan_is_seller_dashboard() ) {
			return;
		}

		$map = array(
			// Layout / navigation.
			'All'                                                    => array( 'Tout' ),
			'Net Sales'                                              => array( 'Ventes nettes' ),
			'Net sales'                                              => array( 'Ventes nettes' ),
			'Charts'                                                 => array( 'Graphiques' ),
			'Commissions'                                            => array( 'Commissions' ),
			'Your Store'                                             => array( 'Votre boutique' ),
			'Reports'                                                => array( 'Rapports' ),
			'Store Image'                                            => array( 'Image du magasin' ),
			'Vendor Dashboard Logo'                                  => array( 'Logo du tableau de bord vendeur' ),
			'User Profile Image'                                     => array( 'Image du profil utilisateur' ),
			'Not allowed'                                            => array( 'Non autorisé' ),
			'Sorry, you are not allowed to access this page.'        => array( 'Désolé, vous n\'êtes pas autorisé à accéder à cette page.' ),
			'Kindly refresh the page to load data or try again.'     => array( 'Veuillez actualiser la page pour charger les données ou réessayer.' ),
			'Try Again'                                              => array( 'Réessayer' ),

			// Overview / withdraw.
			'Your Balance:'                                          => array( 'Votre solde :' ),
			'Balance:'                                               => array( 'Solde :' ),
			'Your Earn:'                                             => array( 'Vos gains :' ),
			'Payable Amount:'                                        => array( 'Montant à payer :' ),
			'Reverse Pay Balance:'                                   => array( 'Solde de paiement inversé :' ),
			'Reverse Withdrawal Balance'                             => array( 'Solde de retrait inversé' ),
			'Reverse Withdrawal Payment'                             => array( 'Paiement de retrait inversé' ),
			'Reverse withdrawal payment data is not available. Please reload the page.' => array( 'Les données du paiement de retrait inversé ne sont pas disponibles. Veuillez recharger la page.' ),
			'Minimum Withdraw Amount: '                              => array( 'Montant minimum de retrait : ' ),
			'Withdraw method'                                        => array( 'Méthode de retrait' ),
			'Withdraw charge'                                        => array( 'Frais de retrait' ),
			'Threshold:'                                             => array( 'Seuil :' ),
			'Payment Methods'                                        => array( 'Méthodes de paiement' ),
			'No payment methods found to submit a withdrawal request.' => array( 'Aucun moyen de paiement trouvé pour soumettre une demande de retrait.' ),
			'You do not have any approved withdraw yet.'             => array( 'Vous n\'avez pas encore de retrait approuvé.' ),
			"You don't have sufficient balance for a withdraw request!" => array( 'Vous n\'avez pas assez de solde pour effectuer un retrait !' ),
			'Cancel Withdraw'                                        => array( 'Annuler le retrait' ),
			'Withdraw request created.'                              => array( 'Demande de retrait créée.' ),
			'Failed to create withdraw.'                             => array( 'Échec de la création du retrait.' ),
			'Failed to fetch withdraw requests'                      => array( 'Échec du chargement des demandes de retrait' ),
			'Request cancelled successfully'                         => array( 'Demande annulée avec succès' ),
			'Failed to cancel request'                               => array( 'Échec de l\'annulation de la demande' ),

			// Orders.
			'No Order Yet'                                           => array( 'Aucune commande' ),
			'All your orders will be listed here'                    => array( 'Toutes vos commandes seront listées ici' ),
			'Date Range'                                             => array( 'Période' ),
			'Date created'                                           => array( 'Date de création' ),
			'Filter by Customer'                                     => array( 'Filtrer par client' ),
			'Failed to load orders'                                  => array( 'Échec du chargement des commandes' ),
			'Order status updated'                                   => array( 'Statut de la commande mis à jour' ),
			'Orders status updated'                                  => array( 'Statuts des commandes mis à jour' ),
			'Failed to update order status'                          => array( 'Échec de la mise à jour du statut de la commande' ),
			'Delivered'                                              => array( 'Livré' ),
			'Not-Delivered'                                          => array( 'Non livré' ),
			'Partially Delivered'                                    => array( 'Partiellement livré' ),
			'Received'                                               => array( 'Reçu' ),
			'Quick view'                                             => array( 'Aperçu rapide' ),
			'View in site'                                           => array( 'Voir sur le site' ),
			'Visit Product'                                          => array( 'Voir le produit' ),

			// Products / services.
			'Create & Continue'                                      => array( 'Créer et continuer' ),
			'Draft product created.'                                 => array( 'Brouillon du produit créé.' ),
			'Failed to create product.'                              => array( 'Échec de la création du produit.' ),
			'Failed to load the product form.'                       => array( 'Échec du chargement du formulaire produit.' ),
			'Failed to load product data'                            => array( 'Échec du chargement des données du produit' ),
			'Product saved successfully.'                            => array( 'Produit enregistré avec succès.' ),
			'Product published successfully.'                        => array( 'Produit publié avec succès.' ),
			'Product deleted successfully.'                          => array( 'Produit supprimé avec succès.' ),
			'Products deleted successfully.'                         => array( 'Produits supprimés avec succès.' ),
			'Products published successfully.'                       => array( 'Produits publiés avec succès.' ),
			'Error saving product.'                                  => array( 'Erreur lors de l\'enregistrement du produit.' ),
			'Failed to publish product.'                             => array( 'Échec de la publication du produit.' ),
			'Failed to publish products.'                            => array( 'Échec de la publication des produits.' ),
			'Failed to delete product.'                              => array( 'Échec de la suppression du produit.' ),
			'Failed to delete products.'                             => array( 'Échec de la suppression des produits.' ),
			'Publish Products'                                       => array( 'Publier les produits' ),
			'Edit details'                                           => array( 'Modifier les détails' ),
			'No data found'                                          => array( 'Aucune donnée trouvée' ),
			'Add new product'                                        => array( 'Ajouter un nouveau produit' ),
			'Save Changes'                                           => array( 'Enregistrer les modifications' ),
			'Save Attributes'                                        => array( 'Enregistrer les attributs' ),
			'Add New Term'                                           => array( 'Ajouter un nouveau terme' ),
			'Select all'                                             => array( 'Tout sélectionner' ),
			'Select none'                                            => array( 'Tout désélectionner' ),

			// Analytics / charts.
			'By day'                                                 => array( 'Par jour' ),
			'By week'                                                => array( 'Par semaine' ),
			'By month'                                               => array( 'Par mois' ),
			'By quarter'                                             => array( 'Par trimestre' ),
			'By year'                                                => array( 'Par an' ),
			'Total sales'                                            => array( 'Total des ventes' ),
			'Gross sales'                                            => array( 'Ventes brutes' ),
			'Gross discounted'                                       => array( 'Brut actualisé' ),
			'Orders'                                                 => array( 'Commandes' ),
			'Items sold'                                             => array( 'Articles vendus' ),
			'Average order value'                                    => array( 'Valeur moyenne des commandes' ),
			'Average items per order'                                => array( 'Articles moyens par commande' ),
			'Returns'                                                => array( 'Retours' ),
			'Discounted orders'                                      => array( 'Commandes avec remise' ),
			'Order tax'                                              => array( 'Taxe de la commande' ),
			'Shipping tax'                                           => array( 'Taxe d\'expédition' ),
			'Total tax'                                              => array( 'Taxe totale' ),
			'Fully refunded'                                         => array( 'Remboursement total' ),
			'Partially refunded'                                     => array( 'Remboursement partiel' ),
			'Full refunds are not deducted from tax or net sales totals' => array( 'Les remboursements complets ne sont pas déduits des taxes ni du total des ventes nettes' ),
			'No data for the current search'                         => array( 'Aucune donnée pour la recherche actuelle' ),
			'No data for the selected date range'                    => array( 'Aucune donnée pour la plage de dates sélectionnée' ),
			'Customer type'                                          => array( 'Type de client' ),
			'Coupon code'                                            => array( 'Code promo' ),
			'New'                                                    => array( 'Nouveau' ),
			'Returning'                                              => array( 'Retour' ),
			'None'                                                   => array( 'Aucun' ),
			'Compare'                                                => array( 'Comparer' ),
			'Show'                                                   => array( 'Afficher' ),

			// Customizable dashboard blocks.
			'Performance'                                            => array( 'Performances' ),
			'Reload'                                                 => array( 'Recharger' ),
			'Add %s section'                                         => array( 'Ajouter une section %s' ),
			'Add more sections'                                      => array( 'Ajouter plus de sections' ),
			'Dashboard Sections'                                     => array( 'Sections du tableau de bord' ),
			'Move up'                                                => array( 'Monter' ),
			'Move down'                                              => array( 'Descendre' ),
			'Remove section'                                         => array( 'Supprimer la section' ),
			'Remove block'                                           => array( 'Supprimer le bloc' ),
			'Section title'                                          => array( 'Titre de la section' ),
			'No data recorded for the selected time period.'         => array( 'Aucune donnée enregistrée pour la période sélectionnée.' ),
			'There was an error getting your stats. Please try again.' => array( 'Une erreur est survenue lors de la récupération de vos statistiques. Veuillez réessayer.' ),

			// Notifications / updater alert.
			'No Information Available'                               => array( 'Aucune information disponible' ),
			'Information needs to be updated'                        => array( 'Les informations doivent être mises à jour' ),
			'Actions'                                                => array( 'Actions' ),
			'No results'                                             => array( 'Aucun résultat' ),
			'Dismiss'                                                => array( 'Fermer' ),
			'Dismissing…'                                            => array( 'Fermeture…' ),
			'Dismissed'                                              => array( 'Fermée' ),
			'Loading…'                                               => array( 'Chargement…' ),
			'Previous notice'                                        => array( 'Avis précédent' ),
			'Next notice'                                            => array( 'Avis suivant' ),
			'Updater Alert!'                                         => array( 'Alerte de mise à jour !' ),
			'%1$s of %2$s'                                           => array( '%1$s sur %2$s' ),

			// Confirmation / error dialogs.
			'Confirmation Dialog'                                    => array( 'Boîte de dialogue de confirmation' ),
			'Delete Confirmation'                                    => array( 'Confirmation de suppression' ),
			'Are you sure you want to proceed with this %1$sdeletion%2$s?' => array( 'Êtes-vous sûr de vouloir procéder à cette %1$ssuppression%2$s ?' ),
			'Yes, Delete'                                            => array( 'Oui, supprimer' ),
			'Oh no! Something went wrong…'                           => array( 'Oh non ! Une erreur est survenue…' ),
			'Confirm'                                                => array( 'Confirmer' ),
			'An unknown error occurred'                              => array( 'Une erreur inconnue s\'est produite' ),
			'Unexpected server response. Please reload the page and try again.' => array( 'Réponse inattendue du serveur. Veuillez recharger la page et réessayer.' ),
			'Failed to add to cart'                                  => array( 'Échec de l\'ajout au panier' ),
			'Submit'                                                 => array( 'Envoyer' ),

			// Media modal.
			'Select or Upload Media'                                 => array( 'Sélectionner ou téléverser un média' ),
			'Use this media'                                         => array( 'Utiliser ce média' ),
			'Upload'                                                 => array( 'Téléverser' ),
			'Choose'                                                 => array( 'Choisir' ),
			'Enter URL or select file'                               => array( 'Saisir l\'URL ou sélectionner un fichier' ),

			// Date / date range.
			'Enter Date'                                             => array( 'Saisir la date' ),
			'Ok'                                                     => array( 'OK' ),
			'Date:'                                                  => array( 'Date :' ),
			'Date'                                                   => array( 'Date' ),
			'Select date'                                            => array( 'Sélectionner une date' ),
			'on'                                                     => array( 'le' ),
			'to'                                                     => array( 'au' ),
			'%1$s at %2$s'                                           => array( '%1$s à %2$s' ),
			'%1$s - %2$s'                                            => array( '%1$s - %2$s' ),
			'%s - %s'                                                => array( '%s - %s' ),
			'%1$s %2$s'                                              => array( '%1$s %2$s' ),
			'(%1$s)'                                                 => array( '(%1$s)' ),

			// Filters / search combos.
			'Remove filter'                                          => array( 'Supprimer le filtre' ),
			'Add Filter'                                             => array( 'Ajouter un filtre' ),
			'Please type 3 or more characters'                       => array( 'Veuillez saisir 3 caractères ou plus' ),
			'No options'                                             => array( 'Aucune option' ),
			'Coupon #%s'                                             => array( 'Code promo n° %s' ),
			'Order #%s'                                              => array( 'Commande n° %s' ),
			'Customer #%s'                                           => array( 'Client n° %s' ),
			'(no title) #%s'                                         => array( '(sans titre) n° %s' ),
			'(no name) #%s'                                          => array( '(sans nom) n° %s' ),
			'#%s'                                                    => array( 'n° %s' ),
			'All Category'                                           => array( 'Toutes les catégories' ),
			'All Categories'                                         => array( 'Toutes les catégories' ),
			'All categories'                                         => array( 'Toutes les catégories' ),
			'All types'                                              => array( 'Tous les types' ),
			'%'                                                      => array( '%' ),
			'+'                                                      => array( '+' ),
			'-'                                                      => array( '-' ),

			// Error / permission pages.
			'Sorry, the page can’t be found'                         => array( 'Désolé, la page est introuvable' ),
			'The page you were looking for appears to have been moved, deleted or does not exist' => array( 'La page que vous recherchiez semble avoir été déplacée, supprimée ou ne pas exister' ),
			'Permission Denied'                                      => array( 'Accès refusé' ),
			'Sorry, you don’t have permission to access this page'   => array( 'Désolé, vous n\'avez pas l\'autorisation d\'accéder à cette page' ),

			// Withdraw / payments.
			'Enter amount'                                           => array( 'Saisir le montant' ),
			'Calculating…'                                           => array( 'Calcul…' ),
			'Creating…'                                              => array( 'Création…' ),
			'Please set up your %1$spayment methods%2$s first.'      => array( 'Veuillez d\'abord configurer vos %1$sméthodes de paiement%2$s.' ),
			'Submit request'                                         => array( 'Envoyer la demande' ),
			'Note'                                                   => array( 'Note' ),
			'Default method updated'                                 => array( 'Méthode par défaut mise à jour' ),
			'Failed to process payment'                              => array( 'Échec du traitement du paiement' ),

			// Form validation / attributes.
			'This field is invalid.'                                 => array( 'Ce champ est invalide.' ),
			'Please fill out this field.'                            => array( 'Veuillez remplir ce champ.' ),
			'Value must be one of the elements.'                     => array( 'La valeur doit être l\'un des éléments.' ),
			'(REQUIRED)'                                             => array( '(OBLIGATOIRE)' ),
			'New Attribute'                                          => array( 'Nouvel attribut' ),
			'Remove'                                                 => array( 'Supprimer' ),
			'e.g. Color or Size'                                     => array( 'ex. Couleur ou Taille' ),
			'Value(s)'                                               => array( 'Valeur(s)' ),
			'Select terms'                                           => array( 'Sélectionner les termes' ),
			'Term Name'                                              => array( 'Nom du terme' ),
			'Enter term name'                                        => array( 'Saisir le nom du terme' ),
			'Enter values'                                           => array( 'Saisir les valeurs' ),
			'Visible on the product page'                            => array( 'Visible sur la page du produit' ),
			'Used for variations'                                    => array( 'Utilisé pour les variations' ),
			'Custom Attribute'                                       => array( 'Attribut personnalisé' ),
			'Add existing attribute or custom'                       => array( 'Ajouter un attribut existant ou personnalisé' ),
			'Default Form Values'                                    => array( 'Valeurs de formulaire par défaut' ),
			'Enter name'                                             => array( 'Saisir le nom' ),

			// AI Assistant.
			'No content generated, please try again.'                => array( 'Aucun contenu généré, veuillez réessayer.' ),
			'Please enter a prompt.'                                 => array( 'Veuillez saisir un prompt.' ),
			'AI Assistant'                                           => array( 'Assistant IA' ),
			'Craft your product information'                         => array( 'Rédigez les informations de votre produit' ),
			'Start Over'                                             => array( 'Recommencer' ),
			'Refine'                                                 => array( 'Affiner' ),
			'Short Description:'                                     => array( 'Description courte :' ),
			'Long Description:'                                      => array( 'Description longue :' ),
			'** If you think the outcome doesn’t match your choice then you can' => array( '** Si vous pensez que le résultat ne correspond pas à votre choix, vous pouvez' ),
			'regenerate all again.'                                  => array( 'tout régénérer.' ),
			'You can generate your product title, short description, long description all at once with this prompt. Type your prompt below' => array( 'Vous pouvez générer le titre, la description courte et la description longue de votre produit en une seule fois avec ce prompt. Saisissez votre prompt ci-dessous' ),
			'Enter prompt'                                           => array( 'Saisir le prompt' ),
			'Insert'                                                 => array( 'Insérer' ),
			'Generating…'                                            => array( 'Génération…' ),
			'Generate'                                               => array( 'Générer' ),
			'Insert Generated Information?'                          => array( 'Insérer les informations générées ?' ),
			'Are you sure you want to insert the generated information? If you insert then your current product information will be updated with the generated content.' => array( 'Êtes-vous sûr de vouloir insérer les informations générées ? Si vous insérez, les informations actuelles de votre produit seront mises à jour avec le contenu généré.' ),
			'Yes, Insert'                                            => array( 'Oui, insérer' ),

			// Product info / type / stock.
			'SKU:'                                                   => array( 'SKU :' ),
			'N/A'                                                    => array( 'N/A' ),
			'Product info:'                                          => array( 'Informations sur le produit :' ),
			'Type'                                                   => array( 'Type' ),
			'Variable'                                               => array( 'Variable' ),
			'Simple'                                                 => array( 'Simple' ),
			'Stock'                                                  => array( 'Stock' ),

			// Misc.
			'Dokan'                                                  => array( 'Dokan' ),
			'Mutable settings should be accessed via data store.'    => array( 'Les paramètres mutables doivent être consultés via le magasin de données.' ),
			'%1$s &lsaquo; %2$s &#8212; Dokan'                       => array( '%1$s &lsaquo; %2$s &#8212; Dokan' ),
		);

		$user_id = get_current_user_id();

		if ( $user_id && 'service' === cds_get_vendor_type( $user_id ) ) {
			$map['Products']                       = array( 'Services' );
			$map['Product']                        = array( 'Service' );
			$map['Add New Product']                = array( 'Ajouter un nouveau service' );
			$map['Add new product']                = array( 'Ajouter un nouveau service' );
			$map['Update Product']                 = array( 'Mettre à jour le service' );
			$map['Save Changes']                   = array( 'Enregistrer les modifications' );
			$map['Save Attributes']                = array( 'Enregistrer les attributs' );
			$map['Publish Product']                = array( 'Publier le service' );
			$map['Create & Continue']              = array( 'Créer le service et continuer' );
			$map['Edit Product']                   = array( 'Modifier le service' );
			$map['Draft product created.']         = array( 'Brouillon du service créé.' );
			$map['Failed to create product.']      = array( 'Échec de la création du service.' );
			$map['Failed to load the product form.'] = array( 'Échec du chargement du formulaire du service.' );
			$map['Failed to load product data']    = array( 'Échec du chargement des données du service' );
			$map['Product saved successfully.']    = array( 'Service enregistré avec succès.' );
			$map['Product published successfully.'] = array( 'Service publié avec succès.' );
			$map['Product deleted successfully.']  = array( 'Service supprimé avec succès.' );
			$map['Error saving product.']          = array( 'Erreur lors de l\'enregistrement du service.' );
			$map['Failed to publish product.']     = array( 'Échec de la publication du service.' );
			$map['Publish Products']               = array( 'Publier les services' );
			$map['Failed to delete product.']      = array( 'Échec de la suppression du service.' );
			$map['Product info:']                  = array( 'Informations sur le service :' );
			$map['Visible on the product page']    = array( 'Visible sur la page du service' );
			$map['Craft your product information'] = array( 'Rédigez les informations de votre service' );
			$map['You can generate your product title, short description, long description all at once with this prompt. Type your prompt below'] = array( 'Vous pouvez générer le titre, la description courte et la description longue de votre service en une seule fois avec ce prompt. Saisissez votre prompt ci-dessous' );
			$map['Are you sure you want to insert the generated information? If you insert then your current product information will be updated with the generated content.'] = array( 'Êtes-vous sûr de vouloir insérer les informations générées ? Si vous insérez, les informations actuelles de votre service seront mises à jour avec le contenu généré.' );
		}

		$dokan_map = array(
			'Please enter a prompt.'                                  => array( 'Veuillez saisir un prompt.' ),
			'Invalid input data'                                      => array( 'Données d\'entrée invalides' ),
			'SKU: %s'                                                 => array( 'SKU : %s' ),
			'You have reached your subscription product limit.'       => array( 'Vous avez atteint la limite de votre abonnement en matière de produits.' ),
			'%1$d product(s) published. %2$d product(s) could not be published due to your subscription limit.' => array( '%1$d produit(s) publié(s). %2$d produit(s) n\'ont pas pu être publiés en raison de votre limite d\'abonnement.' ),
		);

		if ( $user_id && 'service' === cds_get_vendor_type( $user_id ) ) {
			$dokan_map['You have reached your subscription product limit.'] = array( 'Vous avez atteint la limite de votre abonnement en matière de services.' );
			$dokan_map['%1$d product(s) published. %2$d product(s) could not be published due to your subscription limit.'] = array( '%1$d service(s) publié(s). %2$d service(s) n\'ont pas pu être publiés en raison de votre limite d\'abonnement.' );
		}

		$js  = 'wp.i18n && wp.i18n.setLocaleData(' . wp_json_encode( $map ) . ', "dokan-lite");';
		$js .= 'wp.i18n && wp.i18n.setLocaleData(' . wp_json_encode( self::default_domain_overrides() ) . ', "default");';
		$js .= 'wp.i18n && wp.i18n.setLocaleData(' . wp_json_encode( $dokan_map ) . ', "dokan");';
		$js .= 'wp.i18n && wp.i18n.setLocaleData({"No data found":["Aucune donnée trouvée"]}, "woocommerce");';

		foreach ( array( 'dokan-vendor-dashboard', 'dokan-react-frontend', 'dokan-react-components', 'dokan-plugin-ui', 'dokan-utilities', 'dokan-product-editor-utils', 'vendor_analytics_script' ) as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				wp_add_inline_script( $handle, $js, 'before' );
			}
		}
	}
}
