<?php
/*
Plugin Name: Legacy Reserve Auctions
Plugin URI: https://example.com/
Description: Adds auction functionality to WooCommerce products.
Version: 1.0.0
Author: Nic Wienandt / Claude 3
Author URI: https://example.com/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-lra-auction-product.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-lra-bid-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-lra-bidding-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-lra-cron-jobs.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-lra-email-notifications.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-lra-user-settings.php';

// Enqueue CSS file
function lra_enqueue_styles() {
    wp_enqueue_style('lra-styles', plugin_dir_url(__FILE__) . 'assets/css/lra-styles.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'lra_enqueue_styles');

// Initialize the plugin
function lra_init()
{
    // Instantiate the classes
    new LRA_Auction_Product();
    new LRA_Bid_History();
    new LRA_Bidding_Interface();
    new LRA_Cron_Jobs();
    new LRA_Email_Notifications();
    new LRA_User_Settings();
}
add_action('plugins_loaded', 'lra_init');
