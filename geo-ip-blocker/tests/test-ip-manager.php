<?php
/**
 * IP Manager Tests
 *
 * @package GeoIPBlocker
 */

/**
 * Class Test_IP_Manager
 *
 * Tests for IP Manager functionality.
 */
class Test_IP_Manager extends WP_UnitTestCase {

	/**
	 * IP Manager instance.
	 *
	 * @var Geo_IP_Blocker_IP_Manager
	 */
	private $manager;

	/**
	 * Set up test environment.
	 */
	public function setUp() {
		parent::setUp();
		$this->manager = geo_ip_blocker_get_ip_manager();
	}

	/**
	 * Test IPv4 validation.
	 */
	public function test_validate_ipv4() {
		// Valid IPv4 addresses.
		$this->assertTrue( $this->manager->validate_ip( '192.168.1.1' ) !== false );
		$this->assertTrue( $this->manager->validate_ip( '8.8.8.8' ) !== false );
		$this->assertTrue( $this->manager->validate_ip( '127.0.0.1' ) !== false );

		// Invalid IPv4 addresses.
		$this->assertFalse( $this->manager->validate_ip( '999.999.999.999' ) );
		$this->assertFalse( $this->manager->validate_ip( '192.168.1.256' ) );
		$this->assertFalse( $this->manager->validate_ip( 'invalid' ) );
	}

	/**
	 * Test IPv6 validation.
	 */
	public function test_validate_ipv6() {
		// Valid IPv6 addresses.
		$this->assertTrue( $this->manager->validate_ip( '2001:0db8:85a3:0000:0000:8a2e:0370:7334' ) !== false );
		$this->assertTrue( $this->manager->validate_ip( '::1' ) !== false );
		$this->assertTrue( $this->manager->validate_ip( '2001:db8::1' ) !== false );

		// Invalid IPv6 addresses.
		$this->assertFalse( $this->manager->validate_ip( 'gggg::1' ) );
	}

	/**
	 * Test CIDR notation validation.
	 */
	public function test_validate_cidr() {
		// Valid CIDR notation.
		$this->assertTrue( $this->manager->validate_ip( '192.168.1.0/24' ) !== false );
		$this->assertTrue( $this->manager->validate_ip( '10.0.0.0/8' ) !== false );

		// Invalid CIDR notation.
		$this->assertFalse( $this->manager->validate_ip( '192.168.1.0/33' ) );
		$this->assertFalse( $this->manager->validate_ip( '192.168.1.0/-1' ) );
		$this->assertFalse( $this->manager->validate_ip( '999.999.999.999/24' ) );
	}

	/**
	 * Test IP range validation.
	 */
	public function test_validate_ip_range() {
		// Valid IP ranges.
		$this->assertTrue( $this->manager->validate_ip( '192.168.1.1-192.168.1.50' ) !== false );
		$this->assertTrue( $this->manager->validate_ip( '10.0.0.1-10.0.0.255' ) !== false );

		// Invalid IP ranges.
		$this->assertFalse( $this->manager->validate_ip( '192.168.1.1-999.999.999.999' ) );
		$this->assertFalse( $this->manager->validate_ip( '192.168.1.1-' ) );
	}

	/**
	 * Test IP in CIDR range.
	 */
	public function test_ip_in_cidr_range() {
		// IP should be in range.
		$this->assertTrue( $this->manager->is_ip_in_range( '192.168.1.100', '192.168.1.0/24' ) );
		$this->assertTrue( $this->manager->is_ip_in_range( '192.168.1.1', '192.168.1.0/24' ) );
		$this->assertTrue( $this->manager->is_ip_in_range( '192.168.1.254', '192.168.1.0/24' ) );

		// IP should not be in range.
		$this->assertFalse( $this->manager->is_ip_in_range( '192.168.2.1', '192.168.1.0/24' ) );
		$this->assertFalse( $this->manager->is_ip_in_range( '10.0.0.1', '192.168.1.0/24' ) );
	}

	/**
	 * Test IP in hyphen range.
	 */
	public function test_ip_in_hyphen_range() {
		// IP should be in range.
		$this->assertTrue( $this->manager->is_ip_in_range( '192.168.1.25', '192.168.1.1-192.168.1.50' ) );
		$this->assertTrue( $this->manager->is_ip_in_range( '192.168.1.1', '192.168.1.1-192.168.1.50' ) );
		$this->assertTrue( $this->manager->is_ip_in_range( '192.168.1.50', '192.168.1.1-192.168.1.50' ) );

		// IP should not be in range.
		$this->assertFalse( $this->manager->is_ip_in_range( '192.168.1.51', '192.168.1.1-192.168.1.50' ) );
		$this->assertFalse( $this->manager->is_ip_in_range( '192.168.1.0', '192.168.1.1-192.168.1.50' ) );
	}

	/**
	 * Test adding IP to blacklist.
	 */
	public function test_add_ip_to_blacklist() {
		$ip = '1.2.3.4';

		// Add IP to blacklist.
		$result = $this->manager->add_ip_to_list( $ip, 'blacklist' );
		$this->assertTrue( $result );

		// Verify IP is in blacklist.
		$blacklist = $this->manager->get_list( 'blacklist' );
		$this->assertContains( $ip, $blacklist );
	}

	/**
	 * Test adding IP to whitelist.
	 */
	public function test_add_ip_to_whitelist() {
		$ip = '5.6.7.8';

		// Add IP to whitelist.
		$result = $this->manager->add_ip_to_list( $ip, 'whitelist' );
		$this->assertTrue( $result );

		// Verify IP is in whitelist.
		$whitelist = $this->manager->get_list( 'whitelist' );
		$this->assertContains( $ip, $whitelist );
	}

	/**
	 * Test removing IP from list.
	 */
	public function test_remove_ip_from_list() {
		$ip = '9.10.11.12';

		// Add IP to blacklist.
		$this->manager->add_ip_to_list( $ip, 'blacklist' );

		// Verify IP is in blacklist.
		$blacklist = $this->manager->get_list( 'blacklist' );
		$this->assertContains( $ip, $blacklist );

		// Remove IP from blacklist.
		$result = $this->manager->remove_ip_from_list( $ip, 'blacklist' );
		$this->assertTrue( $result );

		// Verify IP is no longer in blacklist.
		$blacklist = $this->manager->get_list( 'blacklist' );
		$this->assertNotContains( $ip, $blacklist );
	}

	/**
	 * Test checking if IP is blocked.
	 */
	public function test_is_ip_blocked() {
		$ip = '13.14.15.16';

		// Initially should not be blocked.
		$this->assertFalse( $this->manager->is_ip_blocked( $ip ) );

		// Add to blacklist.
		$this->manager->add_ip_to_list( $ip, 'blacklist' );

		// Should now be blocked.
		$this->assertTrue( $this->manager->is_ip_blocked( $ip ) );
	}

	/**
	 * Test checking if IP is whitelisted.
	 */
	public function test_is_ip_whitelisted() {
		$ip = '17.18.19.20';

		// Initially should not be whitelisted.
		$this->assertFalse( $this->manager->is_ip_whitelisted( $ip ) );

		// Add to whitelist.
		$this->manager->add_ip_to_list( $ip, 'whitelist' );

		// Should now be whitelisted.
		$this->assertTrue( $this->manager->is_ip_whitelisted( $ip ) );
	}

	/**
	 * Test clearing list.
	 */
	public function test_clear_list() {
		// Add multiple IPs to blacklist.
		$this->manager->add_ip_to_list( '21.22.23.24', 'blacklist' );
		$this->manager->add_ip_to_list( '25.26.27.28', 'blacklist' );

		// Verify blacklist is not empty.
		$blacklist = $this->manager->get_list( 'blacklist' );
		$this->assertNotEmpty( $blacklist );

		// Clear blacklist.
		$this->manager->clear_list( 'blacklist' );

		// Verify blacklist is empty.
		$blacklist = $this->manager->get_list( 'blacklist' );
		$this->assertEmpty( $blacklist );
	}

	/**
	 * Test IP range matching with CIDR in list.
	 */
	public function test_ip_blocked_by_cidr_range() {
		// Add CIDR range to blacklist.
		$this->manager->add_ip_to_list( '192.168.1.0/24', 'blacklist' );

		// IPs in range should be blocked.
		$this->assertTrue( $this->manager->is_ip_blocked( '192.168.1.50' ) );
		$this->assertTrue( $this->manager->is_ip_blocked( '192.168.1.1' ) );
		$this->assertTrue( $this->manager->is_ip_blocked( '192.168.1.254' ) );

		// IPs outside range should not be blocked.
		$this->assertFalse( $this->manager->is_ip_blocked( '192.168.2.1' ) );
	}

	/**
	 * Test duplicate IP handling.
	 */
	public function test_duplicate_ip_handling() {
		$ip = '29.30.31.32';

		// Add IP once.
		$this->manager->add_ip_to_list( $ip, 'blacklist' );
		$blacklist1 = $this->manager->get_list( 'blacklist' );
		$count1     = count( $blacklist1 );

		// Add same IP again.
		$this->manager->add_ip_to_list( $ip, 'blacklist' );
		$blacklist2 = $this->manager->get_list( 'blacklist' );
		$count2     = count( $blacklist2 );

		// Count should be the same (no duplicates).
		$this->assertEquals( $count1, $count2 );
	}

	/**
	 * Test invalid IP rejection.
	 */
	public function test_invalid_ip_rejection() {
		$invalid_ips = array(
			'invalid',
			'999.999.999.999',
			'192.168.1.256',
			'not-an-ip',
			'',
		);

		foreach ( $invalid_ips as $ip ) {
			$result = $this->manager->add_ip_to_list( $ip, 'blacklist' );
			$this->assertFalse( $result, "Invalid IP '$ip' should be rejected" );
		}
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown() {
		// Clean up - clear all lists.
		$this->manager->clear_list( 'blacklist' );
		$this->manager->clear_list( 'whitelist' );
		parent::tearDown();
	}
}
