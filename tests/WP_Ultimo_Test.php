<?php
namespace WP_Ultimo;

class WP_Ultimo_Test extends \WP_UnitTestCase {
	/**
	 * Test if all helper functions are loaded correctly.
	 *
	 * This test case creates an instance of the WP_Ultimo class and calls the load_public_apis method.
	 * It then asserts that the helper functions wu_to_float, wu_replace_dashes, and wu_get_initials are
	 * correctly loaded. Additional assertions can be added for other helper functions.
	 *
	 * @return void
	 */
	public function testLoadAllHelperFunctionsCorrectly(): void {
		// Assert that all helper functions are loaded correctly.
		// This is all done in the bootstrap.
		$this->assertTrue(function_exists('wu_to_float'));
		$this->assertTrue(function_exists('wu_replace_dashes'));
		$this->assertTrue(function_exists('wu_get_initials'));
	}

	public function testLoaded(): void {
		$wpUltimo = \WP_Ultimo();
		$this->assertTrue($wpUltimo->version === \WP_Ultimo::VERSION);
		$this->assertTrue($wpUltimo->is_loaded());
	}

	public function testPublicProperties(): void {
		$wpUltimo = \WP_Ultimo();
		$this->assertTrue($wpUltimo->settings instanceof Settings);
		$this->assertTrue($wpUltimo->helper instanceof Helper);
		$this->assertTrue($wpUltimo->notices instanceof Admin_Notices);
		$this->assertTrue($wpUltimo->scripts instanceof Scripts);
		$this->assertTrue($wpUltimo->currents instanceof Current);
	}
}
