<?php
/**
 * Admin Settings Page
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'api';
?>

<div class="wrap">
    <h1>PBS Schedule Viewer Settings</h1>

    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=pbs-schedule-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
            API Credentials
        </a>
        <a href="?page=pbs-schedule-settings&tab=station" class="nav-tab <?php echo $active_tab === 'station' ? 'nav-tab-active' : ''; ?>">
            Station Configuration
        </a>
        <a href="?page=pbs-schedule-settings&tab=display" class="nav-tab <?php echo $active_tab === 'display' ? 'nav-tab-active' : ''; ?>">
            Display Options
        </a>
        <a href="?page=pbs-schedule-settings&tab=cache" class="nav-tab <?php echo $active_tab === 'cache' ? 'nav-tab-active' : ''; ?>">
            Cache Settings
        </a>
    </h2>

    <form method="post" action="options.php">
        <?php
        switch ($active_tab) {
            case 'api':
                settings_fields('pbs_schedule_api');
                do_settings_sections('pbs_schedule_api');
                break;

            case 'station':
                settings_fields('pbs_schedule_station');
                do_settings_sections('pbs_schedule_station');
                break;

            case 'display':
                settings_fields('pbs_schedule_display');
                do_settings_sections('pbs_schedule_display');
                break;

            case 'cache':
                settings_fields('pbs_schedule_cache');
                do_settings_sections('pbs_schedule_cache');
                break;
        }

        submit_button();
        ?>
    </form>
</div>

<style>
.pbs-station-results {
    margin-top: 10px;
    border: 1px solid #ddd;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

.pbs-station-result-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.pbs-station-result-item:hover {
    background: #f0f0f1;
}

.pbs-station-result-item:last-child {
    border-bottom: none;
}

.pbs-station-result-callsign {
    font-weight: bold;
    color: #2271b1;
}

.pbs-station-result-name {
    display: block;
    color: #646970;
    font-size: 13px;
}

#pbs-feed-selector .pbs-feed-item {
    padding: 8px;
    border: 1px solid #ddd;
    margin: 5px 0;
    background: #f6f7f7;
}

#pbs-feed-selector .pbs-feed-item label {
    display: flex;
    align-items: center;
}

#pbs-feed-selector .pbs-feed-item input[type="checkbox"] {
    margin-right: 8px;
}

.pbs-cache-stats {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
}

.pbs-cache-stats p {
    margin: 5px 0;
}
</style>
