<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Api\Catalog\Product_Item;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api;

/**
 * Response object for API requests that return a Product Item.
 *
 * @since 2.0.0
 */
class Response extends Api\Response {


	/**
	 * Gets the Product Item group ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_group_id() {

		$product_group_id = '';

		if ( isset( $this->response_data->product_group->id ) ) {
			$product_group_id = $this->response_data->product_group->id;
		}

		return $product_group_id;
	}


}