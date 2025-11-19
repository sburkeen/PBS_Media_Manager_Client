<?php
/**
 * Plugin Name: PBS TV Schedule Viewer
 * Plugin URI: https://github.com/tamw-wnet/PBS_Media_Manager_Client
 * Description: Integrates PBS TV Schedules API with PBS Media Manager to display what's on TV now with links to on-demand content.
 * Version: 1.0.0
 * Author: PBS Digital
 * Author URI: https://pbs.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pbs-schedule-viewer
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PBS_SCHEDULE_VIEWER_VERSION', '1.0.0');
define('PBS_SCHEDULE_VIEWER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PBS_SCHEDULE_VIEWER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PBS_SCHEDULE_VIEWER_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class PBS_Schedule_Viewer {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // API Clients
        require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'includes/class-pbs-tvss-api-client.php';
        require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'includes/class-pbs-media-manager-api-client.php';

        // Core Classes
        require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'includes/class-pbs-content-matcher.php';
        require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'includes/class-pbs-cache-manager.php';

        // Admin Classes
        if (is_admin()) {
            require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'admin/class-pbs-admin.php';
            require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'admin/class-pbs-admin-settings.php';
        }

        // Public Classes
        require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'public/class-pbs-public.php';
        require_once PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'public/class-pbs-shortcodes.php';
    }

    /**
     * Define WordPress hooks
     */
    private function define_hooks() {
        // Activation/Deactivation
        register_activation_hook(PBS_SCHEDULE_VIEWER_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PBS_SCHEDULE_VIEWER_PLUGIN_FILE, array($this, 'deactivate'));

        // Initialize admin
        if (is_admin()) {
            PBS_Schedule_Viewer_Admin::get_instance();
        }

        // Initialize public
        PBS_Schedule_Viewer_Public::get_instance();
        PBS_Schedule_Viewer_Shortcodes::get_instance();

        // Register custom post types
        add_action('init', array($this, 'register_post_types'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Register custom post types
        $this->register_post_types();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Create cache directory if it doesn't exist
        $cache_dir = WP_CONTENT_DIR . '/cache/pbs-schedule-viewer';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }

        // Set default options
        $defaults = array(
            'tvss_api_key' => '',
            'mm_client_id' => '',
            'mm_client_secret' => '',
            'mm_endpoint' => 'https://media.services.pbs.org/api/v1',
            'station_callsign' => '',
            'station_zip' => '',
            'selected_feeds' => array(),
            'cache_enabled' => true,
            'cache_schedule_duration' => 900, // 15 minutes
            'cache_ondemand_duration' => 3600, // 1 hour
            'display_hours' => 6,
            'refresh_interval' => 300, // 5 minutes
            'show_images' => true,
            'default_view' => 'grid'
        );

        foreach ($defaults as $key => $value) {
            if (get_option('pbs_schedule_' . $key) === false) {
                add_option('pbs_schedule_' . $key, $value);
            }
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Optionally clear cache
        PBS_Cache_Manager::clear_all_cache();
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // PBS Show post type
        $labels = array(
            'name'               => 'PBS Shows',
            'singular_name'      => 'PBS Show',
            'menu_name'          => 'PBS Shows',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New PBS Show',
            'edit_item'          => 'Edit PBS Show',
            'new_item'           => 'New PBS Show',
            'view_item'          => 'View PBS Show',
            'search_items'       => 'Search PBS Shows',
            'not_found'          => 'No PBS shows found',
            'not_found_in_trash' => 'No PBS shows found in trash'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // We'll add it to our custom menu
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'watch/shows'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'taxonomies'          => array('pbs_show_category')
        );

        register_post_type('pbs_show', $args);

        // Register taxonomy for show categories
        register_taxonomy('pbs_show_category', 'pbs_show', array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'          => 'Show Categories',
                'singular_name' => 'Show Category',
                'search_items'  => 'Search Categories',
                'all_items'     => 'All Categories',
                'edit_item'     => 'Edit Category',
                'update_item'   => 'Update Category',
                'add_new_item'  => 'Add New Category',
                'new_item_name' => 'New Category Name',
                'menu_name'     => 'Categories',
            ),
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'show-category'),
        ));
    }

    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // CSS
        wp_enqueue_style(
            'pbs-schedule-viewer',
            PBS_SCHEDULE_VIEWER_PLUGIN_URL . 'public/css/pbs-viewer.css',
            array(),
            PBS_SCHEDULE_VIEWER_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'pbs-schedule-viewer',
            PBS_SCHEDULE_VIEWER_PLUGIN_URL . 'public/js/pbs-viewer.js',
            array('jquery'),
            PBS_SCHEDULE_VIEWER_VERSION,
            true
        );

        // Localize script
        wp_localize_script('pbs-schedule-viewer', 'pbsScheduleViewer', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pbs_schedule_viewer_nonce'),
            'refreshInterval' => get_option('pbs_schedule_refresh_interval', 300) * 1000
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'pbs-schedule-viewer') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'pbs-schedule-viewer-admin',
            PBS_SCHEDULE_VIEWER_PLUGIN_URL . 'admin/css/pbs-admin.css',
            array(),
            PBS_SCHEDULE_VIEWER_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'pbs-schedule-viewer-admin',
            PBS_SCHEDULE_VIEWER_PLUGIN_URL . 'admin/js/pbs-admin.js',
            array('jquery', 'jquery-ui-autocomplete'),
            PBS_SCHEDULE_VIEWER_VERSION,
            true
        );

        // Localize script
        wp_localize_script('pbs-schedule-viewer-admin', 'pbsScheduleAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pbs_schedule_admin_nonce')
        ));
    }
}

/**
 * Initialize the plugin
 */
function pbs_schedule_viewer_init() {
    return PBS_Schedule_Viewer::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'pbs_schedule_viewer_init');
