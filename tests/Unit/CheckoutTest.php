<?php
declare( strict_types=1 );

use WooCommerce\Facebook\Checkout;

/**
 * Checkout unit test class.
 */
class CheckoutTest extends WP_UnitTestCase {

    /**
     * The Checkout instance being tested.
     *
     * @var Checkout
     */
    protected $checkout;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->checkout = new Checkout();
    }

    /**
     * Tests if hooks are added correctly.
     *
     * @return void
     */
    public function testAddHooks() {
        $this->assertNotFalse(has_action('init', array($this->checkout, 'add_checkout_permalink_rewrite_rule')));
        $this->assertNotFalse(has_filter('query_vars', array($this->checkout, 'add_checkout_permalink_query_var')));
        $this->assertNotFalse(has_filter('template_include', array($this->checkout, 'load_checkout_permalink_template')));
    }

    /**
     * Tests if the checkout permalink rewrite rule is added.
     *
     * @return void
     */
    public function testAddCheckoutPermalinkRewriteRule() {
        do_action('init');

        global $wp_rewrite;
        $rules = $wp_rewrite->wp_rewrite_rules();
        $this->assertArrayHasKey('^fb-checkout/?$', $rules);
    }

    /**
     * Tests if the checkout permalink query vars are added.
     *
     * @return void
     */
    public function testAddCheckoutPermalinkQueryVar() {
        $vars = apply_filters('query_vars', array());

        $this->assertContains('fb_checkout', $vars);
        $this->assertContains('products', $vars);
        $this->assertContains('coupon', $vars);
    }

    /**
     * Tests if the checkout permalink template is loaded.
     *
     * @return void
     */
    public function testLoadCheckoutPermalinkTemplate() {
        $this->mockFunction('get_query_var', function($var) {
            $values = [
                'fb_checkout' => 1,
                'products' => '1:2,3:4',
                'coupon' => 'DISCOUNT10'
            ];
            return $values[$var] ?? null;
        });

        $cart = $this->createMock(WC_Cart::class);
        $cart->expects($this->once())->method('empty_cart');
        $cart->expects($this->exactly(2))->method('add_to_cart')->withConsecutive(
            [1, 2],
            [3, 4]
        );
        $cart->expects($this->once())->method('apply_coupon')->with('DISCOUNT10');

        WC()->cart = $cart;

        ob_start();
        $this->checkout->load_checkout_permalink_template();
        $output = ob_get_clean();

        $this->assertStringContainsString('Templates/CheckoutTemplate.php', $output);
    }

    /**
     * Mocks a global function.
     *
     * @param string $functionName The name of the function to mock.
     * @param callable $callback The callback to use as the mock.
     * @return void
     */
    protected function mockFunction($functionName, $callback) {
        runkit_function_redefine($functionName, '', $callback);
    }
}
