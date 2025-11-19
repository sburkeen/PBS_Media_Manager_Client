# PBS TV Schedule Viewer - WordPress Plugin

A comprehensive WordPress plugin that integrates the PBS TV Schedules API with the PBS Media Manager API to create an interactive TV schedule viewer with on-demand content linking.

## Features

### Core Functionality
- **Dual API Integration**: Seamlessly combines PBS TV Schedules API (TVSS) with PBS Media Manager API
- **Smart Content Matching**: Automatically links TV schedule items to available on-demand episodes
- **Multiple View Options**: Grid and List views for schedule display
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **Real-time Updates**: AJAX-powered refresh with configurable intervals
- **Intelligent Caching**: Built-in caching system to optimize performance and reduce API calls

### Admin Features
- **User-Friendly Settings Panel**: WordPress-native interface with intuitive tabs
- **Station Picker**: Search and select PBS stations with autocomplete
- **Feed Management**: Choose which channels/feeds to display
- **API Credential Management**: Secure storage of API keys and credentials
- **Content Synchronization**: Sync shows from Media Manager to WordPress
- **Cache Management**: View statistics and clear cache with one click
- **API Testing**: Built-in connection testing for both APIs

### Frontend Features
- **TV Schedule Grid**: Traditional TV guide layout with multiple channels
- **TV Schedule List**: Chronological list view of all programming
- **Show Detail Pages**: Dedicated pages for each show with all available episodes
- **On-Demand Integration**: "Watch On Demand" buttons for available content
- **Image Support**: Show thumbnails from Gracenote
- **Metadata Display**: HD, CC, duration, and availability badges
- **Current Show Highlighting**: Automatically highlights what's currently airing

## Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher
- PBS TV Schedules API key (X-PBSAUTH)
- PBS Media Manager API credentials (Client ID and Secret)

### Setup Instructions

1. **Upload the Plugin**
   ```
   Upload the entire plugin folder to /wp-content/plugins/
   Or install via WordPress admin: Plugins > Add New > Upload Plugin
   ```

2. **Activate the Plugin**
   ```
   Navigate to Plugins in WordPress admin
   Click "Activate" next to PBS TV Schedule Viewer
   ```

3. **Configure API Credentials**
   ```
   Go to PBS Schedule > Settings > API Credentials tab
   Enter your TV Schedules API Key
   Enter your Media Manager Client ID and Secret
   Select endpoint (Production or Staging)
   Click "Save Changes"
   ```

4. **Select Your Station**
   ```
   Go to PBS Schedule > Settings > Station Configuration tab
   Use the search box to find your PBS station
   Select your station from the results
   Choose which feeds/channels to display
   Click "Save Changes"
   ```

5. **Customize Display Options** (Optional)
   ```
   Go to PBS Schedule > Settings > Display Options tab
   Adjust time range, refresh interval, and view preferences
   Add custom CSS if desired
   Click "Save Changes"
   ```

6. **Test Configuration**
   ```
   Go to PBS Schedule > Dashboard
   Click "Test API Connection" to verify setup
   ```

## Usage

### Shortcodes

#### Basic Schedule Display
```
[pbs_schedule]
```
Displays the TV schedule with default settings from admin panel.

#### Grid View with Custom Hours
```
[pbs_schedule view="grid" hours="6"]
```
Shows a grid-style TV guide for the next 6 hours.

#### List View
```
[pbs_schedule view="list" hours="12"]
```
Shows a chronological list for the next 12 hours.

#### Specific Feed/Channel
```
[pbs_schedule feed="WETADT"]
```
Display schedule for a specific feed using its CID.

#### Custom Date
```
[pbs_schedule date="20250120"]
```
Show schedule for a specific date (YYYYMMDD format).

#### All Parameters
```
[pbs_schedule view="grid" hours="8" feed="WETADT" images="yes" date="20250120"]
```

### Shortcode Parameters

| Parameter | Values | Default | Description |
|-----------|--------|---------|-------------|
| `view` | `grid`, `list` | From settings | Display mode |
| `hours` | 1-24 | From settings | Hours to display |
| `feed` | Feed CID | All selected | Specific channel |
| `images` | `yes`, `no` | From settings | Show thumbnails |
| `date` | YYYYMMDD | Today | Specific date |

### Show Shortcode
```
[pbs_show id="123"]
```
Display a specific PBS show with all available episodes.

Parameters:
- `id` - WordPress post ID
- `slug` - Post slug
- `show_id` - PBS Media Manager show ID
- `episodes` - `yes`/`no` to show episodes
- `limit` - Number of episodes to display

## Architecture

### Plugin Structure
```
pbs-tv-schedule-viewer/
├── pbs-tv-schedule-viewer.php (Main plugin file)
├── includes/
│   ├── class-pbs-tvss-api-client.php (TV Schedules API)
│   ├── class-pbs-media-manager-api-client.php (Media Manager API)
│   ├── class-pbs-content-matcher.php (Matching logic)
│   └── class-pbs-cache-manager.php (Caching)
├── admin/
│   ├── class-pbs-admin.php (Admin controller)
│   ├── class-pbs-admin-settings.php (Settings API)
│   ├── views/ (Admin templates)
│   ├── css/ (Admin styles)
│   └── js/ (Admin scripts)
└── public/
    ├── class-pbs-public.php (Frontend controller)
    ├── class-pbs-shortcodes.php (Shortcode handlers)
    ├── views/ (Frontend templates)
    ├── css/ (Public styles)
    └── js/ (Public scripts)
```

### Custom Post Type
**pbs_show** - Represents PBS shows with on-demand content
- Automatically created when matching schedule to on-demand
- Permalink structure: `/watch/shows/{show-slug}`
- Stores Media Manager show ID in post meta

### Content Matching

The plugin uses multiple strategies to match TV schedule items with on-demand content:

1. **NOLA Code Matching** (Most Reliable)
   - Uses PBS NOLA root and episode codes
   - Primary method for PBS-produced content

2. **TMS ID Matching**
   - Gracenote TMS identifiers
   - For content with Gracenote metadata

3. **Program ID Matching**
   - Internal TVSS program IDs
   - Configurable mappings

4. **Fuzzy Title Matching** (Fallback)
   - String similarity matching
   - Used when other methods fail

### Caching System

The plugin implements a two-tier caching strategy:

1. **Object Cache** - In-memory WordPress object cache
2. **Transient Cache** - Database-backed persistent cache

Cache categories:
- **Schedule Data**: 15 minutes default (configurable)
- **On-Demand Content**: 1 hour default (configurable)
- **Matched Results**: 1 hour

## API Integration

### TV Schedules Service (TVSS) API

**Base Endpoint**: `https://tvss.services.pbs.org/tvss/`

**Authentication**: X-PBSAUTH header with API key

**Key Endpoints Used**:
- `/[callsign]/today/` - Today's schedule
- `/[callsign]/day/[YYYYMMDD]/` - Schedule by date
- `/[callsign]/today/[feed-cid]/` - Feed-specific schedule
- `/stations/[station_id]/` - Station feed information

### PBS Media Manager API

**Base Endpoint**:
- Production: `https://media.services.pbs.org/api/v1`
- Staging: `https://media-staging.services.pbs.org/api/v1`

**Authentication**: Basic Auth with Client ID and Secret

**Key Endpoints Used**:
- `/shows` - Retrieve shows
- `/shows/[id]/seasons` - Show seasons
- `/seasons/[id]/episodes` - Season episodes
- `/episodes/[id]/assets` - Episode assets

### Station Finder API

**Base Endpoint**: `https://station.services.pbs.org/api/public/v1/`

**Used For**: Station search and information lookup

## Customization

### Custom CSS

Add custom styles via Settings > Display Options > Custom CSS or by enqueueing your own stylesheet:

```php
function my_custom_pbs_styles() {
    wp_enqueue_style('my-pbs-custom', get_stylesheet_directory_uri() . '/pbs-custom.css');
}
add_action('wp_enqueue_scripts', 'my_custom_pbs_styles', 20);
```

### Template Overrides

Copy templates from the plugin to your theme:

```
wp-content/themes/your-theme/pbs-schedule-viewer/
├── schedule-grid.php
├── schedule-list.php
└── single-pbs-show.php
```

### Hooks and Filters

**Actions**:
```php
// Before schedule is displayed
do_action('pbs_before_schedule', $schedule, $atts);

// After schedule is displayed
do_action('pbs_after_schedule', $schedule, $atts);
```

**Filters**:
```php
// Modify schedule data before display
apply_filters('pbs_schedule_data', $schedule, $atts);

// Modify matched content
apply_filters('pbs_matched_content', $match, $listing);
```

## Troubleshooting

### Common Issues

**Schedule Not Displaying**
- Verify API credentials in Settings
- Test API connection via Dashboard
- Check cache settings and clear cache
- Verify station callsign is correct

**On-Demand Links Not Appearing**
- Ensure Media Manager API is configured
- Check that "Link to On-Demand" is enabled in Display Options
- Verify content exists in Media Manager
- Review matching logic in admin logs

**Images Not Loading**
- Ensure "Show Images" is enabled
- Check that `fetch-images` parameter is working
- Verify Gracenote has images for the content

**Performance Issues**
- Enable caching in Cache Settings
- Increase cache duration
- Reduce refresh interval
- Limit display hours

### Debug Mode

Enable WordPress debug mode to see detailed errors:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Changelog

### Version 1.0.0 (2025-01-18)
- Initial release
- TV Schedules API integration
- Media Manager API integration
- Content matching system
- Grid and list views
- Admin settings panel
- Caching system
- Show detail pages
- Shortcode support

## Support

For support with this plugin:
1. Check the documentation above
2. Review PBS API documentation
3. Contact PBS Digital Support for API-related issues
4. Submit issues to the repository

## Credits

**Authors**:
- William Tam (WNET/IEG)
- Augustus Mayo (TPT)
- Aaron Crosman (Cyberwoven)
- Jess Snyder (WETA)

**APIs**:
- PBS TV Schedules Service (TVSS)
- PBS Media Manager
- PBS Station Finder

**License**: GPL v2 or later

## License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
