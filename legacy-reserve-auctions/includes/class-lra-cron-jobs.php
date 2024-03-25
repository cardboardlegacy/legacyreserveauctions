<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LRA_Cron_Jobs
{
    public function __construct()
    {
        add_action('wp', array($this, 'schedule_cron_jobs'));
        add_action('lra_process_ended_auctions', array($this, 'process_ended_auctions'));
        add_action('lra_cleanup_expired_auctions', array($this, 'cleanup_expired_auctions'));
    }

    /**
     * Schedule cron jobs
     */
    public function schedule_cron_jobs()
    {
        if (!wp_next_scheduled('lra_process_ended_auctions')) {
            wp_schedule_event(time(), 'hourly', 'lra_process_ended_auctions');
        }

        if (!wp_next_scheduled('lra_cleanup_expired_auctions')) {
            wp_schedule_event(time(), 'daily', 'lra_cleanup_expired_auctions');
        }
    }

    /**
     * Process ended auctions
     */
    public function process_ended_auctions()
    {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_auction_end_time',
                    'value'   => current_time('mysql'),
                    'compare' => '<',
                    'type'    => 'DATETIME',
                ),
                array(
                    'key'     => '_auction_closed',
                    'value'   => '0',
                    'compare' => '=',
                ),
            ),
        );

        $auctions = get_posts($args);

        foreach ($auctions as $auction) {
            $auction_id = $auction->ID;

            // Anti-sniping: Extend auction if bids are placed in the last 10 seconds
            $anti_sniping = get_post_meta($auction_id, '_auction_anti_sniping', true);
            if ($anti_sniping === 'yes') {
                $last_bid_time = get_post_meta($auction_id, '_auction_last_bid_time', true);
                $end_time = get_post_meta($auction_id, '_auction_end_time', true);
                $end_time_timestamp = strtotime($end_time);

                if ($last_bid_time && $end_time_timestamp - $last_bid_time < 10) {
                    $extended_end_time = date('Y-m-d H:i:s', $end_time_timestamp + 30);
                    update_post_meta($auction_id, '_auction_end_time', $extended_end_time);
                    continue;
                }
            }

            // Get the highest bid
            $highest_bid = $this->get_highest_bid($auction_id);

            if ($highest_bid) {
                // Update the auction as closed
                update_post_meta($auction_id, '_auction_closed', '1');

                // Get the reserve price
                $reserve_price = get_post_meta($auction_id, '_auction_reserve_price', true);

                // Check if the reserve price is met
                if ($highest_bid->bid_amount >= $reserve_price) {
                    // Process the winning bid
                    $this->process_winning_bid($auction_id, $highest_bid->user_id, $highest_bid->bid_amount);
                } else {
                    // Handle reserve price not met
                    $this->handle_reserve_not_met($auction_id);
                }
            } else {
                // Handle no bids
                $this->handle_no_bids($auction_id);
            }
        }
    }

    /**
     * Cleanup expired auctions
     */
    public function cleanup_expired_auctions()
    {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_auction_closed',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
            'date_query'     => array(
                array(
                    'column' => 'post_modified',
                    'before' => '1 month ago',
                ),
            ),
        );

        $auctions = get_posts($args);

        foreach ($auctions as $auction) {
            $auction_id = $auction->ID;
            wp_trash_post($auction_id);
        }
    }

    /**
     * Get the highest bid for an auction
     */
    private function get_highest_bid($auction_id)
    {
        $bid_history = new LRA_Bid_History();
        return $bid_history->get_highest_bid($auction_id);
    }

    /**
     * Process the winning bid
     */
    private function process_winning_bid($auction_id, $user_id, $bid_amount)
    {
        // Add the winning bid as an order
        $order = wc_create_order(array(
            'customer_id' => $user_id,
            'status'      => 'completed',
        ));

        $order->add_product(wc_get_product($auction_id), 1, array(
            'subtotal' => $bid_amount,
            'total'    => $bid_amount,
        ));

        $order->set_address(array(
            'first_name' => get_user_meta($user_id, 'billing_first_name', true),
            'last_name'  => get_user_meta($user_id, 'billing_last_name', true),
            'email'      => get_user_meta($user_id, 'billing_email', true),
            'phone'      => get_user_meta($user_id, 'billing_phone', true),
            'address_1'  => get_user_meta($user_id, 'billing_address_1', true),
            'address_2'  => get_user_meta($user_id, 'billing_address_2', true),
            'city'       => get_user_meta($user_id, 'billing_city', true),
            'state'      => get_user_meta($user_id, 'billing_state', true),
            'postcode'   => get_user_meta($user_id, 'billing_postcode', true),
            'country'    => get_user_meta($user_id, 'billing_country', true),
        ), 'billing');

        $order->calculate_totals();
        $order->update_status('completed');

        // Update the auction meta
        update_post_meta($auction_id, '_auction_winning_bid', $bid_amount);
        update_post_meta($auction_id, '_auction_winner', $user_id);
        update_post_meta($auction_id, '_auction_order_id', $order->get_id());

        // Send notification email to the winner
        WC()->mailer()->get_emails()['WC_Email_Auction_Won']->trigger($auction_id, $user_id);
    }

    /**
     * Handle reserve price not met
     */
    private function handle_reserve_not_met($auction_id)
    {
        // Perform actions when the reserve price is not met
        // For example, send notification emails, update auction status, etc.
    }

    /**
     * Handle no bids
     */
    private function handle_no_bids($auction_id)
    {
        // Perform actions when there are no bids
        // For example, send notification emails, update auction status, etc.

        // Check if auto-republish is enabled
        $auto_republish = get_post_meta($auction_id, '_auction_auto_republish', true);
        if ($auto_republish === 'yes') {
            $this->republish_auction($auction_id);
        }
    }

    /**
     * Republish an auction
     */
    private function republish_auction($auction_id)
    {
        // Reset the auction end time
        $end_time = date('Y-m-d H:i:s', strtotime('+1 week'));
        update_post_meta($auction_id, '_auction_end_time', $end_time);

        // Reset the auction status
        update_post_meta($auction_id, '_auction_closed', '0');

        // Remove the highest bid and winner information
        delete_post_meta($auction_id, '_auction_winning_bid');
        delete_post_meta($auction_id, '_auction_winner');
        delete_post_meta($auction_id, '_auction_order_id');

        // Publish the auction again
        wp_update_post(array(
            'ID'          => $auction_id,
            'post_status' => 'publish',
        ));
    }
}
