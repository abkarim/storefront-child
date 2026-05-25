<?php
$search_placeholder = get_theme_mod('sf_child_header_search_placeholder', __('Search for products', 'storefront-child'));
?>

<div class="store-front-child-header-wrapper">
    <div class="header-col-brand">
        <?php has_custom_logo() ? the_custom_logo() : printf('<a href="%s" class="site-title-fallback">%s</a>', esc_url(home_url('/')), esc_html(get_bloginfo('name'))); ?>
    </div>

    <div class="header-col-search">
        <form role="search" method="get" class="woocommerce-product-search-split" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" class="search-field-input" placeholder="<?php echo esc_attr($search_placeholder); ?>" value="<?php echo get_search_query(); ?>" name="s" />
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
        <div class="utility-user-account">
            <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>" title="<?php is_user_logged_in() ? esc_attr_e('My Account', 'storefront-child') : esc_attr_e('Login / Register', 'storefront-child'); ?>">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="header-utility-icon">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
        <?php if (class_exists('WooCommerce')) : ?>
            <div class="utility-icon-link items-cart-summary">
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="utility-counter-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>