<?php
declare( strict_types=1 );

namespace Api\ProductCatalog\ItemsBatch\Create;

use WooCommerce;
use WP_UnitTestCase;

/**
 * Test cases for Items Batch create API response
 */
class ResponseTest extends WP_UnitTestCase {
	/**
	 * Tests response endpoint config
	 *
	 * @return void
	 */
	public function test_request(): void {
		$json     = '{"handles": [], "validation_status": []}';
		$response = new WooCommerce\Facebook\API\ProductCatalog\ItemsBatch\Create\Response ( $json );

		$this->assertEquals( [], $response->handles );
		$this->assertEquals( [], $response->validation_status );
	}
}