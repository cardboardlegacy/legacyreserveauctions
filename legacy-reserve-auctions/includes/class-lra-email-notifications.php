<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LRA_Email_Notifications
{
    public function __construct()
    {
        add_filter('woocommerce_email_classes', array($this, 'add_email_classes'));
        add_filter('woocommerce_email_actions', array($this, 'add_email_actions'));
        add_action('lra_outbid_notification', array($this, 'send_outbid_notification'), 10, 2);
        add_action('lra_auction_ending_soon', array($this, 'send_auction_ending_soon_notification'), 10, 2);
        add_action('lra_winning_bidder_notification', array($this, 'send_winning_bidder_notification'), 10, 2);
    }

    /**
     * Add custom email classes
     */
    public function add_email_classes($email_classes)
    {
        $email_classes['WC_Email_Outbid'] = new WC_Email_Outbid();
        $email_classes['WC_Email_Auction_Ending_Soon'] = new WC_Email_Auction_Ending_Soon();
        $email_classes['WC_Email_Auction_Won'] = new WC_Email_Auction_Won();
        return $email_classes;
    }

    /**
     * Add custom email actions
     */
    public function add_email_actions($actions)
    {
        $actions[] = 'lra_outbid_notification';
        $actions[] = 'lra_auction_ending_soon';
        $actions[] = 'lra_winning_bidder_notification';
        return $actions;
    }

    /**
     * Send outbid notification
     */
    public function send_outbid_notification($auction_id, $outbid_user_id)
    {
        WC()->mailer()->get_emails()['WC_Email_Outbid']->trigger($auction_id, $outbid_user_id);
    }

    /**
     * Send auction ending soon notification
     */
    public function send_auction_ending_soon_notification($auction_id, $user_id)
    {
        WC()->mailer()->get_emails()['WC_Email_Auction_Ending_Soon']->trigger($auction_id, $user_id);
    }

    /**
     * Send winning bidder notification
     */
    public function send_winning_bidder_notification($auction_id, $winning_bid)
    {
        WC()->mailer()->get_emails()['WC_Email_Auction_Won']->trigger($auction_id, $winning_bid);
    }
}

class WC_Email_Outbid extends WC_Email
{
    public function __construct()
    {
        $this->id = 'outbid';
        $this->title = __('Outbid', 'lra');
        $this->description = __('Outbid notification emails are sent when a user is outbid on an auction.', 'lra');
        $this->template_html = 'emails/outbid.php';
        $this->template_plain = 'emails/plain/outbid.php';

        parent::__construct();
    }

    public function trigger($auction_id, $outbid_user_id)
    {
        $this->setup_locale();

        $outbid_user = get_user_by('id', $outbid_user_id);
        $auction = get_post($auction_id);

        if (!$outbid_user || !$auction) {
            return;
        }

        $this->object = $auction;
        $this->recipient = $outbid_user->user_email;

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

        $this->restore_locale();
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'email_heading' => $this->get_heading(),
                'auction'       => $this->object,
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
            )
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'email_heading' => $this->get_heading(),
                'auction'       => $this->object,
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            )
        );
    }

    public function get_default_subject()
    {
        return __('You have been outbid on an auction', 'lra');
    }

    public function get_default_heading()
    {
        return __('Outbid Notification', 'lra');
    }
}

class WC_Email_Auction_Ending_Soon extends WC_Email
{
    public function __construct()
    {
        $this->id = 'auction_ending_soon';
        $this->title = __('Auction Ending Soon', 'lra');
        $this->description = __('Auction ending soon notification emails are sent to users who have placed bids on an auction that is ending soon.', 'lra');
        $this->template_html = 'emails/auction-ending-soon.php';
        $this->template_plain = 'emails/plain/auction-ending-soon.php';

        parent::__construct();
    }

    public function trigger($auction_id, $user_id)
    {
        $this->setup_locale();

        $user = get_user_by('id', $user_id);
        $auction = get_post($auction_id);

        if (!$user || !$auction) {
            return;
        }

        $this->object = $auction;
        $this->recipient = $user->user_email;

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

        $this->restore_locale();
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'email_heading' => $this->get_heading(),
                'auction'       => $this->object,
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
            )
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'email_heading' => $this->get_heading(),
                'auction'       => $this->object,
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            )
        );
    }

    public function get_default_subject()
    {
        return __('An auction you have bid on is ending soon', 'lra');
    }

    public function get_default_heading()
    {
        return __('Auction Ending Soon', 'lra');
    }
}

class WC_Email_Auction_Won extends WC_Email
{
    public function __construct()
    {
        $this->id = 'auction_won';
        $this->title = __('Auction Won', 'lra');
        $this->description = __('Auction won notification emails are sent to the winning bidder of an auction.', 'lra');
        $this->template_html = 'emails/auction-won.php';
        $this->template_plain = 'emails/plain/auction-won.php';

        parent::__construct();
    }

    public function trigger($auction_id, $winning_bid)
    {
        $this->setup_locale();

        $auction = get_post($auction_id);

        if (!$auction) {
            return;
        }

        $this->object = $auction;
        $this->recipient = get_userdata($winning_bid->user_id)->user_email;

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

        $this->restore_locale();
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'email_heading' => $this->get_heading(),
                'auction'       => $this->object,
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
            )
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'email_heading' => $this->get_heading(),
                'auction'       => $this->object,
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            )
        );
    }

    public function get_default_subject()
    {
        return __('You have won an auction!', 'lra');
    }

    public function get_default_heading()
    {
        return __('Auction Won', 'lra');
    }
}
