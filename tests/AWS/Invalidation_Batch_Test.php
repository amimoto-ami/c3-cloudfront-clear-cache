<?php
namespace C3_CloudFront_Cache_Controller\Test\AWS;
use C3_CloudFront_Cache_Controller\AWS;

class Invalidation_Batch_Test extends \WP_UnitTestCase {
	/**
	 * @dataProvider provide_invalidation_path_test_case
	 */
	public function test_invalidation_path_items( $paths, $expected = null ) {
        if ( ! $expected || empty( $expected ) ) {
            $expected = $paths;
        }

		$target = new AWS\Invalidation_Batch();
        foreach ( $paths as $key => $value ) {
            $target->put_invalidation_path( $value );
        }
        
		$this->assertEquals( $expected, $target->get_invalidation_path_items() );
	}

	public function provide_invalidation_path_test_case() {
		return [
            [[], ['/*']],
            [['a', 'b', 'c']],
            [['a', 'b', 'c','a', 'b', 'c'], ['a', 'b', 'c']],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'], ['/*']],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'h'], ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',]],
		];
	}

    
}