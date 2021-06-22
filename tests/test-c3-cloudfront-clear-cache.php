<?php
namespace C3_CloudFront_Cache_Controller\Test;
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Environment;

class Example_Test extends \WP_UnitTestCase {

	public function test_return_null_array_when_the_env_is_amimoto_managed() {
		$target = new WP\Options_Service(
			new Environment('amimoto_managed')
		);
		$this->assertEquals( $target->get_options(), array(
			'distribution_id' => null,
			'access_key'      => null,
			'secret_key'      => null,
		) );
	}
}