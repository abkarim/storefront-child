<?php

namespace StoreFront_Child;

class Theme
{
    /**
     * Instance holder
     *
     * @var ?self
     */
    private static ?self $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     * @static
     * @return \StoreFront_Child\Theme An instance of the class.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize function
     *
     * @since 1.0.0
     * @access public
     */
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'load_scripts']);

        require_once trailingslashit(__DIR__) . 'layout/layout.php';
        new Layout();
    }

    /**
     * Load scripts
     * 
     * @access public
     * @since 1.0.0
     */
    public function load_scripts()
    {
        wp_enqueue_style(
            'theme-css',
            get_stylesheet_uri(),
            [],
            // If we are on local development, generate a new version string every second
            (defined('WP_DEBUG') && \WP_DEBUG) ? time() : wp_get_theme()->get('Version')
        );
    }
}
