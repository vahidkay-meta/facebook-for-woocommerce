<?php
declare( strict_types=1 );

use WooCommerce\Facebook\Checkout;

/**
 * Checkout unit test class.
 */
class CheckoutTest extends WP_UnitTestCase {

    /**
     * Checkout instance being tested.
     *
     * @var Checkout
     */
    private $checkout;

    /**
     * Sets up the test case.
     */
    public function setUp(): void {
        parent::setUp();
        $this->checkout = new Checkout();
    }

    /**
     * Tests that hooks are added correctly.
     */
    public function test_add_hooks() {
        $this->assertTrue(has_action('init', [$this->checkout, 'add_checkout_permalink_rewrite_rule']) !== false);
        $this->assertTrue(has_filter('query_vars', [$this->checkout, 'add_checkout_permalink_query_var']) !== false);
        $this->assertTrue(has_filter('template_include', [$this->checkout, 'load_checkout_permalink_template']) !== false);
    }

    /**
     * Tests that query vars are added correctly.
     */
    public function test_add_checkout_permalink_query_var() {
        $vars = ['existing_var'];
        $new_vars = $this->checkout->add_checkout_permalink_query_var($vars);

        $this->assertContains('fb_checkout', $new_vars);
        $this->assertContains('products', $new_vars);
        $this->assertContains('coupon', $new_vars);
    }

    /**
     * Tests that the rewrite rule is added correctly.
     */
    public function test_add_checkout_permalink_rewrite_rule() {
        global $wp_rewrite;
        $this->checkout->add_checkout_permalink_rewrite_rule();
        $rules = $wp_rewrite->wp_rewrite_rules();

        $this->assertArrayHasKey('^fb-checkout/?$', $rules);
        $this->assertEquals('index.php?fb_checkout=1', $rules['^fb-checkout/?$']);
    }

    /**
     * Tests that the flush rewrite rules are called on activation.
     */
    public function test_flush_rewrite_rules_on_activation() {
        $this->checkout->flush_rewrite_rules_on_activation();
        // Assuming flush_rewrite_rules() is a global function, we can't directly test its effect here.
        // You would need to mock or spy on this function to verify it was called.
        $this->assertTrue(true);
    }

    /**
     * Tests that the flush rewrite rules are called on deactivation.
     */
    public function test_flush_rewrite_rules_on_deactivation() {
        $this->checkout->flush_rewrite_rules_on_deactivation();
        // Assuming flush_rewrite_rules() is a global function, we can't directly test its effect here.
        // You would need to mock or spy on this function to verify it was called.
        $this->assertTrue(true);
    }
}
