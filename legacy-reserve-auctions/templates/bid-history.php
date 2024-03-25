<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="lra-bid-history">
    <h3><?php _e('Bid History', 'lra'); ?></h3>
    <?php
    global $product;
    $auction_id = $product->get_id();
    $bids = lra_get_auction_bids($auction_id);

    if (empty($bids)) {
        echo '<p>' . __('No bids yet.', 'lra') . '</p>';
    } else {
        ?>
        <table>
            <thead>
                <tr>
                    <th><?php _e('Bidder', 'lra'); ?></th>
                    <th><?php _e('Bid Amount', 'lra'); ?></th>
                    <th><?php _e('Timestamp', 'lra'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bids as $bid) : ?>
                    <tr>
                        <td><?php echo esc_html($bid->bidder_name); ?></td>
                        <td><?php echo wc_price($bid->bid_amount); ?></td>
                        <td><?php echo esc_html($bid->bid_date); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    ?>
</div>
