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
		add_action( 'wp_print_footer_scripts', array( $this, 'print_js_overrides' ), 1000 );
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
	 * Print small `wp.i18n` overrides on the vendor dashboard, after Dokan's
	 * own translation data, so the merged locale data wins for these keys.
	 *
	 * @return void
	 */
	public function print_js_overrides() {
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
		);

		$user_id = get_current_user_id();

		if ( $user_id && 'service' === cds_get_vendor_type( $user_id ) ) {
			$map['Products']                       = array( 'Services' );
			$map['Product']                        = array( 'Service' );
			$map['Add New Product']                = array( 'Ajouter un nouveau service' );
			$map['Update Product']                 = array( 'Mettre à jour le service' );
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
		}

		$js = 'wp.i18n && wp.i18n.setLocaleData(' . wp_json_encode( $map ) . ', "dokan-lite");';
		$js .= 'wp.i18n && wp.i18n.setLocaleData({"No data found":["Aucune donnée trouvée"]}, "woocommerce");';
		wp_print_inline_script_tag( $js );
	}
}
