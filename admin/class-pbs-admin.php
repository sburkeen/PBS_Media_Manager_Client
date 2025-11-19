<?php
/**
 * Admin Controller
 *
 * Handles admin interface and menus
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Schedule_Viewer_Admin {

    /**
     * Single instance
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_pbs_search_stations', array($this, 'ajax_search_stations'));
        add_action('wp_ajax_pbs_get_station_feeds', array($this, 'ajax_get_station_feeds'));
        add_action('wp_ajax_pbs_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_pbs_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_pbs_sync_shows', array($this, 'ajax_sync_shows'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'PBS Schedule Viewer',
            'PBS Schedule',
            'manage_options',
            'pbs-schedule-viewer',
            array($this, 'render_dashboard'),
            'dashicons-video-alt2',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'pbs-schedule-viewer',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'pbs-schedule-viewer',
            array($this, 'render_dashboard')
        );

        // Settings submenu
        add_submenu_page(
            'pbs-schedule-viewer',
            'Settings',
            'Settings',
            'manage_options',
            'pbs-schedule-settings',
            array($this, 'render_settings')
        );

        // Shows submenu
        add_submenu_page(
            'pbs-schedule-viewer',
            'PBS Shows',
            'Shows',
            'manage_options',
            'edit.php?post_type=pbs_show'
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        PBS_Schedule_Viewer_Settings::register();
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        include PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'admin/views/admin-settings.php';
    }

    /**
     * AJAX: Search stations
     */
    public function ajax_search_stations() {
        check_ajax_referer('pbs_schedule_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

        if (empty($query)) {
            wp_send_json_error('Query is required');
        }

        $client = new PBS_TVSS_API_Client();
        $results = $client->search_stations($query);

        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }

        wp_send_json_success($results);
    }

    /**
     * AJAX: Get station feeds
     */
    public function ajax_get_station_feeds() {
        check_ajax_referer('pbs_schedule_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $station_id = isset($_POST['station_id']) ? intval($_POST['station_id']) : 0;

        if (empty($station_id)) {
            wp_send_json_error('Station ID is required');
        }

        $api_key = get_option('pbs_schedule_tvss_api_key', '');
        $client = new PBS_TVSS_API_Client($api_key);
        $feeds = $client->get_station_feeds($station_id);

        if (is_wp_error($feeds)) {
            wp_send_json_error($feeds->get_error_message());
        }

        wp_send_json_success($feeds);
    }

    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api_connection() {
        check_ajax_referer('pbs_schedule_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $results = array();

        // Test TVSS API
        $tvss_key = get_option('pbs_schedule_tvss_api_key', '');
        $callsign = get_option('pbs_schedule_station_callsign', '');

        if (!empty($tvss_key) && !empty($callsign)) {
            $tvss_client = new PBS_TVSS_API_Client($tvss_key);
            $tvss_test = $tvss_client->get_todays_listings($callsign, false);

            $results['tvss'] = array(
                'success' => !is_wp_error($tvss_test),
                'message' => is_wp_error($tvss_test) ?
                    $tvss_test->get_error_message() :
                    'Successfully connected to TV Schedules API'
            );
        } else {
            $results['tvss'] = array(
                'success' => false,
                'message' => 'API key or callsign not configured'
            );
        }

        // Test Media Manager API
        $mm_id = get_option('pbs_schedule_mm_client_id', '');
        $mm_secret = get_option('pbs_schedule_mm_client_secret', '');
        $mm_endpoint = get_option('pbs_schedule_mm_endpoint', '');

        if (!empty($mm_id) && !empty($mm_secret) && !empty($mm_endpoint)) {
            $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);
            $mm_test = $mm_client->get_shows(array('page-size' => 1));

            $results['media_manager'] = array(
                'success' => !is_wp_error($mm_test) && is_array($mm_test),
                'message' => (!is_wp_error($mm_test) && is_array($mm_test)) ?
                    'Successfully connected to Media Manager API' :
                    'Failed to connect to Media Manager API'
            );
        } else {
            $results['media_manager'] = array(
                'success' => false,
                'message' => 'API credentials not configured'
            );
        }

        wp_send_json_success($results);
    }

    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('pbs_schedule_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        PBS_Cache_Manager::clear_all_cache();

        wp_send_json_success('Cache cleared successfully');
    }

    /**
     * AJAX: Sync shows from Media Manager
     */
    public function ajax_sync_shows() {
        check_ajax_referer('pbs_schedule_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $mm_id = get_option('pbs_schedule_mm_client_id', '');
        $mm_secret = get_option('pbs_schedule_mm_client_secret', '');
        $mm_endpoint = get_option('pbs_schedule_mm_endpoint', '');

        if (empty($mm_id) || empty($mm_secret)) {
            wp_send_json_error('Media Manager API not configured');
        }

        $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);
        $shows = $mm_client->get_shows();

        if (is_wp_error($shows) || !is_array($shows)) {
            wp_send_json_error('Failed to fetch shows from Media Manager');
        }

        $created = 0;
        $updated = 0;

        foreach ($shows as $show) {
            $show_id = $show['id'];

            // Check if post exists
            $existing = get_posts(array(
                'post_type' => 'pbs_show',
                'meta_key' => '_pbs_show_id',
                'meta_value' => $show_id,
                'posts_per_page' => 1
            ));

            if (empty($existing)) {
                // Create new post
                $post_id = wp_insert_post(array(
                    'post_type' => 'pbs_show',
                    'post_title' => $show['attributes']['title'],
                    'post_content' => isset($show['attributes']['description_long']) ?
                        $show['attributes']['description_long'] : '',
                    'post_excerpt' => isset($show['attributes']['description_short']) ?
                        $show['attributes']['description_short'] : '',
                    'post_status' => 'publish',
                ));

                if (!is_wp_error($post_id)) {
                    update_post_meta($post_id, '_pbs_show_id', $show_id);
                    $created++;
                }
            } else {
                $updated++;
            }
        }

        wp_send_json_success(array(
            'created' => $created,
            'updated' => $updated,
            'total' => count($shows)
        ));
    }
}
