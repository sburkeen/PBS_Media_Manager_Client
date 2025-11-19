/**
 * PBS Schedule Viewer - Admin JavaScript
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Station search autocomplete
        var searchTimer;
        $('#pbs-station-search').on('input', function() {
            var query = $(this).val();

            clearTimeout(searchTimer);

            if (query.length < 2) {
                $('#pbs-station-results').hide().empty();
                return;
            }

            searchTimer = setTimeout(function() {
                searchStations(query);
            }, 300);
        });

        function searchStations(query) {
            console.log('Searching for stations with query:', query);

            $.ajax({
                url: pbsScheduleAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pbs_search_stations',
                    nonce: pbsScheduleAdmin.nonce,
                    query: query
                },
                success: function(response) {
                    console.log('Station search response:', response);
                    if (response.success && response.data.results) {
                        displayStationResults(response.data.results);
                    } else if (response.success) {
                        console.warn('Search succeeded but no results array found:', response.data);
                        $('#pbs-station-results').html('<div style="padding: 10px;">No stations found</div>').show();
                    } else {
                        console.error('Search failed:', response.data);
                        $('#pbs-station-results').html('<div style="padding: 10px; color: #dc3232;">' + (response.data || 'Error searching stations') + '</div>').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Station search AJAX error:', status, error, xhr.responseText);
                    var errorMsg = 'Error searching stations';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg += ': ' + xhr.responseJSON.data;
                    }
                    $('#pbs-station-results').html('<div style="padding: 10px; color: #dc3232;">' + errorMsg + '</div>').show();
                }
            });
        }

        function displayStationResults(results) {
            var $container = $('#pbs-station-results');
            $container.empty();

            if (results.length === 0) {
                $container.html('<div style="padding: 10px;">No stations found</div>').show();
                return;
            }

            results.forEach(function(station) {
                var $item = $('<div class="pbs-station-result-item"></div>');
                $item.html(
                    '<span class="pbs-station-result-callsign">' + station.call_sign + '</span>' +
                    '<span class="pbs-station-result-name">' + station.common_name + '</span>'
                );

                $item.on('click', function() {
                    selectStation(station);
                });

                $container.append($item);
            });

            $container.show();
        }

        function selectStation(station) {
            $('#pbs-station-id').val(station.id);
            $('#pbs-station-name').val(station.common_name);
            $('#pbs_schedule_station_callsign').val(station.call_sign);
            $('#pbs-station-search').val(station.call_sign + ' - ' + station.common_name);
            $('#pbs-station-results').hide();

            // Load feeds for this station
            loadStationFeeds(station.call_sign);
        }

        function loadStationFeeds(callsign) {
            var $feedSelector = $('#pbs-feed-selector');
            $feedSelector.html('<p>Loading feeds...</p>');

            $.ajax({
                url: pbsScheduleAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pbs_get_station_feeds',
                    nonce: pbsScheduleAdmin.nonce,
                    callsign: callsign
                },
                success: function(response) {
                    if (response.success && response.data.feeds) {
                        displayFeedSelector(response.data.feeds);
                    } else {
                        var errorMsg = response.data || 'No feeds available for this station.';
                        $feedSelector.html('<p class="description">' + errorMsg + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'Error loading feeds';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg += ': ' + xhr.responseJSON.data;
                    }
                    $feedSelector.html('<p style="color: #dc3232;">' + errorMsg + '</p>');
                }
            });
        }

        function displayFeedSelector(feeds) {
            var $container = $('#pbs-feed-selector');
            var selectedFeeds = [];

            try {
                selectedFeeds = JSON.parse($('#pbs-selected-feeds').val() || '[]');
            } catch (e) {
                selectedFeeds = [];
            }

            $container.empty();

            if (feeds.length === 0) {
                $container.html('<p class="description">No feeds available.</p>');
                return;
            }

            feeds.forEach(function(feed) {
                var isChecked = selectedFeeds.includes(feed.external_id);
                var $item = $('<div class="pbs-feed-item"></div>');
                $item.html(
                    '<label>' +
                    '<input type="checkbox" class="pbs-feed-checkbox" value="' + feed.external_id + '"' +
                    (isChecked ? ' checked' : '') + '>' +
                    '<strong>' + feed.full_name + '</strong> (' + feed.short_name + ')' +
                    '</label>'
                );
                $container.append($item);
            });

            // Update hidden field when checkboxes change
            $container.on('change', '.pbs-feed-checkbox', function() {
                var selected = [];
                $('.pbs-feed-checkbox:checked').each(function() {
                    selected.push($(this).val());
                });
                $('#pbs-selected-feeds').val(JSON.stringify(selected));
            });
        }

        // Test API connection
        $('#pbs-test-connection').on('click', function() {
            var $button = $(this);
            var $results = $('#pbs-test-results');

            $button.prop('disabled', true).text('Testing...');
            $results.html('<p>Testing API connections...</p>');

            $.ajax({
                url: pbsScheduleAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pbs_test_api_connection',
                    nonce: pbsScheduleAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var html = '<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">';

                        if (response.data.tvss) {
                            var tvssClass = response.data.tvss.success ? 'yes-alt' : 'warning';
                            html += '<p><span class="dashicons dashicons-' + tvssClass + '"></span> <strong>TV Schedules API:</strong> ' + response.data.tvss.message + '</p>';
                        }

                        if (response.data.media_manager) {
                            var mmClass = response.data.media_manager.success ? 'yes-alt' : 'warning';
                            html += '<p><span class="dashicons dashicons-' + mmClass + '"></span> <strong>Media Manager API:</strong> ' + response.data.media_manager.message + '</p>';
                        }

                        html += '</div>';
                        $results.html(html);
                    } else {
                        $results.html('<p style="color: #dc3232;">Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $results.html('<p style="color: #dc3232;">Error testing API connection</p>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test API Connection');
                }
            });
        });

        // Clear cache
        $('#pbs-clear-cache').on('click', function() {
            if (!confirm('Are you sure you want to clear all cached data?')) {
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: pbsScheduleAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pbs_clear_cache',
                    nonce: pbsScheduleAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error clearing cache');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Clear Cache');
                }
            });
        });

        // Sync shows
        $('#pbs-sync-shows').on('click', function() {
            var $button = $(this);
            var $results = $('#pbs-sync-results');

            $button.prop('disabled', true).text('Syncing...');
            $results.html('<p>Syncing shows from Media Manager API...</p>');

            $.ajax({
                url: pbsScheduleAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pbs_sync_shows',
                    nonce: pbsScheduleAdmin.nonce
                },
                success: function(response) {
                    console.log('Sync shows response:', response);
                    if (response.success) {
                        var message = 'Successfully synced ' + response.data.total + ' shows. ' +
                                    'Created: ' + response.data.created + ', ' +
                                    'Updated: ' + response.data.updated;

                        if (response.data.errors && response.data.errors > 0) {
                            message += ', Errors: ' + response.data.errors;
                        }

                        $results.html('<p style="color: #46b450;">' + message + '</p>');
                    } else {
                        console.error('Sync shows failed:', response.data);
                        $results.html('<p style="color: #dc3232;">Error: ' + response.data + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Sync shows AJAX error:', status, error, xhr.responseText);
                    var errorMsg = 'Error syncing shows';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg += ': ' + xhr.responseJSON.data;
                    }
                    $results.html('<p style="color: #dc3232;">' + errorMsg + '</p>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Sync Shows from Media Manager');
                }
            });
        });

        // Load feeds on page load if station is already selected
        var callsign = $('#pbs_schedule_station_callsign').val();
        if (callsign) {
            loadStationFeeds(callsign);
        }

        // Initialize WordPress color picker for branding fields
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.pbs-color-field').wpColorPicker();
        }

    });

})(jQuery);
