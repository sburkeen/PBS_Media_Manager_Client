<?php
/**
 * Shortcodes
 *
 * Handles all shortcodes for the plugin
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Schedule_Viewer_Shortcodes {

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
        add_shortcode('pbs_schedule', array($this, 'schedule_shortcode'));
        add_shortcode('pbs_show', array($this, 'show_shortcode'));
    }

    /**
     * Schedule shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function schedule_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => get_option('pbs_schedule_default_view', 'grid'),
            'hours' => get_option('pbs_schedule_display_hours', 6),
            'feed' => '',
            'images' => get_option('pbs_schedule_show_images', true) ? 'yes' : 'no',
            'date' => date('Ymd')
        ), $atts, 'pbs_schedule');

        // Convert string to boolean
        $show_images = ($atts['images'] === 'yes');

        // Get schedule data
        $public = PBS_Schedule_Viewer_Public::get_instance();
        $schedule = $public->get_schedule(array(
            'feed' => $atts['feed'],
            'date' => $atts['date'],
            'hours' => intval($atts['hours']),
            'images' => $show_images
        ));

        if (is_wp_error($schedule)) {
            return '<div class="pbs-schedule-error">' . esc_html($schedule->get_error_message()) . '</div>';
        }

        // Filter by time range
        $schedule = $public->filter_schedule_by_hours($schedule, intval($atts['hours']));

        // Load appropriate template
        ob_start();

        if ($atts['view'] === 'grid') {
            include PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'public/views/schedule-grid.php';
        } else {
            include PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'public/views/schedule-list.php';
        }

        return ob_get_clean();
    }

    /**
     * Show shortcode - displays a specific PBS show
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function show_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'slug' => '',
            'show_id' => '',
            'episodes' => 'yes',
            'limit' => -1
        ), $atts, 'pbs_show');

        // Get show post
        if (!empty($atts['id'])) {
            $show = get_post(intval($atts['id']));
        } elseif (!empty($atts['slug'])) {
            $show = get_page_by_path($atts['slug'], OBJECT, 'pbs_show');
        } elseif (!empty($atts['show_id'])) {
            $posts = get_posts(array(
                'post_type' => 'pbs_show',
                'meta_key' => '_pbs_show_id',
                'meta_value' => $atts['show_id'],
                'posts_per_page' => 1
            ));
            $show = !empty($posts) ? $posts[0] : null;
        } else {
            return '<div class="pbs-show-error">No show specified</div>';
        }

        if (!$show) {
            return '<div class="pbs-show-error">Show not found</div>';
        }

        // Get on-demand content
        $show_episodes = array();

        if ($atts['episodes'] === 'yes') {
            $mm_id = get_option('pbs_schedule_mm_client_id', '');
            $mm_secret = get_option('pbs_schedule_mm_client_secret', '');
            $mm_endpoint = get_option('pbs_schedule_mm_endpoint', '');

            if (!empty($mm_id) && !empty($mm_secret)) {
                $pbs_show_id = get_post_meta($show->ID, '_pbs_show_id', true);

                if ($pbs_show_id) {
                    $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);
                    $matcher = new PBS_Content_Matcher($mm_client);
                    $show_episodes = $matcher->get_all_show_episodes($pbs_show_id);

                    if (intval($atts['limit']) > 0) {
                        $show_episodes = array_slice($show_episodes, 0, intval($atts['limit']));
                    }
                }
            }
        }

        ob_start();
        include PBS_SCHEDULE_VIEWER_PLUGIN_DIR . 'public/views/show-detail.php';
        return ob_get_clean();
    }
}
