<?php
/**
 * Show Detail View (for shortcode)
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 *
 * Available variables:
 * - $show: WordPress post object
 * - $show_episodes: Array of episodes from Media Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="pbs-show-detail">
    <?php if (has_post_thumbnail($show->ID)) : ?>
        <div class="pbs-show-detail-hero">
            <?php echo get_the_post_thumbnail($show->ID, 'large', array('class' => 'pbs-show-detail-hero-image')); ?>
        </div>
    <?php endif; ?>

    <div class="pbs-show-detail-header">
        <h2 class="pbs-show-detail-title"><?php echo esc_html($show->post_title); ?></h2>

        <?php if ($show->post_excerpt) : ?>
            <div class="pbs-show-detail-excerpt">
                <?php echo wp_kses_post($show->post_excerpt); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($show->post_content) : ?>
        <div class="pbs-show-detail-content">
            <?php echo wp_kses_post($show->post_content); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($show_episodes)) : ?>
        <div class="pbs-show-detail-episodes">
            <h3 class="pbs-show-episodes-heading">Available Episodes</h3>

            <div class="pbs-episodes-list">
                <?php foreach ($show_episodes as $episode) : ?>
                    <?php
                    $episode_title = isset($episode['attributes']['title']) ? $episode['attributes']['title'] : 'Untitled Episode';
                    $episode_description = isset($episode['attributes']['description_short']) ? $episode['attributes']['description_short'] : '';
                    ?>

                    <div class="pbs-episode-item">
                        <h4 class="pbs-episode-item-title"><?php echo esc_html($episode_title); ?></h4>

                        <?php if ($episode_description) : ?>
                            <p class="pbs-episode-item-description">
                                <?php echo esc_html($episode_description); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
