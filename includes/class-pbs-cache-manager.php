<?php
/**
 * PBS Cache Manager
 *
 * Handles caching for API responses to improve performance
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Cache_Manager {

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'pbs_schedule_';

    /**
     * Cache group
     */
    const CACHE_GROUP = 'pbs_schedule_viewer';

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        return get_option('pbs_schedule_cache_enabled', true);
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed Cached data or false if not found
     */
    public function get($key) {
        if (!self::is_enabled()) {
            return false;
        }

        $cache_key = self::CACHE_PREFIX . $key;
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        // Try transient as fallback
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            // Store in object cache for faster access
            wp_cache_set($cache_key, $cached, self::CACHE_GROUP);
            return $cached;
        }

        return false;
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds (default: 15 minutes)
     * @return bool
     */
    public function set($key, $data, $expiration = 900) {
        if (!self::is_enabled()) {
            return false;
        }

        $cache_key = self::CACHE_PREFIX . $key;

        // Store in object cache
        wp_cache_set($cache_key, $data, self::CACHE_GROUP, $expiration);

        // Also store in transient for persistence
        set_transient($cache_key, $data, $expiration);

        return true;
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key) {
        $cache_key = self::CACHE_PREFIX . $key;

        wp_cache_delete($cache_key, self::CACHE_GROUP);
        delete_transient($cache_key);

        return true;
    }

    /**
     * Clear all cache for the plugin
     *
     * @return bool
     */
    public static function clear_all_cache() {
        global $wpdb;

        // Delete all transients with our prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s
                OR option_name LIKE %s",
                $wpdb->esc_like('_transient_' . self::CACHE_PREFIX) . '%',
                $wpdb->esc_like('_transient_timeout_' . self::CACHE_PREFIX) . '%'
            )
        );

        // Clear object cache (if available)
        wp_cache_flush_group(self::CACHE_GROUP);

        return true;
    }

    /**
     * Get cache for schedule data
     *
     * @param string $callsign Station callsign
     * @param string $date Date in YYYYMMDD format
     * @param string $feed_cid Optional feed CID
     * @return array|false
     */
    public function get_schedule($callsign, $date, $feed_cid = '') {
        $key = sprintf('schedule_%s_%s_%s', $callsign, $date, $feed_cid);
        return $this->get($key);
    }

    /**
     * Set cache for schedule data
     *
     * @param string $callsign Station callsign
     * @param string $date Date in YYYYMMDD format
     * @param array $data Schedule data
     * @param string $feed_cid Optional feed CID
     * @return bool
     */
    public function set_schedule($callsign, $date, $data, $feed_cid = '') {
        $key = sprintf('schedule_%s_%s_%s', $callsign, $date, $feed_cid);
        $expiration = get_option('pbs_schedule_cache_schedule_duration', 900);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cache for on-demand content
     *
     * @param string $show_id Show ID
     * @return array|false
     */
    public function get_ondemand($show_id) {
        $key = sprintf('ondemand_%s', $show_id);
        return $this->get($key);
    }

    /**
     * Set cache for on-demand content
     *
     * @param string $show_id Show ID
     * @param array $data On-demand data
     * @return bool
     */
    public function set_ondemand($show_id, $data) {
        $key = sprintf('ondemand_%s', $show_id);
        $expiration = get_option('pbs_schedule_cache_ondemand_duration', 3600);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Clear schedule cache for a specific date
     *
     * @param string $callsign Station callsign
     * @param string $date Date in YYYYMMDD format
     * @return bool
     */
    public function clear_schedule($callsign, $date) {
        $key = sprintf('schedule_%s_%s', $callsign, $date);
        return $this->delete($key);
    }

    /**
     * Clear on-demand cache for a specific show
     *
     * @param string $show_id Show ID
     * @return bool
     */
    public function clear_ondemand($show_id) {
        $key = sprintf('ondemand_%s', $show_id);
        return $this->delete($key);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options}
                WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . self::CACHE_PREFIX) . '%'
            )
        );

        return array(
            'total_items' => intval($count),
            'enabled' => self::is_enabled()
        );
    }
}
