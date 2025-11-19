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
$schedule_date = isset($schedule['date_formatted']) ? $schedule['date_formatted'] : date('l, M j');
$schedule_date_raw = isset($schedule['date_raw']) ? $schedule['date_raw'] : date('Ymd');
$current_period = isset($schedule['time_period']) ? $schedule['time_period'] : '';
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

// Define time periods for filtering
$time_periods = array(
    '' => 'All Day',
    'early_morning' => 'Early Morning',
    'morning' => 'Morning',
    'afternoon' => 'Afternoon',
    'evening' => 'Evening'
);
?>

<article class="pbs-schedule-clean">
    <header class="pbs-schedule-clean-header">
        <h1>TV Schedule for <?php echo esc_html($schedule_date); ?></h1>

        <nav class="pbs-time-period-nav">
            <?php foreach ($time_periods as $period_key => $period_label) : ?>
                <?php
                $is_active = ($current_period === $period_key);
                $class = $is_active ? 'pbs-time-period-link active' : 'pbs-time-period-link';

                // Build URL with time_period parameter
                $current_url = add_query_arg(array());
                if (!empty($period_key)) {
                    $link_url = add_query_arg('time_period', $period_key, $current_url);
                } else {
                    $link_url = remove_query_arg('time_period', $current_url);
                }
                ?>
                <a href="<?php echo esc_url($link_url); ?>" class="<?php echo esc_attr($class); ?>">
                    <?php echo esc_html($period_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="pbs-time-period-info">
            <?php
            switch ($current_period) {
                case 'early_morning':
                    echo 'Showing programs from 12:00 AM - 6:29 AM';
                    break;
                case 'morning':
                    echo 'Showing programs from 7:00 AM - 11:29 AM';
                    break;
                case 'afternoon':
                    echo 'Showing programs from 12:00 PM - 6:29 PM';
                    break;
                case 'evening':
                    echo 'Showing programs from 6:30 PM - 11:59 PM';
                    break;
                default:
                    echo 'Showing all programs for this day';
            }
            ?>
        </p>
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
