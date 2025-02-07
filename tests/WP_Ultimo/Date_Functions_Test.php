<?php

namespace WP_Ultimo;

class Date_Functions_Test extends \WP_UnitTestCase {
	public function test_wu_get_days_ago() {
		$days = wu_get_days_ago( '2024-01-01 00:00:00', '2024-01-31 00:00:00' );

		$this->assertEquals( -30, $days );
	}
}
