<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LRA_User_Settings
{
    public function __construct()
    {
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        add_action('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_lra_user_settings', array($this, 'render_settings_page'));
        add_action('woocommerce_update_options_lra_user_settings', array($this, 'save_settings'));
    }

    /**
     * Add auction-related fields to the user profile
     */
    public function add_user_profile_fields($user)
    {
        ?>
        <h2><?php _e('Auction Settings', 'lra'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="lra_outbid_notification"><?php _e('Outbid Notification', 'lra'); ?></label></th>
                <td>
                    <input type="checkbox" name="lra_outbid_notification" id="lra_outbid_notification" value="1" <?php checked(get_user_meta($user->ID, 'lra_outbid_notification', true), 1); ?>>
                    <span class="description"><?php _e('Receive email notifications when outbid on an auction', 'lra'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="lra_auction_ending_soon_notification"><?php _e('Auction Ending Soon Notification', 'lra'); ?></label></th>
                <td>
                    <input type="checkbox" name="lra_auction_ending_soon_notification" id="lra_auction_ending_soon_notification" value="1" <?php checked(get_user_meta($user->ID, 'lra_auction_ending_soon_notification', true), 1); ?>>
                    <span class="description"><?php _e('Receive email notifications when an auction you have bid on is ending soon', 'lra'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save auction-related fields from the user profile
     */
    public function save_user_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        update_user_meta($user_id, 'lra_outbid_notification', isset($_POST['lra_outbid_notification']) ? 1 : 0);
        update_user_meta($user_id, 'lra_auction_ending_soon_notification', isset($_POST['lra_auction_ending_soon_notification']) ? 1 : 0);
    }

    /**
     * Add Auction User Settings tab to WooCommerce settings
     */
    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs['lra_user_settings'] = __('Auction User Settings', 'lra');
        return $settings_tabs;
    }

    /**
     * Render the Auction User Settings page
     */
    public function render_settings_page()
    {
        woocommerce_admin_fields($this->get_settings());
    }

    /**
     * Save the Auction User Settings
     */
    public function save_settings()
    {
        woocommerce_update_options($this->get_settings());
    }

    /**
     * Get the Auction User Settings fields
     */
    private function get_settings()
    {
        $settings = array(
            array(
                'title' => __('Auction User Settings', 'lra'),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'lra_user_settings',
            ),
            array(
                'title'   => __('Outbid Notification', 'lra'),
                'desc'    => __('Enable outbid notifications by default for new users', 'lra'),
                'id'      => 'lra_default_outbid_notification',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            array(
                'title'   => __('Auction Ending Soon Notification', 'lra'),
                'desc'    => __('Enable auction ending soon notifications by default for new users', 'lra'),
                'id'      => 'lra_default_auction_ending_soon_notification',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'lra_user_settings',
            ),
        );

        return apply_filters('lra_user_settings', $settings);
    }
}
