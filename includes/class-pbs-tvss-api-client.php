<?php
/**
 * PBS TV Schedules Service (TVSS) API Client
 *
 * A PHP client for the PBS TV Schedules API
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_TVSS_API_Client {

    /**
     * API base endpoint
     */
    private $base_endpoint = 'https://tvss.services.pbs.org/tvss/';

    /**
     * API key for authentication
     */
    private $api_key = '';

    /**
     * Station Finder API endpoint
     */
    private $station_finder_endpoint = 'https://station.services.pbs.org/api/public/v1/';

    /**
     * Constructor
     *
     * @param string $api_key The X-PBSAUTH API key
     */
    public function __construct($api_key = '') {
        $this->api_key = $api_key;
    }

    /**
     * Make an authenticated API request
     *
     * @param string $endpoint The API endpoint (relative to base)
     * @param bool $require_auth Whether this endpoint requires authentication
     * @return array|WP_Error Response data or error
     */
    private function make_request($endpoint, $require_auth = true) {
        $url = $this->base_endpoint . $endpoint;

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        );

        // Add authentication header if required
        if ($require_auth && !empty($this->api_key)) {
            $args['headers']['X-PBSAUTH'] = $this->api_key;
        }

        $response = wp_remote_get($url, $args);

        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Handle non-200 responses
        if ($code !== 200) {
            return new WP_Error('api_error', sprintf(
                'API request failed with status %d: %s',
                $code,
                $body
            ));
        }

        // Decode JSON
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to decode JSON response');
        }

        return $data;
    }

    /**
     * Get listings for a specific date
     *
     * @param string $callsign Station callsign (e.g., 'weta')
     * @param string $date Date in YYYYMMDD format
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_listings_by_date($callsign, $date, $fetch_images = true) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/day/%s/', $callsign, $date);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get KIDS listings for a specific date
     *
     * @param string $callsign Station callsign
     * @param string $date Date in YYYYMMDD format
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_kids_listings_by_date($callsign, $date, $fetch_images = true) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/day/%s/kids/', $callsign, $date);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get listings for today
     *
     * @param string $callsign Station callsign
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_todays_listings($callsign, $fetch_images = true) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/today/', $callsign);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get KIDS listings for today
     *
     * @param string $callsign Station callsign
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_todays_kids_listings($callsign, $fetch_images = true) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/today/kids/', $callsign);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get listings for a specific feed on a specific date
     *
     * @param string $callsign Station callsign
     * @param string $date Date in YYYYMMDD format
     * @param string $feed_cid Feed CID
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_feed_listings_by_date($callsign, $date, $feed_cid, $fetch_images = true) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/day/%s/%s/', $callsign, $date, $feed_cid);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get listings for a specific feed today
     *
     * @param string $callsign Station callsign
     * @param string $feed_cid Feed CID
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_feed_todays_listings($callsign, $feed_cid, $fetch_images = true) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/today/%s/', $callsign, $feed_cid);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get feed listings by feed CID only (no callsign)
     *
     * @param string $feed_cid Feed CID
     * @param string $date Date in YYYYMMDD format
     * @param bool $fetch_images Whether to fetch Gracenote images
     * @return array|WP_Error
     */
    public function get_feed_listings_by_cid($feed_cid, $date, $fetch_images = true) {
        $endpoint = sprintf('feed/%s/day/%s/', $feed_cid, $date);

        if ($fetch_images) {
            $endpoint .= '?fetch-images';
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get feed info for a station
     *
     * @param int $station_id Station ID (from Station Finder API)
     * @return array|WP_Error
     */
    public function get_station_feeds($station_id) {
        $endpoint = sprintf('stations/%d/', $station_id);
        return $this->make_request($endpoint, true);
    }

    /**
     * Search programs and episodes by keyword
     *
     * @param string $callsign Station callsign
     * @param string $keyword Search keyword(s)
     * @return array|WP_Error
     */
    public function search_programs($callsign, $keyword) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/programs/search/?q=%s', $callsign, urlencode($keyword));
        return $this->make_request($endpoint, false); // No auth required
    }

    /**
     * Search upcoming programs and episodes by keyword
     *
     * @param string $callsign Station callsign
     * @param string $keyword Search keyword(s)
     * @return array|WP_Error
     */
    public function search_upcoming_programs($callsign, $keyword) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/programs/upcoming/search/?q=%s', $callsign, urlencode($keyword));
        return $this->make_request($endpoint, false); // No auth required
    }

    /**
     * Get channel/feed lookup by callsign and zip code
     *
     * @param string $callsign Station callsign
     * @param string $zip_code ZIP code (5 digits, zero-padded)
     * @return array|WP_Error
     */
    public function get_channel_lookup($callsign, $zip_code = '') {
        $callsign = strtolower($callsign);

        if (!empty($zip_code)) {
            $zip_code = str_pad($zip_code, 5, '0', STR_PAD_LEFT);
            $endpoint = sprintf('%s/channelfeed/%s/', $callsign, $zip_code);
        } else {
            $endpoint = sprintf('%s/channelfeed/', $callsign);
        }

        return $this->make_request($endpoint, false); // No auth required
    }

    /**
     * Get sibling callsigns for a station
     *
     * @param string $callsign Station callsign
     * @return array|WP_Error
     */
    public function get_sibling_callsigns($callsign) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/callsigns/', $callsign);
        return $this->make_request($endpoint, true);
    }

    /**
     * Get full program list
     *
     * @return array|WP_Error
     */
    public function get_all_programs() {
        return $this->make_request('programs/', false); // No auth required
    }

    /**
     * Get upcoming program listings by program_id or tms_id
     *
     * @param string $callsign Station callsign
     * @param int|string $id Program ID or TMS ID (must start with "SH" for series)
     * @param string $id_type Either 'program_id' or 'tms_id'
     * @return array|WP_Error
     */
    public function get_upcoming_program_listings($callsign, $id, $id_type = 'program_id') {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/upcoming/program/?%s=%s', $callsign, $id_type, $id);
        return $this->make_request($endpoint, true);
    }

    /**
     * Get upcoming show listings by episode_id or onetimeonly_id
     *
     * @param string $callsign Station callsign
     * @param int $episode_id Episode ID
     * @param int $onetimeonly_id One-time-only ID
     * @return array|WP_Error
     */
    public function get_upcoming_show_listings($callsign, $episode_id = null, $onetimeonly_id = null) {
        $callsign = strtolower($callsign);

        if ($episode_id) {
            $endpoint = sprintf('%s/upcoming/show/?episode_id=%d', $callsign, $episode_id);
        } elseif ($onetimeonly_id) {
            $endpoint = sprintf('%s/upcoming/show/?onetimeonly_id=%d', $callsign, $onetimeonly_id);
        } else {
            return new WP_Error('missing_param', 'Either episode_id or onetimeonly_id is required');
        }

        return $this->make_request($endpoint, true);
    }

    /**
     * Get upcoming episode listings by TMS ID
     *
     * @param string $callsign Station callsign
     * @param string $tms_id Gracenote TMS ID for episodes
     * @return array|WP_Error
     */
    public function get_upcoming_episode_by_tms($callsign, $tms_id) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/upcoming/episode/?tms_id=%s', $callsign, $tms_id);
        return $this->make_request($endpoint, true);
    }

    /**
     * Get upcoming one-time-only listings by TMS ID
     *
     * @param string $callsign Station callsign
     * @param string $tms_id Gracenote TMS ID for OTO
     * @return array|WP_Error
     */
    public function get_upcoming_oto_by_tms($callsign, $tms_id) {
        $callsign = strtolower($callsign);
        $endpoint = sprintf('%s/upcoming/onetimeonly/?tms_id=%s', $callsign, $tms_id);
        return $this->make_request($endpoint, true);
    }

    /**
     * STATION FINDER API METHODS
     */

    /**
     * Search for stations by callsign or name
     *
     * @param string $query Search query
     * @return array|WP_Error
     */
    public function search_stations($query) {
        // PBS Station Finder API supports multiple search parameters
        // Try call_sign first, but also support searching by name or zip
        $search_params = '';

        // If query looks like a zip code (5 digits)
        if (preg_match('/^\d{5}$/', $query)) {
            $search_params = 'zip=' . urlencode($query);
        }
        // If query looks like a callsign (4-5 uppercase letters)
        elseif (preg_match('/^[A-Z]{4,5}$/i', $query)) {
            $search_params = 'call_sign=' . urlencode(strtoupper($query));
        }
        // Otherwise search by name
        else {
            $search_params = 'q=' . urlencode($query);
        }

        $url = $this->station_finder_endpoint . 'stations/?' . $search_params;

        error_log('PBS Station Search URL: ' . $url);

        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array('Accept' => 'application/json')
        ));

        if (is_wp_error($response)) {
            error_log('PBS Station Search Error: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            error_log(sprintf('PBS Station Search HTTP Error %d: %s', $code, substr($body, 0, 200)));
            return new WP_Error('api_error', sprintf('Failed to search stations (HTTP %d)', $code));
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('PBS Station Search JSON Error: ' . json_last_error_msg());
            return new WP_Error('json_error', 'Failed to decode JSON response');
        }

        // Log the raw response structure for debugging
        error_log('PBS Station Search Response keys: ' . print_r(array_keys($data), true));
        if (!empty($data)) {
            error_log('PBS Station Search Response (first 500 chars): ' . substr(print_r($data, true), 0, 500));
        }

        // Handle different response formats
        // Format 1: {"results": [...]}
        if (isset($data['results']) && is_array($data['results'])) {
            return array('results' => $data['results']);
        }
        // Format 2: Direct array of stations
        elseif (is_array($data) && !isset($data['errors'])) {
            // Check if it's a paginated response
            if (isset($data['data']) && is_array($data['data'])) {
                return array('results' => $data['data']);
            }
            // Check if first element looks like a station
            if (!empty($data) && isset($data[0]['call_sign'])) {
                return array('results' => $data);
            }
        }

        // If we couldn't parse it, return the raw data
        return $data;
    }

    /**
     * Get station by ID
     *
     * @param int $station_id Station ID
     * @return array|WP_Error
     */
    public function get_station_by_id($station_id) {
        $url = $this->station_finder_endpoint . 'stations/' . intval($station_id) . '/';

        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array('Accept' => 'application/json')
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            return new WP_Error('api_error', 'Failed to get station');
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to decode JSON response');
        }

        return $data;
    }

    /**
     * Helper: Format date as YYYYMMDD
     *
     * @param string|int $date Date string or timestamp
     * @return string
     */
    public static function format_date($date = 'now') {
        if (is_numeric($date)) {
            return date('Ymd', $date);
        }
        return date('Ymd', strtotime($date));
    }

    /**
     * Helper: Parse HHMM time format to minutes
     *
     * @param string $hhmm Time in HHMM format (e.g., "0130" = 1:30 AM)
     * @return int Minutes since midnight
     */
    public static function parse_hhmm($hhmm) {
        $hours = intval(substr($hhmm, 0, 2));
        $minutes = intval(substr($hhmm, 2, 2));
        return ($hours * 60) + $minutes;
    }

    /**
     * Helper: Format HHMM to readable time
     *
     * @param string $hhmm Time in HHMM format
     * @param string $timezone Timezone string
     * @return string Formatted time (e.g., "1:30 PM")
     */
    public static function format_time($hhmm, $timezone = 'America/New_York') {
        $hours = intval(substr($hhmm, 0, 2));
        $minutes = intval(substr($hhmm, 2, 2));

        $timestamp = mktime($hours, $minutes, 0);
        return date('g:i A', $timestamp);
    }

    /**
     * Helper: Calculate end time from start time and duration
     *
     * @param string $start_hhmm Start time in HHMM
     * @param int $duration_minutes Duration in minutes
     * @return string End time in HHMM format
     */
    public static function calculate_end_time($start_hhmm, $duration_minutes) {
        $start_minutes = self::parse_hhmm($start_hhmm);
        $end_minutes = $start_minutes + $duration_minutes;

        $hours = floor($end_minutes / 60) % 24;
        $minutes = $end_minutes % 60;

        return sprintf('%02d%02d', $hours, $minutes);
    }
}
