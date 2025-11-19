/**
 * PBS Schedule Viewer - Public JavaScript
 *
 * @package PBS_Schedule_Viewer
 * @version 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        var refreshInterval = null;

        // Initialize auto-refresh if configured
        if (pbsScheduleViewer.refreshInterval > 0) {
            startAutoRefresh();
        }

        // View toggle
        $('.pbs-schedule-view-toggle').on('click', function() {
            var $button = $(this);
            var targetView = $button.data('toggle-view');
            var $container = $button.closest('.pbs-schedule-container');

            // This would reload with different view parameter
            // For now, we'll just indicate it would switch
            alert('View toggle functionality - would switch to ' + targetView + ' view');
            // In production, you might reload the shortcode with different view parameter
            // or use AJAX to fetch and replace the content
        });

        // Manual refresh
        $('.pbs-schedule-refresh').on('click', function() {
            var $button = $(this);
            refreshSchedule($button.closest('.pbs-schedule-container'));
        });

        /**
         * Refresh schedule data
         */
        function refreshSchedule($container) {
            var $button = $container.find('.pbs-schedule-refresh');
            var originalText = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> Refreshing...');

            // Get current parameters
            var callsign = $container.data('callsign') || '';
            var feed = $container.data('feed') || '';
            var date = $container.data('date') || '';

            $.ajax({
                url: pbsScheduleViewer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pbs_refresh_schedule',
                    nonce: pbsScheduleViewer.nonce,
                    callsign: callsign,
                    feed: feed,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        // Update timestamp
                        updateTimestamp();

                        // In a full implementation, you would update the schedule display
                        // For now, we'll just show success
                        showNotification('Schedule updated', 'success');
                    } else {
                        showNotification('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showNotification('Failed to refresh schedule', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        }

        /**
         * Start auto-refresh timer
         */
        function startAutoRefresh() {
            refreshInterval = setInterval(function() {
                $('.pbs-schedule-container').each(function() {
                    refreshSchedule($(this));
                });
            }, pbsScheduleViewer.refreshInterval);
        }

        /**
         * Stop auto-refresh timer
         */
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }

        /**
         * Update timestamp
         */
        function updateTimestamp() {
            var now = new Date();
            var hours = now.getHours();
            var minutes = now.getMinutes();
            var ampm = hours >= 12 ? 'PM' : 'AM';

            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;

            var timeString = hours + ':' + minutes + ' ' + ampm;

            $('.pbs-schedule-timestamp').text(timeString);
        }

        /**
         * Show notification
         */
        function showNotification(message, type) {
            var $notification = $('<div class="pbs-notification pbs-notification-' + type + '">' + message + '</div>');

            $('body').append($notification);

            setTimeout(function() {
                $notification.addClass('pbs-notification-show');
            }, 10);

            setTimeout(function() {
                $notification.removeClass('pbs-notification-show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }

        /**
         * Highlight current show (if displaying current time)
         */
        function highlightCurrentShow() {
            var now = new Date();
            var currentHour = now.getHours();
            var currentMinute = now.getMinutes();
            var currentTimeMinutes = (currentHour * 60) + currentMinute;

            $('.pbs-schedule-item, .pbs-schedule-list-item').each(function() {
                var $item = $(this);
                var startTime = $item.data('start-time');

                if (!startTime) return;

                // Parse HHMM format
                var hours = parseInt(startTime.substring(0, 2), 10);
                var minutes = parseInt(startTime.substring(2, 4), 10);
                var itemTimeMinutes = (hours * 60) + minutes;

                // Get duration from meta
                var duration = parseInt($item.find('.pbs-schedule-duration').text(), 10) || 60;
                var endTimeMinutes = itemTimeMinutes + duration;

                if (currentTimeMinutes >= itemTimeMinutes && currentTimeMinutes < endTimeMinutes) {
                    $item.addClass('pbs-schedule-item-current');
                } else {
                    $item.removeClass('pbs-schedule-item-current');
                }
            });
        }

        // Highlight current show on load and update every minute
        highlightCurrentShow();
        setInterval(highlightCurrentShow, 60000);

        // Stop auto-refresh when user leaves page
        $(window).on('beforeunload', function() {
            stopAutoRefresh();
        });

        // Pause auto-refresh when page is hidden
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else if (pbsScheduleViewer.refreshInterval > 0) {
                startAutoRefresh();
            }
        });

    });

})(jQuery);

// Add spin animation for refresh button
var style = document.createElement('style');
style.innerHTML = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .pbs-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s;
        z-index: 9999;
    }
    .pbs-notification-show {
        opacity: 1;
        transform: translateX(0);
    }
    .pbs-notification-success {
        border-left: 4px solid #4CAF50;
    }
    .pbs-notification-error {
        border-left: 4px solid #f44336;
    }
    .pbs-schedule-item-current {
        background: #fff9e6 !important;
        border-left: 4px solid #FF9800;
        padding-left: 16px;
    }
`;
document.head.appendChild(style);
