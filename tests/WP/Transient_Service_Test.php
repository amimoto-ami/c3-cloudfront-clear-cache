<?php
namespace C3_CloudFront_Cache_Controller\Test\WP;
use C3_CloudFront_Cache_Controller\WP;

class Transiend_Service_Test extends \WP_UnitTestCase {
    private $target;
    public function setUp() {
        parent::setUp();
        $this->target = new WP\Transient_Service();
    }

    /**
     * @dataProvider provide_query_normalize_test_case
     */
    public function test_query_normalize( $query, $expected ) {
        $this->assertEquals( $expected, $this->target->query_normalize( $query ) );
    }

    public function provide_query_normalize_test_case() {
        return [
            [
                'invalid', 
                array(
                    "Paths" => array(
                        "Quantity" => 0,
                        "Items" => array()
                    )
                )
            ],
            [
                array(
                    "Paths" => array(
                        "Quantity" => 1,
                    )
                ),
                array(
                    "Paths" => array(
                        "Quantity" => 0,
                        "Items" => array()
                    )
                )
            ],
            [
                array(
                    "Paths" => array(
                        "Quantity" => 1,
                        "Items" => array(
                            '/test'
                        )
                    )
                ),
                array(
                    "Paths" => array(
                        "Quantity" => 1,
                        "Items" => array(
                            '/test'
                        )
                    )
                )
            ],
            [
                array(
                    "Paths" => array(
                        "Items" => array(
                            '/test',
                            '/test-2'
                        )
                    )
                ),
                array(
                    "Paths" => array(
                        "Quantity" => 2,
                        "Items" => array(
                            '/test',
                            '/test-2'
                        )
                    )
                )
            ],
        ];
    }
}