<?php

namespace WP_Ultimo\Tests;

use WP_Ultimo\Gateways\Manual_Gateway;
use WP_Ultimo\Managers\Gateway_Manager;

class Gateway_Functions_Test extends \WP_UnitTestCase {

	public function test_wu_get_gateway_returns_false_for_invalid_id(): void {
		$invalid_gateway_id = 'non_existent_gateway';

		$result = wu_get_gateway($invalid_gateway_id);

		$this->assertFalse($result);
	}

	public function test_wu_get_gateway_returns_instance_for_valid_id(): void {
		$valid_gateway_id = 'manual';

		$gateway = wu_get_gateway($valid_gateway_id);

		$this->assertInstanceOf(Manual_Gateway::class, $gateway);
	}
}
