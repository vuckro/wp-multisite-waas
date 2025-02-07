<?php

namespace WP_Ultimo\Models;

use WP_Ultimo\Faker;

/**
 * Unit tests for the Membership class.
 */
class Membership_Test extends \WP_UnitTestCase {

	/**
	 * Membership instance.
	 *
	 * @var Membership
	 */
	protected $membership;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a new Membership instance for each test.
		$this->membership = new Membership();

		// Set a default customer ID.
		$this->membership->set_customer_id( 123 );
	}

	/**
	 * Test if the customer is allowed access to the membership.
	 */
	public function test_is_customer_allowed() {
		// Admins with 'manage_network' capability should always return true.
		$admin_user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $admin_user_id );
		wp_set_current_user( $admin_user_id );
		$this->assertTrue( $this->membership->is_customer_allowed(), 'Failed asserting that admin is allowed.' );

		// Regular customers are allowed if IDs match.
		$customer_id = 123;
		$this->assertTrue(
			$this->membership->is_customer_allowed( $customer_id ),
			'Failed asserting that customer with matching ID is allowed.'
		);

		// Regular customers are denied if IDs do not match.
		$wrong_customer_id = 456;
		wp_set_current_user( $wrong_customer_id );
		$this->assertFalse(
			$this->membership->is_customer_allowed( $wrong_customer_id ),
			'Failed asserting that customer with non-matching ID is denied.'
		);
	}

	/**
	 * Test adding a product to the membership.
	 */
	public function test_add_product() {
		// Add a product with a specific ID and quantity.
		$quantity = 2;
		$faker    = new Faker();
		$faker->generate_fake_products();
		/** @var Product $product */
		$product    = $faker->get_fake_data_generated( 'products' )[0];
		$product_id = $product->get_id();

		$this->membership->add_product( $product_id, $quantity );

		// Verify that the product is added with the correct quantity.
		$addon_products = $this->membership->get_addon_ids();
		$this->assertContains( $product_id, $addon_products, 'Failed asserting that product ID was added.' );
		$this->assertEquals(
			$quantity,
			$this->membership->get_addon_products()[0]['quantity'],
			'Failed asserting that the product quantity is correct.'
		);

		// Add more of the same product and check the updated quantity.
		$additional_quantity = 3;
		$this->membership->add_product( $product_id, $additional_quantity );

		$this->assertEquals(
			$quantity + $additional_quantity,
			$this->membership->get_addon_products()[0]['quantity'],
			'Failed asserting that the quantity was updated correctly for the same product.'
		);
	}

	/**
	 * Test removing a product from the membership.
	 */
	public function test_remove_product() {
		// Add a product with a specific quantity.
		$quantity = 5;
		$faker    = new Faker();
		$faker->generate_fake_products();
		/** @var Product $product */
		$product    = $faker->get_fake_data_generated( 'products' )[0];
		$product_id = $product->get_id();

		$this->membership->add_product( $product_id, $quantity );

		// Remove some of the product's quantity.
		$remove_quantity = 3;
		$this->membership->remove_product( $product_id, $remove_quantity );

		// Verify the updated quantity.
		$this->assertEquals(
			$quantity - $remove_quantity,
			$this->membership->get_addon_products()[0]['quantity'],
			'Failed asserting that the quantity was reduced correctly.'
		);

		// Remove the remaining quantity and verify it is removed.
		$this->membership->remove_product( $product_id, $quantity );
		$addon_products = $this->membership->get_addon_ids();
		$this->assertNotContains( $product_id, $addon_products, 'Failed asserting that the product was removed.' );
	}

	/**
	 * Test get_remaining_days_in_cycle() method.
	 */
	public function test_get_remaining_days_in_cycle() {
		$this->membership->set_amount( 12.99 );
		// Case 1: Non-recurring membership should return 10000.
		$this->membership->set_recurring( false );
		$this->assertEquals(
			10000,
			$this->membership->get_remaining_days_in_cycle(),
			'Failed asserting that non-recurring membership returns 10000.'
		);

		// Case 2: Invalid expiration date should return 0.
		$this->membership->set_recurring( true );
		$this->membership->set_date_expiration( 'invalid-date' ); // Setting an invalid date.
		$this->assertEquals(
			0,
			$this->membership->get_remaining_days_in_cycle(),
			'Failed asserting that an invalid expiration date returns 0.'
		);

		// Case 3: No expiration date should return 0.
		$this->membership->set_date_expiration( '' );
		$this->assertEquals(
			0,
			$this->membership->get_remaining_days_in_cycle(),
			'Failed asserting that no expiration date returns 0.'
		);

		// Case 4: Expiration date is in the future and remaining days are calculated properly.
		$today = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		$today->add( new \DateInterval( 'P10D' ) );
		$this->membership->set_date_expiration( $today->format( 'Y-m-d H:i:s' ) );
		$remaining_days = $this->membership->get_remaining_days_in_cycle();
		$this->assertEquals(
			10,
			$remaining_days,
			'Failed asserting that 10 days remain when expiration date is 10 days in the future.'
		);

		// Case 5: Expiration date is in the past, should return 0.
		$this->membership->set_date_expiration( date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ) );
		$remaining_days = $this->membership->get_remaining_days_in_cycle();
		$this->assertEquals(
			0,
			$remaining_days,
			'Failed asserting that remaining days return 0 when the expiration date is in the past.'
		);
	}
}
