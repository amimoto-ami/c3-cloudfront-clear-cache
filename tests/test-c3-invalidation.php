<?php
require_once('module/model/invalidation.php');

class C3_Invalidation_test extends WP_UnitTestCase {
	protected $class;
	function setUp() {
		$this->class = C3_Invalidation::get_instance();;
	}
	function test_should_return_empty_array_when_given_string() {
		$query = $this->class->query_normalyze( '' );
		$this->assertEquals( $query, array(
			'Paths' => array(
				'Quantity' => 0,
				'Items' => array(),
			)
		));
	}
	function test_should_return_empty_array_when_given_empqy_array() {
		$query = $this->class->query_normalyze( array() );
		$this->assertEquals( $query, array(
			'Paths' => array(
				'Quantity' => 0,
				'Items' => array(),
			)
		));
	}
	function test_should_return_empty_array_when_given_invalid_array() {
		$query = $this->class->query_normalyze( array(
			'Paths' => array()
		) );
		$this->assertEquals( $query, array(
			'Paths' => array(
				'Quantity' => 0,
				'Items' => array(),
			)
		));
	}
	function test_should_return_empty_array_when_given_array_without_items() {
		$query = $this->class->query_normalyze( array(
			'Paths' => array(
				'Quantity' => 100,
			)
		) );
		$this->assertEquals( array(
			'Paths' => array(
				'Quantity' => 0,
				'Items' => array(),
			),
		), $query);
	}
	function test_should_return_empty_array_when_given_array_without_quantity() {
		$query = $this->class->query_normalyze( array(
			'Paths' => array(
				'Items' => array('1')
			)
		) );
		$this->assertEquals( array(
			'Paths' => array(
				'Quantity' => 1,
				'Items' => array('1'),
			),
		), $query);
	}
	function test_should_return_empty_array_when_given_valid_array() {
		$query = $this->class->query_normalyze( array(
			'Paths' => array(
				'Quantity' => 1,
				'Items' => array('1')
			)
		) );
		$this->assertEquals( array(
			'Paths' => array(
				'Quantity' => 1,
				'Items' => array('1'),
			),
		), $query);
	}
}