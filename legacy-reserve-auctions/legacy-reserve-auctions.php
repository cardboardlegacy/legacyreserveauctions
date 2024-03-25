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

/**
 * Add custom product type for auctions
 */
function lra_add_auction_product_type()
{
    class WC_Product_Auction extends WC_Product
    {
        public function __construct($product)
        {
            $this->product_type = 'auction';
            parent::__construct($product);
        }
    }

    add_filter('product_type_selector', 'lra_add_auction_product_type_selector');
    add_filter('woocommerce_product_class', 'lra_woocommerce_product_class', 10, 2);
}
add_action('init', 'lra_add_auction_product_type');

/**
 * Add "Auction" to the product type dropdown
 */
function lra_add_auction_product_type_selector($types)
{
    $types['auction'] = __('Auction', 'lra');
    return $types;
}

/**
 * Load the auction product class
 */
function lra_woocommerce_product_class($classname, $product_type)
{
    if ($product_type === 'auction') {
        $classname = 'WC_Product_Auction';
    }
    return $classname;
}

/**
 * Add "Auction Details" tab to product meta box
 */
function lra_add_auction_tab($tabs)
{
    $tabs['auction'] = array(
        'label'    => __('Auction Details', 'lra'),
        'target'   => 'auction_options',
        'class'    => array('show_if_auction'),
        'priority' => 21,
    );
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'lra_add_auction_tab');

/**
 * Add fields to "Auction Details" tab
 */
function lra_add_auction_fields()
{
    global $post;
    ?>
    <div id='auction_options' class='panel woocommerce_options_panel'>
        <?php
        woocommerce_wp_text_input(array(
            'id'          => '_auction_start_time',
            'label'       => __('Start Time', 'lra'),
            'placeholder' => 'YYYY-MM-DD HH:MM',
            'desc_tip'    => 'true',
            'description' => __('Set the start time for the auction.', 'lra'),
            'class'       => 'lra-datetimepicker',
        ));

        woocommerce_wp_text_input(array(
            'id'          => '_auction_end_time',
            'label'       => __('End Time', 'lra'),
            'placeholder' => 'YYYY-MM-DD HH:MM',
            'desc_tip'    => 'true',
            'description' => __('Set the end time for the auction.', 'lra'),
            'class'       => 'lra-datetimepicker',
        ));

        woocommerce_wp_text_input(array(
            'id'          => '_auction_start_price',
            'label'       => __('Starting Price', 'lra'),
            'placeholder' => '0.00',
            'desc_tip'    => 'true',
            'description' => __('Set the starting price for the auction.', 'lra'),
            'data_type'   => 'price',
        ));

        woocommerce_wp_text_input(array(
            'id'          => '_auction_reserve_price',
            'label'       => __('Reserve Price', 'lra'),
            'placeholder' => '0.00',
            'desc_tip'    => 'true',
            'description' => __('Set the reserve price for the auction.', 'lra'),
            'data_type'   => 'price',
        ));

        woocommerce_wp_text_input(array(
            'id'          => '_auction_buy_it_now_price',
            'label'       => __('Buy It Now Price', 'lra'),
            'placeholder' => '0.00',
            'desc_tip'    => 'true',
            'description' => __('Set the Buy It Now price for the auction.', 'lra'),
            'data_type'   => 'price',
        ));

        woocommerce_wp_checkbox(array(
            'id'          => '_auction_anti_sniping',
            'label'       => __('Anti-Sniping', 'lra'),
            'description' => __('Enable anti-sniping for the auction.', 'lra'),
        ));

        woocommerce_wp_checkbox(array(
            'id'          => '_auction_auto_republish',
            'label'       => __('Auto Republish', 'lra'),
            'description' => __('Automatically republish the auction if it doesn\'t receive bids or meet the reserve price.', 'lra'),
        ));

        woocommerce_wp_text_input(array(
            'id'          => '_auction_consigner',
            'label'       => __('Consigner', 'lra'),
            'placeholder' => 'Enter consigner name',
            'desc_tip'    => 'true',
            'description' => __('Enter the name of the person selling the item.', 'lra'),
        ));

        woocommerce_wp_text_input(array(
            'id'          => '_auction_internal_item_number',
            'label'       => __('Internal Item Number', 'lra'),
            'placeholder' => 'Enter internal item number',
            'desc_tip'    => 'true',
            'description' => __('Enter the unique internal item number for the auction item.', 'lra'),
        ));
        ?>
    </div>
    <?php
}
add_action('woocommerce_product_data_panels', 'lra_add_auction_fields');

/**
 * Save auction fields
 */
function lra_save_auction_fields($post_id)
{
    $auction_start_time = isset($_POST['_auction_start_time']) ? sanitize_text_field($_POST['_auction_start_time']) : '';
    $auction_end_time = isset($_POST['_auction_end_time']) ? sanitize_text_field($_POST['_auction_end_time']) : '';
    $auction_start_price = isset($_POST['_auction_start_price']) ? wc_format_decimal($_POST['_auction_start_price']) : '';
    $auction_reserve_price = isset($_POST['_auction_reserve_price']) ? wc_format_decimal($_POST['_auction_reserve_price']) : '';
    $auction_buy_it_now_price = isset($_POST['_auction_buy_it_now_price']) ? wc_format_decimal($_POST['_auction_buy_it_now_price']) : '';
    $auction_anti_sniping = isset($_POST['_auction_anti_sniping']) ? 'yes' : 'no';
    $auction_auto_republish = isset($_POST['_auction_auto_republish']) ? 'yes' : 'no';
    $auction_consigner = isset($_POST['_auction_consigner']) ? sanitize_text_field($_POST['_auction_consigner']) : '';
    $auction_internal_item_number = isset($_POST['_auction_internal_item_number']) ? sanitize_text_field($_POST['_auction_internal_item_number']) : '';

    update_post_meta($post_id, '_auction_start_time', $auction_start_time);
    update_post_meta($post_id, '_auction_end_time', $auction_end_time);
    update_post_meta($post_id, '_auction_start_price', $auction_start_price);
    update_post_meta($post_id, '_auction_reserve_price', $auction_reserve_price);
    update_post_meta($post_id, '_auction_buy_it_now_price', $auction_buy_it_now_price);
    update_post_meta($post_id, '_auction_anti_sniping', $auction_anti_sniping);
    update_post_meta($post_id, '_auction_auto_republish', $auction_auto_republish);
    update_post_meta($post_id, '_auction_consigner', $auction_consigner);
    update_post_meta($post_id, '_auction_internal_item_number', $auction_internal_item_number);
}
add_action('woocommerce_process_product_meta_auction', 'lra_save_auction_fields');

/**
 * Display auction fields on the front end
 */
function lra_display_auction_fields()
{
    global $post;

    if ($post->post_type !== 'product') {
        return;
    }

    $product = wc_get_product($post->ID);

    if ($product->get_type() !== 'auction') {
        return;
    }

    $auction_start_time = get_post_meta($post->ID, '_auction_start_time', true);
    $auction_end_time = get_post_meta($post->ID, '_auction_end_time', true);
    $auction_start_price = get_post_meta($post->ID, '_auction_start_price', true);
    $auction_reserve_price = get_post_meta($post->ID, '_auction_reserve_price', true);
    $auction_buy_it_now_price = get_post_meta($post->ID, '_auction_buy_it_now_price', true);
    $auction_anti_sniping = get_post_meta($post->ID, '_auction_anti_sniping', true);
    $auction_auto_republish = get_post_meta($post->ID, '_auction_auto_republish', true);
    $auction_consigner = get_post_meta($post->ID, '_auction_consigner', true);
    $auction_internal_item_number = get_post_meta($post->ID, '_auction_internal_item_number', true);

    ?>
    <div class="lra-auction-details">
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Start Time:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo esc_html($auction_start_time); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('End Time:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo esc_html($auction_end_time); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Starting Price:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo wc_price($auction_start_price); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Reserve Price:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo wc_price($auction_reserve_price); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Buy It Now Price:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo wc_price($auction_buy_it_now_price); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Anti-Sniping:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo $auction_anti_sniping === 'yes' ? __('Enabled', 'lra') : __('Disabled', 'lra'); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Auto Republish:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo $auction_auto_republish === 'yes' ? __('Enabled', 'lra') : __('Disabled', 'lra'); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Consigner:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo esc_html($auction_consigner); ?></span>
        </div>
        <div class="lra-auction-field">
            <span class="lra-auction-label"><?php _e('Internal Item Number:', 'lra'); ?></span>
            <span class="lra-auction-value"><?php echo esc_html($auction_internal_item_number); ?></span>
        </div>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'lra_display_auction_fields', 25);

/**
 * Enqueue scripts and styles
 */
function lra_enqueue_scripts()
{
    wp_enqueue_style('lra-styles', plugin_dir_url(__FILE__) . 'css/lra-styles.css', array(), '1.0.0');
    wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_script('jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'), '1.12.1', true);
    wp_enqueue_script('jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js', array('jquery', 'jquery-ui-datepicker'), '1.6.3', true);
    wp_enqueue_script('lra-scripts', plugin_dir_url(__FILE__) . 'js/lra-scripts.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'lra_enqueue_scripts');
add_action('admin_enqueue_scripts', 'lra_enqueue_scripts');

/**
 * Initialize datepicker and timepicker
 */
function lra_initialize_datepicker()
{
    ?>
    <script>
        jQuery(function($) {
            $('.lra-datetimepicker').datetimepicker({
                dateFormat: 'yy-mm-dd',
                timeFormat: 'HH:mm',
                stepMinute: 1,
                oneLine: true
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'lra_initialize_datepicker');

/**
 * Modify product search query to include internal item number
 */
function lra_search_by_internal_item_number($query)
{
    if (!is_admin() && $query->is_main_query() && $query->is_search() && isset($_GET['s'])) {
        $search_term = sanitize_text_field($_GET['s']);
        $query->set('meta_query', array(
            array(
                'key'     => '_auction_internal_item_number',
                'value'   => $search_term,
                'compare' => 'LIKE',
            ),
        ));
    }
}
add_action('pre_get_posts', 'lra_search_by_internal_item_number'); 
