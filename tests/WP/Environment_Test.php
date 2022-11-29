<?php
namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP;

class Environment_Test extends \WP_UnitTestCase {
    /**
     * @dataProvider provide_is_supported_version
     */
    public function test_is_supported_version( $version1, $version2, $expectedResult ) {
        $target = new WP\Environment();
        $result = $target->is_supported_version( $version1, $version2 );
        $this->assertEquals( $expectedResult, $result );
    }

    public function provide_is_supported_version() {
        return [
            ['7.3.0', '7.3.1', true],
            ['7.3.0', '7.3.0', true],
            ['7.3.0', '7.2.0', false],
        ];
    }
}