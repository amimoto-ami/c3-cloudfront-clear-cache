<?php
namespace C3_CloudFront_Cache_Controller\Test\WP\Options;
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Environment;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Options;

class Amimoto_Managed_Test extends \WP_UnitTestCase {
	public function test_return_null_array_by_default() {
		$target = new WP\Options_Service(
			new Environment('amimoto_managed')
		);
		$this->assertEquals( $target->get_options(), array(
			'distribution_id' => null,
			'access_key'      => null,
			'secret_key'      => null,
		) );
	}
	public function test_return_null_array_even_if_parameter_has_been_saved() {
		$target = new WP\Options_Service(
			new Environment('amimoto_managed'),
            new Options()
		);
		$this->assertEquals( $target->get_options(), array(
			'distribution_id' => null,
			'access_key'      => null,
			'secret_key'      => null,
		) );
	}
}