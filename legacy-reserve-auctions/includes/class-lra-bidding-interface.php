<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LRA_Bidding_Interface
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_after_single_product', array($this, 'display_bidding_interface'));
        add_action('wp_ajax_lra_place_bid', array($this, 'place_bid_ajax'));
        add_action('wp_ajax_nopriv_lra_place_bid', array($this, 'place_bid_ajax'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts()
    {
        if (is_product()) {
            wp_enqueue_style('lra-bidding-styles', plugins_url('assets/css/bidding-interface.css', LRA_PLUGIN_FILE), array(), LRA_VERSION);
            ?>
            <style>
                .lra-bidding-interface {
                    margin-top: 20px;
                    padding: 20px;
                    background-color: #f9f9f9;
                    border: 1px solid #ddd;
                }
                .lra-bidding-form {
                    margin-top: 10px;
                }
                .lra-bidding-form label {
                    display: block;
                    margin-bottom: 5px;
                }
                .lra-bidding-form input[type="number"] {
                    width: 100px;
                }
                .lra-bidding-form button {
                    margin-left: 10px;
                }
                .lra-bidding-message {
                    margin-top: 10px;
                    font-weight: bold;
                }
                .lra-bidding-message.success {
                    color: green;
                }
                .lra-bidding-message.error {
                    color: red;
                }
            </style>
            <?php
            wp_enqueue_script('lra-bidding-scripts', plugins_url('assets/js/bidding-interface.js', LRA_PLUGIN_FILE), array('jquery'), LRA_VERSION, true);
            ?>
            <script>
                jQuery(function($) {
                    $('.lra-bidding-form').on('submit', function(e) {
                        e.preventDefault();
                        var $form = $(this);
                        var $message = $form.find('.lra-bidding-message');
                        var $button = $form.find('button');
                        var bid_amount = $form.find('input[name="bid_amount"]').val();
                        var auction_id = $form.find('input[name="auction_id"]').val();
                        var nonce = $form.find('input[name="nonce"]').val();
                        $button.prop('disabled', true);
                        $message.removeClass('success error').text('<?php _e('Placing bid...', 'lra'); ?>');
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'lra_place_bid',
                                bid_amount: bid_amount,
                                auction_id: auction_id,
                                nonce: nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    $message.addClass('success').text(response.data.message);
                                    $form.find('input[name="bid_amount"]').val(response.data.min_bid_amount);
                                    $('.lra-current-bid').text(response.data.current_bid);
                                    $('.lra-highest-bid').text(response.data.highest_bid);
                                } else {
                                    $message.addClass('error').text(response.data);
                                }
                            },
                            error: function() {
                                $message.addClass('error').text('<?php _e('Error placing bid. Please try again.', 'lra'); ?>');
                            },
                            complete: function() {
                                $button.prop('disabled', false);
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    }

    /**
     * Display the bidding interface on the single product page
     */
    public function display_bidding_interface()
    {
        global $product;

        if ($product->get_type() !== 'auction') {
            return;
        }

        $auction_id = $product->get_id();
        $current_user = wp_get_current_user();
        $current_bid = $this->get_current_bid($auction_id);
        $highest_bid = $this->get_highest_bid($auction_id);
        $start_price = $product->get_meta('_auction_start_price');
        $bid_increment = apply_filters('lra_bid_increment', 1);
        $min_bid_amount = $current_bid ? $current_bid + $bid_increment : $start_price;
        ?>
        <div class="lra-bidding-interface">
            <h3><?php _e('Place a Bid', 'lra'); ?></h3>
            <p><?php printf(__('Current Bid: %s', 'lra'), '<span class="lra-current-bid">' . wc_price($current_bid) . '</span>'); ?></p>
            <p><?php printf(__('Highest Bid: %s', 'lra'), '<span class="lra-highest-bid">' . wc_price($highest_bid->bid_amount) . '</span>'); ?></p>
            <form class="lra-bidding-form" method="post">
                <input type="hidden" name="auction_id" value="<?php echo esc_attr($auction_id); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('lra_place_bid_nonce'); ?>">
                <label for="lra-bid-amount"><?php _e('Your Bid:', 'lra'); ?></label>
                <input type="number" id="lra-bid-amount" name="bid_amount" min="<?php echo esc_attr($min_bid_amount); ?>" step="<?php echo esc_attr($bid_increment); ?>" value="<?php echo esc_attr($min_bid_amount); ?>" required>
                <button type="submit"><?php _e('Place Bid', 'lra'); ?></button>
                <div class="lra-bidding-message"></div>
            </form>
        </div>
        <?php
    }

    /**
     * Place a bid via AJAX
     */
    public function place_bid_ajax()
    {
        check_ajax_referer('lra_place_bid_nonce', 'nonce');

        $auction_id = intval($_POST['auction_id']);
        $bid_amount = floatval($_POST['bid_amount']);

        if (!$auction_id || !$bid_amount) {
            wp_send_json_error(__('Invalid bid data.', 'lra'));
        }

        $product = wc_get_product($auction_id);

        if (!$product || $product->get_type() !== 'auction') {
            wp_send_json_error(__('Invalid auction product.', 'lra'));
        }

        $current_user = wp_get_current_user();
        $current_bid = $this->get_current_bid($auction_id);
        $min_bid_amount = $current_bid ? $current_bid + 1 : $product->get_meta('_auction_start_price');

        if ($bid_amount < $min_bid_amount) {
            wp_send_json_error(sprintf(__('Bid amount must be at least %s.', 'lra'), wc_price($min_bid_amount)));
        }

        $result = $this->place_bid($auction_id, $current_user->ID, $bid_amount);

        if ($result) {
            $highest_bid = $this->get_highest_bid($auction_id);
            $response = array(
                'status'       => 'success',
                'message'      => __('Bid placed successfully.', 'lra'),
                'current_bid'  => $bid_amount,
                'highest_bid'  => $highest_bid->bid_amount,
                'min_bid_amount' => $bid_amount + 1,
            );
            wp_send_json_success($response);
        } else {
            wp_send_json_error(__('Failed to place bid. Please try again.', 'lra'));
        }
    }

    /**
     * Place a bid
     */
    private function place_bid($auction_id, $user_id, $bid_amount)
    {
        $bid_history = new LRA_Bid_History();
        $bid_id = $bid_history->create_bid($auction_id, $user_id, $bid_amount);

        if ($bid_id) {
            update_post_meta($auction_id, '_current_bid', $bid_amount);
            update_post_meta($auction_id, '_current_bidder', $user_id);
            do_action('lra_after_place_bid', $auction_id, $user_id, $bid_amount);
            return true;
        }

        return false;
    }

    /**
     * Get the current bid for an auction
     */
    private function get_current_bid($auction_id)
    {
        return get_post_meta($auction_id, '_current_bid', true);
    }

    /**
     * Get the highest bid for an auction
     */
    private function get_highest_bid($auction_id)
    {
        $bid_history = new LRA_Bid_History();
        return $bid_history->get_highest_bid($auction_id);
    }
}
