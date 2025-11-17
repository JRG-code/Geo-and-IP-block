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

// Load Composer autoloader if available.
if ( file_exists( GEO_IP_BLOCKER_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize Plugin Update Checker.
if ( file_exists( GEO_IP_BLOCKER_PLUGIN_DIR . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

	$geo_ip_blocker_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/JRG-code/Geo-and-IP-block',
		__FILE__,
		'geo-ip-blocker'
	);

	// Set the branch to check for updates.
	$geo_ip_blocker_update_checker->setBranch( 'main' );

	// Optional: Set authentication if repository is private.
	// $geo_ip_blocker_update_checker->setAuthentication( 'your-github-token' );
}

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
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_database' ) );
		add_action( 'plugins_loaded', array( $this, 'check_version' ) );
		add_action( 'init', array( $this, 'check_requirements' ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/functions.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/country-regions.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-database.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-security.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-cache.php';
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'includes/class-rate-limiter.php';
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
	 * Maybe upgrade database schema.
	 *
	 * Checks if database needs upgrading and runs upgrade if necessary.
	 */
	public function maybe_upgrade_database() {
		// Only run in admin or during AJAX requests.
		if ( ! is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// Run upgrade.
		if ( $this->database ) {
			$this->database->upgrade_database();
		}
	}

	/**
	 * Check plugin version and run updates if necessary.
	 *
	 * Runs on plugins_loaded to ensure plugin is fully loaded.
	 */
	public function check_version() {
		$current_version = get_option( 'geo_ip_blocker_version', '0.0.0' );

		// If versions don't match, run updates.
		if ( version_compare( $current_version, GEO_IP_BLOCKER_VERSION, '<' ) ) {
			$this->run_updates( $current_version );
			update_option( 'geo_ip_blocker_version', GEO_IP_BLOCKER_VERSION );

			// Clear cache after update.
			if ( function_exists( 'geo_ip_blocker_get_cache' ) ) {
				$cache = geo_ip_blocker_get_cache();
				$cache->flush();
			}
		}
	}

	/**
	 * Run plugin updates for version changes.
	 *
	 * @param string $from_version Previous version.
	 */
	private function run_updates( $from_version ) {
		// Example: Update from 0.x to 1.0.0.
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			// Run any necessary data migrations for 1.0.0.
			$this->update_to_1_0_0();
		}

		// Future versions can add their update methods here.
		// Example:
		// if ( version_compare( $from_version, '1.1.0', '<' ) ) {
		//     $this->update_to_1_1_0();
		// }

		do_action( 'geo_ip_blocker_updated', $from_version, GEO_IP_BLOCKER_VERSION );
	}

	/**
	 * Update to version 1.0.0.
	 *
	 * Initial release - no migrations needed.
	 */
	private function update_to_1_0_0() {
		// Ensure database tables are up to date.
		if ( $this->database ) {
			$this->database->upgrade_database();
		}

		// Set activation timestamp if not set.
		if ( ! get_option( 'geo_ip_blocker_activated' ) ) {
			update_option( 'geo_ip_blocker_activated', time() );
		}
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
		$database->upgrade_database();

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
