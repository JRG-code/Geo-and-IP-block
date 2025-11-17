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
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . GEO_IP_BLOCKER_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
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

		include GEO_IP_BLOCKER_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
