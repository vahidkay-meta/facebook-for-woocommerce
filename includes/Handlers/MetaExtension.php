<?php
// phpcs:ignoreFile
/**
 * MetaExtension handler for iframe-specific logic and token storage.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Handlers;

use WooCommerce\Facebook\Handlers\Connection;


defined( 'ABSPATH' ) or exit;

class MetaExtension {
	/** @var string Business name */
	const BUSINESS_NAME = 'WooCommerce';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
	}

	/**
	 * Generates the Commerce Hub iframe splash page URL.
	 *
	 * @param bool $is_connected Whether the plugin is currently connected.
	 * @param object $plugin The plugin instance.
	 * @param string $external_business_id External business ID.
	 *
	 * @return string
	 */
	public static function generateIframeSplashUrl( $is_connected, $plugin, $external_business_id, $timezone) {
		$external_client_metadata = array(
			'shop_domain'                           => get_home_url(),
			'admin_url'                             => get_admin_url(),
			'client_version'                        => $plugin->get_version(),
			'commerce_partner_seller_platform_type' => 'SELF_SERVE_PLATFORM',
			'country_code'                          => WC()->countries->get_base_country(),
		);

		return add_query_arg(
			array(
				'business_vertical'        => 'ECOMMERCE',
				'channel'                  => 'COMMERCE',
				'app_id'                   => Connection::CLIENT_ID,
				'business_name'            =>
					get_option( 'woocommerce_store_name', self::BUSINESS_NAME ),
				'currency'                 => get_woocommerce_currency(),
				'timezone'                 => $timezone,
				'external_business_id'     => $external_business_id,
				'installed'                => $is_connected,
				'external_client_metadata' => rawurlencode( wp_json_encode( $external_client_metadata ) ),
			),
			'https://www.commercepartnerhub.com/commerce_extension/splash/'
		);
	}
}
