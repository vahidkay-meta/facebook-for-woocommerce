<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Handlers;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\Handlers\Connection;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles Meta Commerce Extension functionality and configuration.
 *
 * @since 2.0.0
 */
class MetaExtension {

	/** @var string Client token */
	const CLIENT_TOKEN = '195311308289826|52dcd04d6c7ed113121b5eb4be23b4a7';
	const APP_ID       = '474166926521348';
	/** @var string Business name */
	const BUSINESS_NAME = 'WooCommerce';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_wc_facebook_update_tokens', array( __CLASS__, 'ajax_update_fb_settings' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'init_rest_endpoint' ) );
	}

	/**
	 * Generates the Commerce Hub iframe splash page URL.
	 *
	 * @param bool   $is_connected         Whether the plugin is currently connected.
	 * @param object $plugin               The plugin instance.
	 * @param string $external_business_id External business ID.
	 * @return string
	 */
	public static function generate_iframe_splash_url( $is_connected, $plugin, $external_business_id ) {
		$external_client_metadata = array(
			'shop_domain'                           => wc_get_page_permalink( 'shop' ) ? wc_get_page_permalink( 'shop' ) : \home_url(),
			'admin_url'                             => admin_url(),
			'client_version'                        => $plugin->get_version(),
			'commerce_partner_seller_platform_type' => 'SELF_SERVE_PLATFORM',
			'country_code'                          => WC()->countries->get_base_country(),
		);
		return add_query_arg(
			array(
				'access_client_token'      => self::CLIENT_TOKEN,
				'business_vertical'        => 'ECOMMERCE',
				'channel'                  => 'COMMERCE',
				'app_id'                   => Connection::CLIENT_ID,
				'business_name'            => self::BUSINESS_NAME,
				'currency'                 => get_woocommerce_currency(),
				'timezone'                 => 'America/Los_Angeles',
				'external_business_id'     => $external_business_id,
				'installed'                => $is_connected,
				'external_client_metadata' => rawurlencode( wp_json_encode( $external_client_metadata ) ),
			),
			'https://www.commercepartnerhub.com/commerce_extension/splash/'
		);
	}

	/**
	 * AJAX endpoint to update Facebook settings with authenticated tokens.
	 *
	 * Expects POST parameters:
	 *  - nonce: security nonce.
	 *  - access_token: system user access token.
	 *  - merchant_access_token: merchant access token.
	 *  - page_access_token: page access token.
	 *  - product_catalog_id: product catalog ID (optional).
	 *  - pixel_id: pixel ID (optional).
	 *
	 * @return void JSON response.
	 */
	public static function ajax_update_fb_settings() {
		// Ensure the current user can manage WooCommerce settings.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized request', 'facebook-for-woocommerce' ) ) );
		}

		// Validate the nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wc_facebook_ajax_token_update' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'facebook-for-woocommerce' ) ) );
		}

		// Sanitize and retrieve POST data.
		$access_token          = isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';
		$merchant_access_token = isset( $_POST['merchant_access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['merchant_access_token'] ) ) : '';
		$page_access_token     = isset( $_POST['page_access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['page_access_token'] ) ) : '';
		$product_catalog_id    = isset( $_POST['product_catalog_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_catalog_id'] ) ) : '';
		$pixel_id              = isset( $_POST['pixel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['pixel_id'] ) ) : '';

		// Validate required tokens.
		if ( empty( $access_token ) || empty( $merchant_access_token ) || empty( $page_access_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required token data', 'facebook-for-woocommerce' ) ) );
		}

		// Update Facebook settings via options.
		update_option( 'wc_facebook_access_token', $access_token );
		update_option( 'wc_facebook_merchant_access_token', $merchant_access_token );
		update_option( 'wc_facebook_page_access_token', $page_access_token );
		update_option( 'wc_facebook_product_catalog_id', $product_catalog_id );
		update_option( 'wc_facebook_pixel_id', $pixel_id );

		wp_send_json_success( array( 'message' => __( 'Facebook settings updated successfully', 'facebook-for-woocommerce' ) ) );
	}

	/**
	 * REST API endpoint initialization.
	 *
	 * @return void
	 */
	public static function init_rest_endpoint() {
		register_rest_route(
			'wc-facebook/v1',
			'update_tokens',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_update_fb_tokens' ),
				'permission_callback' => array( __CLASS__, 'rest_update_fb_tokens_permission_callback' ),
			)
		);
	}

	/**
	 * Permission callback for the REST API endpoint.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool
	 */
	public static function rest_update_fb_tokens_permission_callback( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * REST API endpoint callback to update Facebook settings.
	 *
	 * Expects POST parameters:
	 *  - nonce: security nonce.
	 *  - access_token: system user access token.
	 *  - merchant_access_token: merchant access token.
	 *  - page_access_token: page access token.
	 *  - product_catalog_id: product catalog ID (optional).
	 *  - pixel_id: pixel ID (optional).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_update_fb_tokens( WP_REST_Request $request ) {
		// Get JSON data from request body
		$params = $request->get_json_params();

		// Sanitize and retrieve data
		$access_token          = isset( $params['access_token'] ) ? sanitize_text_field( $params['access_token'] ) : '';
		$merchant_access_token = isset( $params['merchant_access_token'] ) ? sanitize_text_field( $params['merchant_access_token'] ) : '';
		$page_access_token     = isset( $params['page_access_token'] ) ? sanitize_text_field( $params['page_access_token'] ) : '';
		$product_catalog_id    = isset( $params['product_catalog_id'] ) ? sanitize_text_field( $params['product_catalog_id'] ) : '';
		$pixel_id              = isset( $params['pixel_id'] ) ? sanitize_text_field( $params['pixel_id'] ) : '';

		// Only validate merchant_access_token as required
		if ( empty( $merchant_access_token ) ) {
			return new WP_Error( 'missing_token', __( 'Missing merchant access token', 'facebook-for-woocommerce' ), array( 'status' => 400 ) );
		}

		// Update all available options
		if ( ! empty( $access_token ) ) {
			update_option( 'wc_facebook_access_token', $access_token );
		}

		update_option( 'wc_facebook_merchant_access_token', $merchant_access_token );

		if ( ! empty( $page_access_token ) ) {
			update_option( 'wc_facebook_page_access_token', $page_access_token );
		}

		if ( ! empty( $product_catalog_id ) ) {
			update_option( 'wc_facebook_product_catalog_id', $product_catalog_id );
		}

		if ( ! empty( $pixel_id ) ) {
			update_option( 'wc_facebook_pixel_id', $pixel_id );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Facebook settings updated successfully', 'facebook-for-woocommerce' ),
			),
			200
		);
	}

	/**
	 * Makes an API call to Facebook's Graph API.
	 *
	 * @param string $method HTTP method (GET, POST, etc.)
	 * @param string $endpoint API endpoint
	 * @param array  $params Request parameters
	 * @return array Response data
	 * @throws \Exception If the request fails.
	 */
	private static function call_api( $method, $endpoint, $params ) {
		$url = 'https://graph.facebook.com/v18.0/' . $endpoint;

		if ( 'GET' === $method ) {
			$url = add_query_arg( $params, $url );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		if ( 'POST' === $method ) {
			$args['body'] = json_encode( $params );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

	/**
	 * Get a URL to use to render the CommerceExtension IFrame for an onboarded Store.
	 *
	 * @param string      $external_business_id External business ID
	 * @param string|null $access_token Access token
	 * @return string
	 */
	public static function get_commerce_extension_iframe_url( $external_business_id, $access_token = null ) {
		if ( empty( $access_token ) ) {
			$access_token = get_option( 'wc_facebook_access_token', '' );
		}

		try {
			$request = array(
				'access_token'             => $access_token,
				'fields'                   => 'commerce_extension',
				'fbe_external_business_id' => $external_business_id,
			);

			$response = self::call_api( 'GET', 'fbe_business', $request );

			if ( ! empty( $response['commerce_extension']['uri'] ) ) {
				$uri = $response['commerce_extension']['uri'];

				// Allow for URL override through constant or filter
				$base_url_override = defined( 'FACEBOOK_COMMERCE_EXTENSION_BASE_URL' )
					? constant( 'FACEBOOK_COMMERCE_EXTENSION_BASE_URL' )
					: null;

				$base_url_override = apply_filters( 'wc_facebook_commerce_extension_base_url', $base_url_override );

				if ( $base_url_override ) {
					$uri = str_replace( 'https://www.commercepartnerhub.com/', $base_url_override, $uri );
				}

				return $uri;
			}
		} catch ( \Exception $e ) {
			error_log( 'Facebook Commerce Extension URL Error: ' . $e->getMessage() );
		}

		return '';
	}

	/**
	 * Generates the Commerce Hub iframe management page URL.
	 *
	 * @param object $plugin The plugin instance.
	 * @param string $external_business_id External business ID.
	 * @return string
	 */
	public static function generate_iframe_management_url( $plugin, $external_business_id ) {
		$access_token = get_option( 'wc_facebook_access_token', '' );

		if ( empty( $access_token ) ) {
			return '';
		}

		return self::get_commerce_extension_iframe_url( $external_business_id, $access_token );
	}

	/**
	 * Renders the management iframe.
	 *
	 * @param object $plugin               The plugin instance.
	 * @param string $external_business_id External business ID.
	 * @return void
	 */
	public static function render_management_iframe( $plugin, $external_business_id ) {
		$iframe_url = self::generate_iframe_management_url( $plugin, $external_business_id );

		if ( empty( $iframe_url ) ) {
			return;
		}

		?>
		<iframe
			src="<?php echo esc_url( $iframe_url ); ?>"
			width="100%"
			height="800"
			frameborder="0"
			style="background: transparent;"
			id="facebook-commerce-management-iframe">
		</iframe>
		<?php
	}
}
