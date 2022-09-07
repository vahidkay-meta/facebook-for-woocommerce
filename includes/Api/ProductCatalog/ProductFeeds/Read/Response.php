<?php
/**
 *
 */

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductFeeds\Read;

use WooCommerce\Facebook\Api\Response as ApiResponse;

defined( 'ABSPATH' ) or exit;

/**
 * Response object for Product Catalog > Product Feeds > Read Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-feed/#Reading
 * @property-read array $data Facebook Product Feeds.
 */
class Response extends ApiResponse {}