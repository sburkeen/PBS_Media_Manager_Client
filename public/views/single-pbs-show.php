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

$episodes = array();
$show_data = null;

if (!empty($mm_id) && !empty($mm_secret) && !empty($show_id)) {
    $mm_client = new PBS_Media_Manager_API_Client($mm_id, $mm_secret, $mm_endpoint);
    $matcher = new PBS_Content_Matcher($mm_client);

    // Get show data
    $show_data = $mm_client->get_show($show_id);

    // Get all episodes
    $episodes = $matcher->get_all_show_episodes($show_id);
}
?>

<div class="pbs-show-single">
    <article id="post-<?php the_ID(); ?>" <?php post_class('pbs-show-article'); ?>>

        <?php if (has_post_thumbnail()) : ?>
            <div class="pbs-show-hero">
                <?php the_post_thumbnail('large', array('class' => 'pbs-show-hero-image')); ?>
            </div>
        <?php endif; ?>

        <div class="pbs-show-header">
            <h1 class="pbs-show-title"><?php the_title(); ?></h1>

            <?php if (has_excerpt()) : ?>
                <div class="pbs-show-excerpt">
                    <?php the_excerpt(); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="pbs-show-content">
            <?php the_content(); ?>
        </div>

        <?php if (!empty($episodes)) : ?>
            <div class="pbs-show-episodes">
                <h2 class="pbs-show-episodes-title">Available Episodes</h2>

                <div class="pbs-episodes-grid">
                    <?php foreach ($episodes as $episode) : ?>
                        <?php
                        $episode_title = isset($episode['attributes']['title']) ? $episode['attributes']['title'] : '';
                        $episode_description = isset($episode['attributes']['description_short']) ? $episode['attributes']['description_short'] : '';
                        $episode_id = $episode['id'];

                        // Get episode assets
                        $assets = array();
                        if ($mm_client) {
                            $assets = $mm_client->get_episode_assets($episode_id, 'full_length', 'all_members');
                        }

                        // Get episode image
                        $episode_images = array();
                        if ($mm_client && method_exists($mm_client, 'get_episode_images')) {
                            $episode_images = $mm_client->get_episode_images($episode_id);
                        }

                        $image_url = '';
                        if (!empty($episode_images) && is_array($episode_images)) {
                            foreach ($episode_images as $image) {
                                if (isset($image['image'])) {
                                    $image_url = $image['image'];
                                    break;
                                }
                            }
                        }
                        ?>

                        <div class="pbs-episode-card" data-episode-id="<?php echo esc_attr($episode_id); ?>">
                            <?php if ($image_url) : ?>
                                <div class="pbs-episode-image">
                                    <img src="<?php echo esc_url($image_url); ?>"
                                         alt="<?php echo esc_attr($episode_title); ?>"
                                         loading="lazy">
                                </div>
                            <?php endif; ?>

                            <div class="pbs-episode-content">
                                <h3 class="pbs-episode-title"><?php echo esc_html($episode_title); ?></h3>

                                <?php if ($episode_description) : ?>
                                    <div class="pbs-episode-description">
                                        <?php echo esc_html($episode_description); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($assets) && is_array($assets)) : ?>
                                    <div class="pbs-episode-meta">
                                        <span class="pbs-badge pbs-badge-available">Available</span>

                                        <?php
                                        // Get first asset for duration info
                                        $asset = $assets[0];
                                        if (isset($asset['attributes']['duration'])) {
                                            $duration_seconds = $asset['attributes']['duration'];
                                            $duration_minutes = round($duration_seconds / 60);
                                            ?>
                                            <span class="pbs-episode-duration"><?php echo esc_html($duration_minutes); ?> min</span>
                                        <?php } ?>
                                    </div>

                                    <div class="pbs-episode-actions">
                                        <?php
                                        // Check if we have a player URL
                                        if (isset($asset['attributes']['player_url'])) {
                                            ?>
                                            <a href="<?php echo esc_url($asset['attributes']['player_url']); ?>"
                                               class="pbs-button pbs-button-primary"
                                               target="_blank">
                                                Watch Now
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php else : ?>
                                    <div class="pbs-episode-meta">
                                        <span class="pbs-badge pbs-badge-unavailable">Not Available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </article>
</div>

<?php
get_footer();
