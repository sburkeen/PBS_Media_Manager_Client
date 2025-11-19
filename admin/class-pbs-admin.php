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

        $callsign = isset($_POST['callsign']) ? sanitize_text_field($_POST['callsign']) : '';

        if (empty($callsign)) {
            wp_send_json_error('Station callsign is required');
        }

        $api_key = get_option('pbs_schedule_tvss_api_key', '');

        if (empty($api_key)) {
            wp_send_json_error('TV Schedules API key not configured. Please add your API key in the API Credentials tab.');
        }

        $client = new PBS_TVSS_API_Client($api_key);

        // Get channel/feed information for this station
        $feeds = $client->get_channel_lookup($callsign);

        if (is_wp_error($feeds)) {
            wp_send_json_error('Unable to load feeds: ' . $feeds->get_error_message());
        }

        // Extract feeds from the response
        $feed_list = array();
        if (isset($feeds['feeds']) && is_array($feeds['feeds'])) {
            $feed_list = $feeds['feeds'];
        }

        wp_send_json_success(array('feeds' => $feed_list));
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

        if (empty($mm_endpoint)) {
            $mm_endpoint = 'https://media.services.pbs.org/api/v1';
        }

        if (!empty($mm_id) && !empty($mm_secret)) {
            // Check if cURL is available
            if (!function_exists('curl_init')) {
                $results['media_manager'] = array(
                    'success' => false,
                    'message' => 'cURL PHP extension is not installed'
                );
            } else {
                try {
                    $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);
                    $mm_test = $mm_client->get_shows(array('page-size' => 1));

                    // Check for error response
                    if (isset($mm_test['errors'])) {
                        $error_msg = 'API Error';
                        if (isset($mm_test['errors']['info']['http_code'])) {
                            $error_msg .= ' (HTTP ' . $mm_test['errors']['info']['http_code'] . ')';
                        }
                        if (isset($mm_test['errors']['response'][0]['title'])) {
                            $error_msg .= ': ' . $mm_test['errors']['response'][0]['title'];
                        }
                        $results['media_manager'] = array(
                            'success' => false,
                            'message' => $error_msg
                        );
                    } elseif (is_array($mm_test) && !empty($mm_test)) {
                        $results['media_manager'] = array(
                            'success' => true,
                            'message' => 'Successfully connected to Media Manager API (found ' . count($mm_test) . ' shows)'
                        );
                    } else {
                        $results['media_manager'] = array(
                            'success' => false,
                            'message' => 'Invalid response from Media Manager API'
                        );
                    }
                } catch (Throwable $e) {
                    $results['media_manager'] = array(
                        'success' => false,
                        'message' => 'Exception: ' . $e->getMessage()
                    );
                } catch (Exception $e) {
                    $results['media_manager'] = array(
                        'success' => false,
                        'message' => 'Exception: ' . $e->getMessage()
                    );
                }
            }
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
            wp_send_json_error('Media Manager API not configured. Please add your Client ID and Client Secret in the API Credentials tab.');
        }

        if (empty($mm_endpoint)) {
            $mm_endpoint = 'https://media.services.pbs.org/api/v1';
        }

        error_log('PBS Sync Shows: Starting sync with endpoint ' . $mm_endpoint);

        // Check if cURL is available
        if (!function_exists('curl_init')) {
            error_log('PBS Sync Shows: cURL extension is not installed');
            wp_send_json_error('cURL PHP extension is required but not installed. Please contact your hosting provider.');
        }

        // Increase memory limit for this operation
        $original_memory_limit = ini_get('memory_limit');
        $current_memory = intval($original_memory_limit);
        if ($current_memory > 0 && $current_memory < 256) {
            @ini_set('memory_limit', '256M');
            error_log('PBS Sync Shows: Increased memory limit from ' . $original_memory_limit . ' to 256M');
        }

        // Increase execution time
        @set_time_limit(120);

        try {
            error_log('PBS Sync Shows: Creating Media Manager client');
            $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);

            error_log('PBS Sync Shows: Calling get_shows() - fetching first page only with 10 items');
            // Fetch only 10 shows at a time to prevent memory issues
            // To get all shows, we'd need to implement pagination
            $shows = $mm_client->get_shows(array(
                'page-size' => 10,
                'page' => 1  // Only fetch first page
            ));
            error_log('PBS Sync Shows: get_shows() returned');

            // Log the response for debugging
            error_log('PBS Sync Shows: API Response type: ' . gettype($shows));

            if (is_wp_error($shows)) {
                error_log('PBS Sync Shows: WP_Error - ' . $shows->get_error_message());
                wp_send_json_error('Failed to fetch shows: ' . $shows->get_error_message());
            }

            if (!is_array($shows)) {
                error_log('PBS Sync Shows: Response is not an array: ' . print_r($shows, true));
                wp_send_json_error('Invalid response from Media Manager API. Check error logs for details.');
            }

            // Check if it's an error response array
            if (isset($shows['errors']) && is_array($shows['errors'])) {
                $error_msg = 'API Error: ';
                if (isset($shows['errors'][0]['title'])) {
                    $error_msg .= $shows['errors'][0]['title'];
                }
                if (isset($shows['errors'][0]['detail'])) {
                    $error_msg .= ' - ' . $shows['errors'][0]['detail'];
                }
                error_log('PBS Sync Shows: ' . $error_msg);
                wp_send_json_error($error_msg);
            }

            if (empty($shows)) {
                error_log('PBS Sync Shows: No shows returned from API');
                wp_send_json_error('No shows found in Media Manager. This could mean your API credentials are valid but no shows are available.');
            }

            error_log('PBS Sync Shows: Found ' . count($shows) . ' shows');

            $created = 0;
            $updated = 0;
            $errors = 0;

            foreach ($shows as $show) {
                if (!isset($show['id']) || !isset($show['attributes']['title'])) {
                    error_log('PBS Sync Shows: Invalid show data: ' . print_r($show, true));
                    $errors++;
                    continue;
                }

                $show_id = $show['id'];
                $title = $show['attributes']['title'];

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
                        'post_title' => $title,
                        'post_content' => isset($show['attributes']['description_long']) ?
                            $show['attributes']['description_long'] : '',
                        'post_excerpt' => isset($show['attributes']['description_short']) ?
                            $show['attributes']['description_short'] : '',
                        'post_status' => 'publish',
                    ));

                    if (!is_wp_error($post_id)) {
                        update_post_meta($post_id, '_pbs_show_id', $show_id);

                        // Store additional metadata
                        if (isset($show['attributes']['nola_root'])) {
                            update_post_meta($post_id, '_pbs_nola_root', $show['attributes']['nola_root']);
                        }

                        $created++;
                        error_log(sprintf('PBS Sync Shows: Created post for show "%s" (ID: %s)', $title, $show_id));
                    } else {
                        error_log(sprintf('PBS Sync Shows: Failed to create post for show "%s": %s', $title, $post_id->get_error_message()));
                        $errors++;
                    }
                } else {
                    $updated++;
                }
            }

            error_log(sprintf('PBS Sync Shows: Complete - Created: %d, Updated: %d, Errors: %d', $created, $updated, $errors));

            $message = sprintf('Synced %d shows (Created: %d, Updated: %d', count($shows), $created, $updated);
            if ($errors > 0) {
                $message .= ', Errors: ' . $errors;
            }
            $message .= '). Note: This syncs 10 shows at a time to prevent memory issues.';

            wp_send_json_success(array(
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'total' => count($shows),
                'message' => $message
            ));

        } catch (Throwable $e) {
            error_log('PBS Sync Shows Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            error_log('PBS Sync Shows Stack Trace: ' . $e->getTraceAsString());
            wp_send_json_error('Error occurred: ' . $e->getMessage() . '. Check error logs for details.');
        } catch (Exception $e) {
            // Fallback for PHP 5.x
            error_log('PBS Sync Shows Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            error_log('PBS Sync Shows Stack Trace: ' . $e->getTraceAsString());
            wp_send_json_error('Error occurred: ' . $e->getMessage() . '. Check error logs for details.');
        }
    }
}
