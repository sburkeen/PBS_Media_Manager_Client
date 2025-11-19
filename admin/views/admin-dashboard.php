<?php
/**
 * Admin Dashboard
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$station_name = get_option('pbs_schedule_station_name', '');
$station_callsign = get_option('pbs_schedule_station_callsign', '');
?>

<div class="wrap">
    <h1>PBS Schedule Viewer Dashboard</h1>

    <?php if (empty($station_callsign)) : ?>
        <div class="notice notice-warning">
            <p><strong>Setup Required:</strong> Please <a href="<?php echo admin_url('admin.php?page=pbs-schedule-settings'); ?>">configure your settings</a> to get started.</p>
        </div>
    <?php endif; ?>

    <div class="pbs-dashboard-grid">
        <!-- Status Card -->
        <div class="pbs-dashboard-card">
            <h2>Configuration Status</h2>
            <div class="pbs-status-list">
                <?php
                $tvss_key = get_option('pbs_schedule_tvss_api_key', '');
                $mm_id = get_option('pbs_schedule_mm_client_id', '');
                $mm_secret = get_option('pbs_schedule_mm_client_secret', '');
                ?>
                <div class="pbs-status-item">
                    <span class="dashicons dashicons-<?php echo !empty($tvss_key) ? 'yes-alt' : 'warning'; ?>"></span>
                    TV Schedules API: <?php echo !empty($tvss_key) ? '<strong>Configured</strong>' : '<em>Not configured</em>'; ?>
                </div>
                <div class="pbs-status-item">
                    <span class="dashicons dashicons-<?php echo (!empty($mm_id) && !empty($mm_secret)) ? 'yes-alt' : 'warning'; ?>"></span>
                    Media Manager API: <?php echo (!empty($mm_id) && !empty($mm_secret)) ? '<strong>Configured</strong>' : '<em>Not configured</em>'; ?>
                </div>
                <div class="pbs-status-item">
                    <span class="dashicons dashicons-<?php echo !empty($station_callsign) ? 'yes-alt' : 'warning'; ?>"></span>
                    Station: <?php echo !empty($station_name) ? '<strong>' . esc_html($station_name) . '</strong>' : '<em>Not selected</em>'; ?>
                </div>
            </div>
            <p>
                <button type="button" class="button button-secondary" id="pbs-test-connection">Test API Connection</button>
            </p>
            <div id="pbs-test-results" style="margin-top: 10px;"></div>
        </div>

        <!-- Quick Stats Card -->
        <div class="pbs-dashboard-card">
            <h2>Content Statistics</h2>
            <?php
            $show_count = wp_count_posts('pbs_show');
            $cache_stats = PBS_Cache_Manager::get_stats();
            ?>
            <div class="pbs-stats-list">
                <div class="pbs-stat-item">
                    <span class="pbs-stat-number"><?php echo esc_html($show_count->publish); ?></span>
                    <span class="pbs-stat-label">PBS Shows</span>
                </div>
                <div class="pbs-stat-item">
                    <span class="pbs-stat-number"><?php echo esc_html($cache_stats['total_items']); ?></span>
                    <span class="pbs-stat-label">Cached Items</span>
                </div>
            </div>
            <p>
                <button type="button" class="button button-secondary" id="pbs-sync-shows">Sync Shows from Media Manager</button>
            </p>
            <div id="pbs-sync-results" style="margin-top: 10px;"></div>
        </div>

        <!-- Shortcode Reference Card -->
        <div class="pbs-dashboard-card pbs-dashboard-card-full">
            <h2>Shortcode Reference</h2>
            <p>Use these shortcodes to display the PBS TV schedule on your website:</p>

            <h3>Basic Usage</h3>
            <code>[pbs_schedule]</code>
            <p class="description">Displays the TV schedule with default settings</p>

            <h3>Grid View</h3>
            <code>[pbs_schedule view="grid" hours="6"]</code>
            <p class="description">Shows a grid-style TV guide for the next 6 hours</p>

            <h3>List View</h3>
            <code>[pbs_schedule view="list" hours="12"]</code>
            <p class="description">Shows a list-style schedule for the next 12 hours</p>

            <h3>Specific Feed</h3>
            <code>[pbs_schedule feed="WETADT"]</code>
            <p class="description">Display schedule for a specific feed/channel</p>

            <h3>Available Parameters</h3>
            <ul>
                <li><code>view</code> - "grid" or "list" (default: from settings)</li>
                <li><code>hours</code> - Number of hours to display (1-24, default: from settings)</li>
                <li><code>feed</code> - Specific feed CID to display (default: all selected feeds)</li>
                <li><code>images</code> - "yes" or "no" to show/hide images (default: from settings)</li>
                <li><code>date</code> - Specific date in YYYYMMDD format (default: today)</li>
            </ul>
        </div>
    </div>
</div>

<style>
.pbs-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.pbs-dashboard-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.pbs-dashboard-card-full {
    grid-column: 1 / -1;
}

.pbs-dashboard-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
}

.pbs-status-item,
.pbs-stat-item {
    padding: 8px 0;
    display: flex;
    align-items: center;
}

.pbs-status-item .dashicons {
    margin-right: 8px;
}

.pbs-status-item .dashicons-yes-alt {
    color: #46b450;
}

.pbs-status-item .dashicons-warning {
    color: #ffb900;
}

.pbs-stat-item {
    flex-direction: column;
    align-items: flex-start;
}

.pbs-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}

.pbs-stat-label {
    font-size: 14px;
    color: #646970;
}

.pbs-dashboard-card code {
    display: block;
    background: #f6f7f7;
    padding: 8px 12px;
    margin: 10px 0;
    border-left: 3px solid #2271b1;
}

.pbs-dashboard-card h3 {
    margin-top: 20px;
    margin-bottom: 5px;
}
</style>
