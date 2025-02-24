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
 * The Connection settings screen object.
 */
class Connection extends Abstract_Settings_Screen
{

	/** @var string screen ID */
	const ID = 'connection';

	/**
	 * Determines if the iframe connection should be used.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function use_iframe_connection()
	{
		return true;
	}

	/**
	 * Connection constructor.
	 */
	public function __construct()
	{

		$this->id    = self::ID;
		$this->label = __('Connection', 'facebook-for-woocommerce');
		$this->title = __('Connection', 'facebook-for-woocommerce');

		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

		add_action('admin_notices', array($this, 'add_notices'));

		// Add action to enqueue the message handler script
		add_action( 'admin_footer', array( $this, 'render_message_handler' ) );
	}


	/**
	 * Adds admin notices.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function add_notices()
	{

		// display a notice if the connection has previously failed
		if (get_transient('wc_facebook_connection_failed')) {

			$message = sprintf(
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag, %5$s - <a> tag, %6$s - </a> tag */
				__('%1$sHeads up!%2$s It looks like there was a problem with reconnecting your site to Facebook. Please %3$sclick here%4$s to try again, or %5$sget in touch with our support team%6$s for assistance.', 'facebook-for-woocommerce'),
				'<strong>',
				'</strong>',
				'<a href="' . esc_url(facebook_for_woocommerce()->get_connection_handler()->get_connect_url()) . '">',
				'</a>',
				'<a href="' . esc_url(facebook_for_woocommerce()->get_support_url()) . '" target="_blank">',
				'</a>'
			);

			facebook_for_woocommerce()->get_admin_notice_handler()->add_admin_notice(
				$message,
				'wc_facebook_connection_failed',
				array(
					'notice_class' => 'error',
				)
			);

			delete_transient('wc_facebook_connection_failed');
		}
	}


	/**
	 * Enqueue the assets.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function enqueue_assets()
	{

		if (! $this->is_current_screen_page()) {
			return;
		}

		wp_enqueue_style('wc-facebook-admin-connection-settings', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-connection.css', array(), \WC_Facebookcommerce::VERSION);
	}


	/**
	 * Renders the screen.
	 *
	 * @since 2.0.0
	 */
	public function render()
	{

		$is_connected = facebook_for_woocommerce()->get_connection_handler()->is_connected();

		// always render the CTA box
		$this->render_facebook_box($is_connected);

		// don't proceed further if not connected
		if (! $is_connected) {
			return;
		}

		/**
		 * Build the basic static elements.
		 *
		 * At a minimum, we display their raw ID. If they have an API resource, we replace that ID with whatever data
		 * we can get our hands on, with an external link if possible. Current abilities:
		 *
		 * + Page: just the ID
		 * + Pixel: just the ID
		 * + Catalog: name, full URL
		 * + Business manager: name, full URL
		 * + Ad account: not currently available
		 *
		 * TODO: add pixel & ad account API retrieval when we gain the ads_management permission
		 * TODO: add the page name and link when we gain the manage_pages permission
		 */
		$static_items = $this->use_iframe_connection() ? array() : array(
			'page'                          => array(
				'label' => __('Page', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_integration()->get_facebook_page_id(),
			),
			'pixel'                         => array(
				'label' => __('Pixel', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_integration()->get_facebook_pixel_id(),
			),
			'catalog'                       => array(
				'label' => __('Catalog', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_integration()->get_product_catalog_id(),
				'url'   => 'https://facebook.com/products',
			),
			'business-manager'              => array(
				'label' => __('Business Manager account', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_connection_handler()->get_business_manager_id(),
			),
			'ad-account'                    => array(
				'label' => __('Ad Manager account', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_connection_handler()->get_ad_account_id(),
			),
			'instagram-business-id'         => array(
				'label' => __('Instagram Business ID', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_connection_handler()->get_instagram_business_id(),
			),
			'commerce-merchant-settings-id' => array(
				'label' => __('Commerce Merchant Settings ID', 'facebook-for-woocommerce'),
				'value' => facebook_for_woocommerce()->get_connection_handler()->get_commerce_merchant_settings_id(),
			),
		);

		// if the catalog ID is set, update the URL and try to get its name for display
		$catalog_id = $static_items['catalog']['value'];
		if ( ! empty( $catalog_id ) ) {
			$static_items['catalog']['url'] = "https://www.facebook.com/commerce/catalogs/{$catalog_id}/products/";
			try {
				$response = facebook_for_woocommerce()->get_api()->get_catalog($catalog_id);
				if ($name = $response->name) {
					$static_items['catalog']['value'] = $name;
				}
			} catch (ApiException $exception) {
				// Log the exception with additional information
				facebook_for_woocommerce()->log(
					sprintf(
						'Connection failed for catalog %s: %s ',
						$catalog_id,
						$exception->getMessage(),
					)
				);
			}
		}

?>

		<table class="form-table">
			<tbody>

				<?php
				foreach ($static_items as $id => $item) :

					$item = wp_parse_args(
						$item,
						array(
							'label' => '',
							'value' => '',
							'url'   => '',
						)
					);

				?>

					<tr valign="top" class="wc-facebook-connected-<?php echo esc_attr($id); ?>">

						<th scope="row" class="titledesc">
							<?php echo esc_html($item['label']); ?>
						</th>

						<td class="forminp">

							<?php if ($item['url']) : ?>

								<a href="<?php echo esc_url($item['url']); ?>" target="_blank">

									<?php echo esc_html($item['value']); ?>

									<span
										class="dashicons dashicons-external"
										style="margin-right: 8px; vertical-align: bottom; text-decoration: none;"></span>

								</a>

							<?php elseif (is_numeric($item['value'])) : ?>

								<code><?php echo esc_html($item['value']); ?></code>

							<?php elseif (! empty($item['value'])) : ?>

								<?php echo esc_html($item['value']); ?>

							<?php else : ?>

								<?php echo '-'; ?>

							<?php endif; ?>

						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>

	<?php

		parent::render();
	}


	/**
	 * Renders the Facebook CTA box.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $is_connected whether the plugin is connected
	 */
	private function render_facebook_box( $is_connected ) {
		if ( $this->use_iframe_connection() ) {
			$this->render_facebook_box_iframe( $is_connected );
		} else {
			$this->render_facebook_box_legacy( $is_connected );
		}
	}

	/**
	 * Renders the Facebook CTA box using iframe implementation.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $is_connected whether the plugin is connected
	 */
	private function render_facebook_box_iframe($is_connected)
	{
		$connection = facebook_for_woocommerce()->get_connection_handler();
		$iframe_url = \WooCommerce\Facebook\Handlers\MetaExtension::generate_iframe_splash_url(
			$is_connected,
			$connection->get_plugin(),
			$connection->get_external_business_id()
		);
	?>
		<iframe
			src="<?php echo esc_url($iframe_url); ?>"
			width="100%"
			height="600"
			frameborder="0"
			style="background: transparent;"
			id="facebook-commerce-iframe"></iframe>
	<?php
	}

	/**
	 * Renders the legacy Facebook CTA box.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $is_connected whether the plugin is connected
	 */
	private function render_facebook_box_legacy($is_connected)
	{
		if ($is_connected) {
			$title = __('Reach the Right People and Sell More Online', 'facebook-for-woocommerce');
		} else {
			$title = __('Grow your business on Facebook', 'facebook-for-woocommerce');
		}



		$subtitle = __( 'Use this WooCommerce and Facebook integration to:', 'facebook-for-woocommerce' );
		$benefits = array(
			__( 'Create an ad in a few steps', 'facebook-for-woocommerce' ),
			__( 'Use built-in best practices for online sales', 'facebook-for-woocommerce' ),
			__( 'Get reporting on sales and revenue', 'facebook-for-woocommerce' ),
		);

		?>
		<div id="wc-facebook-connection-box">
			<div class="logo"></div>
			<h1><?php echo esc_html($title); ?></h1>
			<h2><?php echo esc_html($subtitle); ?></h2>
			<ul class="benefits">
				<?php foreach ($benefits as $key => $benefit) : ?>
					<li class="benefit benefit-<?php echo esc_attr($key); ?>"><?php echo esc_html($benefit); ?></li>
				<?php endforeach; ?>
			</ul>
			<div class="actions">
				<?php if ($is_connected) : ?>
					<a href="<?php echo esc_url(facebook_for_woocommerce()->get_connection_handler()->get_disconnect_url()); ?>" class="button button-primary uninstall" onclick="return confirmDialog();">
						<?php esc_html_e('Disconnect', 'facebook-for-woocommerce'); ?>
					</a>
					<script>
						function confirmDialog() {
							return confirm('Are you sure you want to disconnect from Facebook?');
						}
					</script>
				<?php else : ?>
					<a href="<?php echo esc_url(facebook_for_woocommerce()->get_connection_handler()->get_connect_url()); ?>" class="button button-primary">
						<?php esc_html_e('Get Started', 'facebook-for-woocommerce'); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php
	}

	/**
	 * Renders the message handler script in the footer.
	 *
	 * @since 2.0.0
	 */
	public function render_message_handler() {
		if ( ! $this->is_current_screen_page() ) {
			return;
		}
	?>
		<script type="text/javascript">
			window.addEventListener('message', function(event) {
				console.log('message', event);
				if (event.origin !== 'https://www.commercepartnerhub.com') {
					return;
				}

				const message = event.data;
				const messageEvent = message.event;

				if (messageEvent === 'CommerceExtension::INSTALL' && message.success) {
					fetch('/wp-json/wc-facebook/v1/update_tokens', {
							method: 'POST',
							credentials: 'same-origin',
							headers: {
								'Content-Type': 'application/json',
								'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
							},
							body: JSON.stringify({
								access_token: message.access_token,
								merchant_access_token: message.merchant_access_token,
								page_access_token: message.page_access_token,
								product_catalog_id: message.catalog_id,
								pixel_id: message.pixel_id
							})
						})
						.then(response => {
							if (!response.ok) {
								throw new Error('Network response was not ok');
							}
							return response.json();
						})
						.then(data => {
							if (data.success) {
								window.location.reload();
							} else {
								console.error('Failed to update Facebook settings:', data.message);
							}
						})
						.catch(error => {
							console.error('Error updating Facebook settings:', error);
						});
				}

				// Handle iframe resize events
				if (messageEvent === 'CommerceExtension::RESIZE') {
					const iframe = document.getElementById('facebook-commerce-iframe');
					if (iframe && message.height) {
						iframe.height = message.height;
					}
				}

				// Handle uninstall/disconnect event
				if (messageEvent === 'CommerceExtension::UNINSTALL') {
					window.location.reload();
				}
			}, false);
		</script>
<?php
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_settings()
	{

		return array(

			array(
				'title' => __('Debug', 'facebook-for-woocommerce'),
				'type'  => 'title',
			),

			array(
				'id'       => \WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE,
				'title'    => __('Enable debug mode', 'facebook-for-woocommerce'),
				'type'     => 'checkbox',
				'desc'     => __( 'Log plugin events for debugging.', 'facebook-for-woocommerce' ),
				/* translators: %s URL to the documentation page. */
				'desc_tip' => sprintf( __( 'Only enable this if you are experiencing problems with the plugin. <a href="%s" target="_blank">Learn more</a>.', 'facebook-for-woocommerce' ), 'https://woocommerce.com/document/facebook-for-woocommerce/#debug-tools' ),
				'default'  => 'no',
			),

			array(
				'id'       => \WC_Facebookcommerce_Integration::SETTING_ENABLE_NEW_STYLE_FEED_GENERATOR,
				'title'    => __('Experimental! Enable new style feed generation', 'facebook-for-woocommerce'),
				'type'     => 'checkbox',
				'desc'     => __( 'Use new, memory improved, feed generation process.', 'facebook-for-woocommerce' ),
				/* translators: %s URL to the documentation page. */
				'desc_tip' => sprintf( __( 'This is an experimental feature in testing phase. Only enable this if you are experiencing problems with feed generation. <a href="%s" target="_blank">Learn more</a>.', 'facebook-for-woocommerce' ), 'https://woocommerce.com/document/facebook-for-woocommerce/#feed-generation' ),
				'default'  => 'no',
			),
			array('type' => 'sectionend'),
		);
	}
}
