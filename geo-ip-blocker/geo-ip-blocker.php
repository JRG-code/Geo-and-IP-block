<?php
/**
 * Plugin Name: Geo & IP Blocker for WooCommerce
 * Plugin URI: https://github.com/JRG-code/Geo-and-IP-block
 * Description: Bloqueie acesso por país, região ou IP
 * Version: 1.0.0
 * Author: JRG Code
 * Author URI: https://github.com/JRG-code
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * Text Domain: geo-ip-blocker
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'GEO_IP_BLOCKER_VERSION', '1.0.0' );
define( 'GEO_IP_BLOCKER_PLUGIN_FILE', __FILE__ );
define( 'GEO_IP_BLOCKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEO_IP_BLOCKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEO_IP_BLOCKER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class Class name to load.
 */
function geo_ip_blocker_autoloader( $class ) {
	// Check if the class uses our namespace.
	$prefix = 'GeoIPBlocker\\';
	$len    = strlen( $prefix );

	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $class, $len );

	// Convert namespace to file path.
	$file = GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/';

	// Convert class name to file name format.
	$class_file = 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
	$file      .= $class_file;

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}
spl_autoload_register( 'geo_ip_blocker_autoloader' );

/**
 * Main plugin class - Singleton pattern.
 */
final class Geo_IP_Blocker {

	/**
	 * Plugin instance.
	 *
	 * @var Geo_IP_Blocker
	 */
	private static $instance = null;

	/**
	 * Database handler instance.
	 *
	 * @var Geo_IP_Blocker_Database
	 */
	public $database;

	/**
	 * Admin handler instance.
	 *
	 * @var Geo_IP_Blocker_Admin
	 */
	public $admin;

	/**
	 * Get singleton instance.
	 *
	 * @return Geo_IP_Blocker
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
		$this->includes();
		$this->init();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		register_activation_hook( GEO_IP_BLOCKER_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( GEO_IP_BLOCKER_PLUGIN_FILE, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'check_requirements' ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/functions.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-database.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-geolocation.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-ip-manager.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-logger.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-geo-blocker.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-blocker.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-blocked-page.php';

		// WooCommerce integration.
		if ( class_exists( 'WooCommerce' ) ) {
			require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-woocommerce.php';
		}

		if ( is_admin() ) {
			require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/class-admin.php';
		}
	}

	/**
	 * Initialize components.
	 */
	private function init() {
		$this->database = new Geo_IP_Blocker_Database();

		if ( is_admin() ) {
			$this->admin = new Geo_IP_Blocker_Admin();
		} else {
			// Initialize blocker for frontend only.
			geo_ip_blocker_get_blocker();
		}

		// Initialize WooCommerce integration.
		if ( class_exists( 'WooCommerce' ) ) {
			new Geo_IP_Blocker_WooCommerce();
		}
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'geo-ip-blocker',
			false,
			dirname( GEO_IP_BLOCKER_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Check plugin requirements.
	 */
	public function check_requirements() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: WooCommerce download link */
					esc_html__( 'Geo & IP Blocker requires WooCommerce to be installed and active. You can download %s here.', 'geo-ip-blocker' ),
					'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * PHP version notice.
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: Required PHP version */
					esc_html__( 'Geo & IP Blocker requires PHP version %s or higher. Please update your PHP version.', 'geo-ip-blocker' ),
					'7.4'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Check requirements before activation.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( GEO_IP_BLOCKER_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Geo & IP Blocker requires PHP 7.4 or higher.', 'geo-ip-blocker' ),
				esc_html__( 'Plugin Activation Error', 'geo-ip-blocker' ),
				array( 'back_link' => true )
			);
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( GEO_IP_BLOCKER_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Geo & IP Blocker requires WooCommerce to be installed and active.', 'geo-ip-blocker' ),
				esc_html__( 'Plugin Activation Error', 'geo-ip-blocker' ),
				array( 'back_link' => true )
			);
		}

		// Create database tables.
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-database.php';
		$database = new Geo_IP_Blocker_Database();
		$database->create_tables();

		// Set default options.
		add_option( 'geo_ip_blocker_version', GEO_IP_BLOCKER_VERSION );
		add_option( 'geo_ip_blocker_activated', time() );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}

/**
 * Get main instance of Geo_IP_Blocker.
 *
 * @return Geo_IP_Blocker
 */
function geo_ip_blocker() {
	return Geo_IP_Blocker::instance();
}

// Initialize the plugin.
geo_ip_blocker();
