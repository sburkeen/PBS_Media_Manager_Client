<?php
/**
 * PBS Content Matcher
 *
 * Matches TV schedule listings with on-demand content from Media Manager
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PBS_Content_Matcher {

    /**
     * Media Manager API client
     */
    private $mm_client;

    /**
     * Cache manager
     */
    private $cache;

    /**
     * Constructor
     *
     * @param PBS_Media_Manager_API_Client $mm_client Media Manager API client
     */
    public function __construct($mm_client = null) {
        $this->mm_client = $mm_client;
        $this->cache = new PBS_Cache_Manager();
    }

    /**
     * Match a schedule listing to on-demand content
     *
     * @param array $listing Single listing from TVSS API
     * @return array|null Matched on-demand content or null if no match
     */
    public function match_listing($listing) {
        // Try multiple matching strategies in order of reliability
        $match = null;

        // Strategy 1: Match by NOLA code (most reliable for PBS content)
        if (!empty($listing['nola_root']) && !empty($listing['nola_episode'])) {
            $match = $this->match_by_nola($listing['nola_root'], $listing['nola_episode']);
            if ($match) {
                $match['match_method'] = 'nola';
                return $match;
            }
        }

        // Strategy 2: Match by TMS ID (for Gracenote-sourced content)
        if (!empty($listing['tms_id'])) {
            $match = $this->match_by_tms_id($listing['tms_id']);
            if ($match) {
                $match['match_method'] = 'tms_id';
                return $match;
            }
        }

        // Strategy 3: Match by program_id if available
        if (!empty($listing['program_id'])) {
            $match = $this->match_by_program_id($listing['program_id']);
            if ($match) {
                $match['match_method'] = 'program_id';
                return $match;
            }
        }

        // Strategy 4: Fuzzy title match (least reliable, use as fallback)
        if (!empty($listing['title'])) {
            $match = $this->match_by_title($listing);
            if ($match) {
                $match['match_method'] = 'title';
                return $match;
            }
        }

        return null;
    }

    /**
     * Match by NOLA code
     *
     * @param string $nola_root NOLA root code
     * @param string $nola_episode NOLA episode code
     * @return array|null
     */
    private function match_by_nola($nola_root, $nola_episode) {
        $cache_key = 'nola_' . $nola_root . '_' . $nola_episode;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        if (!$this->mm_client) {
            return null;
        }

        // Search for show by NOLA root
        $shows = $this->mm_client->get_shows(array(
            'nola-root' => $nola_root
        ));

        if (empty($shows) || !is_array($shows)) {
            return null;
        }

        $show = $shows[0];
        $show_id = $show['id'];

        // Get all episodes for this show
        $episodes = $this->get_all_show_episodes($show_id);

        // Find matching episode by NOLA code
        foreach ($episodes as $episode) {
            if (isset($episode['attributes']['nola_episode']) &&
                $episode['attributes']['nola_episode'] === $nola_episode) {

                $result = array(
                    'show' => $show,
                    'episode' => $episode,
                    'assets' => $this->get_episode_assets($episode['id'])
                );

                $this->cache->set($cache_key, $result, 3600); // Cache for 1 hour
                return $result;
            }
        }

        return null;
    }

    /**
     * Match by TMS ID
     *
     * @param string $tms_id Gracenote TMS ID
     * @return array|null
     */
    private function match_by_tms_id($tms_id) {
        $cache_key = 'tms_' . $tms_id;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        if (!$this->mm_client) {
            return null;
        }

        // Search for content by TMS ID
        // Note: Media Manager may not have TMS ID indexing
        // This is a placeholder for future enhancement
        return null;
    }

    /**
     * Match by program ID
     *
     * @param int $program_id TVSS program ID
     * @return array|null
     */
    private function match_by_program_id($program_id) {
        $cache_key = 'program_' . $program_id;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Store mapping in WordPress options for future reference
        $mapping = get_option('pbs_tvss_program_mapping', array());

        if (isset($mapping[$program_id])) {
            $show_id = $mapping[$program_id];
            return $this->get_show_content($show_id);
        }

        return null;
    }

    /**
     * Match by title (fuzzy matching)
     *
     * @param array $listing Schedule listing
     * @return array|null
     */
    private function match_by_title($listing) {
        if (!$this->mm_client) {
            return null;
        }

        $title = $listing['title'];
        $episode_title = isset($listing['episode_title']) ? $listing['episode_title'] : '';

        // Normalize title for searching
        $search_title = $this->normalize_title($title);

        // Search shows by title
        $shows = $this->mm_client->get_shows(array(
            'title' => $search_title
        ));

        if (empty($shows) || !is_array($shows)) {
            return null;
        }

        // Get the first matching show
        $show = $shows[0];

        // If we have an episode title, try to find the specific episode
        if (!empty($episode_title)) {
            $episodes = $this->get_all_show_episodes($show['id']);

            foreach ($episodes as $episode) {
                if (isset($episode['attributes']['title'])) {
                    $ep_title = $this->normalize_title($episode['attributes']['title']);
                    $search_ep_title = $this->normalize_title($episode_title);

                    if (strpos($ep_title, $search_ep_title) !== false ||
                        strpos($search_ep_title, $ep_title) !== false) {

                        return array(
                            'show' => $show,
                            'episode' => $episode,
                            'assets' => $this->get_episode_assets($episode['id'])
                        );
                    }
                }
            }
        }

        // Return show without specific episode
        return array(
            'show' => $show,
            'episode' => null,
            'assets' => $this->get_show_assets($show['id'])
        );
    }

    /**
     * Normalize title for matching
     *
     * @param string $title Title to normalize
     * @return string
     */
    private function normalize_title($title) {
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9\s]/', '', $title);
        $title = trim($title);
        return $title;
    }

    /**
     * Get all episodes for a show
     *
     * @param string $show_id Show ID
     * @return array
     */
    private function get_all_show_episodes($show_id) {
        $cache_key = 'show_episodes_' . $show_id;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $episodes = array();

        // Get all seasons
        $seasons = $this->mm_client->get_show_seasons($show_id);

        if (is_array($seasons)) {
            foreach ($seasons as $season) {
                $season_episodes = $this->mm_client->get_season_episodes($season['id']);
                if (is_array($season_episodes)) {
                    $episodes = array_merge($episodes, $season_episodes);
                }
            }
        }

        // Also get specials
        $specials = $this->mm_client->get_show_specials($show_id);
        if (is_array($specials)) {
            $episodes = array_merge($episodes, $specials);
        }

        $this->cache->set($cache_key, $episodes, 3600); // Cache for 1 hour
        return $episodes;
    }

    /**
     * Get assets for an episode
     *
     * @param string $episode_id Episode ID
     * @return array
     */
    private function get_episode_assets($episode_id) {
        $cache_key = 'episode_assets_' . $episode_id;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Get full-length assets that are publicly available
        $assets = $this->mm_client->get_episode_assets($episode_id, 'full_length', 'all_members');

        if (!is_array($assets)) {
            $assets = array();
        }

        $this->cache->set($cache_key, $assets, 3600);
        return $assets;
    }

    /**
     * Get assets for a show
     *
     * @param string $show_id Show ID
     * @return array
     */
    private function get_show_assets($show_id) {
        $cache_key = 'show_assets_' . $show_id;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $assets = $this->mm_client->get_show_assets($show_id, 'full_length', 'all_members');

        if (!is_array($assets)) {
            $assets = array();
        }

        $this->cache->set($cache_key, $assets, 3600);
        return $assets;
    }

    /**
     * Get show content by ID
     *
     * @param string $show_id Show ID
     * @return array|null
     */
    private function get_show_content($show_id) {
        $cache_key = 'show_content_' . $show_id;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $show = $this->mm_client->get_show($show_id);

        if (!$show) {
            return null;
        }

        $result = array(
            'show' => $show,
            'episode' => null,
            'assets' => $this->get_show_assets($show_id)
        );

        $this->cache->set($cache_key, $result, 3600);
        return $result;
    }

    /**
     * Batch match multiple listings
     *
     * @param array $listings Array of schedule listings
     * @return array Array of listings with matched on-demand content
     */
    public function match_listings($listings) {
        $matched = array();

        foreach ($listings as $listing) {
            $match = $this->match_listing($listing);

            $matched[] = array(
                'listing' => $listing,
                'on_demand' => $match,
                'has_on_demand' => !empty($match)
            );
        }

        return $matched;
    }

    /**
     * Create or update PBS Show post for matched content
     *
     * @param array $match Matched content from match_listing()
     * @param array $listing Original schedule listing
     * @return int|null Post ID or null on failure
     */
    public function create_show_post($match, $listing) {
        if (empty($match) || empty($match['show'])) {
            return null;
        }

        $show = $match['show'];
        $show_id = $show['id'];

        // Check if post already exists
        $existing = get_posts(array(
            'post_type' => 'pbs_show',
            'meta_key' => '_pbs_show_id',
            'meta_value' => $show_id,
            'posts_per_page' => 1
        ));

        if (!empty($existing)) {
            return $existing[0]->ID;
        }

        // Create new post
        $post_data = array(
            'post_type' => 'pbs_show',
            'post_title' => $show['attributes']['title'],
            'post_content' => isset($show['attributes']['description_long']) ?
                $show['attributes']['description_long'] :
                (isset($show['attributes']['description_short']) ?
                    $show['attributes']['description_short'] : ''),
            'post_excerpt' => isset($show['attributes']['description_short']) ?
                $show['attributes']['description_short'] : '',
            'post_status' => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id) || !$post_id) {
            return null;
        }

        // Store metadata
        update_post_meta($post_id, '_pbs_show_id', $show_id);
        update_post_meta($post_id, '_pbs_nola_root', $listing['nola_root']);

        if (isset($show['attributes']['genre'])) {
            update_post_meta($post_id, '_pbs_genre', $show['attributes']['genre']);
        }

        // Set featured image if available
        if (!empty($listing['image']) && is_array($listing['image'])) {
            $this->set_post_thumbnail_from_url($post_id, $listing['image'][0]['image']);
        }

        return $post_id;
    }

    /**
     * Set post thumbnail from remote URL
     *
     * @param int $post_id Post ID
     * @param string $image_url Remote image URL
     * @return int|null Attachment ID or null on failure
     */
    private function set_post_thumbnail_from_url($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download image
        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return null;
        }

        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );

        // Upload to media library
        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return null;
        }

        // Set as featured image
        set_post_thumbnail($post_id, $id);

        return $id;
    }
}
