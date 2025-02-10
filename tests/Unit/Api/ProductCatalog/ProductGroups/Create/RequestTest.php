<?php
declare( strict_types=1 );

namespace Api\ProductCatalog\ProductGroups\Create;

use WooCommerce;
use WP_UnitTestCase;

/**
 * Api unit test clas.
 */
class RequestTest extends WP_UnitTestCase {
	/**
	 * @return void
	 */
	public function test_request() {
		$product_catalog_id = 'facebook-product-catalog-id';
		$data = [
			'retailer_id' => 'retailer_id',
			'variants'    => [
				[
					'product_field' => 'color',
					'label'         => 'Color',
					'options'       => [ 'Red', 'Green', 'Blue' ],
				],
				[
					'product_field' => 'size',
					'label'         => 'Size',
					'options'       => [ 'Small', 'Medium', 'Large' ],
				],
			],
		];

		$request = new WooCommerce\Facebook\API\ProductCatalog\ProductGroups\Create\Request( $product_catalog_id, $data );

		$this->assertEquals( 'POST', $request->get_method() );
		$this->assertEquals( '/'.$product_catalog_id.'/product_groups', $request->get_path() );
		$this->assertEquals( $data, $request->get_data() );
	}
}
