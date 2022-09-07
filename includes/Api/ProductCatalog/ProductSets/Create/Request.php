<?php
/**
 *
 */

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductSets\Create;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) or exit;

/**
 * Request object for Product Catalog > Product Sets > Create Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_sets/
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_catalog_id Facebook Product Catalog ID.
	 * @param array  $data Facebook Product Set Data.
	 */
	public function __construct( string $product_catalog_id, array $data ) {
		parent::__construct( "/{$product_catalog_id}/product_sets", 'POST' );
		parent::set_data( $data );
	}
}