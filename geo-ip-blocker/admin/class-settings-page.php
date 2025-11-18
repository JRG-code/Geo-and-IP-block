<?php
/**
 * Settings Page Handler
 *
 * @package GeoIPBlocker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geo_IP_Blocker_Settings_Page
 *
 * Handles the plugin settings page with WordPress Settings API.
 */
class Geo_IP_Blocker_Settings_Page {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private $option_name = 'geo_ip_blocker_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $default_settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_default_settings();
		$this->init_hooks();
	}

	/**
	 * Set default settings.
	 */
	private function set_default_settings() {
		$this->default_settings = array(
			// General Settings.
			'enabled'              => true,
			'blocking_mode'        => 'blacklist', // 'whitelist' or 'blacklist'.
			'block_action'         => 'message',   // 'message', 'redirect', 'page', '403'.
			'block_message'        => __( 'Access denied. Your location or IP address is not allowed to access this site.', 'geo-ip-blocker' ),
			'redirect_url'         => '',
			'block_page_id'        => 0,
			'exempt_administrators' => true,
			'exempt_logged_in'     => false,
			'woocommerce_mode'     => 'all', // 'all', 'woo_only', 'checkout_only', 'none'.

			// API Configuration.
			'geolocation_provider' => 'ip-api', // 'maxmind', 'ip2location', 'ip-api'.
			'maxmind_license_key'  => '',
			'ip2location_api_key'  => '',
			'enable_local_database' => false,
			'auto_update_database' => true,
			'last_db_update'       => '',

			// Country Blocking.
			'blocked_countries'    => array(),
			'allowed_countries'    => array(),
			'blocked_regions'      => array(),

			// IP Blocking - uses existing IP manager options.
			// (ip_whitelist and ip_blacklist are managed separately via geo_blocker_ip_whitelist and geo_blocker_ip_blacklist options).

			// Exceptions.
			'exempt_roles'         => array( 'administrator' ),
			'exempt_users'         => array(),
			'exempt_pages'         => array(),
			'allow_login_page'     => true,
			'allow_admin_area'     => true,

			// WooCommerce Integration.
			'woo_enable_blocking'      => false,
			'woo_blocking_level'       => 'entire_site', // 'entire_site', 'shop_only', 'cart_checkout', 'checkout_only'.
			'woo_block_shop'           => true,
			'woo_block_cart'           => true,
			'woo_block_checkout'       => true,
			'woo_block_account'        => false,
			'woo_enable_product_blocking' => false,
			'woo_enable_category_blocking' => false,
			'woo_blocked_product_message' => __( 'This product is not available in your region.', 'geo-ip-blocker' ),
			'woo_hide_price'           => false,
			'woo_hide_add_to_cart'     => true,

			// Logging.
			'enable_logging'       => true,
			'max_logs'             => 10000,
			'log_retention_days'   => 90,

			// Tools & Debug.
			'debug_mode'           => false,

			// Frontend Template Settings.
			'block_template_style' => 'default', // 'default', 'minimal', 'dark', 'custom'.
			'show_block_details'   => false,
			'contact_url'          => '',
			'use_theme_styles'     => false,
			'show_powered_by'      => false,
		);
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_geo_ip_blocker_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_geo_ip_blocker_test_api', array( $this, 'ajax_test_api' ) );
		add_action( 'wp_ajax_geo_ip_blocker_update_database', array( $this, 'ajax_update_database' ) );
		add_action( 'wp_ajax_geo_ip_blocker_get_regions', array( $this, 'ajax_get_regions' ) );

		// IP Management AJAX handlers.
		add_action( 'wp_ajax_geo_ip_blocker_add_ip', array( $this, 'ajax_add_ip' ) );
		add_action( 'wp_ajax_geo_ip_blocker_remove_ip', array( $this, 'ajax_remove_ip' ) );
		add_action( 'wp_ajax_geo_ip_blocker_get_current_ip', array( $this, 'ajax_get_current_ip' ) );

		// Exceptions AJAX handlers.
		add_action( 'wp_ajax_geo_ip_blocker_search_users', array( $this, 'ajax_search_users' ) );
		add_action( 'wp_ajax_geo_ip_blocker_search_pages', array( $this, 'ajax_search_pages' ) );

		// Tools AJAX handlers.
		add_action( 'wp_ajax_geo_ip_blocker_test_ip_location', array( $this, 'ajax_test_ip_location' ) );
		add_action( 'wp_ajax_geo_ip_blocker_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_geo_ip_blocker_export_settings', array( $this, 'ajax_export_settings' ) );
		add_action( 'wp_ajax_geo_ip_blocker_import_settings', array( $this, 'ajax_import_settings' ) );
		add_action( 'wp_ajax_geo_ip_blocker_reset_settings', array( $this, 'ajax_reset_settings' ) );
		add_action( 'wp_ajax_geo_ip_blocker_view_debug_log', array( $this, 'ajax_view_debug_log' ) );
		add_action( 'wp_ajax_geo_ip_blocker_clear_debug_log', array( $this, 'ajax_clear_debug_log' ) );
	}

	/**
	 * Register settings with WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'geo_ip_blocker_settings_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->default_settings,
			)
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// General Settings.
		$sanitized['enabled']              = ! empty( $input['enabled'] );
		$sanitized['blocking_mode']        = isset( $input['blocking_mode'] ) && in_array( $input['blocking_mode'], array( 'whitelist', 'blacklist' ), true ) ? $input['blocking_mode'] : 'blacklist';
		$sanitized['block_action']         = isset( $input['block_action'] ) && in_array( $input['block_action'], array( 'message', 'redirect', 'page', '403' ), true ) ? $input['block_action'] : 'message';
		$sanitized['block_message']        = isset( $input['block_message'] ) ? wp_kses_post( $input['block_message'] ) : '';
		$sanitized['redirect_url']         = isset( $input['redirect_url'] ) ? esc_url_raw( $input['redirect_url'] ) : '';
		$sanitized['block_page_id']        = isset( $input['block_page_id'] ) ? absint( $input['block_page_id'] ) : 0;
		$sanitized['exempt_administrators'] = ! empty( $input['exempt_administrators'] );
		$sanitized['exempt_logged_in']     = ! empty( $input['exempt_logged_in'] );
		$sanitized['woocommerce_mode']     = isset( $input['woocommerce_mode'] ) && in_array( $input['woocommerce_mode'], array( 'all', 'woo_only', 'checkout_only', 'none' ), true ) ? $input['woocommerce_mode'] : 'all';

		// API Configuration.
		$sanitized['geolocation_provider'] = isset( $input['geolocation_provider'] ) && in_array( $input['geolocation_provider'], array( 'maxmind', 'ip2location', 'ip-api' ), true ) ? $input['geolocation_provider'] : 'ip-api';
		$sanitized['maxmind_license_key']  = isset( $input['maxmind_license_key'] ) ? sanitize_text_field( $input['maxmind_license_key'] ) : '';
		$sanitized['ip2location_api_key']  = isset( $input['ip2location_api_key'] ) ? sanitize_text_field( $input['ip2location_api_key'] ) : '';
		$sanitized['enable_local_database'] = ! empty( $input['enable_local_database'] );
		$sanitized['auto_update_database'] = ! empty( $input['auto_update_database'] );
		$sanitized['last_db_update']       = isset( $input['last_db_update'] ) ? sanitize_text_field( $input['last_db_update'] ) : '';

		// Country Blocking.
		$sanitized['blocked_countries']    = isset( $input['blocked_countries'] ) && is_array( $input['blocked_countries'] ) ? array_map( 'sanitize_text_field', $input['blocked_countries'] ) : array();
		$sanitized['allowed_countries']    = isset( $input['allowed_countries'] ) && is_array( $input['allowed_countries'] ) ? array_map( 'sanitize_text_field', $input['allowed_countries'] ) : array();
		$sanitized['blocked_regions']      = isset( $input['blocked_regions'] ) && is_array( $input['blocked_regions'] ) ? array_map( 'sanitize_text_field', $input['blocked_regions'] ) : array();

		// IP Blocking - IPs are managed separately via IP Manager class.

		// Exceptions.
		$sanitized['exempt_roles']         = isset( $input['exempt_roles'] ) && is_array( $input['exempt_roles'] ) ? array_map( 'sanitize_text_field', $input['exempt_roles'] ) : array();
		$sanitized['exempt_users']         = isset( $input['exempt_users'] ) && is_array( $input['exempt_users'] ) ? array_map( 'absint', $input['exempt_users'] ) : array();
		$sanitized['exempt_pages']         = isset( $input['exempt_pages'] ) && is_array( $input['exempt_pages'] ) ? array_map( 'absint', $input['exempt_pages'] ) : array();
		$sanitized['allow_login_page']     = ! empty( $input['allow_login_page'] );
		$sanitized['allow_admin_area']     = ! empty( $input['allow_admin_area'] );

		// WooCommerce Integration.
		$sanitized['woo_enable_blocking']      = ! empty( $input['woo_enable_blocking'] );
		$sanitized['woo_blocking_level']       = isset( $input['woo_blocking_level'] ) && in_array( $input['woo_blocking_level'], array( 'entire_site', 'shop_only', 'cart_checkout', 'checkout_only' ), true ) ? $input['woo_blocking_level'] : 'entire_site';
		$sanitized['woo_block_shop']           = ! empty( $input['woo_block_shop'] );
		$sanitized['woo_block_cart']           = ! empty( $input['woo_block_cart'] );
		$sanitized['woo_block_checkout']       = ! empty( $input['woo_block_checkout'] );
		$sanitized['woo_block_account']        = ! empty( $input['woo_block_account'] );
		$sanitized['woo_enable_product_blocking'] = ! empty( $input['woo_enable_product_blocking'] );
		$sanitized['woo_enable_category_blocking'] = ! empty( $input['woo_enable_category_blocking'] );
		$sanitized['woo_blocked_product_message'] = isset( $input['woo_blocked_product_message'] ) ? wp_kses_post( $input['woo_blocked_product_message'] ) : '';
		$sanitized['woo_hide_price']           = ! empty( $input['woo_hide_price'] );
		$sanitized['woo_hide_add_to_cart']     = ! empty( $input['woo_hide_add_to_cart'] );

		// Logging.
		$sanitized['enable_logging']       = ! empty( $input['enable_logging'] );
		$sanitized['max_logs']             = isset( $input['max_logs'] ) ? absint( $input['max_logs'] ) : 10000;
		$sanitized['log_retention_days']   = isset( $input['log_retention_days'] ) ? absint( $input['log_retention_days'] ) : 90;

		// Tools.
		$sanitized['debug_mode']           = ! empty( $input['debug_mode'] );

		// Frontend Template Settings.
		$sanitized['block_template_style'] = isset( $input['block_template_style'] ) && in_array( $input['block_template_style'], array( 'default', 'minimal', 'dark', 'custom' ), true ) ? $input['block_template_style'] : 'default';
		$sanitized['show_block_details']   = ! empty( $input['show_block_details'] );
		$sanitized['contact_url']          = isset( $input['contact_url'] ) ? esc_url_raw( $input['contact_url'] ) : '';
		$sanitized['use_theme_styles']     = ! empty( $input['use_theme_styles'] );
		$sanitized['show_powered_by']      = ! empty( $input['show_powered_by'] );

		return $sanitized;
	}

	/**
	 * Enqueue scripts and styles for settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our settings page.
		if ( 'geo-ip-blocker_page_geo-ip-blocker-settings' !== $hook ) {
			return;
		}

		// Enqueue WordPress color picker.
		wp_enqueue_style( 'wp-color-picker' );

		// Enqueue Select2.
		wp_enqueue_style(
			'select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
			array(),
			'4.1.0'
		);
		wp_enqueue_script(
			'select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
			array( 'jquery' ),
			'4.1.0',
			true
		);

		// Enqueue our admin scripts and styles.
		wp_enqueue_style(
			'geo-ip-blocker-admin',
			GEO_IP_BLOCKER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			GEO_IP_BLOCKER_VERSION
		);

		wp_enqueue_script(
			'geo-ip-blocker-admin',
			GEO_IP_BLOCKER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'select2', 'wp-color-picker' ),
			GEO_IP_BLOCKER_VERSION,
			true
		);

		// Localize script with AJAX URL and nonce.
		wp_localize_script(
			'geo-ip-blocker-admin',
			'geoIPBlockerSettings',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'geo_ip_blocker_settings_nonce' ),
				'strings'           => array(
					'saving'           => __( 'Saving...', 'geo-ip-blocker' ),
					'saved'            => __( 'Settings saved successfully!', 'geo-ip-blocker' ),
					'error'            => __( 'Error saving settings. Please try again.', 'geo-ip-blocker' ),
					'testing'          => __( 'Testing connection...', 'geo-ip-blocker' ),
					'testSuccess'      => __( 'Connection successful!', 'geo-ip-blocker' ),
					'testFailed'       => __( 'Connection failed. Please check your API credentials.', 'geo-ip-blocker' ),
					'updating'         => __( 'Updating database...', 'geo-ip-blocker' ),
					'updateSuccess'    => __( 'Database updated successfully!', 'geo-ip-blocker' ),
					'updateFailed'     => __( 'Database update failed. Please try again.', 'geo-ip-blocker' ),
					'confirmClear'     => __( 'Are you sure you want to clear all selections?', 'geo-ip-blocker' ),
					'selectCountries'  => __( 'Select countries...', 'geo-ip-blocker' ),
					'noCountries'      => __( 'No countries selected.', 'geo-ip-blocker' ),
					'invalidIP'        => __( 'Invalid IP address or format.', 'geo-ip-blocker' ),
					'ipAdded'          => __( 'IP address added successfully!', 'geo-ip-blocker' ),
					'ipRemoved'        => __( 'IP address removed successfully!', 'geo-ip-blocker' ),
					'confirmRemoveIP'  => __( 'Are you sure you want to remove this IP?', 'geo-ip-blocker' ),
					'searchUsers'      => __( 'Search users...', 'geo-ip-blocker' ),
					'searchPages'      => __( 'Search pages...', 'geo-ip-blocker' ),
				),
			)
		);
	}

	/**
	 * Get current settings.
	 *
	 * @return array Current settings merged with defaults.
	 */
	public function get_settings() {
		$settings = get_option( $this->option_name, array() );
		return wp_parse_args( $settings, $this->default_settings );
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		$settings     = $this->get_settings();
		$active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		$allowed_tabs = array( 'general', 'api', 'countries', 'ip_blocking', 'exceptions', 'woocommerce', 'logging', 'tools' );

		if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
			$active_tab = 'general';
		}

		?>
		<div class="wrap geo-ip-blocker-settings">
			<h1><?php esc_html_e( 'Geo & IP Blocker Settings', 'geo-ip-blocker' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=geo-ip-blocker-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General Settings', 'geo-ip-blocker' ); ?>
				</a>
				<a href="?page=geo-ip-blocker-settings&tab=api" class="nav-tab <?php echo 'api' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'API Configuration', 'geo-ip-blocker' ); ?>
				</a>
				<a href="?page=geo-ip-blocker-settings&tab=countries" class="nav-tab <?php echo 'countries' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Country Blocking', 'geo-ip-blocker' ); ?>
				</a>
				<a href="?page=geo-ip-blocker-settings&tab=ip_blocking" class="nav-tab <?php echo 'ip_blocking' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'IP Blocking', 'geo-ip-blocker' ); ?>
				</a>
				<a href="?page=geo-ip-blocker-settings&tab=exceptions" class="nav-tab <?php echo 'exceptions' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Exceptions', 'geo-ip-blocker' ); ?>
				</a>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a href="?page=geo-ip-blocker-settings&tab=woocommerce" class="nav-tab <?php echo 'woocommerce' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'WooCommerce', 'geo-ip-blocker' ); ?>
				</a>
				<?php endif; ?>
				<a href="?page=geo-ip-blocker-settings&tab=logging" class="nav-tab <?php echo 'logging' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Logging', 'geo-ip-blocker' ); ?>
				</a>
				<a href="?page=geo-ip-blocker-settings&tab=tools" class="nav-tab <?php echo 'tools' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Tools', 'geo-ip-blocker' ); ?>
				</a>
			</h2>

			<div class="geo-ip-blocker-settings-content">
				<form method="post" id="geo-ip-blocker-settings-form">
					<?php wp_nonce_field( 'geo_ip_blocker_settings_nonce', 'geo_ip_blocker_settings_nonce' ); ?>

					<?php
					switch ( $active_tab ) {
						case 'general':
							$this->render_general_tab( $settings );
							break;
						case 'api':
							$this->render_api_tab( $settings );
							break;
						case 'countries':
							$this->render_countries_tab( $settings );
							break;
						case 'ip_blocking':
							$this->render_ip_blocking_tab( $settings );
							break;
						case 'exceptions':
							$this->render_exceptions_tab( $settings );
							break;
						case 'woocommerce':
							$this->render_woocommerce_tab( $settings );
							break;
						case 'logging':
							$this->render_logging_tab( $settings );
							break;
						case 'tools':
							$this->render_tools_tab( $settings );
							break;
					}
					?>

					<div class="geo-ip-blocker-settings-footer">
						<?php submit_button( __( 'Save Settings', 'geo-ip-blocker' ), 'primary', 'submit', false ); ?>
						<span class="spinner"></span>
						<span class="geo-ip-blocker-message"></span>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render General Settings tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_general_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="enabled"><?php esc_html_e( 'Enable Blocking', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="settings[enabled]" id="enabled" value="1" <?php checked( $settings['enabled'], true ); ?>>
						<?php esc_html_e( 'Enable geo and IP blocking', 'geo-ip-blocker' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Turn this off to temporarily disable all blocking without losing your configuration.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="blocking_mode"><?php esc_html_e( 'Blocking Mode', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<select name="settings[blocking_mode]" id="blocking_mode">
						<option value="blacklist" <?php selected( $settings['blocking_mode'], 'blacklist' ); ?>>
							<?php esc_html_e( 'Blacklist (block selected countries)', 'geo-ip-blocker' ); ?>
						</option>
						<option value="whitelist" <?php selected( $settings['blocking_mode'], 'whitelist' ); ?>>
							<?php esc_html_e( 'Whitelist (allow only selected countries)', 'geo-ip-blocker' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'Choose whether to block or allow specific countries.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="block_action"><?php esc_html_e( 'Block Action', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<select name="settings[block_action]" id="block_action">
						<option value="message" <?php selected( $settings['block_action'], 'message' ); ?>>
							<?php esc_html_e( 'Show Message', 'geo-ip-blocker' ); ?>
						</option>
						<option value="redirect" <?php selected( $settings['block_action'], 'redirect' ); ?>>
							<?php esc_html_e( 'Redirect to URL', 'geo-ip-blocker' ); ?>
						</option>
						<option value="page" <?php selected( $settings['block_action'], 'page' ); ?>>
							<?php esc_html_e( 'Show Page', 'geo-ip-blocker' ); ?>
						</option>
						<option value="403" <?php selected( $settings['block_action'], '403' ); ?>>
							<?php esc_html_e( '403 Forbidden', 'geo-ip-blocker' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'What should happen when a visitor is blocked?', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr class="block-action-option block-action-message">
				<th scope="row">
					<label for="block_message"><?php esc_html_e( 'Block Message', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<?php
					wp_editor(
						$settings['block_message'],
						'block_message',
						array(
							'textarea_name' => 'settings[block_message]',
							'textarea_rows' => 5,
							'media_buttons' => false,
							'teeny'         => true,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Message shown to blocked visitors.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr class="block-action-option block-action-redirect">
				<th scope="row">
					<label for="redirect_url"><?php esc_html_e( 'Redirect URL', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<input type="url" name="settings[redirect_url]" id="redirect_url" value="<?php echo esc_url( $settings['redirect_url'] ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'URL to redirect blocked visitors to.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr class="block-action-option block-action-page">
				<th scope="row">
					<label for="block_page_id"><?php esc_html_e( 'Block Page', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => 'settings[block_page_id]',
							'id'                => 'block_page_id',
							'selected'          => $settings['block_page_id'],
							'show_option_none'  => __( '— Select Page —', 'geo-ip-blocker' ),
							'option_none_value' => 0,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Page to show to blocked visitors.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Exemptions', 'geo-ip-blocker' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="settings[exempt_administrators]" value="1" <?php checked( $settings['exempt_administrators'], true ); ?>>
						<?php esc_html_e( 'Exempt administrators', 'geo-ip-blocker' ); ?>
					</label>
					<br>
					<label>
						<input type="checkbox" name="settings[exempt_logged_in]" value="1" <?php checked( $settings['exempt_logged_in'], true ); ?>>
						<?php esc_html_e( 'Exempt all logged-in users', 'geo-ip-blocker' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Choose which users should bypass blocking rules.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<tr>
				<th scope="row">
					<label for="woocommerce_mode"><?php esc_html_e( 'WooCommerce Integration', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<select name="settings[woocommerce_mode]" id="woocommerce_mode">
						<option value="all" <?php selected( $settings['woocommerce_mode'], 'all' ); ?>>
							<?php esc_html_e( 'Block entire site', 'geo-ip-blocker' ); ?>
						</option>
						<option value="woo_only" <?php selected( $settings['woocommerce_mode'], 'woo_only' ); ?>>
							<?php esc_html_e( 'Block WooCommerce pages only', 'geo-ip-blocker' ); ?>
						</option>
						<option value="checkout_only" <?php selected( $settings['woocommerce_mode'], 'checkout_only' ); ?>>
							<?php esc_html_e( 'Block checkout/cart only', 'geo-ip-blocker' ); ?>
						</option>
						<option value="none" <?php selected( $settings['woocommerce_mode'], 'none' ); ?>>
							<?php esc_html_e( 'No WooCommerce blocking', 'geo-ip-blocker' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'Configure blocking behavior for WooCommerce pages.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Render API Configuration tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_api_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="geolocation_provider"><?php esc_html_e( 'Geolocation Provider', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<select name="settings[geolocation_provider]" id="geolocation_provider">
						<option value="ip-api" <?php selected( $settings['geolocation_provider'], 'ip-api' ); ?>>
							<?php esc_html_e( 'IP-API.com (Free, no key required)', 'geo-ip-blocker' ); ?>
						</option>
						<option value="maxmind" <?php selected( $settings['geolocation_provider'], 'maxmind' ); ?>>
							<?php esc_html_e( 'MaxMind GeoIP2 (Requires license key)', 'geo-ip-blocker' ); ?>
						</option>
						<option value="ip2location" <?php selected( $settings['geolocation_provider'], 'ip2location' ); ?>>
							<?php esc_html_e( 'IP2Location (Requires API key)', 'geo-ip-blocker' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'Select your preferred geolocation service.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr class="api-provider-option api-provider-maxmind">
				<th scope="row">
					<label for="maxmind_license_key"><?php esc_html_e( 'MaxMind License Key', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<input type="password" name="settings[maxmind_license_key]" id="maxmind_license_key" value="<?php echo esc_attr( $settings['maxmind_license_key'] ); ?>" class="regular-text">
					<p class="description">
						<?php
						printf(
							/* translators: %s: MaxMind signup URL */
							esc_html__( 'Get your license key from %s', 'geo-ip-blocker' ),
							'<a href="https://www.maxmind.com/en/geolite2/signup" target="_blank">MaxMind</a>'
						);
						?>
					</p>
				</td>
			</tr>

			<tr class="api-provider-option api-provider-ip2location">
				<th scope="row">
					<label for="ip2location_api_key"><?php esc_html_e( 'IP2Location API Key', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<input type="password" name="settings[ip2location_api_key]" id="ip2location_api_key" value="<?php echo esc_attr( $settings['ip2location_api_key'] ); ?>" class="regular-text">
					<p class="description">
						<?php
						printf(
							/* translators: %s: IP2Location signup URL */
							esc_html__( 'Get your API key from %s', 'geo-ip-blocker' ),
							'<a href="https://www.ip2location.com/sign-up" target="_blank">IP2Location</a>'
						);
						?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Test Connection', 'geo-ip-blocker' ); ?>
				</th>
				<td>
					<button type="button" id="test-api-connection" class="button button-secondary">
						<?php esc_html_e( 'Test API Connection', 'geo-ip-blocker' ); ?>
					</button>
					<span class="spinner"></span>
					<p class="api-test-result"></p>
				</td>
			</tr>

			<tr class="api-provider-option api-provider-maxmind">
				<th scope="row">
					<label for="enable_local_database"><?php esc_html_e( 'Local Database', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="settings[enable_local_database]" id="enable_local_database" value="1" <?php checked( $settings['enable_local_database'], true ); ?>>
						<?php esc_html_e( 'Use local MaxMind database for faster lookups', 'geo-ip-blocker' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Downloads and uses the GeoLite2-City database locally for better performance.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr class="api-provider-option api-provider-maxmind local-db-option">
				<th scope="row">
					<label for="auto_update_database"><?php esc_html_e( 'Auto-Update Database', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="settings[auto_update_database]" id="auto_update_database" value="1" <?php checked( $settings['auto_update_database'], true ); ?>>
						<?php esc_html_e( 'Automatically update database weekly', 'geo-ip-blocker' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Keep your local database up to date with automatic weekly updates.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr class="api-provider-option api-provider-maxmind local-db-option">
				<th scope="row">
					<?php esc_html_e( 'Database Status', 'geo-ip-blocker' ); ?>
				</th>
				<td>
					<?php if ( ! empty( $settings['last_db_update'] ) ) : ?>
						<p>
							<?php
							printf(
								/* translators: %s: Last update date */
								esc_html__( 'Last updated: %s', 'geo-ip-blocker' ),
								esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $settings['last_db_update'] ) ) )
							);
							?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'Database not yet downloaded.', 'geo-ip-blocker' ); ?></p>
					<?php endif; ?>
					<button type="button" id="update-database" class="button button-secondary">
						<?php esc_html_e( 'Update Database Now', 'geo-ip-blocker' ); ?>
					</button>
					<span class="spinner"></span>
					<p class="database-update-result"></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Country Blocking tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_countries_tab( $settings ) {
		$countries        = geo_ip_blocker_get_countries();
		$regions          = geo_ip_blocker_get_country_regions();
		$blocking_mode    = $settings['blocking_mode'];
		$blocked_countries = $settings['blocked_countries'];
		$allowed_countries = $settings['allowed_countries'];
		?>
		<div class="geo-ip-blocker-countries-section">
			<p class="description">
				<?php
				if ( 'blacklist' === $blocking_mode ) {
					esc_html_e( 'Select countries or regions to block. Visitors from these countries will not be able to access your site.', 'geo-ip-blocker' );
				} else {
					esc_html_e( 'Select countries or regions to allow. Only visitors from these countries will be able to access your site.', 'geo-ip-blocker' );
				}
				?>
			</p>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="region-select">
							<?php esc_html_e( 'Quick Select Regions', 'geo-ip-blocker' ); ?>
						</label>
					</th>
					<td>
						<select id="region-select" class="geo-ip-blocker-region-select" style="width: 100%; max-width: 500px;">
							<option value=""><?php esc_html_e( '-- Select a region --', 'geo-ip-blocker' ); ?></option>
							<?php
							foreach ( $regions as $region_code => $region_data ) {
								printf(
									'<option value="%s" data-countries="%s">%s (%d countries)</option>',
									esc_attr( $region_code ),
									esc_attr( implode( ',', $region_data['countries'] ) ),
									esc_html( $region_data['name'] ),
									count( $region_data['countries'] )
								);
							}
							?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Select a region to quickly add all its member countries to the list below.', 'geo-ip-blocker' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="country-select">
							<?php echo 'blacklist' === $blocking_mode ? esc_html__( 'Blocked Countries', 'geo-ip-blocker' ) : esc_html__( 'Allowed Countries', 'geo-ip-blocker' ); ?>
						</label>
					</th>
					<td>
						<select name="<?php echo 'blacklist' === $blocking_mode ? 'settings[blocked_countries][]' : 'settings[allowed_countries][]'; ?>"
								id="country-select"
								class="geo-ip-blocker-country-select"
								multiple="multiple"
								style="width: 100%; max-width: 500px;">
							<?php
							$selected_countries = 'blacklist' === $blocking_mode ? $blocked_countries : $allowed_countries;
							foreach ( $countries as $code => $name ) {
								printf(
									'<option value="%s"%s>%s</option>',
									esc_attr( $code ),
									in_array( $code, $selected_countries, true ) ? ' selected' : '',
									esc_html( $name )
								);
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Search and select multiple countries from the list.', 'geo-ip-blocker' ); ?></p>

						<div class="country-actions" style="margin-top: 10px;">
							<button type="button" id="select-all-countries" class="button button-secondary">
								<?php esc_html_e( 'Select All', 'geo-ip-blocker' ); ?>
							</button>
							<button type="button" id="clear-all-countries" class="button button-secondary">
								<?php esc_html_e( 'Clear Selection', 'geo-ip-blocker' ); ?>
							</button>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Selected Countries', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<div id="selected-countries-list" class="selected-countries-list">
							<?php if ( ! empty( $selected_countries ) ) : ?>
								<?php foreach ( $selected_countries as $country_code ) : ?>
									<?php if ( isset( $countries[ $country_code ] ) ) : ?>
										<span class="country-tag" data-country="<?php echo esc_attr( $country_code ); ?>">
											<?php echo esc_html( $countries[ $country_code ] ); ?>
											<button type="button" class="remove-country" aria-label="<?php esc_attr_e( 'Remove', 'geo-ip-blocker' ); ?>">&times;</button>
										</span>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="no-countries"><?php esc_html_e( 'No countries selected.', 'geo-ip-blocker' ); ?></p>
							<?php endif; ?>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Logging tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_logging_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="enable_logging"><?php esc_html_e( 'Enable Logging', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="settings[enable_logging]" id="enable_logging" value="1" <?php checked( $settings['enable_logging'], true ); ?>>
						<?php esc_html_e( 'Log all blocked access attempts', 'geo-ip-blocker' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Track and log all blocked visitors for analysis.', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="max_logs"><?php esc_html_e( 'Maximum Logs', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<input type="number" name="settings[max_logs]" id="max_logs" value="<?php echo esc_attr( $settings['max_logs'] ); ?>" min="100" max="100000" step="100" class="small-text">
					<p class="description"><?php esc_html_e( 'Maximum number of log entries to keep in the database (100-100,000).', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="log_retention_days"><?php esc_html_e( 'Log Retention Period', 'geo-ip-blocker' ); ?></label>
				</th>
				<td>
					<input type="number" name="settings[log_retention_days]" id="log_retention_days" value="<?php echo esc_attr( $settings['log_retention_days'] ); ?>" min="1" max="365" class="small-text">
					<?php esc_html_e( 'days', 'geo-ip-blocker' ); ?>
					<p class="description"><?php esc_html_e( 'Automatically delete logs older than this many days (1-365).', 'geo-ip-blocker' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Handle AJAX settings save.
	 */
	public function ajax_save_settings() {
		// TEMPORARY DEBUG LOGGING - Remove after debugging
		$debug_log_file = GEO_IP_BLOCKER_PLUGIN_DIR . 'debug-save.log';
		$debug_data = array(
			'timestamp' => date( 'Y-m-d H:i:s' ),
			'post_data' => $_POST,
			'settings_received' => isset( $_POST['settings'] ) ? 'YES' : 'NO',
		);

		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		// Get existing settings.
		$existing_settings = $this->get_settings();

		// Get new settings from POST.
		$new_settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// DEBUG: Log what we received
		$debug_data['new_settings_count'] = count( $new_settings );
		$debug_data['new_settings_keys'] = array_keys( $new_settings );
		$debug_data['existing_settings_count'] = count( $existing_settings );

		// Merge new settings with existing ones to preserve values from other tabs.
		$merged_settings = array_merge( $existing_settings, $new_settings );

		// DEBUG: Log merged
		$debug_data['merged_settings_count'] = count( $merged_settings );

		// Sanitize merged settings.
		$sanitized = $this->sanitize_settings( $merged_settings );

		// DEBUG: Log sanitized
		$debug_data['sanitized_count'] = count( $sanitized );

		// Save settings.
		$result = update_option( $this->option_name, $sanitized );

		// DEBUG: Log result
		$debug_data['update_result'] = $result ? 'SUCCESS' : 'FAILED';
		$debug_data['saved_value'] = get_option( $this->option_name );

		// Write debug log
		file_put_contents( $debug_log_file, print_r( $debug_data, true ) . "\n\n", FILE_APPEND );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'geo-ip-blocker' ) ) );
	}

	/**
	 * Handle AJAX API connection test.
	 */
	public function ajax_test_api() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
		$api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		// Test connection using geolocation class.
		$geolocation = geo_ip_blocker_get_geolocation();
		$test_ip     = '8.8.8.8'; // Google DNS for testing.

		// Temporarily set API key if provided.
		if ( ! empty( $api_key ) ) {
			$settings = $this->get_settings();
			if ( 'maxmind' === $provider ) {
				$settings['maxmind_license_key'] = $api_key;
			} elseif ( 'ip2location' === $provider ) {
				$settings['ip2location_api_key'] = $api_key;
			}
			$settings['geolocation_provider'] = $provider;
			update_option( $this->option_name, $settings );
		}

		$result = $geolocation->get_location_data( $test_ip );

		if ( ! empty( $result['country_code'] ) ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %s: Country name */
						__( 'Connection successful! Test IP resolved to: %s', 'geo-ip-blocker' ),
						$result['country_name']
					),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Connection failed. Please check your API credentials.', 'geo-ip-blocker' ) ) );
		}
	}

	/**
	 * Handle AJAX database update.
	 */
	public function ajax_update_database() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$geolocation = geo_ip_blocker_get_geolocation();
		$result      = $geolocation->update_local_database();

		if ( $result ) {
			// Update last update time.
			$settings                     = $this->get_settings();
			$settings['last_db_update']   = current_time( 'mysql' );
			update_option( $this->option_name, $settings );

			wp_send_json_success( array( 'message' => __( 'Database updated successfully!', 'geo-ip-blocker' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Database update failed. Please try again.', 'geo-ip-blocker' ) ) );
		}
	}

	/**
	 * Handle AJAX get regions for a country.
	 */
	public function ajax_get_regions() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) : '';

		if ( empty( $country_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid country code.', 'geo-ip-blocker' ) ) );
		}

		// This would require additional implementation to get regions per country.
		// For now, return empty array.
		wp_send_json_success( array( 'regions' => array() ) );
	}

	/**
	 * Render IP Blocking tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_ip_blocking_tab( $settings ) {
		$ip_manager    = geo_ip_blocker_get_ip_manager();
		$ip_whitelist  = $ip_manager ? $ip_manager->get_list( 'whitelist' ) : array();
		$ip_blacklist  = $ip_manager ? $ip_manager->get_list( 'blacklist' ) : array();
		$geolocation   = geo_ip_blocker_get_geolocation();
		$current_ip    = $geolocation ? $geolocation->get_visitor_ip() : '';
		?>
		<div class="geo-ip-blocker-ip-section">
			<!-- Blacklist Section -->
			<div class="ip-list-section">
				<h3><?php esc_html_e( 'Blocked IPs (Blacklist)', 'geo-ip-blocker' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Add IP addresses to block from accessing your site. Supports individual IPs, CIDR notation (192.168.1.0/24), and ranges (192.168.1.1-192.168.1.50).', 'geo-ip-blocker' ); ?>
				</p>

				<div class="ip-add-form">
					<input type="text" id="blacklist-ip-input" class="regular-text" placeholder="<?php esc_attr_e( '192.168.1.100 or 10.0.0.0/24 or 172.16.0.1-172.16.0.50', 'geo-ip-blocker' ); ?>">
					<button type="button" class="button button-secondary" data-list-type="blacklist" data-action="add-ip">
						<?php esc_html_e( 'Add to Blacklist', 'geo-ip-blocker' ); ?>
					</button>
					<span class="spinner"></span>
					<span class="ip-message"></span>
				</div>

				<div class="ip-list-container" data-list-type="blacklist">
					<div class="ip-list-header">
						<input type="text" class="ip-search" placeholder="<?php esc_attr_e( 'Search IPs...', 'geo-ip-blocker' ); ?>">
						<span class="ip-count"><?php printf( esc_html__( '%d IPs', 'geo-ip-blocker' ), count( $ip_blacklist ) ); ?></span>
					</div>
					<div class="ip-list">
						<?php if ( ! empty( $ip_blacklist ) ) : ?>
							<?php foreach ( $ip_blacklist as $ip ) : ?>
								<div class="ip-item" data-ip="<?php echo esc_attr( $ip ); ?>">
									<span class="ip-address"><?php echo esc_html( $ip ); ?></span>
									<button type="button" class="button button-link-delete remove-ip" data-list-type="blacklist" data-ip="<?php echo esc_attr( $ip ); ?>">
										<?php esc_html_e( 'Remove', 'geo-ip-blocker' ); ?>
									</button>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="no-ips"><?php esc_html_e( 'No IPs in blacklist.', 'geo-ip-blocker' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Whitelist Section -->
			<div class="ip-list-section">
				<h3><?php esc_html_e( 'Allowed IPs (Whitelist)', 'geo-ip-blocker' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Add IP addresses to always allow access, regardless of other blocking rules. Whitelist takes priority over all other rules.', 'geo-ip-blocker' ); ?>
				</p>

				<div class="ip-add-form">
					<input type="text" id="whitelist-ip-input" class="regular-text" placeholder="<?php esc_attr_e( '192.168.1.100 or 10.0.0.0/24 or 172.16.0.1-172.16.0.50', 'geo-ip-blocker' ); ?>">
					<button type="button" class="button button-secondary" data-list-type="whitelist" data-action="add-ip">
						<?php esc_html_e( 'Add to Whitelist', 'geo-ip-blocker' ); ?>
					</button>
					<?php if ( $current_ip ) : ?>
						<button type="button" class="button button-secondary add-current-ip" data-list-type="whitelist" data-ip="<?php echo esc_attr( $current_ip ); ?>">
							<?php printf( esc_html__( 'Add My IP (%s)', 'geo-ip-blocker' ), esc_html( $current_ip ) ); ?>
						</button>
					<?php endif; ?>
					<span class="spinner"></span>
					<span class="ip-message"></span>
				</div>

				<div class="ip-list-container" data-list-type="whitelist">
					<div class="ip-list-header">
						<input type="text" class="ip-search" placeholder="<?php esc_attr_e( 'Search IPs...', 'geo-ip-blocker' ); ?>">
						<span class="ip-count"><?php printf( esc_html__( '%d IPs', 'geo-ip-blocker' ), count( $ip_whitelist ) ); ?></span>
					</div>
					<div class="ip-list">
						<?php if ( ! empty( $ip_whitelist ) ) : ?>
							<?php foreach ( $ip_whitelist as $ip ) : ?>
								<div class="ip-item" data-ip="<?php echo esc_attr( $ip ); ?>">
									<span class="ip-address"><?php echo esc_html( $ip ); ?></span>
									<button type="button" class="button button-link-delete remove-ip" data-list-type="whitelist" data-ip="<?php echo esc_attr( $ip ); ?>">
										<?php esc_html_e( 'Remove', 'geo-ip-blocker' ); ?>
									</button>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="no-ips"><?php esc_html_e( 'No IPs in whitelist.', 'geo-ip-blocker' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Exceptions tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_exceptions_tab( $settings ) {
		global $wp_roles;

		$all_roles     = $wp_roles->roles;
		$exempt_roles  = isset( $settings['exempt_roles'] ) ? $settings['exempt_roles'] : array();
		$exempt_users  = isset( $settings['exempt_users'] ) ? $settings['exempt_users'] : array();
		$exempt_pages  = isset( $settings['exempt_pages'] ) ? $settings['exempt_pages'] : array();
		?>
		<div class="geo-ip-blocker-exceptions-section">
			<table class="form-table">
				<!-- User Roles -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Exempt User Roles', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Select user roles to exempt from blocking', 'geo-ip-blocker' ); ?></legend>
							<?php foreach ( $all_roles as $role_slug => $role_data ) : ?>
								<label>
									<input type="checkbox" name="settings[exempt_roles][]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $exempt_roles, true ) ); ?>>
									<?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?>
								</label>
								<br>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Users with these roles will always be allowed access, regardless of their location or IP address.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Specific Users -->
				<tr>
					<th scope="row">
						<label for="exempt-users-select"><?php esc_html_e( 'Exempt Specific Users', 'geo-ip-blocker' ); ?></label>
					</th>
					<td>
						<select name="settings[exempt_users][]" id="exempt-users-select" class="geo-ip-blocker-users-select" multiple="multiple" style="width: 100%; max-width: 500px;">
							<?php
							if ( ! empty( $exempt_users ) ) {
								foreach ( $exempt_users as $user_id ) {
									$user = get_user_by( 'id', $user_id );
									if ( $user ) {
										printf(
											'<option value="%d" selected>%s (%s)</option>',
											esc_attr( $user_id ),
											esc_html( $user->display_name ),
											esc_html( $user->user_email )
										);
									}
								}
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Search and select specific users to exempt from blocking.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Specific Pages -->
				<tr>
					<th scope="row">
						<label for="exempt-pages-select"><?php esc_html_e( 'Exempt Specific Pages', 'geo-ip-blocker' ); ?></label>
					</th>
					<td>
						<select name="settings[exempt_pages][]" id="exempt-pages-select" class="geo-ip-blocker-pages-select" multiple="multiple" style="width: 100%; max-width: 500px;">
							<?php
							if ( ! empty( $exempt_pages ) ) {
								foreach ( $exempt_pages as $page_id ) {
									$page = get_post( $page_id );
									if ( $page ) {
										printf(
											'<option value="%d" selected>%s</option>',
											esc_attr( $page_id ),
											esc_html( $page->post_title )
										);
									}
								}
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Search and select specific pages/posts to exempt from blocking.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Special Options -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Special Access Options', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="settings[allow_login_page]" value="1" <?php checked( $settings['allow_login_page'], true ); ?>>
							<?php esc_html_e( 'Always allow access to login page (wp-login.php)', 'geo-ip-blocker' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="settings[allow_admin_area]" value="1" <?php checked( $settings['allow_admin_area'], true ); ?>>
							<?php esc_html_e( 'Always allow access to admin area (wp-admin)', 'geo-ip-blocker' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Configure special access rules for critical WordPress areas.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render WooCommerce tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_woocommerce_tab( $settings ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'WooCommerce plugin is not installed or activated.', 'geo-ip-blocker' ); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<div class="geo-ip-blocker-woocommerce-section">
			<table class="form-table">
				<!-- Enable WooCommerce Blocking -->
				<tr>
					<th scope="row">
						<label for="woo_enable_blocking"><?php esc_html_e( 'Enable WooCommerce Blocking', 'geo-ip-blocker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="settings[woo_enable_blocking]" id="woo_enable_blocking" value="1" <?php checked( $settings['woo_enable_blocking'], true ); ?>>
							<?php esc_html_e( 'Activate geo-blocking for WooCommerce', 'geo-ip-blocker' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Enable this option to apply geo-blocking specifically to WooCommerce pages and products.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Blocking Level -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Blocking Level', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Select blocking level', 'geo-ip-blocker' ); ?></legend>
							<label>
								<input type="radio" name="settings[woo_blocking_level]" value="entire_site" <?php checked( $settings['woo_blocking_level'], 'entire_site' ); ?>>
								<?php esc_html_e( 'Block entire site', 'geo-ip-blocker' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="settings[woo_blocking_level]" value="shop_only" <?php checked( $settings['woo_blocking_level'], 'shop_only' ); ?>>
								<?php esc_html_e( 'Block only shop pages', 'geo-ip-blocker' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="settings[woo_blocking_level]" value="cart_checkout" <?php checked( $settings['woo_blocking_level'], 'cart_checkout' ); ?>>
								<?php esc_html_e( 'Block cart and checkout only', 'geo-ip-blocker' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="settings[woo_blocking_level]" value="checkout_only" <?php checked( $settings['woo_blocking_level'], 'checkout_only' ); ?>>
								<?php esc_html_e( 'Allow browsing, block checkout only', 'geo-ip-blocker' ); ?>
							</label>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Choose how restrictive the geo-blocking should be for WooCommerce.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Specific Pages -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Specific Pages', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Select pages to block', 'geo-ip-blocker' ); ?></legend>
							<label>
								<input type="checkbox" name="settings[woo_block_shop]" value="1" <?php checked( $settings['woo_block_shop'], true ); ?>>
								<?php esc_html_e( 'Shop page', 'geo-ip-blocker' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="settings[woo_block_cart]" value="1" <?php checked( $settings['woo_block_cart'], true ); ?>>
								<?php esc_html_e( 'Cart page', 'geo-ip-blocker' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="settings[woo_block_checkout]" value="1" <?php checked( $settings['woo_block_checkout'], true ); ?>>
								<?php esc_html_e( 'Checkout page', 'geo-ip-blocker' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="settings[woo_block_account]" value="1" <?php checked( $settings['woo_block_account'], true ); ?>>
								<?php esc_html_e( 'My Account page', 'geo-ip-blocker' ); ?>
							</label>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Select which WooCommerce pages should be blocked based on geo-location.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Product and Category Blocking -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Product & Category Blocking', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="settings[woo_enable_product_blocking]" value="1" <?php checked( $settings['woo_enable_product_blocking'], true ); ?>>
							<?php esc_html_e( 'Enable per-product geo-blocking', 'geo-ip-blocker' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="settings[woo_enable_category_blocking]" value="1" <?php checked( $settings['woo_enable_category_blocking'], true ); ?>>
							<?php esc_html_e( 'Enable per-category geo-blocking', 'geo-ip-blocker' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Allow individual products and categories to have their own geo-blocking rules.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Blocked Product Message -->
				<tr>
					<th scope="row">
						<label for="woo_blocked_product_message"><?php esc_html_e( 'Blocked Product Message', 'geo-ip-blocker' ); ?></label>
					</th>
					<td>
						<?php
						wp_editor(
							$settings['woo_blocked_product_message'],
							'woo_blocked_product_message',
							array(
								'textarea_name' => 'settings[woo_blocked_product_message]',
								'textarea_rows' => 5,
								'media_buttons' => false,
								'teeny'         => true,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Message displayed when a product is not available in the user\'s region.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>

				<!-- Product Display Options -->
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Product Display Options', 'geo-ip-blocker' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox" name="settings[woo_hide_price]" value="1" <?php checked( $settings['woo_hide_price'], true ); ?>>
							<?php esc_html_e( 'Hide price for blocked products', 'geo-ip-blocker' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="settings[woo_hide_add_to_cart]" value="1" <?php checked( $settings['woo_hide_add_to_cart'], true ); ?>>
							<?php esc_html_e( 'Hide "Add to Cart" button for blocked products', 'geo-ip-blocker' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Control what information is shown for products that are geo-blocked.', 'geo-ip-blocker' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Handle AJAX add IP.
	 */
	public function ajax_add_ip() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$ip        = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		$list_type = isset( $_POST['list_type'] ) ? sanitize_text_field( wp_unslash( $_POST['list_type'] ) ) : '';

		if ( empty( $ip ) || ! in_array( $list_type, array( 'whitelist', 'blacklist' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'geo-ip-blocker' ) ) );
		}

		// Use IP manager to add IP.
		$result = geo_ip_blocker_add_ip( $ip, $list_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'IP address added successfully!', 'geo-ip-blocker' ),
				'ip'      => $ip,
			)
		);
	}

	/**
	 * Handle AJAX remove IP.
	 */
	public function ajax_remove_ip() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$ip        = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		$list_type = isset( $_POST['list_type'] ) ? sanitize_text_field( wp_unslash( $_POST['list_type'] ) ) : '';

		if ( empty( $ip ) || ! in_array( $list_type, array( 'whitelist', 'blacklist' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'geo-ip-blocker' ) ) );
		}

		// Use IP manager to remove IP.
		$result = geo_ip_blocker_remove_ip( $ip, $list_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'IP address removed successfully!', 'geo-ip-blocker' ),
				'ip'      => $ip,
			)
		);
	}

	/**
	 * Handle AJAX get current IP.
	 */
	public function ajax_get_current_ip() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$current_ip = geo_ip_blocker_get_current_ip();

		if ( empty( $current_ip ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not detect your IP address.', 'geo-ip-blocker' ) ) );
		}

		wp_send_json_success(
			array(
				'ip' => $current_ip,
			)
		);
	}

	/**
	 * Handle AJAX search users.
	 */
	public function ajax_search_users() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

		if ( strlen( $search ) < 2 ) {
			wp_send_json_success( array( 'results' => array() ) );
		}

		$users = get_users(
			array(
				'search'         => '*' . $search . '*',
				'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
				'number'         => 20,
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			$results[] = array(
				'id'   => $user->ID,
				'text' => sprintf( '%s (%s)', $user->display_name, $user->user_email ),
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Handle AJAX search pages.
	 */
	public function ajax_search_pages() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

		if ( strlen( $search ) < 2 ) {
			wp_send_json_success( array( 'results' => array() ) );
		}

		$pages = get_posts(
			array(
				's'              => $search,
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => 'publish',
				'posts_per_page' => 20,
			)
		);

		$results = array();
		foreach ( $pages as $page ) {
			$results[] = array(
				'id'   => $page->ID,
				'text' => sprintf( '%s (%s)', $page->post_title, get_post_type( $page->ID ) ),
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Handle AJAX test IP location.
	 */
	public function ajax_test_ip_location() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';

		if ( empty( $ip ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide an IP address.', 'geo-ip-blocker' ) ) );
		}

		// Validate IP format.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid IP address format.', 'geo-ip-blocker' ) ) );
		}

		$geolocation = geo_ip_blocker_get_geolocation();
		$result      = $geolocation->get_location_data( $ip );

		if ( empty( $result ) || empty( $result['country_code'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to locate IP address. Please check your API configuration.', 'geo-ip-blocker' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'IP location found successfully!', 'geo-ip-blocker' ),
				'data'    => $result,
			)
		);
	}

	/**
	 * Handle AJAX clear cache.
	 */
	public function ajax_clear_cache() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		// Clear transients used for caching geolocation data.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_geo_ip_blocker_%' OR option_name LIKE '_transient_timeout_geo_ip_blocker_%'" );

		wp_send_json_success( array( 'message' => __( 'Cache cleared successfully!', 'geo-ip-blocker' ) ) );
	}

	/**
	 * Handle AJAX export settings.
	 */
	public function ajax_export_settings() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$settings = $this->get_settings();

		// Remove sensitive data.
		unset( $settings['maxmind_license_key'] );
		unset( $settings['ip2location_api_key'] );

		// Prepare export data.
		$export_data = array(
			'version'      => GEO_IP_BLOCKER_VERSION,
			'export_date'  => current_time( 'mysql' ),
			'settings'     => $settings,
		);

		wp_send_json_success(
			array(
				'message'  => __( 'Settings exported successfully!', 'geo-ip-blocker' ),
				'data'     => wp_json_encode( $export_data, JSON_PRETTY_PRINT ),
				'filename' => 'geo-ip-blocker-settings-' . gmdate( 'Y-m-d-His' ) . '.json',
			)
		);
	}

	/**
	 * Handle AJAX import settings.
	 */
	public function ajax_import_settings() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		if ( ! isset( $_POST['settings_data'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No settings data provided.', 'geo-ip-blocker' ) ) );
		}

		$import_data = json_decode( wp_unslash( $_POST['settings_data'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON format.', 'geo-ip-blocker' ) ) );
		}

		if ( ! isset( $import_data['settings'] ) || ! is_array( $import_data['settings'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings file format.', 'geo-ip-blocker' ) ) );
		}

		// Merge with current settings to preserve sensitive data.
		$current_settings = $this->get_settings();
		$new_settings     = array_merge( $current_settings, $import_data['settings'] );

		// Preserve API keys from current settings.
		$new_settings['maxmind_license_key'] = $current_settings['maxmind_license_key'];
		$new_settings['ip2location_api_key'] = $current_settings['ip2location_api_key'];

		// Sanitize and save.
		$sanitized = $this->sanitize_settings( $new_settings );
		update_option( $this->option_name, $sanitized );

		wp_send_json_success( array( 'message' => __( 'Settings imported successfully!', 'geo-ip-blocker' ) ) );
	}

	/**
	 * Handle AJAX reset settings.
	 */
	public function ajax_reset_settings() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		// Reset to default settings.
		update_option( $this->option_name, $this->default_settings );

		wp_send_json_success( array( 'message' => __( 'Settings reset to defaults successfully!', 'geo-ip-blocker' ) ) );
	}

	/**
	 * Handle AJAX view debug log.
	 */
	public function ajax_view_debug_log() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$log_file = WP_CONTENT_DIR . '/geo-ip-blocker-debug.log';

		if ( ! file_exists( $log_file ) ) {
			wp_send_json_success(
				array(
					'message' => __( 'Debug log is empty.', 'geo-ip-blocker' ),
					'content' => '',
				)
			);
		}

		// Read last 1000 lines.
		$lines = array();
		$file  = new SplFileObject( $log_file, 'r' );
		$file->seek( PHP_INT_MAX );
		$last_line = $file->key();
		$start     = max( 0, $last_line - 1000 );

		$file->seek( $start );
		while ( ! $file->eof() ) {
			$lines[] = $file->fgets();
		}

		$content = implode( '', $lines );

		wp_send_json_success(
			array(
				'message' => __( 'Debug log loaded successfully!', 'geo-ip-blocker' ),
				'content' => $content,
			)
		);
	}

	/**
	 * Handle AJAX clear debug log.
	 */
	public function ajax_clear_debug_log() {
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		$log_file = WP_CONTENT_DIR . '/geo-ip-blocker-debug.log';

		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink
			unlink( $log_file );
		}

		wp_send_json_success( array( 'message' => __( 'Debug log cleared successfully!', 'geo-ip-blocker' ) ) );
	}

	/**
	 * Render Tools tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_tools_tab( $settings ) {
		global $wpdb;
		$geolocation = geo_ip_blocker_get_geolocation();
		$current_ip  = $geolocation ? $geolocation->get_visitor_ip() : '';
		?>
		<div class="geo-ip-blocker-tools-section">
			<!-- Section 1: Test IP Location -->
			<div class="tools-section">
				<h2><?php esc_html_e( '1. Test IP Location', 'geo-ip-blocker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Test the geolocation lookup for any IP address to verify your API configuration and results.', 'geo-ip-blocker' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="test-ip-input"><?php esc_html_e( 'IP Address to Test', 'geo-ip-blocker' ); ?></label>
						</th>
						<td>
							<input type="text" id="test-ip-input" class="regular-text" placeholder="<?php esc_attr_e( 'Enter IP address (e.g., 8.8.8.8)', 'geo-ip-blocker' ); ?>" value="">
							<button type="button" id="test-ip-button" class="button button-secondary">
								<?php esc_html_e( 'Test IP Location', 'geo-ip-blocker' ); ?>
							</button>
							<?php if ( $current_ip ) : ?>
							<button type="button" id="test-current-ip-button" class="button button-secondary" data-ip="<?php echo esc_attr( $current_ip ); ?>">
								<?php printf( esc_html__( 'Test My IP (%s)', 'geo-ip-blocker' ), esc_html( $current_ip ) ); ?>
							</button>
							<?php endif; ?>
							<span class="spinner"></span>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Test Results', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<div id="ip-test-results" class="ip-test-results" style="display: none;">
								<table class="widefat striped">
									<tbody>
										<tr>
											<th><?php esc_html_e( 'IP Address:', 'geo-ip-blocker' ); ?></th>
											<td id="result-ip"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'Country:', 'geo-ip-blocker' ); ?></th>
											<td id="result-country"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'Country Code:', 'geo-ip-blocker' ); ?></th>
											<td id="result-country-code"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'Region:', 'geo-ip-blocker' ); ?></th>
											<td id="result-region"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'City:', 'geo-ip-blocker' ); ?></th>
											<td id="result-city"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'Latitude:', 'geo-ip-blocker' ); ?></th>
											<td id="result-lat"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'Longitude:', 'geo-ip-blocker' ); ?></th>
											<td id="result-lon"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'Timezone:', 'geo-ip-blocker' ); ?></th>
											<td id="result-timezone"></td>
										</tr>
										<tr>
											<th><?php esc_html_e( 'ISP:', 'geo-ip-blocker' ); ?></th>
											<td id="result-isp"></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div id="ip-test-error" class="notice notice-error inline" style="display: none;">
								<p></p>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<hr>

			<!-- Section 2: GeoIP Database Management -->
			<div class="tools-section">
				<h2><?php esc_html_e( '2. GeoIP Database Management', 'geo-ip-blocker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Manage your local GeoIP database, update it manually, and clear cached data.', 'geo-ip-blocker' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Database Information', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<table class="widefat striped">
								<tbody>
									<tr>
										<th><?php esc_html_e( 'Provider:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( ucfirst( $settings['geolocation_provider'] ) ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Local Database:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo $settings['enable_local_database'] ? esc_html__( 'Enabled', 'geo-ip-blocker' ) : esc_html__( 'Disabled', 'geo-ip-blocker' ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Auto-Update:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo $settings['auto_update_database'] ? esc_html__( 'Enabled', 'geo-ip-blocker' ) : esc_html__( 'Disabled', 'geo-ip-blocker' ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Last Update:', 'geo-ip-blocker' ); ?></th>
										<td>
											<?php
											if ( ! empty( $settings['last_db_update'] ) ) {
												echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $settings['last_db_update'] ) ) );
											} else {
												esc_html_e( 'Never', 'geo-ip-blocker' );
											}
											?>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Database Actions', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<button type="button" id="update-geoip-database" class="button button-secondary">
								<?php esc_html_e( 'Update Database Now', 'geo-ip-blocker' ); ?>
							</button>
							<button type="button" id="clear-geoip-cache" class="button button-secondary">
								<?php esc_html_e( 'Clear Cache', 'geo-ip-blocker' ); ?>
							</button>
							<span class="spinner"></span>
							<p class="description"><?php esc_html_e( 'Manually update the GeoIP database or clear cached geolocation results.', 'geo-ip-blocker' ); ?></p>
							<div id="database-action-result" class="notice inline" style="display: none; margin-top: 10px;">
								<p></p>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<hr>

			<!-- Section 3: Import/Export Settings -->
			<div class="tools-section">
				<h2><?php esc_html_e( '3. Import/Export Settings', 'geo-ip-blocker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Backup your plugin settings by exporting to JSON or restore settings from a backup file.', 'geo-ip-blocker' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Export Settings', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<button type="button" id="export-settings-button" class="button button-secondary">
								<?php esc_html_e( 'Export Settings to JSON', 'geo-ip-blocker' ); ?>
							</button>
							<p class="description"><?php esc_html_e( 'Download all plugin settings as a JSON file for backup or migration.', 'geo-ip-blocker' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="import-settings-file"><?php esc_html_e( 'Import Settings', 'geo-ip-blocker' ); ?></label>
						</th>
						<td>
							<input type="file" id="import-settings-file" accept=".json" class="regular-text">
							<button type="button" id="import-settings-button" class="button button-secondary">
								<?php esc_html_e( 'Import Settings', 'geo-ip-blocker' ); ?>
							</button>
							<span class="spinner"></span>
							<p class="description"><?php esc_html_e( 'Upload a JSON backup file to restore settings. This will overwrite current settings.', 'geo-ip-blocker' ); ?></p>
							<div id="import-result" class="notice inline" style="display: none; margin-top: 10px;">
								<p></p>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Reset Settings', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<button type="button" id="reset-settings-button" class="button button-secondary">
								<?php esc_html_e( 'Reset to Defaults', 'geo-ip-blocker' ); ?>
							</button>
							<span class="spinner"></span>
							<p class="description"><?php esc_html_e( 'Reset all plugin settings to their default values. This action cannot be undone.', 'geo-ip-blocker' ); ?></p>
							<div id="reset-result" class="notice inline" style="display: none; margin-top: 10px;">
								<p></p>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<hr>

			<!-- Section 4: Country List Bulk Management -->
			<div class="tools-section">
				<h2><?php esc_html_e( '4. Country List Bulk Management', 'geo-ip-blocker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Quickly select or deselect groups of countries using predefined regional presets.', 'geo-ip-blocker' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Search Countries', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<input type="text" id="country-search-input" class="regular-text" placeholder="<?php esc_attr_e( 'Search for countries...', 'geo-ip-blocker' ); ?>">
							<button type="button" id="country-search-button" class="button button-secondary">
								<?php esc_html_e( 'Search', 'geo-ip-blocker' ); ?>
							</button>
							<div id="country-search-results" class="country-search-results" style="margin-top: 10px; display: none;">
								<!-- Results will be populated via JavaScript -->
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Regional Presets', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<p class="description"><?php esc_html_e( 'Quickly select multiple countries by region:', 'geo-ip-blocker' ); ?></p>
							<div class="regional-presets" style="margin-top: 10px;">
								<button type="button" class="button button-secondary select-region-preset" data-region="eu">
									<?php esc_html_e( 'European Union (27)', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="europe">
									<?php esc_html_e( 'Europe (All)', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="north-america">
									<?php esc_html_e( 'North America', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="south-america">
									<?php esc_html_e( 'South America', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="asia">
									<?php esc_html_e( 'Asia', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="africa">
									<?php esc_html_e( 'Africa', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="oceania">
									<?php esc_html_e( 'Oceania', 'geo-ip-blocker' ); ?>
								</button>
								<button type="button" class="button button-secondary select-region-preset" data-region="middle-east">
									<?php esc_html_e( 'Middle East', 'geo-ip-blocker' ); ?>
								</button>
							</div>
							<p class="description" style="margin-top: 10px;">
								<?php esc_html_e( 'Note: These presets will be applied to your current blocking mode (whitelist or blacklist) when you navigate to the Countries tab.', 'geo-ip-blocker' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<hr>

			<!-- Section 5: System Info and Debug -->
			<div class="tools-section">
				<h2><?php esc_html_e( '5. System Information & Debug', 'geo-ip-blocker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'View system information and enable debug mode for troubleshooting.', 'geo-ip-blocker' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'System Information', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<table class="widefat striped">
								<tbody>
									<tr>
										<th><?php esc_html_e( 'PHP Version:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( phpversion() ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'WordPress Version:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'WooCommerce Version:', 'geo-ip-blocker' ); ?></th>
										<td>
											<?php
											if ( class_exists( 'WooCommerce' ) ) {
												echo esc_html( WC()->version );
											} else {
												esc_html_e( 'Not installed', 'geo-ip-blocker' );
											}
											?>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Plugin Version:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( GEO_IP_BLOCKER_VERSION ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Active Geolocation Provider:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( ucfirst( $settings['geolocation_provider'] ) ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Database Status:', 'geo-ip-blocker' ); ?></th>
										<td>
											<?php
											$table_name = $wpdb->prefix . 'geo_ip_blocker_logs';
											$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
											echo $table_exists ? esc_html__( 'Tables created', 'geo-ip-blocker' ) : esc_html__( 'Tables missing', 'geo-ip-blocker' );
											?>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Memory Limit:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Max Upload Size:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Server IP:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'N/A' ); ?></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Your IP:', 'geo-ip-blocker' ); ?></th>
										<td><?php echo esc_html( $current_ip ? $current_ip : 'N/A' ); ?></td>
									</tr>
								</tbody>
							</table>
							<button type="button" id="copy-system-info" class="button button-secondary" style="margin-top: 10px;">
								<?php esc_html_e( 'Copy System Info to Clipboard', 'geo-ip-blocker' ); ?>
							</button>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Debug Mode', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="settings[debug_mode]" id="debug_mode" value="1" <?php checked( ! empty( $settings['debug_mode'] ), true ); ?>>
								<?php esc_html_e( 'Enable debug mode', 'geo-ip-blocker' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'When enabled, detailed debugging information will be logged to help troubleshoot issues.', 'geo-ip-blocker' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Debug Log', 'geo-ip-blocker' ); ?>
						</th>
						<td>
							<button type="button" id="view-debug-log" class="button button-secondary">
								<?php esc_html_e( 'View Debug Log', 'geo-ip-blocker' ); ?>
							</button>
							<button type="button" id="clear-debug-log" class="button button-secondary">
								<?php esc_html_e( 'Clear Debug Log', 'geo-ip-blocker' ); ?>
							</button>
							<span class="spinner"></span>
							<div id="debug-log-viewer" class="debug-log-viewer" style="display: none; margin-top: 10px;">
								<textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;"></textarea>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}
}
