<?php
/**
 * Main plugin class. Bootstraps modules and checks dependencies.
 *
 * @package Camalg_Dokan_Services
 */

defined( 'ABSPATH' ) || exit;

final class CDS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var CDS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Module instances.
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return CDS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor: loads modules.
	 */
	private function __construct() {
		$this->load_textdomain();

		if ( ! $this->dependencies_ok() ) {
			add_action( 'admin_notices', array( $this, 'missing_dependencies_notice' ) );

			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );

		$this->init_modules();
	}

	/**
	 * Register front-end assets (enqueued only where needed).
	 *
	 * @return void
	 */
	public function register_frontend_assets() {
		wp_register_style( 'cds-frontend', CDS_PLUGIN_URL . 'assets/css/cds-frontend.css', array(), CDS_VERSION );
	}

	/**
	 * Load plugin translations (Loco Translate / Polylang / WPML).
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'camalg-services', false, dirname( plugin_basename( CDS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * WooCommerce and Dokan are required.
	 *
	 * @return bool
	 */
	private function dependencies_ok() {
		return class_exists( 'WooCommerce' ) && ( class_exists( 'WeDevs\Dokan\Vendor\Vendor' ) || defined( 'DOKAN_PLUGIN_VERSION' ) );
	}

	/**
	 * Admin notice when dependencies are missing.
	 *
	 * @return void
	 */
	public function missing_dependencies_notice() {
		/* translators: %s: plugin name. */
		$message = sprintf( __( '%s requires WooCommerce and Dokan to be active.', 'camalg-services' ), '<strong>NS Dokan Services</strong>' );

		echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Instantiate all feature modules.
	 *
	 * @return void
	 */
	private function init_modules() {
		$classes = array(
			'CDS_Settings'        => 'class-cds-settings.php',
			'CDS_Registration'    => 'class-cds-registration.php',
			'CDS_Listing'         => 'class-cds-listing.php',
			'CDS_Vendor_Listing'  => 'class-cds-vendor-listing.php',
			'CDS_Single'          => 'class-cds-single.php',
			'CDS_Store_Page'      => 'class-cds-store-page.php',
			'CDS_Text_Overrides'  => 'class-cds-text-overrides.php',
		);

		foreach ( $classes as $class => $file ) {
			if ( ! class_exists( $class ) ) {
				require_once CDS_PLUGIN_DIR . 'includes/' . $file;
			}

			$this->modules[ $class ] = new $class();
		}
	}

	/**
	 * Get a loaded module instance.
	 *
	 * @param string $class Module class name.
	 *
	 * @return object|null
	 */
	public function module( $class ) {
		return isset( $this->modules[ $class ] ) ? $this->modules[ $class ] : null;
	}
}
