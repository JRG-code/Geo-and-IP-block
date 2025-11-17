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

			// Logging.
			'enable_logging'       => true,
			'max_logs'             => 10000,
			'log_retention_days'   => 90,
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
		$sanitized['blocking_mode']        = in_array( $input['blocking_mode'], array( 'whitelist', 'blacklist' ), true ) ? $input['blocking_mode'] : 'blacklist';
		$sanitized['block_action']         = in_array( $input['block_action'], array( 'message', 'redirect', 'page', '403' ), true ) ? $input['block_action'] : 'message';
		$sanitized['block_message']        = wp_kses_post( $input['block_message'] );
		$sanitized['redirect_url']         = esc_url_raw( $input['redirect_url'] );
		$sanitized['block_page_id']        = absint( $input['block_page_id'] );
		$sanitized['exempt_administrators'] = ! empty( $input['exempt_administrators'] );
		$sanitized['exempt_logged_in']     = ! empty( $input['exempt_logged_in'] );
		$sanitized['woocommerce_mode']     = in_array( $input['woocommerce_mode'], array( 'all', 'woo_only', 'checkout_only', 'none' ), true ) ? $input['woocommerce_mode'] : 'all';

		// API Configuration.
		$sanitized['geolocation_provider'] = in_array( $input['geolocation_provider'], array( 'maxmind', 'ip2location', 'ip-api' ), true ) ? $input['geolocation_provider'] : 'ip-api';
		$sanitized['maxmind_license_key']  = sanitize_text_field( $input['maxmind_license_key'] );
		$sanitized['ip2location_api_key']  = sanitize_text_field( $input['ip2location_api_key'] );
		$sanitized['enable_local_database'] = ! empty( $input['enable_local_database'] );
		$sanitized['auto_update_database'] = ! empty( $input['auto_update_database'] );
		$sanitized['last_db_update']       = isset( $input['last_db_update'] ) ? sanitize_text_field( $input['last_db_update'] ) : '';

		// Country Blocking.
		$sanitized['blocked_countries']    = is_array( $input['blocked_countries'] ) ? array_map( 'sanitize_text_field', $input['blocked_countries'] ) : array();
		$sanitized['allowed_countries']    = is_array( $input['allowed_countries'] ) ? array_map( 'sanitize_text_field', $input['allowed_countries'] ) : array();
		$sanitized['blocked_regions']      = is_array( $input['blocked_regions'] ) ? array_map( 'sanitize_text_field', $input['blocked_regions'] ) : array();

		// Logging.
		$sanitized['enable_logging']       = ! empty( $input['enable_logging'] );
		$sanitized['max_logs']             = absint( $input['max_logs'] );
		$sanitized['log_retention_days']   = absint( $input['log_retention_days'] );

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
		$allowed_tabs = array( 'general', 'api', 'countries', 'logging' );

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
				<a href="?page=geo-ip-blocker-settings&tab=logging" class="nav-tab <?php echo 'logging' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Logging', 'geo-ip-blocker' ); ?>
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
						case 'logging':
							$this->render_logging_tab( $settings );
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
		$blocking_mode    = $settings['blocking_mode'];
		$blocked_countries = $settings['blocked_countries'];
		$allowed_countries = $settings['allowed_countries'];
		?>
		<div class="geo-ip-blocker-countries-section">
			<p class="description">
				<?php
				if ( 'blacklist' === $blocking_mode ) {
					esc_html_e( 'Select countries to block. Visitors from these countries will not be able to access your site.', 'geo-ip-blocker' );
				} else {
					esc_html_e( 'Select countries to allow. Only visitors from these countries will be able to access your site.', 'geo-ip-blocker' );
				}
				?>
			</p>

			<table class="form-table">
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
		// Verify nonce.
		check_ajax_referer( 'geo_ip_blocker_settings_nonce', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'geo-ip-blocker' ) ) );
		}

		// Get and sanitize settings.
		$settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sanitized = $this->sanitize_settings( $settings );

		// Save settings.
		update_option( $this->option_name, $sanitized );

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
}
