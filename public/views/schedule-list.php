<?php
/**
 * Schedule List View Template
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 *
 * Available variables:
 * - $schedule: Schedule data from API
 * - $atts: Shortcode attributes
 * - $show_images: Boolean for showing images
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$show_images = isset($show_images) ? $show_images : true;

// Combine all listings from all feeds and sort by time
$all_listings = array();
if (isset($schedule['feeds']) && !empty($schedule['feeds'])) {
    foreach ($schedule['feeds'] as $feed) {
        if (!empty($feed['listings'])) {
            foreach ($feed['listings'] as $listing) {
                $listing['feed_name'] = $feed['full_name'];
                $listing['feed_cid'] = $feed['cid'];
                $all_listings[] = $listing;
            }
        }
    }

    // Sort by start time
    usort($all_listings, function($a, $b) {
        return strcmp($a['start_time'], $b['start_time']);
    });
}
?>

<div class="pbs-schedule-container pbs-schedule-list-view" data-view="list">
    <div class="pbs-schedule-header">
        <h2 class="pbs-schedule-title">PBS TV Schedule</h2>
        <div class="pbs-schedule-controls">
            <button class="pbs-schedule-view-toggle" data-toggle-view="grid">
                <span class="dashicons dashicons-grid-view"></span> Grid View
            </button>
            <button class="pbs-schedule-refresh">
                <span class="dashicons dashicons-update"></span> Refresh
            </button>
        </div>
    </div>

    <?php if (!empty($all_listings)) : ?>

        <div class="pbs-schedule-list">
            <?php
            $timezone = isset($schedule['timezone']) ? $schedule['timezone'] : 'America/New_York';
            $current_time_slot = null;

            foreach ($all_listings as $listing) :
                $time_slot = substr($listing['start_time'], 0, 2) . ':00';

                // Add time divider if we're in a new hour
                if ($time_slot !== $current_time_slot) :
                    $current_time_slot = $time_slot;
            ?>
                    <div class="pbs-schedule-time-divider">
                        <span class="pbs-schedule-divider-time"><?php echo esc_html(PBS_TVSS_API_Client::format_time($listing['start_time'], $timezone)); ?></span>
                    </div>
            <?php endif; ?>

                <div class="pbs-schedule-list-item <?php echo isset($listing['has_on_demand']) && $listing['has_on_demand'] ? 'has-on-demand' : ''; ?>"
                     data-show-id="<?php echo esc_attr($listing['show_id']); ?>"
                     data-start-time="<?php echo esc_attr($listing['start_time']); ?>"
                     data-feed-cid="<?php echo esc_attr($listing['feed_cid']); ?>">

                    <?php if ($show_images && !empty($listing['image']) && is_array($listing['image'])) : ?>
                        <div class="pbs-schedule-list-item-image">
                            <?php if (isset($listing['show_url'])) : ?>
                                <a href="<?php echo esc_url($listing['show_url']); ?>">
                                    <img src="<?php echo esc_url($listing['image'][0]['image']); ?>"
                                         alt="<?php echo esc_attr($listing['title']); ?>"
                                         loading="lazy">
                                </a>
                            <?php else : ?>
                                <img src="<?php echo esc_url($listing['image'][0]['image']); ?>"
                                     alt="<?php echo esc_attr($listing['title']); ?>"
                                     loading="lazy">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pbs-schedule-list-item-content">
                        <div class="pbs-schedule-list-item-header">
                            <div class="pbs-schedule-list-item-time">
                                <?php
                                $start_time = PBS_TVSS_API_Client::format_time($listing['start_time'], $timezone);
                                $end_time_hhmm = PBS_TVSS_API_Client::calculate_end_time($listing['start_time'], $listing['minutes']);
                                $end_time = PBS_TVSS_API_Client::format_time($end_time_hhmm, $timezone);
                                ?>
                                <span class="pbs-schedule-start-time"><?php echo esc_html($start_time); ?></span>
                                <span class="pbs-schedule-time-separator">-</span>
                                <span class="pbs-schedule-end-time"><?php echo esc_html($end_time); ?></span>
                            </div>

                            <div class="pbs-schedule-list-item-channel">
                                <span class="pbs-schedule-channel-badge"><?php echo esc_html($listing['feed_name']); ?></span>
                            </div>
                        </div>

                        <h4 class="pbs-schedule-list-item-title">
                            <?php if (isset($listing['show_url'])) : ?>
                                <a href="<?php echo esc_url($listing['show_url']); ?>">
                                    <?php echo esc_html($listing['title']); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html($listing['title']); ?>
                            <?php endif; ?>
                        </h4>

                        <?php if (!empty($listing['episode_title'])) : ?>
                            <div class="pbs-schedule-list-item-episode-title">
                                <?php echo esc_html($listing['episode_title']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($listing['description']) || !empty($listing['episode_description'])) : ?>
                            <div class="pbs-schedule-list-item-description">
                                <?php echo esc_html(!empty($listing['episode_description']) ? $listing['episode_description'] : $listing['description']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="pbs-schedule-list-item-meta">
                            <?php if (!empty($listing['minutes'])) : ?>
                                <span class="pbs-schedule-duration"><?php echo esc_html($listing['minutes']); ?> min</span>
                            <?php endif; ?>

                            <?php if (isset($listing['broadcast_hd']) && $listing['broadcast_hd']) : ?>
                                <span class="pbs-schedule-badge pbs-badge-hd">HD</span>
                            <?php endif; ?>

                            <?php if (isset($listing['closed_captions_available']) && $listing['closed_captions_available']) : ?>
                                <span class="pbs-schedule-badge pbs-badge-cc">CC</span>
                            <?php endif; ?>

                            <?php if (isset($listing['has_on_demand']) && $listing['has_on_demand']) : ?>
                                <span class="pbs-schedule-badge pbs-badge-ondemand">On Demand</span>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($listing['has_on_demand']) && $listing['has_on_demand'] && isset($listing['show_url'])) : ?>
                            <div class="pbs-schedule-list-item-actions">
                                <a href="<?php echo esc_url($listing['show_url']); ?>" class="pbs-button pbs-button-ondemand">
                                    Watch On Demand
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

    <?php else : ?>
        <div class="pbs-schedule-empty">
            <p>No schedule data available.</p>
        </div>
    <?php endif; ?>

    <div class="pbs-schedule-footer">
        <p class="pbs-schedule-updated">
            Last updated: <span class="pbs-schedule-timestamp"><?php echo date('g:i A'); ?></span>
        </p>
    </div>
</div>
