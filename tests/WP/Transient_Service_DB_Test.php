<?php
/**
 * Transient Service DB operation test – save/load/delete
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

namespace C3_CloudFront_Cache_Controller\Test\WP;

use C3_CloudFront_Cache_Controller\WP\Transient_Service;

/**
 * @group transient
 */
class Transient_Service_DB_Test extends \WP_UnitTestCase {
	private $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Transient_Service();
	}

	public function test_save_load_delete_cycle() {
		$query = [
			'Paths' => [
				'Items'    => ['/sample-path'],
				'Quantity' => 1,
			],
		];

		// Save
		$this->service->save_invalidation_query($query);

		// Load & verify
		$loaded = $this->service->load_invalidation_query();
		$this->assertIsArray($loaded, 'Loaded value should be array');
		$this->assertSame(['/sample-path'], $loaded['Paths']['Items']);

		// Delete
		$this->service->delete_invalidation_query();
		$after_delete = $this->service->load_invalidation_query();
		$this->assertFalse($after_delete, 'Transient should be deleted');
	}
} 