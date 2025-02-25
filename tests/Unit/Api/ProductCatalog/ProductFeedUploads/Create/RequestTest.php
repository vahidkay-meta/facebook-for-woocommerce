<?php
declare( strict_types=1 );

use WooCommerce\Facebook\API\ProductCatalog\ProductFeedUploads\Create\Request;

/**
 * Request unit test class.
 */
class ProductFeedUploadRequestTest extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_request() {
        $product_feed_id = 'facebook-product-feed-id';
        $data = [
            'name' => 'Test Product Feed',
            'schedule' => [
                'interval' => 'DAILY',
                'url' => 'http://example.com/feed.xml',
            ],
        ];

        $request = new Request($product_feed_id, $data);

        $this->assertEquals('POST', $request->get_method());
        $this->assertEquals('/facebook-product-feed-id/uploads', $request->get_path());
        $this->assertEquals($data, $request->get_data());
    }
}
