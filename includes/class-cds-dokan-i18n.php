<?php
/**
 * Dokan French translation installer + JS overrides.
 *
 * Bundles the official Dokan Lite French translations (.mo/.po for PHP
 * strings and JED .json files for the React dashboard) inside the plugin so
 * they are never lost, and exposes an admin button to (re)install them into
 * wp-content/languages/plugins. Also applies small `wp.i18n` overrides on the
 * vendor dashboard (fix lowercase "tout", and "Products" -> "Services" for
 * service vendors).
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
			'All'       => array( 'Tout' ),
			'Net Sales' => array( 'Ventes nettes' ),
			'Net sales' => array( 'Ventes nettes' ),
			'Charts'    => array( 'Graphiques' ),
		);

		$user_id = get_current_user_id();

		if ( $user_id && 'service' === cds_get_vendor_type( $user_id ) ) {
			$map['Products'] = array( 'Services' );
			$map['Product']  = array( 'Service' );
		}

		$js = 'wp.i18n && wp.i18n.setLocaleData(' . wp_json_encode( $map ) . ', "dokan-lite");';
		wp_print_inline_script_tag( $js );
	}
}
