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

// Get date from schedule or use today
$schedule_date = isset($schedule['date']) ? $schedule['date'] : date('l, M j');
$timezone = isset($schedule['timezone']) ? $schedule['timezone'] : 'America/New_York';

// Helper function to format duration
function format_duration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($hours > 0 && $mins > 0) {
        return sprintf('%d hour%s %d minute%s', $hours, $hours > 1 ? 's' : '', $mins, $mins > 1 ? 's' : '');
    } elseif ($hours > 0) {
        return sprintf('%d hour%s', $hours, $hours > 1 ? 's' : '');
    } else {
        return sprintf('%d minute%s', $mins, $mins > 1 ? 's' : '');
    }
}
?>

<article class="pbs-schedule-clean">
    <header class="pbs-schedule-clean-header">
        <h1>TV Schedule for <?php echo esc_html($schedule_date); ?></h1>
    </header>

    <?php if (isset($schedule['feeds']) && !empty($schedule['feeds'])) : ?>

        <?php foreach ($schedule['feeds'] as $feed) : ?>
            <?php if (!empty($feed['listings'])) : ?>

                <section class="pbs-schedule-feed-section">
                    <h2><?php echo esc_html($feed['full_name']); ?></h2>

                    <ul class="pbs-schedule-listings-clean">
                        <?php foreach ($feed['listings'] as $listing) : ?>
                            <li class="pbs-schedule-item-clean">
                                <div class="pbs-schedule-time-clean">
                                    <strong><?php echo esc_html(PBS_TVSS_API_Client::format_time($listing['start_time'], $timezone)); ?></strong>
                                </div>

                                <div class="pbs-schedule-show-info">
                                    <div class="pbs-schedule-show-title">
                                        <strong><?php echo esc_html($listing['title']); ?></strong>
                                    </div>

                                    <?php if (!empty($listing['episode_title'])) : ?>
                                        <div class="pbs-schedule-episode-title">
                                            <em><?php echo esc_html($listing['episode_title']); ?></em>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($listing['minutes'])) : ?>
                                        <div class="pbs-schedule-duration-clean">
                                            Duration: <?php echo esc_html(format_duration($listing['minutes'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($listing['description']) || !empty($listing['episode_description'])) : ?>
                                        <div class="pbs-schedule-description-clean">
                                            Description: <?php echo esc_html(!empty($listing['episode_description']) ? $listing['episode_description'] : $listing['description']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($listing['show_url'])) : ?>
                                        <div class="pbs-schedule-link-clean">
                                            <a href="<?php echo esc_url($listing['show_url']); ?>">View show page</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

            <?php endif; ?>
        <?php endforeach; ?>

    <?php else : ?>
        <p>No schedule data available.</p>
    <?php endif; ?>
</article>
