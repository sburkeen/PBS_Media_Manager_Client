<?php
/**
 * Admin Settings
 *
 * Handles settings registration and rendering
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Schedule_Viewer_Settings {

    /**
     * Register settings
     */
    public static function register() {
        // API Credentials
        register_setting('pbs_schedule_api', 'pbs_schedule_tvss_api_key');
        register_setting('pbs_schedule_api', 'pbs_schedule_mm_client_id');
        register_setting('pbs_schedule_api', 'pbs_schedule_mm_client_secret');
        register_setting('pbs_schedule_api', 'pbs_schedule_mm_endpoint');

        // Station Configuration
        register_setting('pbs_schedule_station', 'pbs_schedule_station_id');
        register_setting('pbs_schedule_station', 'pbs_schedule_station_callsign');
        register_setting('pbs_schedule_station', 'pbs_schedule_station_name');
        register_setting('pbs_schedule_station', 'pbs_schedule_station_zip');
        register_setting('pbs_schedule_station', 'pbs_schedule_selected_feeds');

        // Display Options
        register_setting('pbs_schedule_display', 'pbs_schedule_display_hours');
        register_setting('pbs_schedule_display', 'pbs_schedule_refresh_interval');
        register_setting('pbs_schedule_display', 'pbs_schedule_show_images');
        register_setting('pbs_schedule_display', 'pbs_schedule_default_view');
        register_setting('pbs_schedule_display', 'pbs_schedule_link_ondemand');
        register_setting('pbs_schedule_display', 'pbs_schedule_custom_css');

        // Cache Settings
        register_setting('pbs_schedule_cache', 'pbs_schedule_cache_enabled');
        register_setting('pbs_schedule_cache', 'pbs_schedule_cache_schedule_duration');
        register_setting('pbs_schedule_cache', 'pbs_schedule_cache_ondemand_duration');

        // Add sections
        add_settings_section(
            'pbs_schedule_api_section',
            'API Credentials',
            array(__CLASS__, 'render_api_section'),
            'pbs_schedule_api'
        );

        add_settings_section(
            'pbs_schedule_station_section',
            'Station Configuration',
            array(__CLASS__, 'render_station_section'),
            'pbs_schedule_station'
        );

        add_settings_section(
            'pbs_schedule_display_section',
            'Display Options',
            array(__CLASS__, 'render_display_section'),
            'pbs_schedule_display'
        );

        add_settings_section(
            'pbs_schedule_cache_section',
            'Cache Settings',
            array(__CLASS__, 'render_cache_section'),
            'pbs_schedule_cache'
        );

        // Add fields
        self::add_api_fields();
        self::add_station_fields();
        self::add_display_fields();
        self::add_cache_fields();
    }

    /**
     * Add API credential fields
     */
    private static function add_api_fields() {
        add_settings_field(
            'tvss_api_key',
            'TV Schedules API Key',
            array(__CLASS__, 'render_text_field'),
            'pbs_schedule_api',
            'pbs_schedule_api_section',
            array(
                'name' => 'pbs_schedule_tvss_api_key',
                'type' => 'password',
                'description' => 'Your X-PBSAUTH key for the TV Schedules API'
            )
        );

        add_settings_field(
            'mm_client_id',
            'Media Manager Client ID',
            array(__CLASS__, 'render_text_field'),
            'pbs_schedule_api',
            'pbs_schedule_api_section',
            array(
                'name' => 'pbs_schedule_mm_client_id',
                'description' => 'Your PBS Media Manager API Client ID'
            )
        );

        add_settings_field(
            'mm_client_secret',
            'Media Manager Client Secret',
            array(__CLASS__, 'render_text_field'),
            'pbs_schedule_api',
            'pbs_schedule_api_section',
            array(
                'name' => 'pbs_schedule_mm_client_secret',
                'type' => 'password',
                'description' => 'Your PBS Media Manager API Client Secret'
            )
        );

        add_settings_field(
            'mm_endpoint',
            'Media Manager Endpoint',
            array(__CLASS__, 'render_select_field'),
            'pbs_schedule_api',
            'pbs_schedule_api_section',
            array(
                'name' => 'pbs_schedule_mm_endpoint',
                'options' => array(
                    'https://media.services.pbs.org/api/v1' => 'Production',
                    'https://media-staging.services.pbs.org/api/v1' => 'Staging'
                ),
                'description' => 'Select the API endpoint'
            )
        );
    }

    /**
     * Add station configuration fields
     */
    private static function add_station_fields() {
        add_settings_field(
            'station_search',
            'Search for Station',
            array(__CLASS__, 'render_station_search'),
            'pbs_schedule_station',
            'pbs_schedule_station_section'
        );

        add_settings_field(
            'station_callsign',
            'Station Callsign',
            array(__CLASS__, 'render_text_field'),
            'pbs_schedule_station',
            'pbs_schedule_station_section',
            array(
                'name' => 'pbs_schedule_station_callsign',
                'readonly' => true,
                'description' => 'Automatically populated from station search'
            )
        );

        add_settings_field(
            'station_zip',
            'ZIP Code (Optional)',
            array(__CLASS__, 'render_text_field'),
            'pbs_schedule_station',
            'pbs_schedule_station_section',
            array(
                'name' => 'pbs_schedule_station_zip',
                'description' => 'Used for channel/feed lookup'
            )
        );

        add_settings_field(
            'selected_feeds',
            'Select Feeds/Channels',
            array(__CLASS__, 'render_feed_selector'),
            'pbs_schedule_station',
            'pbs_schedule_station_section'
        );
    }

    /**
     * Add display option fields
     */
    private static function add_display_fields() {
        add_settings_field(
            'display_hours',
            'Time Range (Hours)',
            array(__CLASS__, 'render_number_field'),
            'pbs_schedule_display',
            'pbs_schedule_display_section',
            array(
                'name' => 'pbs_schedule_display_hours',
                'min' => 1,
                'max' => 24,
                'default' => 6,
                'description' => 'Number of hours to display in the schedule'
            )
        );

        add_settings_field(
            'refresh_interval',
            'Refresh Interval (Seconds)',
            array(__CLASS__, 'render_number_field'),
            'pbs_schedule_display',
            'pbs_schedule_display_section',
            array(
                'name' => 'pbs_schedule_refresh_interval',
                'min' => 60,
                'max' => 3600,
                'default' => 300,
                'description' => 'How often to refresh the schedule (minimum 60 seconds)'
            )
        );

        add_settings_field(
            'show_images',
            'Show Images',
            array(__CLASS__, 'render_checkbox_field'),
            'pbs_schedule_display',
            'pbs_schedule_display_section',
            array(
                'name' => 'pbs_schedule_show_images',
                'description' => 'Display show thumbnails in the schedule'
            )
        );

        add_settings_field(
            'default_view',
            'Default View',
            array(__CLASS__, 'render_select_field'),
            'pbs_schedule_display',
            'pbs_schedule_display_section',
            array(
                'name' => 'pbs_schedule_default_view',
                'options' => array(
                    'grid' => 'Grid View',
                    'list' => 'List View'
                ),
                'description' => 'Default schedule view'
            )
        );

        add_settings_field(
            'link_ondemand',
            'Link to On-Demand',
            array(__CLASS__, 'render_checkbox_field'),
            'pbs_schedule_display',
            'pbs_schedule_display_section',
            array(
                'name' => 'pbs_schedule_link_ondemand',
                'description' => 'Link schedule items to on-demand content when available'
            )
        );

        add_settings_field(
            'custom_css',
            'Custom CSS',
            array(__CLASS__, 'render_textarea_field'),
            'pbs_schedule_display',
            'pbs_schedule_display_section',
            array(
                'name' => 'pbs_schedule_custom_css',
                'description' => 'Add custom CSS to style the schedule display'
            )
        );
    }

    /**
     * Add cache setting fields
     */
    private static function add_cache_fields() {
        add_settings_field(
            'cache_enabled',
            'Enable Caching',
            array(__CLASS__, 'render_checkbox_field'),
            'pbs_schedule_cache',
            'pbs_schedule_cache_section',
            array(
                'name' => 'pbs_schedule_cache_enabled',
                'description' => 'Cache API responses to improve performance'
            )
        );

        add_settings_field(
            'cache_schedule_duration',
            'Schedule Cache Duration (Seconds)',
            array(__CLASS__, 'render_number_field'),
            'pbs_schedule_cache',
            'pbs_schedule_cache_section',
            array(
                'name' => 'pbs_schedule_cache_schedule_duration',
                'min' => 60,
                'max' => 86400,
                'default' => 900,
                'description' => 'How long to cache schedule data (default: 15 minutes)'
            )
        );

        add_settings_field(
            'cache_ondemand_duration',
            'On-Demand Cache Duration (Seconds)',
            array(__CLASS__, 'render_number_field'),
            'pbs_schedule_cache',
            'pbs_schedule_cache_section',
            array(
                'name' => 'pbs_schedule_cache_ondemand_duration',
                'min' => 300,
                'max' => 604800,
                'default' => 3600,
                'description' => 'How long to cache on-demand content data (default: 1 hour)'
            )
        );

        add_settings_field(
            'cache_stats',
            'Cache Statistics',
            array(__CLASS__, 'render_cache_stats'),
            'pbs_schedule_cache',
            'pbs_schedule_cache_section'
        );
    }

    /**
     * Section renderers
     */
    public static function render_api_section() {
        echo '<p>Enter your PBS API credentials. You can request access from PBS Digital support.</p>';
    }

    public static function render_station_section() {
        echo '<p>Configure your PBS station settings.</p>';
    }

    public static function render_display_section() {
        echo '<p>Customize how the schedule is displayed on your website.</p>';
    }

    public static function render_cache_section() {
        echo '<p>Configure caching to improve performance and reduce API calls.</p>';
    }

    /**
     * Field renderers
     */
    public static function render_text_field($args) {
        $name = $args['name'];
        $value = get_option($name, isset($args['default']) ? $args['default'] : '');
        $type = isset($args['type']) ? $args['type'] : 'text';
        $readonly = isset($args['readonly']) && $args['readonly'] ? 'readonly' : '';
        $description = isset($args['description']) ? $args['description'] : '';

        printf(
            '<input type="%s" name="%s" value="%s" class="regular-text" %s />',
            esc_attr($type),
            esc_attr($name),
            esc_attr($value),
            $readonly
        );

        if ($description) {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    public static function render_select_field($args) {
        $name = $args['name'];
        $value = get_option($name, isset($args['default']) ? $args['default'] : '');
        $options = $args['options'];
        $description = isset($args['description']) ? $args['description'] : '';

        printf('<select name="%s">', esc_attr($name));

        foreach ($options as $option_value => $option_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }

        echo '</select>';

        if ($description) {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    public static function render_checkbox_field($args) {
        $name = $args['name'];
        $value = get_option($name, isset($args['default']) ? $args['default'] : false);
        $description = isset($args['description']) ? $args['description'] : '';

        printf(
            '<input type="checkbox" name="%s" value="1" %s />',
            esc_attr($name),
            checked($value, true, false)
        );

        if ($description) {
            printf(' <span class="description">%s</span>', esc_html($description));
        }
    }

    public static function render_number_field($args) {
        $name = $args['name'];
        $value = get_option($name, isset($args['default']) ? $args['default'] : '');
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        $description = isset($args['description']) ? $args['description'] : '';

        printf(
            '<input type="number" name="%s" value="%s" min="%s" max="%s" class="small-text" />',
            esc_attr($name),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max)
        );

        if ($description) {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    public static function render_textarea_field($args) {
        $name = $args['name'];
        $value = get_option($name, isset($args['default']) ? $args['default'] : '');
        $description = isset($args['description']) ? $args['description'] : '';

        printf(
            '<textarea name="%s" rows="10" class="large-text code">%s</textarea>',
            esc_attr($name),
            esc_textarea($value)
        );

        if ($description) {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    public static function render_station_search($args) {
        $station_id = get_option('pbs_schedule_station_id', '');
        $station_name = get_option('pbs_schedule_station_name', '');
        ?>
        <input type="text" id="pbs-station-search" class="regular-text" placeholder="Search by callsign or station name..." />
        <input type="hidden" name="pbs_schedule_station_id" id="pbs-station-id" value="<?php echo esc_attr($station_id); ?>" />
        <input type="hidden" name="pbs_schedule_station_name" id="pbs-station-name" value="<?php echo esc_attr($station_name); ?>" />
        <div id="pbs-station-results" class="pbs-station-results"></div>
        <?php if ($station_name) : ?>
            <p class="description">Current station: <strong><?php echo esc_html($station_name); ?></strong></p>
        <?php endif; ?>
        <p class="description">Start typing to search for your PBS station</p>
        <?php
    }

    public static function render_feed_selector($args) {
        $selected_feeds = get_option('pbs_schedule_selected_feeds', array());
        ?>
        <div id="pbs-feed-selector">
            <p class="description">Select your station first to load available feeds.</p>
        </div>
        <input type="hidden" name="pbs_schedule_selected_feeds" id="pbs-selected-feeds" value="<?php echo esc_attr(json_encode($selected_feeds)); ?>" />
        <?php
    }

    public static function render_cache_stats($args) {
        $stats = PBS_Cache_Manager::get_stats();
        ?>
        <div class="pbs-cache-stats">
            <p>Cached items: <strong><?php echo esc_html($stats['total_items']); ?></strong></p>
            <p>Status: <strong><?php echo $stats['enabled'] ? 'Enabled' : 'Disabled'; ?></strong></p>
            <button type="button" class="button" id="pbs-clear-cache">Clear Cache</button>
        </div>
        <?php
    }
}
