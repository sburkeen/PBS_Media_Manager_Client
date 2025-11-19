<?php
/**
 * Public Controller
 *
 * Handles frontend functionality
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Schedule_Viewer_Public {

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
        add_action('wp_ajax_pbs_refresh_schedule', array($this, 'ajax_refresh_schedule'));
        add_action('wp_ajax_nopriv_pbs_refresh_schedule', array($this, 'ajax_refresh_schedule'));
        add_action('wp_head', array($this, 'add_custom_css'));
        add_filter('single_template', array($this, 'load_show_template'));
    }

    /**
     * AJAX: Refresh schedule
     */
    public function ajax_refresh_schedule() {
        check_ajax_referer('pbs_schedule_viewer_nonce', 'nonce');

        $callsign = isset($_POST['callsign']) ? sanitize_text_field($_POST['callsign']) : '';
        $feed_cid = isset($_POST['feed']) ? sanitize_text_field($_POST['feed']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Ymd');

        if (empty($callsign)) {
            $callsign = get_option('pbs_schedule_station_callsign', '');
        }

        if (empty($callsign)) {
            wp_send_json_error('No station configured');
        }

        $api_key = get_option('pbs_schedule_tvss_api_key', '');
        $client = new PBS_TVSS_API_Client($api_key);

        // Get schedule
        if (!empty($feed_cid)) {
            $schedule = $client->get_feed_listings_by_date($callsign, $date, $feed_cid, true);
        } else {
            $schedule = $client->get_listings_by_date($callsign, $date, true);
        }

        if (is_wp_error($schedule)) {
            wp_send_json_error($schedule->get_error_message());
        }

        // Match with on-demand content if enabled
        $link_ondemand = get_option('pbs_schedule_link_ondemand', true);

        if ($link_ondemand) {
            $matched_schedule = $this->match_schedule_content($schedule);
        } else {
            $matched_schedule = $schedule;
        }

        wp_send_json_success($matched_schedule);
    }

    /**
     * Match schedule with on-demand content
     *
     * @param array $schedule Schedule data from TVSS
     * @return array Matched schedule
     */
    private function match_schedule_content($schedule) {
        $mm_id = get_option('pbs_schedule_mm_client_id', '');
        $mm_secret = get_option('pbs_schedule_mm_client_secret', '');
        $mm_endpoint = get_option('pbs_schedule_mm_endpoint', '');

        if (empty($mm_id) || empty($mm_secret)) {
            return $schedule;
        }

        $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);
        $matcher = new PBS_Content_Matcher($mm_client);

        // Process each feed
        foreach ($schedule['feeds'] as &$feed) {
            foreach ($feed['listings'] as &$listing) {
                $match = $matcher->match_listing($listing);
                if ($match) {
                    $listing['on_demand'] = $match;
                    $listing['has_on_demand'] = true;

                    // Get or create show post for linking
                    $post_id = $matcher->create_show_post($match, $listing);
                    if ($post_id) {
                        $listing['show_url'] = get_permalink($post_id);
                    }
                } else {
                    $listing['has_on_demand'] = false;
                }
            }
        }

        return $schedule;
    }

    /**
     * Add custom CSS to head
     */
    public function add_custom_css() {
        $custom_css = get_option('pbs_schedule_custom_css', '');

        if (!empty($custom_css)) {
            echo '<style type="text/css">' . "\n";
            echo wp_strip_all_tags($custom_css) . "\n";
            echo '</style>' . "\n";
        }
    }

    /**
     * Load custom template for PBS Show post type
     */
    public function load_show_template($template) {
        global $post;

        if ($post->post_type === 'pbs_show') {
            $plugin_template = PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'public/views/single-pbs-show.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Get schedule data
     *
     * @param array $args Arguments for fetching schedule
     * @return array|WP_Error Schedule data
     */
    public function get_schedule($args = array()) {
        $defaults = array(
            'callsign' => get_option('pbs_schedule_station_callsign', ''),
            'feed' => '',
            'date' => date('Ymd'),
            'hours' => get_option('pbs_schedule_display_hours', 6),
            'images' => get_option('pbs_schedule_show_images', true),
            'link_ondemand' => get_option('pbs_schedule_link_ondemand', true)
        );

        $args = wp_parse_args($args, $defaults);

        if (empty($args['callsign'])) {
            return new WP_Error('no_callsign', 'No station callsign configured');
        }

        // Check cache first
        $cache = new PBS_Cache_Manager();
        $cached = $cache->get_schedule($args['callsign'], $args['date'], $args['feed']);

        if ($cached !== false) {
            return $cached;
        }

        // Fetch from API
        $api_key = get_option('pbs_schedule_tvss_api_key', '');
        $client = new PBS_TVSS_API_Client($api_key);

        if (!empty($args['feed'])) {
            $schedule = $client->get_feed_listings_by_date(
                $args['callsign'],
                $args['date'],
                $args['feed'],
                $args['images']
            );
        } else {
            $schedule = $client->get_listings_by_date(
                $args['callsign'],
                $args['date'],
                $args['images']
            );
        }

        if (is_wp_error($schedule)) {
            return $schedule;
        }

        // Match with on-demand content if enabled
        if ($args['link_ondemand']) {
            $schedule = $this->match_schedule_content($schedule);
        }

        // Cache the result
        $cache->set_schedule($args['callsign'], $args['date'], $schedule, $args['feed']);

        return $schedule;
    }

    /**
     * Filter schedule by time range
     *
     * @param array $schedule Schedule data
     * @param int $hours Number of hours from now
     * @return array Filtered schedule
     */
    public function filter_schedule_by_hours($schedule, $hours = 6) {
        if (!isset($schedule['feeds']) || !is_array($schedule['feeds'])) {
            return $schedule;
        }

        $now = current_time('timestamp');
        $end_time = $now + ($hours * 3600);

        foreach ($schedule['feeds'] as &$feed) {
            if (!isset($feed['listings']) || !is_array($feed['listings'])) {
                continue;
            }

            $filtered_listings = array();

            foreach ($feed['listings'] as $listing) {
                // Calculate listing timestamp
                $listing_time = PBS_TVSS_API_Client::parse_hhmm($listing['start_time']);
                $listing_timestamp = strtotime($listing_time . ' minutes', strtotime('today', $now));

                if ($listing_timestamp >= $now && $listing_timestamp <= $end_time) {
                    $filtered_listings[] = $listing;
                }
            }

            $feed['listings'] = $filtered_listings;
        }

        return $schedule;
    }

    /**
     * Filter schedule by time period
     *
     * @param array $schedule Schedule data
     * @param string $time_period Time period (early_morning, morning, afternoon, evening, all_day)
     * @return array Filtered schedule
     */
    public function filter_by_time_period($schedule, $time_period) {
        if (!isset($schedule['feeds']) || !is_array($schedule['feeds'])) {
            return $schedule;
        }

        // Define time ranges in HHMM format
        $time_ranges = array(
            'early_morning' => array('start' => '0000', 'end' => '0629'),  // 12am - 6:29am
            'morning' => array('start' => '0700', 'end' => '1129'),        // 7am - 11:29am
            'afternoon' => array('start' => '1200', 'end' => '1829'),      // 12pm - 6:29pm
            'evening' => array('start' => '1830', 'end' => '2359'),        // 6:30pm - 11:59pm
            'all_day' => array('start' => '0000', 'end' => '2359')         // All day
        );

        // If invalid or all_day, return all listings
        if ($time_period === 'all_day' || !isset($time_ranges[$time_period])) {
            return $schedule;
        }

        $range = $time_ranges[$time_period];
        $start_time = intval($range['start']);
        $end_time = intval($range['end']);

        foreach ($schedule['feeds'] as &$feed) {
            if (!isset($feed['listings']) || !is_array($feed['listings'])) {
                continue;
            }

            $filtered_listings = array();

            foreach ($feed['listings'] as $listing) {
                $listing_time = intval($listing['start_time']);

                // Check if listing falls within time range
                if ($listing_time >= $start_time && $listing_time <= $end_time) {
                    $filtered_listings[] = $listing;
                }
            }

            $feed['listings'] = $filtered_listings;
        }

        return $schedule;
    }
}
