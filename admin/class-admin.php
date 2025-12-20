<?php
/**
 * Admin handler class.
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Admin
 *
 * Handles admin interface and settings.
 */
class Geo_IP_Blocker_Admin {

	/**
	 * Settings page instance.
	 *
	 * @var Geo_IP_Blocker_Settings_Page
	 */
	private $settings_page;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		require_once GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/class-settings-page.php';
		$this->settings_page = new Geo_IP_Blocker_Settings_Page();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . GEO_IP_BLOCKER_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// Logs page AJAX handlers.
		add_action( 'wp_ajax_geo_ip_blocker_export_csv', array( $this, 'ajax_export_csv' ) );
		add_action( 'wp_ajax_geo_ip_blocker_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'admin_post_geo_ip_blocker_cleanup_logs', array( $this, 'handle_cleanup_logs' ) );
	}

	/**
	 * Add menu pages.
	 */
	public function add_menu_pages() {
		add_menu_page(
			__( 'Geo & IP Blocker', 'geo-ip-blocker' ),
			__( 'Geo & IP Blocker', 'geo-ip-blocker' ),
			'manage_options',
			'geo-ip-blocker',
			array( $this, 'render_dashboard_page' ),
			'dashicons-shield-alt',
			56
		);

		add_submenu_page(
			'geo-ip-blocker',
			__( 'Dashboard', 'geo-ip-blocker' ),
			__( 'Dashboard', 'geo-ip-blocker' ),
			'manage_options',
			'geo-ip-blocker',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'geo-ip-blocker',
			__( 'Rules', 'geo-ip-blocker' ),
			__( 'Rules', 'geo-ip-blocker' ),
			'manage_options',
			'geo-ip-blocker-rules',
			array( $this, 'render_rules_page' )
		);

		add_submenu_page(
			'geo-ip-blocker',
			__( 'Logs', 'geo-ip-blocker' ),
			__( 'Logs', 'geo-ip-blocker' ),
			'manage_options',
			'geo-ip-blocker-logs',
			array( $this, 'render_logs_page' )
		);

		add_submenu_page(
			'geo-ip-blocker',
			__( 'Settings', 'geo-ip-blocker' ),
			__( 'Settings', 'geo-ip-blocker' ),
			'manage_options',
			'geo-ip-blocker-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only enqueue on plugin pages.
		if ( strpos( $hook, 'geo-ip-blocker' ) === false ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'geo-ip-blocker-admin',
			GEO_IP_BLOCKER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			GEO_IP_BLOCKER_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'geo-ip-blocker-admin',
			GEO_IP_BLOCKER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			GEO_IP_BLOCKER_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'geo-ip-blocker-admin',
			'geoIPBlockerAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'geo-ip-blocker-admin' ),
			)
		);
	}

	/**
	 * Add action links to plugin page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=geo-ip-blocker-settings' ),
			__( 'Settings', 'geo-ip-blocker' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render rules page.
	 */
	public function render_rules_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/views/rules.php';
	}

	/**
	 * Render logs page.
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/views/logs.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->settings_page->render();
	}

	/**
	 * Handle AJAX CSV export.
	 */
	public function ajax_export_csv() {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		// Verify nonce.
		check_ajax_referer( 'geo-ip-blocker-admin', 'nonce' );

		// Get filters.
		$filters = array();
		if ( ! empty( $_POST['filters'] ) ) {
			$filters = json_decode( sanitize_text_field( wp_unslash( $_POST['filters'] ) ), true );
		}

		// Get logger.
		$logger = geo_ip_blocker_get_logger();

		// Export to CSV.
		$filepath = $logger->export_logs( 'csv', $filters );

		if ( false === $filepath ) {
			wp_send_json_error( array( 'message' => __( 'Failed to export logs.', 'geo-ip-blocker' ) ) );
		}

		// Get download URL.
		$upload_dir = wp_upload_dir();
		$fileurl    = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $filepath );

		wp_send_json_success(
			array(
				'message' => __( 'Logs exported successfully!', 'geo-ip-blocker' ),
				'url'     => $fileurl,
			)
		);
	}

	/**
	 * Handle AJAX clear all logs.
	 */
	public function ajax_clear_logs() {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		// Verify nonce.
		check_ajax_referer( 'geo-ip-blocker-admin', 'nonce' );

		// Get logger.
		$logger = geo_ip_blocker_get_logger();

		// Clear all logs.
		$result = $logger->clear_all_logs();

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to clear logs.', 'geo-ip-blocker' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'All logs have been cleared successfully!', 'geo-ip-blocker' ),
			)
		);
	}

	/**
	 * Handle cleanup logs form submission.
	 */
	public function handle_cleanup_logs() {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'geo-ip-blocker' ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['cleanup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cleanup_nonce'] ) ), 'geo_ip_blocker_cleanup_logs' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'geo-ip-blocker' ) );
		}

		$action = isset( $_POST['cleanup_action'] ) ? sanitize_text_field( wp_unslash( $_POST['cleanup_action'] ) ) : '';

		if ( 'save_cleanup_settings' === $action ) {
			// Save cleanup settings.
			$settings = get_option( 'geo_ip_blocker_settings', array() );

			$settings['log_retention_days'] = isset( $_POST['log_retention_days'] ) ? absint( $_POST['log_retention_days'] ) : 90;
			$settings['max_logs']            = isset( $_POST['max_logs'] ) ? absint( $_POST['max_logs'] ) : 10000;
			$settings['auto_cleanup_logs']   = ! empty( $_POST['auto_cleanup_logs'] );

			update_option( 'geo_ip_blocker_settings', $settings );

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'geo-ip-blocker-logs',
						'updated' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;

		} elseif ( 'cleanup_now' === $action ) {
			// Clean up logs now.
			$retention_days = isset( $_POST['log_retention_days'] ) ? absint( $_POST['log_retention_days'] ) : 90;

			$logger = geo_ip_blocker_get_logger();
			$logger->delete_logs( $retention_days );
			$logger->maybe_trim_logs();

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'      => 'geo-ip-blocker-logs',
						'cleanup' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=geo-ip-blocker-logs' ) );
		exit;
	}
}
