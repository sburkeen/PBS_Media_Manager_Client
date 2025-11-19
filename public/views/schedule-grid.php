<?php
/**
 * Schedule Grid View Template
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
?>

<div class="pbs-schedule-container pbs-schedule-grid-view" data-view="grid">
    <div class="pbs-schedule-header">
        <h2 class="pbs-schedule-title">PBS TV Schedule</h2>
        <div class="pbs-schedule-controls">
            <button class="pbs-schedule-view-toggle" data-toggle-view="list">
                <span class="dashicons dashicons-list-view"></span> List View
            </button>
            <button class="pbs-schedule-refresh">
                <span class="dashicons dashicons-update"></span> Refresh
            </button>
        </div>
    </div>

    <?php if (isset($schedule['feeds']) && !empty($schedule['feeds'])) : ?>

        <div class="pbs-schedule-grid">
            <?php foreach ($schedule['feeds'] as $feed) : ?>

                <div class="pbs-schedule-feed" data-feed-cid="<?php echo esc_attr($feed['cid']); ?>">
                    <div class="pbs-schedule-feed-header">
                        <h3 class="pbs-schedule-feed-name"><?php echo esc_html($feed['full_name']); ?></h3>
                        <?php if (!empty($feed['short_name'])) : ?>
                            <span class="pbs-schedule-feed-short-name"><?php echo esc_html($feed['short_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="pbs-schedule-listings">
                        <?php if (!empty($feed['listings'])) : ?>
                            <?php foreach ($feed['listings'] as $listing) : ?>

                                <div class="pbs-schedule-item <?php echo isset($listing['has_on_demand']) && $listing['has_on_demand'] ? 'has-on-demand' : ''; ?>"
                                     data-show-id="<?php echo esc_attr($listing['show_id']); ?>"
                                     data-start-time="<?php echo esc_attr($listing['start_time']); ?>">

                                    <?php if ($show_images && !empty($listing['image']) && is_array($listing['image'])) : ?>
                                        <div class="pbs-schedule-item-image">
                                            <img src="<?php echo esc_url($listing['image'][0]['image']); ?>"
                                                 alt="<?php echo esc_attr($listing['title']); ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>

                                    <div class="pbs-schedule-item-content">
                                        <div class="pbs-schedule-item-time">
                                            <?php
                                            $timezone = isset($schedule['timezone']) ? $schedule['timezone'] : 'America/New_York';
                                            $start_time = PBS_TVSS_API_Client::format_time($listing['start_time'], $timezone);
                                            $end_time_hhmm = PBS_TVSS_API_Client::calculate_end_time($listing['start_time'], $listing['minutes']);
                                            $end_time = PBS_TVSS_API_Client::format_time($end_time_hhmm, $timezone);
                                            ?>
                                            <span class="pbs-schedule-start-time"><?php echo esc_html($start_time); ?></span>
                                            <span class="pbs-schedule-time-separator">-</span>
                                            <span class="pbs-schedule-end-time"><?php echo esc_html($end_time); ?></span>
                                        </div>

                                        <h4 class="pbs-schedule-item-title">
                                            <?php if (isset($listing['show_url'])) : ?>
                                                <a href="<?php echo esc_url($listing['show_url']); ?>">
                                                    <?php echo esc_html($listing['title']); ?>
                                                </a>
                                            <?php else : ?>
                                                <?php echo esc_html($listing['title']); ?>
                                            <?php endif; ?>
                                        </h4>

                                        <?php if (!empty($listing['episode_title'])) : ?>
                                            <div class="pbs-schedule-item-episode-title">
                                                <?php echo esc_html($listing['episode_title']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($listing['description']) || !empty($listing['episode_description'])) : ?>
                                            <div class="pbs-schedule-item-description">
                                                <?php echo esc_html(!empty($listing['episode_description']) ? $listing['episode_description'] : $listing['description']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="pbs-schedule-item-meta">
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
                                            <div class="pbs-schedule-item-actions">
                                                <a href="<?php echo esc_url($listing['show_url']); ?>" class="pbs-button pbs-button-ondemand">
                                                    Watch On Demand
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="pbs-schedule-empty">No listings available for this feed.</div>
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
