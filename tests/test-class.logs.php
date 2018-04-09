<?php
require_once( 'module/classes/class.logs.php' );
class C3_Log_Utils_Test extends WP_UnitTestCase
{
	protected $class;
	function setUp() {
		$this->class = new C3_Log_Utils();
	}

	function test_should_return_empty_array_when_get_v2_clients_empty_response() {
		$response = array(
			'Quantity' => 0
		);
		$result = $this->class->parse_invalidation_lists($response);
		$this->assertEquals( $result, array() );
	}
	function test_should_return_v2_client_response() {
		$response = array(
			'Quantity' => 1,
			'Items' => array(
				'hoge' => true
			)
		);
		$result = $this->class->parse_invalidation_lists($response);
		$this->assertEquals( $result, array(
			'hoge' => true
		) );
	}

	function test_should_return_v3_client_response() {
		$response = array(
			'InvalidationList' => array(
				'Quantity' => 1,
				'Items' => array(
					'hoge' => true
				)
			)
		);
		$result = $this->class->parse_invalidation_lists($response);
		$this->assertEquals( $result, array(
			'hoge' => true
		) );
	}
}
