<?php
/**
 * Security Tests
 *
 * @package GeoIPBlocker
 */

/**
 * Class Test_Security
 *
 * Tests for Security functionality.
 */
class Test_Security extends WP_UnitTestCase {

	/**
	 * Security instance.
	 *
	 * @var Geo_IP_Blocker_Security
	 */
	private $security;

	/**
	 * Set up test environment.
	 */
	public function setUp() {
		parent::setUp();
		$this->security = geo_ip_blocker_get_security();
	}

	/**
	 * Test IP validation.
	 */
	public function test_validate_ip() {
		// Valid IPs.
		$this->assertNotFalse( $this->security->validate_ip( '192.168.1.1' ) );
		$this->assertNotFalse( $this->security->validate_ip( '8.8.8.8' ) );
		$this->assertNotFalse( $this->security->validate_ip( '2001:db8::1' ) );

		// Invalid IPs.
		$this->assertFalse( $this->security->validate_ip( '999.999.999.999' ) );
		$this->assertFalse( $this->security->validate_ip( 'invalid' ) );
		$this->assertFalse( $this->security->validate_ip( '' ) );
	}

	/**
	 * Test CIDR validation.
	 */
	public function test_validate_cidr() {
		// Valid CIDR.
		$this->assertNotFalse( $this->security->validate_ip( '192.168.1.0/24' ) );
		$this->assertNotFalse( $this->security->validate_ip( '10.0.0.0/8' ) );

		// Invalid CIDR.
		$this->assertFalse( $this->security->validate_ip( '192.168.1.0/33' ) );
		$this->assertFalse( $this->security->validate_ip( '192.168.1.0/-1' ) );
	}

	/**
	 * Test IP range validation.
	 */
	public function test_validate_ip_range() {
		// Valid ranges.
		$this->assertNotFalse( $this->security->validate_ip( '192.168.1.1-192.168.1.50' ) );

		// Invalid ranges.
		$this->assertFalse( $this->security->validate_ip( '192.168.1.1-invalid' ) );
		$this->assertFalse( $this->security->validate_ip( '192.168.1.1-' ) );
	}

	/**
	 * Test country code validation.
	 */
	public function test_validate_country_code() {
		// Valid codes.
		$this->assertEquals( 'US', $this->security->validate_country_code( 'us' ) );
		$this->assertEquals( 'BR', $this->security->validate_country_code( 'BR' ) );
		$this->assertEquals( 'GB', $this->security->validate_country_code( 'gb' ) );

		// Invalid codes.
		$this->assertFalse( $this->security->validate_country_code( 'USA' ) );
		$this->assertFalse( $this->security->validate_country_code( '1' ) );
		$this->assertFalse( $this->security->validate_country_code( '' ) );
		$this->assertFalse( $this->security->validate_country_code( '12' ) );
	}

	/**
	 * Test country codes array validation.
	 */
	public function test_validate_country_codes() {
		$input = array( 'us', 'BR', 'gb', 'invalid', '123', 'FR' );
		$result = $this->security->validate_country_codes( $input );

		// Should only contain valid codes.
		$this->assertContains( 'US', $result );
		$this->assertContains( 'BR', $result );
		$this->assertContains( 'GB', $result );
		$this->assertContains( 'FR', $result );

		// Should not contain invalid codes.
		$this->assertNotContains( 'invalid', $result );
		$this->assertNotContains( '123', $result );

		// Should have 4 valid codes.
		$this->assertEquals( 4, count( $result ) );
	}

	/**
	 * Test IP list validation.
	 */
	public function test_validate_ip_list() {
		$input = array(
			'192.168.1.1',
			'8.8.8.8',
			'invalid',
			'999.999.999.999',
			'10.0.0.0/24',
		);

		$result = $this->security->validate_ip_list( $input );

		// Should contain valid IPs.
		$this->assertContains( '192.168.1.1', $result );
		$this->assertContains( '8.8.8.8', $result );
		$this->assertContains( '10.0.0.0/24', $result );

		// Should not contain invalid IPs.
		$this->assertNotContains( 'invalid', $result );
		$this->assertNotContains( '999.999.999.999', $result );

		// Should have 3 valid IPs.
		$this->assertEquals( 3, count( $result ) );
	}

	/**
	 * Test URL sanitization.
	 */
	public function test_sanitize_url() {
		// Valid URL.
		$url = 'https://example.com/path?param=value';
		$sanitized = $this->security->sanitize_url( $url );
		$this->assertEquals( $url, $sanitized );

		// URL with dangerous characters should be escaped.
		$dangerous_url = 'javascript:alert(1)';
		$sanitized = $this->security->sanitize_url( $dangerous_url );
		$this->assertNotEquals( $dangerous_url, $sanitized );
	}

	/**
	 * Test text sanitization.
	 */
	public function test_sanitize_text() {
		// Normal text.
		$text = 'Normal text';
		$this->assertEquals( $text, $this->security->sanitize_text( $text ) );

		// Text with HTML should be stripped.
		$html = '<script>alert(1)</script>Hello';
		$sanitized = $this->security->sanitize_text( $html );
		$this->assertNotContains( '<script>', $sanitized );
	}

	/**
	 * Test HTML sanitization.
	 */
	public function test_sanitize_html() {
		// Allowed HTML.
		$html = '<p>Hello <strong>World</strong></p>';
		$sanitized = $this->security->sanitize_html( $html );
		$this->assertContains( '<p>', $sanitized );
		$this->assertContains( '<strong>', $sanitized );

		// Dangerous HTML should be stripped.
		$dangerous = '<p>Hello</p><script>alert(1)</script>';
		$sanitized = $this->security->sanitize_html( $dangerous );
		$this->assertNotContains( '<script>', $sanitized );
	}

	/**
	 * Test boolean sanitization.
	 */
	public function test_sanitize_boolean() {
		// Truthy values.
		$this->assertTrue( $this->security->sanitize_boolean( true ) );
		$this->assertTrue( $this->security->sanitize_boolean( 1 ) );
		$this->assertTrue( $this->security->sanitize_boolean( '1' ) );
		$this->assertTrue( $this->security->sanitize_boolean( 'yes' ) );

		// Falsy values.
		$this->assertFalse( $this->security->sanitize_boolean( false ) );
		$this->assertFalse( $this->security->sanitize_boolean( 0 ) );
		$this->assertFalse( $this->security->sanitize_boolean( '' ) );
		$this->assertFalse( $this->security->sanitize_boolean( null ) );
	}

	/**
	 * Test integer sanitization.
	 */
	public function test_sanitize_integer() {
		// Normal integer.
		$this->assertEquals( 42, $this->security->sanitize_integer( 42 ) );
		$this->assertEquals( 100, $this->security->sanitize_integer( '100' ) );

		// With min/max boundaries.
		$this->assertEquals( 10, $this->security->sanitize_integer( 5, 10, 100 ) );
		$this->assertEquals( 100, $this->security->sanitize_integer( 150, 10, 100 ) );
		$this->assertEquals( 50, $this->security->sanitize_integer( 50, 10, 100 ) );

		// Negative numbers should become 0 (default min).
		$this->assertEquals( 0, $this->security->sanitize_integer( -10 ) );
	}

	/**
	 * Test local IP detection.
	 */
	public function test_is_local_ip() {
		// Local/private IPs.
		$this->assertTrue( $this->security->is_local_ip( '127.0.0.1' ) );
		$this->assertTrue( $this->security->is_local_ip( '192.168.1.1' ) );
		$this->assertTrue( $this->security->is_local_ip( '10.0.0.1' ) );
		$this->assertTrue( $this->security->is_local_ip( '172.16.0.1' ) );

		// Public IPs.
		$this->assertFalse( $this->security->is_local_ip( '8.8.8.8' ) );
		$this->assertFalse( $this->security->is_local_ip( '1.1.1.1' ) );
	}

	/**
	 * Test token generation.
	 */
	public function test_generate_token() {
		// Default length (32).
		$token = $this->security->generate_token();
		$this->assertEquals( 32, strlen( $token ) );

		// Custom length.
		$token = $this->security->generate_token( 64 );
		$this->assertEquals( 64, strlen( $token ) );

		// Should be unique.
		$token1 = $this->security->generate_token();
		$token2 = $this->security->generate_token();
		$this->assertNotEquals( $token1, $token2 );
	}

	/**
	 * Test data hashing.
	 */
	public function test_hash_data() {
		$data = 'test data';
		$hash = $this->security->hash_data( $data );

		// Should return a hash.
		$this->assertNotEmpty( $hash );

		// Same data should produce same hash.
		$hash2 = $this->security->hash_data( $data );
		$this->assertEquals( $hash, $hash2 );

		// Different data should produce different hash.
		$hash3 = $this->security->hash_data( 'different data' );
		$this->assertNotEquals( $hash, $hash3 );
	}

	/**
	 * Test settings validation.
	 */
	public function test_validate_settings() {
		$input = array(
			'enabled'              => '1',
			'blocking_mode'        => 'blacklist',
			'block_action'         => 'message',
			'block_message'        => '<p>Test <script>alert(1)</script></p>',
			'redirect_url'         => 'https://example.com',
			'block_page_id'        => '42',
			'exempt_administrators' => true,
			'blocked_countries'    => array( 'us', 'BR' ),
			'enable_logging'       => true,
			'max_logs'             => '5000',
			'log_retention_days'   => '60',
		);

		$output = $this->security->validate_settings( $input );

		// Boolean fields.
		$this->assertTrue( $output['enabled'] );
		$this->assertTrue( $output['exempt_administrators'] );
		$this->assertTrue( $output['enable_logging'] );

		// Enum fields.
		$this->assertEquals( 'blacklist', $output['blocking_mode'] );
		$this->assertEquals( 'message', $output['block_action'] );

		// Sanitized HTML.
		$this->assertNotContains( '<script>', $output['block_message'] );

		// URL.
		$this->assertEquals( 'https://example.com', $output['redirect_url'] );

		// Integer.
		$this->assertEquals( 42, $output['block_page_id'] );
		$this->assertEquals( 5000, $output['max_logs'] );
		$this->assertEquals( 60, $output['log_retention_days'] );

		// Country codes.
		$this->assertContains( 'US', $output['blocked_countries'] );
		$this->assertContains( 'BR', $output['blocked_countries'] );
	}

	/**
	 * Test permission check.
	 */
	public function test_check_permission() {
		// Create admin user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Admin should have permission.
		$this->assertTrue( $this->security->check_permission( 'manage_options' ) );

		// Create subscriber user.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		// Subscriber should not have permission.
		$this->assertFalse( $this->security->check_permission( 'manage_options' ) );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown() {
		parent::tearDown();
	}
}
