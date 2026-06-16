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
        add_action('upload_mimes', [$this, 'add_file_types_to_uploads']);


        require_once trailingslashit(__DIR__) . 'layout/layout.php';
        new Layout();

        require_once trailingslashit(__DIR__) . 'customization.php';
        new Customization();
    }

    /**
     * Load scripts
     * 
     * @access public
     * @since 1.0.0
     */
    public function load_scripts()
    {
        $version = (defined('WP_DEBUG') && \WP_DEBUG) ? time() : wp_get_theme()->get('Version');

        wp_enqueue_style(
            'theme-css',
            get_stylesheet_uri(),
            [],
            $version
        );

        wp_enqueue_script(
            'theme-js',
            get_stylesheet_directory_uri() . '/assets/js/app.js',
            [],
            $version,
            true
        );

        if (is_page('compare')) {
            wp_enqueue_script(
                'theme-compare-js',
                get_stylesheet_directory_uri() . '/assets/js/compareContents.js',
                [],
                $version,
                true
            );
        }
    }

    /**
     * add SVG to allowed file uploads
     * 
     * @since 1.0.2
     * @access public
     * @return array
     */
    public function add_file_types_to_uploads(array $mimes): array
    {
        // New allowed mime types.
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';

        return $mimes;
    }
}
