<?php
/**
 * Single PBS Show Template
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

get_header();

$show_id = get_post_meta(get_the_ID(), '_pbs_show_id', true);
$mm_id = get_option('pbs_schedule_mm_client_id', '');
$mm_secret = get_option('pbs_schedule_mm_client_secret', '');
$mm_endpoint = get_option('pbs_schedule_mm_endpoint', '');

$seasons = array();
$episodes_by_season = array();
$extras = array();
$show_data = null;

if (!empty($mm_id) && !empty($mm_secret) && !empty($show_id)) {
    $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);

    // Get show data
    $show_data = $mm_client->get_show($show_id);

    // Get all seasons
    $seasons = $mm_client->get_show_seasons($show_id);

    // Organize episodes by season
    if (!empty($seasons) && is_array($seasons)) {
        foreach ($seasons as $season) {
            $season_id = $season['id'];
            $season_ordinal = isset($season['attributes']['ordinal']) ? $season['attributes']['ordinal'] : 0;

            $season_episodes = $mm_client->get_season_episodes($season_id);

            if (!empty($season_episodes) && is_array($season_episodes)) {
                $episodes_by_season[$season_ordinal] = array(
                    'season' => $season,
                    'episodes' => $season_episodes
                );
            }
        }
        ksort($episodes_by_season);
    }

    // Get extras (clips, previews, etc.)
    $all_assets = $mm_client->get_show_assets($show_id, '', 'all_members');
    if (!empty($all_assets) && is_array($all_assets)) {
        foreach ($all_assets as $asset) {
            $asset_type = isset($asset['attributes']['object_type']) ? $asset['attributes']['object_type'] : '';
            if (in_array($asset_type, array('clip', 'preview', 'extra'))) {
                $extras[] = $asset;
            }
        }
    }
}

// Helper function to format duration
function format_show_duration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($hours > 0 && $minutes > 0) {
        return sprintf('%dh %dm', $hours, $minutes);
    } elseif ($hours > 0) {
        return sprintf('%dh', $hours);
    } else {
        return sprintf('%dm', $minutes);
    }
}

// Get upcoming schedule for this show
$nola_root = get_post_meta(get_the_ID(), '_pbs_nola_root', true);
$upcoming_schedule = array();

if (!empty($nola_root)) {
    $tvss_key = get_option('pbs_schedule_tvss_api_key', '');
    $callsign = get_option('pbs_schedule_station_callsign', '');

    if (!empty($tvss_key) && !empty($callsign)) {
        $tvss_client = new PBS_TVSS_API_Client($tvss_key);
        // Get next 7 days of listings
        for ($i = 0; $i < 7; $i++) {
            $date = date('Ymd', strtotime("+$i days"));
            $listings = $tvss_client->get_listings_by_date($callsign, $date, false);

            if (!is_wp_error($listings) && isset($listings['feeds'])) {
                foreach ($listings['feeds'] as $feed) {
                    if (!empty($feed['listings'])) {
                        foreach ($feed['listings'] as $listing) {
                            if (isset($listing['nola_root']) && $listing['nola_root'] === $nola_root) {
                                $upcoming_schedule[] = array(
                                    'date' => $date,
                                    'feed' => $feed['full_name'],
                                    'listing' => $listing
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<article class="np-show-page">
    <header class="np-show-header">
        <h1><?php the_title(); ?></h1>
        <div class="np-show-description">
            <?php the_content(); ?>
        </div>
    </header>

    <?php if (!empty($episodes_by_season)) : ?>
        <?php foreach ($episodes_by_season as $season_ordinal => $season_data) : ?>
            <section class="np-season-section">
                <h2>Episodes (Season <?php echo esc_html($season_ordinal); ?>)</h2>

                <ol class="np-episodes-list">
                    <?php foreach ($season_data['episodes'] as $index => $episode) : ?>
                        <?php
                        $episode_title = isset($episode['attributes']['title']) ? $episode['attributes']['title'] : 'Untitled Episode';
                        $episode_description = isset($episode['attributes']['description_short']) ? $episode['attributes']['description_short'] : '';
                        $episode_ordinal = isset($episode['attributes']['ordinal']) ? $episode['attributes']['ordinal'] : ($index + 1);
                        $episode_id = $episode['id'];

                        // Get episode duration from assets
                        $duration_text = '';
                        if (!empty($mm_client)) {
                            $assets = $mm_client->get_episode_assets($episode_id, 'full_length', 'all_members');
                            if (!empty($assets) && is_array($assets) && isset($assets[0]['attributes']['duration'])) {
                                $duration_text = format_show_duration($assets[0]['attributes']['duration']);
                            }
                        }
                        ?>

                        <li class="np-episode-item">
                            <div class="np-episode-meta">
                                S<?php echo esc_html($season_ordinal); ?> E<?php echo esc_html($episode_ordinal); ?>
                                <?php if ($duration_text) : ?>
                                    · <?php echo esc_html($duration_text); ?>
                                <?php endif; ?>
                            </div>

                            <div class="np-episode-title">
                                <strong><?php echo esc_html($episode_title); ?></strong>
                            </div>

                            <?php if ($episode_description) : ?>
                                <div class="np-episode-description">
                                    <?php echo esc_html($episode_description); ?>
                                </div>
                            <?php endif; ?>

                            <div class="np-show-link">
                                <a href="#">Watch episode</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($extras)) : ?>
        <section class="np-extras-section">
            <h2>Extras + Features</h2>

            <ul class="np-extras-list">
                <?php foreach ($extras as $extra) : ?>
                    <?php
                    $extra_title = isset($extra['attributes']['title']) ? $extra['attributes']['title'] : 'Untitled';
                    $extra_description = isset($extra['attributes']['description_short']) ? $extra['attributes']['description_short'] : '';
                    $duration_text = '';
                    if (isset($extra['attributes']['duration'])) {
                        $duration_text = format_show_duration($extra['attributes']['duration']);
                    }
                    ?>

                    <li class="np-extra-item">
                        <div class="np-extra-title">
                            <strong><?php echo esc_html($extra_title); ?></strong>
                        </div>

                        <?php if ($duration_text) : ?>
                            <div class="np-extra-duration">
                                <?php echo esc_html($duration_text); ?>
                                <?php if ($extra_description) : ?>
                                    · <?php echo esc_html($extra_description); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="np-show-link">
                            <a href="#">Watch clip</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($upcoming_schedule)) : ?>
        <section class="np-upcoming-section">
            <h2>Upcoming TV Schedule</h2>

            <ul class="np-upcoming-list">
                <?php
                $timezone = isset($schedule['timezone']) ? $schedule['timezone'] : 'America/New_York';
                foreach ($upcoming_schedule as $upcoming) :
                    $date_formatted = date('D M j', strtotime($upcoming['date']));
                    $time_formatted = PBS_TVSS_API_Client::format_time($upcoming['listing']['start_time'], $timezone);
                    $episode_title = isset($upcoming['listing']['episode_title']) ? $upcoming['listing']['episode_title'] : '';
                ?>
                    <li class="np-upcoming-item">
                        <span class="np-upcoming-date"><?php echo esc_html($date_formatted); ?></span> ·
                        <span class="np-upcoming-time"><?php echo esc_html($time_formatted); ?></span> ·
                        <span class="np-upcoming-feed"><?php echo esc_html($upcoming['feed']); ?></span>
                        <?php if ($episode_title) : ?>
                            · <span class="np-upcoming-episode"><em><?php echo esc_html($episode_title); ?></em></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

</article>

<?php
get_footer();
