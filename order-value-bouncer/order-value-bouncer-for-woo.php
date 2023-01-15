<?php

/**
 * Plugin Name: Order Value Bouncer - Minimum Order Amount for WooCommerce
 * Plugin URI: https://danielnordmark.se/plugins/order-value-bouncer-for-woocommerce/
 * Description: Set a minimum order amount for WooCommerce and displays a message if the order total is below the minimum. If the order amount is bellow the setting the user won't be able to go to the checkout page.
 * Version: 0.1b
 * Author: Daniel Nordmark
 * Author URI: https://danielnordmark.se
 * License: GPL2
 */

defined('ABSPATH') || exit;

//Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


// Add the settings page
function minimum_order_amount_add_settings_page()
{
    add_options_page('Minimum Order Amount', 'Minimum Order Amount', 'manage_options', 'minimum-order-amount', 'minimum_order_amount_settings_page');
}
add_action('admin_menu', 'minimum_order_amount_add_settings_page');

// Register the settings
function minimum_order_amount_register_settings()
{
    register_setting('minimum-order-amount-settings-group', 'minimum_order_amount_minimum', 'minimum_order_amount_minimum_validate');
    register_setting('minimum-order-amount-settings-group', 'minimum_order_amount_message', 'minimum_order_amount_message_validate');
}
add_action('admin_init', 'minimum_order_amount_register_settings');

// Validate minimum order amount setting
function minimum_order_amount_minimum_validate($input)
{
    $input = intval($input);
    if (!is_int($input) || $input < 0) {
        add_settings_error('minimum_order_amount_minimum', 'minimum_order_amount_minimum_error', 'Minimum Order Amount should be an integer greater than or equal to 0');
        return get_option('minimum_order_amount_minimum');
    }
    return $input;
}

// Validate message setting
function minimum_order_amount_message_validate($input)
{
    $input = sanitize_text_field($input);
    $input = wp_kses_post($input);
    if (empty($input)) {
        add_settings_error('minimum_order_amount_message', 'minimum_order_amount_message_error', 'Message should not be empty');
        return get_option('minimum_order_amount_message');
    }
    return $input;
}

// The settings page
function minimum_order_amount_settings_page()
{
?>
    <div class="wrap">
        <h1>Order Value Bouncer for WooCommerce</h1>
        <p><strong>Prevents customer to checkout if their order value is below the minimum order amount.</strong></p>
        <form method="post" action="options.php">
            <?php settings_fields('minimum-order-amount-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Minimum Order Amount</th>
                    <td>
                        <input type="text" name="minimum_order_amount_minimum" value="<?php echo esc_attr(get_option('minimum_order_amount_minimum')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cart message</th>
                    <td>
                        <textarea name="minimum_order_amount_message" rows="5" cols="50"><?php echo esc_attr(get_option('minimum_order_amount_message')); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

<?php
}

// Get the minimum order amount and message from the settings
$minimum = get_option('minimum_order_amount_minimum', 50);
$message = get_option('minimum_order_amount_message', 'Your current order total is below the minimum order amount.');

// Display a message in the cart if the order total is below the minimum
function minimum_order_amount_cart_notice()
{
    global $minimum;
    global $message;
    $cart_total = WC()->cart->total;
    if ($cart_total < $minimum) {
        wc_add_notice($message, 'error');
        wp_redirect(wc_get_page_permalink('cart'));
        exit;
    }
}

//Redirect user back to previous page if the order total is below the minimum
function minimum_order_amount_checkout_notice()
{
    global $minimum;
    global $message;
    $cart_total = WC()->cart->total;
    if ($cart_total < $minimum) {
        wc_add_notice($message, 'error');
        wp_redirect(wc_get_page_permalink('cart'));
        exit;
    }
}

add_action('woocommerce_check_cart_items', 'minimum_order_amount_cart_notice');
add_action('woocommerce_before_checkout_process', 'minimum_order_amount_checkout_notice');
