<?php

namespace WooCommerce\Facebook\Tests\Unit\Handlers;

use PHPUnit\Framework\TestCase;
use WooCommerce\Facebook\Handlers\MetaExtension;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class MetaExtensionTest
 * 
 * @package WooCommerce\Facebook\Tests\Unit\Handlers
 */
class MetaExtensionTest extends TestCase
{
    /** @var MetaExtension */
    private $meta_extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->meta_extension = new MetaExtension();
    }

    /**
     * Test generateIframeSplashUrl generates correct URL with all parameters
     */
    public function test_generateIframeSplashUrl()
    {
        $plugin = $this->createMock(\WC_Facebookcommerce::class);
        $plugin->method('get_version')->willReturn('1.0.0');

        $url = MetaExtension::generateIframeSplashUrl(true, $plugin, 'test_business_id');

        $this->assertStringContainsString('access_client_token=' . MetaExtension::CLIENT_TOKEN, $url);
        $this->assertStringContainsString('app_id=' . MetaExtension::APP_ID, $url);
        $this->assertStringContainsString('business_name=' . urlencode(MetaExtension::BUSINESS_NAME), $url);
        $this->assertStringContainsString('external_business_id=test_business_id', $url);
        $this->assertStringContainsString('installed=1', $url);

        // Negative assertions - things that should NOT be in the URL (Sanity Check)
        $this->assertStringNotContainsString('utm_source=wordpress', $url, 'URL should not contain WordPress as source');
        $this->assertStringNotContainsString('access_token=', $url, 'URL should not contain access tokens');
        $this->assertStringNotContainsString('localhost', $url, 'URL should not contain localhost references');

        // Verify the URL structure is valid
        $this->assertTrue(filter_var($url, FILTER_VALIDATE_URL) !== false, 'Should be a valid URL');
    }

    /**
     * Test REST API token update with valid data
     */
    public function test_rest_update_fb_tokens_valid_data()
    {
        $request = new WP_REST_Request('POST', '/wc-facebook/v1/update_tokens');
        $request->set_body_params([
            'merchant_access_token' => 'test_merchant_token',
            'access_token' => 'test_access_token',
            'page_access_token' => 'test_page_token',
            'product_catalog_id' => '123456',
            'pixel_id' => '789012'
        ]);

        $response = MetaExtension::rest_update_fb_tokens($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    /**
     * Test REST API token update with missing required merchant token
     */
    public function test_rest_update_fb_tokens_missing_merchant_token()
    {
        $request = new WP_REST_Request('POST', '/wc-facebook/v1/update_tokens');
        $request->set_body_params([
            'access_token' => 'test_access_token',
            'page_access_token' => 'test_page_token'
        ]);

        $response = MetaExtension::rest_update_fb_tokens($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals('missing_token', $response->get_error_code());
    }

    /**
     * Test getCommerceExtensionIFrameURL with valid access token
     */
    public function test_getCommerceExtensionIFrameURL_with_valid_token()
    {
        $external_business_id = 'test_business_id';
        $access_token = 'test_access_token';

        // Mock the API response
        $api_response = [
            'commerce_extension' => [
                'uri' => 'https://www.commercepartnerhub.com/test-uri'
            ]
        ];

        // Mock callApi method using reflection
        $method = new \ReflectionMethod(MetaExtension::class, 'callApi');
        $method->setAccessible(true);

        $mock = $this->getMockBuilder(MetaExtension::class)
            ->onlyMethods(['callApi'])
            ->getMock();

        $mock->expects($this->once())
            ->method('callApi')
            ->with('GET', 'fbe_business', [
                'access_token' => $access_token,
                'fields' => 'commerce_extension',
                'fbe_external_business_id' => $external_business_id,
            ])
            ->willReturn($api_response);

        $url = $mock->getCommerceExtensionIFrameURL($external_business_id, $access_token);

        $this->assertEquals('https://www.commercepartnerhub.com/test-uri', $url);
    }

    /**
     * Test getCommerceExtensionIFrameURL with custom base URL override
     */
    public function test_getCommerceExtensionIFrameURL_with_base_url_override()
    {
        define('FACEBOOK_COMMERCE_EXTENSION_BASE_URL', 'https://test-override.com/');

        $external_business_id = 'test_business_id';
        $access_token = 'test_access_token';

        // Mock API response
        $api_response = [
            'commerce_extension' => [
                'uri' => 'https://www.commercepartnerhub.com/test-uri'
            ]
        ];

        $mock = $this->getMockBuilder(MetaExtension::class)
            ->onlyMethods(['callApi'])
            ->getMock();

        $mock->expects($this->once())
            ->method('callApi')
            ->willReturn($api_response);

        $url = $mock->getCommerceExtensionIFrameURL($external_business_id, $access_token);

        $this->assertEquals('https://test-override.com/test-uri', $url);
    }

    /**
     * Test getCommerceExtensionIFrameURL error handling
     */
    public function test_getCommerceExtensionIFrameURL_error_handling()
    {
        $external_business_id = 'test_business_id';
        $access_token = 'test_access_token';

        $mock = $this->getMockBuilder(MetaExtension::class)
            ->onlyMethods(['callApi'])
            ->getMock();

        $mock->expects($this->once())
            ->method('callApi')
            ->willThrowException(new \Exception('API Error'));

        $url = $mock->getCommerceExtensionIFrameURL($external_business_id, $access_token);

        $this->assertEquals('', $url);
    }
}
