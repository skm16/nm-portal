/**
 * Analytics Dashboard JavaScript
 * NMDA Portal Admin Analytics
 */

(function ($) {
    'use strict';

    console.log('NMDA Analytics: Script loaded');
    console.log('NMDA Analytics: jQuery available?', typeof $ !== 'undefined');
    console.log('NMDA Analytics: Chart.js available?', typeof Chart !== 'undefined');
    console.log('NMDA Analytics: Flatpickr available?', typeof flatpickr !== 'undefined');

    // NMDA Brand Colors
    const colors = {
        primary: '#512c1d',
        secondary: '#8b0c12',
        gold: '#f5be29',
        success: '#28a745',
        info: '#17a2b8',
        warning: '#ffc107',
        danger: '#dc3545',
    };

    let userGrowthChart, applicationsChart, classificationsChart;

    /**
     * Initialize analytics dashboard
     */
    function init() {
        console.log('NMDA Analytics: Init function called');
        console.log('NMDA Analytics: nmdaAnalytics object available?', typeof nmdaAnalytics !== 'undefined');
        if (typeof nmdaAnalytics !== 'undefined') {
            console.log('NMDA Analytics: ajax_url =', nmdaAnalytics.ajax_url);
            console.log('NMDA Analytics: nonce =', nmdaAnalytics.nonce ? 'Present' : 'Missing');
        }

        // Update status indicator
        const $status = $('#js-status');
        const $details = $('#js-details');
        const $jsCheck = $('#nmda-js-check');

        try {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded. Charts will not be displayed.');
                $status.html('❌ Failed - Chart.js not loaded');
                $details.html('Chart.js library failed to load from CDN. Charts cannot be displayed.');
                $jsCheck.css('background', '#f8d7da').css('border-left-color', '#dc3545');
                return; // Exit if Chart.js not loaded
            }

            console.log('NMDA Analytics: Chart.js loaded successfully, version:', Chart.version);

            // Configure Chart.js defaults after checking it's loaded
            Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
            Chart.defaults.font.size = 13;

            console.log('NMDA Analytics: Initializing components...');
            initDatePicker();
            initCharts();
            initTabs();
            initExportButtons();
            initRefreshButton();
            console.log('NMDA Analytics: Initialization complete');

            // Update status to success
            $status.html('✅ Loaded Successfully');
            $details.html(`jQuery: ✓ | Chart.js v${Chart.version}: ✓ | Flatpickr: ${typeof flatpickr !== 'undefined' ? '✓' : '✗'} | All components initialized`);
            $jsCheck.css('background', '#d4edda').css('border-left-color', '#28a745');

            // Hide the indicator after 3 seconds
            setTimeout(function() {
                $jsCheck.fadeOut();
            }, 3000);
        } catch (error) {
            console.error('Error initializing analytics dashboard:', error);
            console.error('Error stack:', error.stack);
            $status.html('❌ Error during initialization');
            $details.html(`Error: ${error.message}<br>Check browser console for details.`);
            $jsCheck.css('background', '#f8d7da').css('border-left-color', '#dc3545');
        }
    }

    /**
     * Initialize date range picker
     */
    function initDatePicker() {
        if (typeof flatpickr === 'undefined') {
            return;
        }

        flatpickr('#analytics-date-range', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            maxDate: 'today',
        });

        $('#apply-date-filter').on('click', function () {
            const dateRange = $('#analytics-date-range').val();
            if (!dateRange) {
                alert('Please select a date range');
                return;
            }
            // Reload page with date filter (would need backend support)
            console.log('Apply date filter:', dateRange);
        });
    }

    /**
     * Initialize all charts
     */
    function initCharts() {
        initUserGrowthChart();
        initApplicationsChart();
        initClassificationsChart();
    }

    /**
     * User Growth Chart
     */
    function initUserGrowthChart() {
        try {
            const data = getChartData('user-registrations-data');
            if (!data || data.length === 0) {
                console.warn('No user registration data available for chart.');
                return;
            }

            const ctx = document.getElementById('user-growth-chart');
            if (!ctx) {
                console.warn('User growth chart canvas element not found.');
                return;
            }

            const labels = data.map(item => item.month_label);
            const values = data.map(item => parseInt(item.count, 10));

            userGrowthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'New Users',
                    data: values,
                    borderColor: colors.primary,
                    backgroundColor: createGradient(ctx, colors.primary),
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: colors.primary,
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                        },
                        bodyFont: {
                            size: 13,
                        },
                        callbacks: {
                            label: function (context) {
                                return 'Registrations: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                        }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error initializing user growth chart:', error);
        }
    }

    /**
     * Applications Chart (Stacked Bar)
     */
    function initApplicationsChart() {
        try {
            const data = getChartData('applications-data');
            if (!data || data.length === 0) {
                console.warn('No applications data available for chart.');
                return;
            }

            const ctx = document.getElementById('applications-chart');
            if (!ctx) {
                console.warn('Applications chart canvas element not found.');
                return;
            }

        const labels = data.map(item => item.month_label);
        const approved = data.map(item => parseInt(item.approved, 10));
        const pending = data.map(item => parseInt(item.pending, 10));
        const rejected = data.map(item => parseInt(item.rejected, 10));

        applicationsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Approved',
                        data: approved,
                        backgroundColor: colors.success,
                        borderWidth: 0,
                    },
                    {
                        label: 'Pending',
                        data: pending,
                        backgroundColor: colors.warning,
                        borderWidth: 0,
                    },
                    {
                        label: 'Rejected',
                        data: rejected,
                        backgroundColor: colors.danger,
                        borderWidth: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false,
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error initializing applications chart:', error);
        }
    }

    /**
     * Business Classifications Chart (Doughnut)
     */
    function initClassificationsChart() {
        try {
            const data = getChartData('business-classifications-data');
            if (!data || Object.keys(data).length === 0) {
                console.warn('No business classifications data available for chart.');
                return;
            }

            const ctx = document.getElementById('classifications-chart');
            if (!ctx) {
                console.warn('Classifications chart canvas element not found.');
                return;
            }

        const labels = Object.keys(data);
        const values = Object.values(data).map(val => parseInt(val, 10));

        // Generate colors for each classification
        const backgroundColors = [
            colors.primary,
            colors.secondary,
            colors.gold,
            colors.success,
            colors.info,
            colors.warning,
            hexToRgba(colors.primary, 0.6),
            hexToRgba(colors.secondary, 0.6),
            hexToRgba(colors.gold, 0.6),
            hexToRgba(colors.success, 0.6),
        ];

        classificationsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return {
                                            text: `${label} (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
            }
        });
        } catch (error) {
            console.error('Error initializing classifications chart:', error);
        }
    }

    /**
     * Create gradient for charts
     * @param {HTMLCanvasElement} canvas - The canvas element
     * @param {string} color - Hex color code
     * @returns {CanvasGradient} Linear gradient
     */
    function createGradient(canvas, color) {
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, hexToRgba(color, 0.3));
        gradient.addColorStop(1, hexToRgba(color, 0.05));
        return gradient;
    }

    /**
     * Convert hex color to rgba
     */
    function hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    /**
     * Get chart data from JSON script element
     */
    function getChartData(elementId) {
        try {
            const element = document.getElementById(elementId);
            if (!element) {
                console.warn(`Chart data element not found: ${elementId}`);
                return null;
            }

            const data = JSON.parse(element.textContent);
            if (!data) {
                console.warn(`Chart data is empty for: ${elementId}`);
                return null;
            }

            return data;
        } catch (error) {
            console.error(`Error parsing chart data for ${elementId}:`, error);
            return null;
        }
    }

    /**
     * Initialize tab switching
     */
    function initTabs() {
        $('.nav-tab').on('click', function (e) {
            e.preventDefault();

            const target = $(this).attr('href');

            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            // Show target content
            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        });
    }

    /**
     * Initialize export buttons
     */
    function initExportButtons() {
        $('[data-export]').on('click', function () {
            const exportType = $(this).data('export');
            exportData(exportType);
        });

        // Main export button
        $('#export-analytics').on('click', function () {
            exportFullReport();
        });
    }

    /**
     * Export data to CSV
     */
    function exportData(type) {
        let data, filename;

        switch (type) {
            case 'user-growth':
                data = getChartData('user-registrations-data');
                filename = 'user-growth-' + getCurrentDate() + '.csv';
                exportToCSV(data, ['month_label', 'count'], ['Month', 'Registrations'], filename);
                break;

            case 'applications':
                data = getChartData('applications-data');
                filename = 'applications-' + getCurrentDate() + '.csv';
                exportToCSV(data, ['month_label', 'total', 'approved', 'pending', 'rejected'],
                    ['Month', 'Total', 'Approved', 'Pending', 'Rejected'], filename);
                break;

            case 'users-table':
                exportTableToCSV('.nmda-analytics-table', 'users-report-' + getCurrentDate() + '.csv');
                break;

            case 'businesses-table':
                exportTableToCSV('.nmda-analytics-table', 'businesses-report-' + getCurrentDate() + '.csv');
                break;

            case 'reimbursements-table':
                exportTableToCSV('.nmda-analytics-table', 'reimbursements-report-' + getCurrentDate() + '.csv');
                break;

            default:
                console.log('Unknown export type:', type);
        }
    }

    /**
     * Export to CSV from data array
     */
    function exportToCSV(data, fields, headers, filename) {
        if (!data || data.length === 0) {
            alert('No data to export');
            return;
        }

        let csv = headers.join(',') + '\n';

        data.forEach(function (row) {
            const values = fields.map(function (field) {
                let value = row[field] || '';
                // Escape commas and quotes
                if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                    value = '"' + value.replace(/"/g, '""') + '"';
                }
                return value;
            });
            csv += values.join(',') + '\n';
        });

        downloadCSV(csv, filename);
    }

    /**
     * Export table to CSV
     */
    function exportTableToCSV(tableSelector, filename) {
        const $table = $(tableSelector);
        if ($table.length === 0) {
            alert('Table not found');
            return;
        }

        let csv = '';

        // Headers
        $table.find('thead th').each(function () {
            csv += '"' + $(this).text().replace(/"/g, '""') + '",';
        });
        csv = csv.slice(0, -1) + '\n';

        // Rows
        $table.find('tbody tr').each(function () {
            $(this).find('td').each(function () {
                const text = $(this).text().trim().replace(/"/g, '""');
                csv += '"' + text + '",';
            });
            csv = csv.slice(0, -1) + '\n';
        });

        downloadCSV(csv, filename);
    }

    /**
     * Download CSV file
     */
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');

        if (navigator.msSaveBlob) {
            // IE 10+
            navigator.msSaveBlob(blob, filename);
        } else {
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    /**
     * Export full analytics report
     */
    function exportFullReport() {
        alert('Full report export would generate a comprehensive PDF or multi-sheet Excel file. This requires server-side processing.');
        // This would typically make an AJAX request to generate a comprehensive report
    }

    /**
     * Get current date in YYYY-MM-DD format
     */
    function getCurrentDate() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Refresh analytics data
     */
    function initRefreshButton() {
        let retryCount = 0;
        const maxRetries = 2;

        function performRefresh($btn, originalHtml) {
            $.ajax({
                url: nmdaAnalytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'nmda_refresh_analytics',
                    nonce: nmdaAnalytics.nonce
                },
                timeout: 30000, // 30 second timeout
                success: function (response) {
                    try {
                        if (response && response.success) {
                            $btn.html('<span class="dashicons dashicons-yes"></span> Refreshed! Reloading...');
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        } else {
                            const errorMsg = response && response.data && response.data.message
                                ? response.data.message
                                : 'Unknown error occurred';
                            console.error('Refresh failed:', errorMsg);
                            alert('Error refreshing data: ' + errorMsg);
                            $btn.prop('disabled', false).html(originalHtml);
                            retryCount = 0;
                        }
                    } catch (error) {
                        console.error('Error processing refresh response:', error);
                        alert('Error processing refresh response. Please try again.');
                        $btn.prop('disabled', false).html(originalHtml);
                        retryCount = 0;
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', {xhr, status, error});

                    if (retryCount < maxRetries) {
                        retryCount++;
                        console.log(`Retrying refresh... Attempt ${retryCount} of ${maxRetries}`);
                        $btn.html(`<span class="analytics-loading"></span> Retrying... (${retryCount}/${maxRetries})`);
                        setTimeout(function() {
                            performRefresh($btn, originalHtml);
                        }, 1000 * retryCount); // Exponential backoff
                    } else {
                        const errorMsg = status === 'timeout'
                            ? 'Request timed out. The server may be busy.'
                            : 'Network error occurred. Please check your connection.';
                        alert(`Error refreshing data: ${errorMsg}`);
                        $btn.prop('disabled', false).html(originalHtml);
                        retryCount = 0;
                    }
                }
            });
        }

        $('#refresh-analytics').on('click', function () {
            const $btn = $(this);
            const originalHtml = $btn.html();

            if (!nmdaAnalytics || !nmdaAnalytics.ajax_url || !nmdaAnalytics.nonce) {
                console.error('Analytics configuration missing');
                alert('Configuration error. Please reload the page.');
                return;
            }

            $btn.prop('disabled', true).html('<span class="analytics-loading"></span> Refreshing...');
            retryCount = 0;
            performRefresh($btn, originalHtml);
        });
    }

    // Initialize on document ready
    $(document).ready(function () {
        init();
    });

})(jQuery);
