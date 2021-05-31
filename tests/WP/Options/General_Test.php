<?php
namespace C3_CloudFront_Cache_Controller\Test\WP\Options;
use C3_CloudFront_Cache_Controller\WP;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Environment;
use C3_CloudFront_Cache_Controller\Test\Mocks\WP\Options;

class General_Test extends \WP_UnitTestCase {
	public function test_return_null_array_when_no_parameters_are_defined() {
		$target = new WP\Options_Service(
			new Environment()
		);
		$this->assertEquals( $target->get_options(), null);
	}

    public function test_return_saved_value_when_options_return_values() {
        $target = new WP\Options_Service(
            new Environment(),
            new Options()
        );
        $this->assertEquals( $target->get_options(), array(
            'distribution_id' => 'DIST_ID',
            'access_key'      => 'ACCESS_KEY',
            'secret_key'      => 'SECRET_KEY',
        ) );
    }

    public function test_return_empty_array_when_options_has_no_value() {
        $target = new WP\Options_Service(
            new Environment(),
            new Options( true )
        );
        $this->assertEquals( $target->get_options(), null );
    }

    public function test_return_array_when_env_props_has_defined() {
        $env = new Environment();
        $env->set_distribution_id( 'distribution_id' );
        $env->set_aws_access_key( 'aws_access_key' );
        $env->set_aws_secret_key( 'aws_secret_key' );
        $target = new WP\Options_Service(
            $env,
            new Options( true )
        );
        
        $this->assertEquals( $target->get_options(), array(
			'distribution_id' => 'distribution_id' ,
			'access_key'      => 'aws_access_key',
			'secret_key'      => 'aws_secret_key',
        ) );
    }

    public function test_return_defined_parameter_array_even_if_persisted_parameters_are_available() {
        $env = new Environment();
        $env->set_distribution_id( 'distribution_id' );
        $env->set_aws_access_key( 'aws_access_key' );
        $env->set_aws_secret_key( 'aws_secret_key' );
        $target = new WP\Options_Service(
            $env,
            new Options()
        );
        $this->assertEquals( $target->get_options(), array(
			'distribution_id' => 'distribution_id' ,
			'access_key'      => 'aws_access_key',
			'secret_key'      => 'aws_secret_key',
        ) );
    }

    public function test_return_persisted_parameters_when_options_return_values_and_defined_parameters_are_not_fullfilled() {
        $env = new Environment();
        $env->set_distribution_id( 'distribution_id' );
        $env->set_aws_access_key( 'aws_access_key' );
        $target = new WP\Options_Service(
            $env,
            new Options()
        );
        $this->assertEquals( $target->get_options(), array(
            'distribution_id' => 'DIST_ID',
            'access_key'      => 'ACCESS_KEY',
            'secret_key'      => 'SECRET_KEY',
        ) );
    }
}