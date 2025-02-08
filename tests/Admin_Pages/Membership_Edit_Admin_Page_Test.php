<?php

namespace WP_Ultimo\Admin_Pages;

use WP_Ultimo\Checkout\Cart;
use WP_Ultimo\Faker;
use WP_Ultimo\Models\Membership;
use WP_UnitTestCase;

/**
 * Test class for the Membership_Edit_Admin_Page class.
 *
 * This class specifically tests the page_loaded method.
 */
class Membership_Edit_Admin_Page_Test extends WP_UnitTestCase {

	/**
	 * Instance of Membership_Edit_Admin_Page.
	 */
	protected Membership_Edit_Admin_Page $membership_edit_admin_page;

	protected Membership $membership;

	protected int $swap_time;

	/**
	 * Sets up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$faker = new Faker();
		$faker->generate_fake_memberships();
		$this->swap_time = strtotime('+100 days');

		$this->membership = current($faker->get_fake_data_generated('memberships'));
		$cart             = new Cart(array());
		$this->membership->schedule_swap($cart, gmdate('Y-m-d H:i:s', $this->swap_time));
		// Mock Membership_Edit_Admin_Page with dependencies and methods.
		$this->membership_edit_admin_page = new Membership_Edit_Admin_Page();
	}


	/**
	 * Tests that page_loaded calls add_swap_notices.
	 */
	public function test_page_loaded_calls_add_swap_notices() {
		$_REQUEST['id'] = $this->membership->get_id();
		$this->membership_edit_admin_page->page_loaded();

		$membership = $this->membership_edit_admin_page->get_object();
		$this->assertInstanceOf(Membership::class, $membership);
		$this->assertEquals($membership->get_id(), $this->membership->get_id());
		$this->assertTrue($this->membership_edit_admin_page->edit);

		$notices = \WP_Ultimo()->notices->get_notices('network-admin');
		$this->assertNotEmpty($notices);
		$notice = array_shift($notices);
		$this->assertEquals('warning', $notice['type']);
		$this->assertFalse($notice['dismissible_key']);
		$this->assertNotEmpty($notice['actions']);
		$this->assertStringContainsString(gmdate(get_option('date_format'), $this->swap_time), $notice['message']);
	}
}
