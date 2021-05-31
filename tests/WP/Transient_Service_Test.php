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
    /**
     * @dataProvider provide_merge_transient_invalidation_query_test_case(
     */
    public function test_merge_transient_invalidation_query( $query, $transiented_query, $expected ) {
        $result = $this->target->merge_transient_invalidation_query( $query, $transiented_query );
        $this->assertEquals( $expected, $result );
    }

    public function provide_merge_transient_invalidation_query_test_case() {
        return [
            [
                array(
                    "Paths" => array(
                        "Items" => array(
                            '/test',
                            '/test-2'
                        )
                    )
                ),
                null,
                array(
                    "Paths" => array(
                        "Quantity" => 2,
                        "Items" => array(
                            '/test',
                            '/test-2'
                        )
                    )
                )
            ], [
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
                        "Items" => array(
                            '/test',
                            '/test-1'
                        )
                    )
                ),
                array(
                    "Paths" => array(
                        "Quantity" => 3,
                        "Items" => array(
                            '/test',
                            '/test-2',
                            '/test-1'
                        )
                    )
                )
            ]
        ];
    }

    public function test_merge_transient_invalidation_query_will_replace_wilcard() {
        $query = array(
            "Paths" => array(
                "Items" => array(
                    '/test',
                    '/test-2'
                )
            )
        );
        $transiented_query = array(
            "Paths" => array(
                "Items" => array(
                    '/test',
                    '/test-1'
                )
            )
        );
        add_filter( 'c3_invalidation_item_limits', array( $this,'dummy_limit_items' ) );
        $result = $this->target->merge_transient_invalidation_query( $query, $transiented_query );
        $this->assertEquals( array(
            "Paths" => array(
                "Quantity" => 1,
                "Items" => array(
                    '/*',
                )
            )
        ), $result );
        remove_filter( 'c3_invalidation_item_limits', array( $this,'dummy_limit_items' ) );
    }

    public function dummy_limit_items() {
        return 1;
    }
}