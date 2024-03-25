<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LRA_Bid_History
{
    public function __construct()
    {
        add_action('init', array($this, 'register_bid_post_type'));
        add_action('woocommerce_single_product_summary', array($this, 'display_bid_history'), 30);
    }

    /**
     * Register the bid post type
     */
    public function register_bid_post_type()
    {
        $labels = array(
            'name'               => _x('Bids', 'post type general name', 'lra'),
            'singular_name'      => _x('Bid', 'post type singular name', 'lra'),
            'menu_name'          => _x('Bids', 'admin menu', 'lra'),
            'name_admin_bar'     => _x('Bid', 'add new on admin bar', 'lra'),
            'add_new'            => _x('Add New', 'bid', 'lra'),
            'add_new_item'       => __('Add New Bid', 'lra'),
            'new_item'           => __('New Bid', 'lra'),
            'edit_item'          => __('Edit Bid', 'lra'),
            'view_item'          => __('View Bid', 'lra'),
            'all_items'          => __('All Bids', 'lra'),
            'search_items'       => __('Search Bids', 'lra'),
            'parent_item_colon'  => __('Parent Bids:', 'lra'),
            'not_found'          => __('No bids found.', 'lra'),
            'not_found_in_trash' => __('No bids found in Trash.', 'lra')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title')
        );

        register_post_type('lra_bid', $args);
    }

    /**
     * Display bid history on the single product page
     */
    public function display_bid_history()
    {
        global $post;

        if ($post->post_type !== 'product') {
            return;
        }

        $product = wc_get_product($post->ID);

        if ($product->get_type() !== 'auction') {
            return;
        }

        $bids = $this->get_bids($post->ID);

        if (empty($bids)) {
            return;
        }
        ?>
        <div class="lra-bid-history">
            <h3><?php _e('Bid History', 'lra'); ?></h3>
            <table class="lra-bid-history-table">
                <thead>
                    <tr>
                        <th><?php _e('Bidder', 'lra'); ?></th>
                        <th><?php _e('Amount', 'lra'); ?></th>
                        <th><?php _e('Time', 'lra'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bids as $bid) : ?>
                        <tr>
                            <td><?php echo esc_html($bid->bidder_name); ?></td>
                            <td><?php echo wc_price($bid->bid_amount); ?></td>
                            <td><?php echo esc_html($bid->bid_time); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get bids for a specific auction product
     */
    private function get_bids($product_id)
    {
        $bids = get_posts(array(
            'post_type'   => 'lra_bid',
            'post_status' => 'publish',
            'meta_query'  => array(
                array(
                    'key'   => '_auction_id',
                    'value' => $product_id,
                ),
            ),
            'orderby'     => 'meta_value_num',
            'meta_key'    => '_bid_amount',
            'order'       => 'DESC',
        ));

        $bid_data = array();

        foreach ($bids as $bid) {
            $bid_data[] = (object) array(
                'bidder_name' => get_post_meta($bid->ID, '_bidder_name', true),
                'bid_amount'  => get_post_meta($bid->ID, '_bid_amount', true),
                'bid_time'    => get_post_meta($bid->ID, '_bid_time', true),
            );
        }

        return $bid_data;
    }

    /**
     * Create a new bid
     */
    public function create_bid($auction_id, $bidder_name, $bid_amount)
    {
        $bid_data = array(
            'post_title'  => sprintf(__('Bid on Auction #%d', 'lra'), $auction_id),
            'post_status' => 'publish',
            'post_type'   => 'lra_bid',
            'meta_input'  => array(
                '_auction_id'  => $auction_id,
                '_bidder_name' => $bidder_name,
                '_bid_amount'  => $bid_amount,
                '_bid_time'    => current_time('mysql'),
            ),
        );

        return wp_insert_post($bid_data);
    }

    /**
     * Get the highest bid for a specific auction product
     */
    public function get_highest_bid($product_id)
    {
        $bids = get_posts(array(
            'post_type'   => 'lra_bid',
            'post_status' => 'publish',
            'meta_query'  => array(
                array(
                    'key'   => '_auction_id',
                    'value' => $product_id,
                ),
            ),
            'orderby'     => 'meta_value_num',
            'meta_key'    => '_bid_amount',
            'order'       => 'DESC',
            'numberposts' => 1,
        ));

        if (!empty($bids)) {
            $highest_bid = $bids[0];
            return (object) array(
                'bidder_name' => get_post_meta($highest_bid->ID, '_bidder_name', true),
                'bid_amount'  => get_post_meta($highest_bid->ID, '_bid_amount', true),
            );
        }

        return null;
    }
}
