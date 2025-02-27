<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Admin\Settings_Screens;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\Admin\Abstract_Settings_Screen;
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

/**
 * The Whatsapp Utility settings screen object.
 */
class Whatsapp_Utility extends Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'whatsapp_utility';


	/**
	 * Whatsapp Utility constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initHook' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Initializes this whatsapp utility settings page's properties.
	 */
	public function initHook(): void {
		$this->id    = self::ID;
		$this->label = __( 'WhatsApp Utility', 'facebook-for-woocommerce' );
		$this->title = __( 'Whatsapp Utility', 'facebook-for-woocommerce' );
	}

	/**
	 * Enqueue the assets.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function enqueue_assets() {

		if ( ! $this->is_current_screen_page() ) {
			return;
		}

		wp_enqueue_style( 'wc-facebook-admin-whatsapp-settings', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-whatsapp-utility.css', array(), \WC_Facebookcommerce::VERSION );
	}


	/**
	 * Renders the screen.
	 *
	 * @since 2.0.0
	 */
	public function render() {

		?>
	<div class="onboarding-card">
		<h2>Get started with WhatsApp utility messages</h2>
		<p>Connect your WhatsApp Business Account to start sending utility messages.</p>
		<button class="connect-button">Connect WhatsApp Account</button>
	</div>
		<?php

		parent::render();
	}

	/**
	 * Gets the screen settings.
	 * Note: Need to implement this method to satisfy the interface.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_settings() {
		return array();
	}
}
