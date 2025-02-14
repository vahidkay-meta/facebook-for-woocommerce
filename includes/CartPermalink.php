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

namespace WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

/**
 * The cart permalink.
 *
 * @since 3.3.0
 */
class CartPermalink {

  /**
	 * CartPermalink constructor.
	 *
	 * @since 3.3.0
	 */
	public function __construct() {
    // add the necessary action and filter hooks
		$this->add_hooks();
	}

  /**
	 * Adds the necessary action and filter hooks.
	 *
	 * @since 3.3.0
	 */
	public function add_hooks() {
		// add the rewrite rule for the cart permalink
    add_action('init', array($this, 'add_cart_permalink_rewrite_rule'));

		// add the query var for the cart permalink
		add_filter('query_vars', array($this, 'add_cart_permalink_query_var'));

    // load the cart permalink template
		add_filter('template_include', array($this, 'load_cart_permalink_template'));

    // flush rewrite rules when plugin is activated
    register_activation_hook(__FILE__, array($this, 'flush_rewrite_rules_on_activation'));

    // flush rewrite rules when plugin is deactivated
    register_deactivation_hook(__FILE__, array($this, 'flush_rewrite_rules_on_deactivation'));
	}

  /**
   * Adds a rewrite rule for the cart permalink.
	 *
	 * @since 3.3.0
	 */
  public function add_cart_permalink_rewrite_rule() {
      add_rewrite_rule('^fb-cart/?$', 'index.php?fb_cart=1', 'top');
  }

  /**
   * Adds query vars for the cart permalink.
	 *
	 * @since 3.3.0
   *
   * @param array $vars
   * @return array
	 */
  public function add_cart_permalink_query_var($vars) {
    // Add 'fb_cart' as a query var
    $vars[] = 'fb_cart';

    // Add 'products' as a query var
    $vars[] = 'products';

    // Add 'coupon' as a query var
    $vars[] = 'coupon';

    return $vars;
  }

  /**
   * Loads the cart permalink template.
	 *
	 * @since 3.3.0
	 */
  public function load_cart_permalink_template() {
    if (get_query_var('fb_cart')) {
        // Clear the WooCommerce cart
        WC()->cart->empty_cart();

        // Get the 'products' query parameter
        $products_param = get_query_var('products');

        if ($products_param) {
            // Split multiple products by comma
            $products = explode(',', $products_param);

            foreach ($products as $product) {
                // Parse each product ID and quantity
                list($product_id, $quantity) = explode(':', $product);

                // Validate and add the product to the cart
                if (is_numeric($product_id) && is_numeric($quantity) && $quantity > 0) {
                    WC()->cart->add_to_cart($product_id, $quantity);
                }
            }
        }

        // Get the 'coupon' query parameter
        $coupon_code = get_query_var('coupon');

        if ($coupon_code) {
            // Apply the coupon to the cart
            WC()->cart->apply_coupon(sanitize_text_field($coupon_code));
        }

        // Use a custom template file
        include plugin_dir_path(__FILE__) . 'Templates/CartPermaLinkTemplate.php';

        exit;
    }
  }

  /**
   * Flushes rewrite rules when the plugin is activated.
	 *
	 * @since 3.3.0
	 */
  public function flush_rewrite_rules_on_activation() {
    $this->add_cart_permalink_rewrite_rule();
    flush_rewrite_rules();
  }

  /**
   * Flushes rewrite rules when the plugin is deactivated.
	 *
	 * @since 3.3.0
	 */
  public function flush_rewrite_rules_on_deactivation() {
    flush_rewrite_rules();
  }
}
