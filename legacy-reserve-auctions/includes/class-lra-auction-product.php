<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LRA_Auction_Product
{
    public function __construct()
    {
        add_action('init', array($this, 'register_auction_product_type'));
        add_filter('product_type_selector', array($this, 'add_auction_product_type'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_auction_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_auction_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_auction_fields'));
        add_action('woocommerce_single_product_summary', array($this, 'display_auction_fields'), 25);
        add_filter('woocommerce_product_class', array($this, 'woocommerce_product_class'), 10, 2);
    }

    /**
     * Register the auction product type
     */
    public function register_auction_product_type()
    {
        class WC_Product_Auction extends WC_Product
        {
            public function __construct($product)
            {
                $this->product_type = 'auction';
                parent::__construct($product);
            }
        }
    }

    /**
     * Add the auction product type to the product type selector
     */
    public function add_auction_product_type($types)
    {
        $types['auction'] = __('Auction', 'lra');
        return $types;
    }

    /**
     * Load the auction product class
     */
    public function woocommerce_product_class($classname, $product_type)
    {
        if ($product_type === 'auction') {
            $classname = 'WC_Product_Auction';
        }
        return $classname;
    }

    /**
     * Add "Auction Details" tab to product meta box
     */
    public function add_auction_tab($tabs)
    {
        $tabs['auction'] = array(
            'label'    => __('Auction Details', 'lra'),
            'target'   => 'auction_options',
            'class'    => array('show_if_auction'),
            'priority' => 21,
        );
        return $tabs;
    }

    /**
     * Add fields to "Auction Details" tab
     */
    public function add_auction_fields()
    {
        global $post;
        $post_id = $post->ID;
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

    /**
     * Save auction fields
     */
    public function save_auction_fields($post_id)
    {
        $product = wc_get_product($post_id);

        if ($product->get_type() !== 'auction') {
            return;
        }

        $auction_fields = array(
            '_auction_start_time',
            '_auction_end_time',
            '_auction_start_price',
            '_auction_reserve_price',
            '_auction_buy_it_now_price',
            '_auction_anti_sniping',
            '_auction_auto_republish',
            '_auction_consigner',
            '_auction_internal_item_number',
        );

        foreach ($auction_fields as $field) {
            $value = isset($_POST[$field]) ? wc_clean(wp_unslash($_POST[$field])) : '';
            $product->update_meta_data($field, $value);
        }

        $product->save();
    }

    /**
     * Display auction fields on the front end
     */
    public function display_auction_fields()
    {
        global $post;

        if ($post->post_type !== 'product') {
            return;
        }

        $product = wc_get_product($post->ID);

        if ($product->get_type() !== 'auction') {
            return;
        }

        $auction_fields = array(
            '_auction_start_time',
            '_auction_end_time',
            '_auction_start_price',
            '_auction_reserve_price',
            '_auction_buy_it_now_price',
            '_auction_anti_sniping',
            '_auction_auto_republish',
            '_auction_consigner',
            '_auction_internal_item_number',
        );

        foreach ($auction_fields as $field) {
            $value = $product->get_meta($field);

            if (!empty($value)) {
                $label = str_replace('_', ' ', $field);
                $label = ucwords(trim($label, ' _'));
                ?>
                <div class="lra-auction-field">
                    <span class="lra-auction-label"><?php echo esc_html($label); ?>:</span>
                    <span class="lra-auction-value"><?php echo esc_html($value); ?></span>
                </div>
                <?php
            }
        }
    }
}
