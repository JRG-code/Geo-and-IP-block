<?php
/**
 * Integration Tests
 *
 * @package GeoIPBlocker
 */

/**
 * Class Test_Integration
 *
 * Tests for complete plugin workflows.
 */
class Test_Integration extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 */
	public function setUp() {
		parent::setUp();

		// Reset settings.
		delete_option( 'geo_ip_blocker_settings' );
	}

	/**
	 * Test complete blocking flow for IP blacklist.
	 */
	public function test_complete_ip_blocking_flow() {
		$test_ip = '1.2.3.4';

		// Configure settings for IP blocking.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'       => true,
				'blocking_mode' => 'blacklist',
				'block_action'  => 'message',
			)
		);

		// Add IP to blacklist.
		$ip_manager = geo_ip_blocker_get_ip_manager();
		$ip_manager->add_ip_to_list( $test_ip, 'blacklist' );

		// Simulate request from blocked IP.
		$_SERVER['REMOTE_ADDR'] = $test_ip;

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// Verify IP should be blocked.
		$this->assertTrue( $blocker->should_block_ip( $test_ip ) );
	}

	/**
	 * Test complete blocking flow for country blacklist.
	 */
	public function test_complete_country_blocking_flow() {
		// Configure settings for country blocking.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'           => true,
				'blocking_mode'     => 'blacklist',
				'blocked_countries' => array( 'US', 'CN' ),
			)
		);

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// Test with blocked country.
		$should_block = $blocker->should_block_country( 'US' );
		$this->assertTrue( $should_block );

		// Test with allowed country.
		$should_block = $blocker->should_block_country( 'BR' );
		$this->assertFalse( $should_block );
	}

	/**
	 * Test whitelist mode.
	 */
	public function test_whitelist_mode() {
		// Configure whitelist mode.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'           => true,
				'blocking_mode'     => 'whitelist',
				'allowed_countries' => array( 'US', 'GB' ),
			)
		);

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// Countries in whitelist should be allowed.
		$this->assertFalse( $blocker->should_block_country( 'US' ) );
		$this->assertFalse( $blocker->should_block_country( 'GB' ) );

		// Countries not in whitelist should be blocked.
		$this->assertTrue( $blocker->should_block_country( 'BR' ) );
		$this->assertTrue( $blocker->should_block_country( 'CN' ) );
	}

	/**
	 * Test administrator exemption.
	 */
	public function test_administrator_exemption() {
		// Create admin user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Configure blocking with admin exemption.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'               => true,
				'blocking_mode'         => 'blacklist',
				'blocked_countries'     => array( 'US' ),
				'exempt_administrators' => true,
			)
		);

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// Admin should not be blocked even from blocked country.
		$this->assertFalse( $blocker->should_block_country( 'US' ) );
	}

	/**
	 * Test logging functionality.
	 */
	public function test_logging_functionality() {
		// Enable logging.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enable_logging' => true,
			)
		);

		// Get logger instance.
		$logger = geo_ip_blocker_get_logger();

		// Add log entry.
		$log_data = array(
			'ip_address'   => '5.6.7.8',
			'country_code' => 'US',
			'blocked_url'  => 'https://example.com/test',
			'block_reason' => 'Country blocked',
		);

		$result = $logger->log_blocked_access( $log_data );
		$this->assertTrue( $result );

		// Retrieve logs.
		$logs = $logger->get_logs( array( 'limit' => 10 ) );
		$this->assertNotEmpty( $logs );

		// Find our log entry.
		$found = false;
		foreach ( $logs as $log ) {
			if ( $log['ip_address'] === '5.6.7.8' ) {
				$found = true;
				$this->assertEquals( 'US', $log['country_code'] );
				$this->assertEquals( 'Country blocked', $log['block_reason'] );
			}
		}
		$this->assertTrue( $found, 'Log entry not found' );
	}

	/**
	 * Test CIDR range blocking.
	 */
	public function test_cidr_range_blocking() {
		// Add CIDR range to blacklist.
		$ip_manager = geo_ip_blocker_get_ip_manager();
		$ip_manager->add_ip_to_list( '192.168.1.0/24', 'blacklist' );

		// Configure settings.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'       => true,
				'blocking_mode' => 'blacklist',
			)
		);

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// IPs in range should be blocked.
		$this->assertTrue( $blocker->should_block_ip( '192.168.1.50' ) );
		$this->assertTrue( $blocker->should_block_ip( '192.168.1.1' ) );
		$this->assertTrue( $blocker->should_block_ip( '192.168.1.254' ) );

		// IPs outside range should not be blocked.
		$this->assertFalse( $blocker->should_block_ip( '192.168.2.1' ) );
	}

	/**
	 * Test IP whitelist priority over country blocking.
	 */
	public function test_ip_whitelist_priority() {
		$test_ip = '9.10.11.12';

		// Configure to block country US.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'           => true,
				'blocking_mode'     => 'blacklist',
				'blocked_countries' => array( 'US' ),
			)
		);

		// Add specific IP to whitelist.
		$ip_manager = geo_ip_blocker_get_ip_manager();
		$ip_manager->add_ip_to_list( $test_ip, 'whitelist' );

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// IP should be whitelisted despite country blocking.
		$this->assertFalse( $blocker->should_block_ip( $test_ip ) );
	}

	/**
	 * Test cache functionality.
	 */
	public function test_cache_functionality() {
		$cache = geo_ip_blocker_get_cache();

		// Test basic cache operations.
		$test_key   = 'test_key';
		$test_value = array( 'data' => 'test' );

		// Set cache.
		$result = $cache->set( $test_key, $test_value, 3600 );
		$this->assertTrue( $result );

		// Get from cache.
		$cached_value = $cache->get( $test_key );
		$this->assertEquals( $test_value, $cached_value );

		// Delete from cache.
		$result = $cache->delete( $test_key );
		$this->assertTrue( $result );

		// Should no longer be in cache.
		$cached_value = $cache->get( $test_key );
		$this->assertFalse( $cached_value );
	}

	/**
	 * Test geolocation caching.
	 */
	public function test_geolocation_caching() {
		$cache = geo_ip_blocker_get_cache();
		$test_ip = '13.14.15.16';

		$location_data = array(
			'country_code' => 'US',
			'country_name' => 'United States',
			'region'       => 'California',
			'city'         => 'San Francisco',
		);

		// Cache location data.
		$cache->cache_location( $test_ip, $location_data );

		// Retrieve from cache.
		$cached_data = $cache->get_location( $test_ip );

		$this->assertNotFalse( $cached_data );
		$this->assertEquals( 'US', $cached_data['country_code'] );
		$this->assertEquals( 'United States', $cached_data['country_name'] );
	}

	/**
	 * Test rate limiting.
	 */
	public function test_rate_limiting() {
		$rate_limiter = geo_ip_blocker_get_rate_limiter();
		$identifier   = 'test_user';

		// Set custom low limit for testing.
		$rate_limiter->set_limit( 'test_action', 3, 60 );

		// First 3 attempts should be allowed.
		$this->assertTrue( $rate_limiter->check_rate_limit( 'test_action', $identifier ) );
		$this->assertTrue( $rate_limiter->check_rate_limit( 'test_action', $identifier ) );
		$this->assertTrue( $rate_limiter->check_rate_limit( 'test_action', $identifier ) );

		// 4th attempt should be rate limited.
		$this->assertFalse( $rate_limiter->check_rate_limit( 'test_action', $identifier ) );

		// Check is_rate_limited.
		$this->assertTrue( $rate_limiter->is_rate_limited( 'test_action', $identifier ) );

		// Reset limit.
		$rate_limiter->reset_limit( 'test_action', $identifier );

		// Should be allowed again.
		$this->assertTrue( $rate_limiter->check_rate_limit( 'test_action', $identifier ) );
	}

	/**
	 * Test database operations.
	 */
	public function test_database_operations() {
		$database = new Geo_IP_Blocker_Database();

		// Add rule.
		$result = $database->add_rule(
			array(
				'rule_type' => 'country',
				'value'     => 'US',
				'action'    => 'block',
				'priority'  => 10,
			)
		);
		$this->assertNotFalse( $result );

		// Get rules.
		$rules = $database->get_rules(
			array(
				'rule_type' => 'country',
			)
		);
		$this->assertNotEmpty( $rules );

		// Verify rule exists.
		$found = false;
		foreach ( $rules as $rule ) {
			if ( $rule->value === 'US' && $rule->rule_type === 'country' ) {
				$found   = true;
				$rule_id = $rule->id;
			}
		}
		$this->assertTrue( $found );

		// Delete rule.
		if ( isset( $rule_id ) ) {
			$result = $database->delete_rule( $rule_id );
			$this->assertNotFalse( $result );
		}
	}

	/**
	 * Test plugin disabled state.
	 */
	public function test_plugin_disabled() {
		// Disable plugin.
		update_option(
			'geo_ip_blocker_settings',
			array(
				'enabled'           => false,
				'blocked_countries' => array( 'US' ),
			)
		);

		// Get blocker instance.
		$blocker = geo_ip_blocker_get_blocker();

		// Nothing should be blocked when disabled.
		$this->assertFalse( $blocker->should_block_country( 'US' ) );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown() {
		// Clean up.
		delete_option( 'geo_ip_blocker_settings' );

		// Clear IP lists.
		$ip_manager = geo_ip_blocker_get_ip_manager();
		$ip_manager->clear_list( 'blacklist' );
		$ip_manager->clear_list( 'whitelist' );

		// Clear cache.
		$cache = geo_ip_blocker_get_cache();
		$cache->flush();

		parent::tearDown();
	}
}
