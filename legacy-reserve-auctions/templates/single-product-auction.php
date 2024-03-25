<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop');

while (have_posts()) : the_post();
    global $product;

    if ($product->get_type() !== 'auction') {
        wc_get_template_part('content', 'single-product');
        continue;
    }
    ?>

    <div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>
        <?php
        do_action('woocommerce_before_single_product');

        if (post_password_required()) {
            echo get_the_password_form();
            return;
        }
        ?>

        <div class="summary entry-summary">
            <?php do_action('woocommerce_single_product_summary'); ?>
        </div>

        <?php
        do_action('woocommerce_after_single_product_summary');
        ?>
    </div>

    <?php do_action('woocommerce_after_single_product'); ?>

<?php endwhile;

get_footer('shop');
