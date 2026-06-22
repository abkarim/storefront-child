<?php
$search_placeholder = get_theme_mod('sf_child_header_search_placeholder', __('Search for products', 'storefront-child'));
?>

<div class="store-front-child-header-wrapper">
    <div class="header-col-brand">
        <?php has_custom_logo() ? the_custom_logo() : printf('<a href="%s" class="site-title-fallback">%s</a>', esc_url(home_url('/')), esc_html(get_bloginfo('name'))); ?>
    </div>

    <div class="header-col-search">
        <form role="search" method="get" class="woocommerce-product-search-split" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" class="search-field-input" placeholder="<?php echo esc_attr($search_placeholder); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s" />
            <button type="submit" class="search-submit-btn">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
            <input type="hidden" name="post_type" value="product" />
        </form>
    </div>

    <div class="header-col-utilities">


        <div class="utility-icon-link items-track-order">
            <?php
            // Generates fallback to your account dashboard page if a custom track page isn't assigned yet
            $track_order_url = class_exists('WooCommerce') ? wc_get_endpoint_url('orders', '', get_permalink(get_option('woocommerce_myaccount_page_id'))) : '#';

            ?>
            <a href="<?php echo esc_url($track_order_url); ?>" title="<?php esc_attr_e('Track Your Order', 'storefront-child'); ?>">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="header-utility-icon">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
            </a>
        </div>

        <div class="utility-icon-link items-compare-summary">
            <?php
            // Dynamically retrieve the URL of the page with the slug 'compare'
            $compare_page = get_page_by_path('compare');
            $compare_url  = $compare_page ? get_permalink($compare_page->ID) : '#';
            ?>
            <a href="<?php echo esc_url($compare_url); ?>" title="<?php esc_attr_e('Compare Products', 'storefront-child'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 17h16M16 13l4 4-4 4"></path>
                    <path d="M20 7H4M8 3L4 7l4 4"></path>
                </svg>
                <span class="utility-counter-badge product-compare-count"></span>
            </a>
        </div>

        <?php if (class_exists('WooCommerce')) : ?>
            <div class="utility-icon-link items-cart-summary">
                <a title="<?php esc_attr_e('View Cart', 'storefront-child'); ?>" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>

                    <?php
                    $card_count = WC()->cart->get_cart_contents_count();
                    if ($card_count > 0) :
                    ?>
                        <span class="utility-counter-badge"><?php echo $card_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>