<?php
namespace WooCommerce\Facebook\Tests\Unit\Admin\Settings_Screens;

use PHPUnit\Framework\TestCase;
use WooCommerce\Facebook\Admin\Settings_Screens\Connection;
use WooCommerce\Facebook\Handlers\MetaExtension;

/**
 * Class ConnectionTest
 * 
 * @package WooCommerce\Facebook\Tests\Unit\Admin\Settings_Screens
 */
class ConnectionTest extends TestCase {
    /** @var Connection */
    private $connection;

    protected function setUp(): void {
        parent::setUp();
        $this->connection = new Connection();
    }

    /**
     * Test that iframe connection is enabled by default
     */
    public function test_use_iframe_connection_enabled_by_default() {
        $reflection = new \ReflectionClass($this->connection);
        $method = $reflection->getMethod('use_iframe_connection');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->connection));
    }

    /**
     * Test that render_facebook_box_iframe generates correct iframe with URL
     */
    public function test_render_facebook_box_iframe() {
        // Mock the connection handler
        $connection_handler = $this->createMock(\WooCommerce\Facebook\Handlers\Connection::class);
        $connection_handler->method('get_plugin')->willReturn(new \stdClass());
        $connection_handler->method('get_external_business_id')->willReturn('test_business_id');

        // Mock facebook_for_woocommerce() global function
        global $facebook_for_woocommerce;
        $facebook_for_woocommerce = $this->createMock(\WC_Facebookcommerce::class);
        $facebook_for_woocommerce->method('get_connection_handler')
            ->willReturn($connection_handler);

        // Start output buffering
        ob_start();
        
        $reflection = new \ReflectionClass($this->connection);
        $method = $reflection->getMethod('render_facebook_box_iframe');
        $method->setAccessible(true);
        $method->invoke($this->connection, true);

        $output = ob_get_clean();

        // Assert iframe HTML structure
        $this->assertStringContainsString('<iframe', $output);
        $this->assertStringContainsString('id="facebook-commerce-iframe"', $output);
        $this->assertStringContainsString('width="100%"', $output);
        $this->assertStringContainsString('height="600"', $output);
        $this->assertStringContainsString('frameborder="0"', $output);
        
        // Assert URL contains required parameters
        $this->assertStringContainsString('external_business_id=test_business_id', $output);
        $this->assertStringContainsString('installed=1', $output);
    }

    /**
     * Test that render_message_handler generates correct JavaScript
     */
    public function test_render_message_handler() {
        // Mock is_current_screen_page to return true
        $reflection = new \ReflectionClass($this->connection);
        $method = $reflection->getMethod('is_current_screen_page');
        $method->setAccessible(true);
        
        $connection_mock = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['is_current_screen_page'])
            ->getMock();
        
        $connection_mock->method('is_current_screen_page')
            ->willReturn(true);

        // Start output buffering
        ob_start();
        
        $connection_mock->render_message_handler();
        
        $output = ob_get_clean();

        // Assert JavaScript event listeners and handlers
        $this->assertStringContainsString('window.addEventListener(\'message\'', $output);
        $this->assertStringContainsString('CommerceExtension::INSTALL', $output);
        $this->assertStringContainsString('CommerceExtension::RESIZE', $output);
        $this->assertStringContainsString('CommerceExtension::UNINSTALL', $output);
        
        // Assert fetch request setup
        $this->assertStringContainsString('fetch(\'/wp-json/wc-facebook/v1/update_tokens\'', $output);
        $this->assertStringContainsString('method: \'POST\'', $output);
        $this->assertStringContainsString('credentials: \'same-origin\'', $output);
    }

    /**
     * Test that render_message_handler doesn't output when not on current screen
     */
    public function test_render_message_handler_not_current_screen() {
        $connection_mock = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['is_current_screen_page'])
            ->getMock();
        
        $connection_mock->method('is_current_screen_page')
            ->willReturn(false);

        ob_start();
        $connection_mock->render_message_handler();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    /**
     * Test that render_message_handler doesn't output when merchant token exists
     */
    public function test_render_message_handler_with_merchant_token() {
        $connection_mock = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['is_current_screen_page'])
            ->getMock();
        
        $connection_mock->method('is_current_screen_page')
            ->willReturn(true);

        // Mock get_option to return a merchant token
        global $wp_options;
        $wp_options = ['wc_facebook_merchant_access_token' => 'test_token'];
        
        ob_start();
        $connection_mock->render_message_handler();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
} 