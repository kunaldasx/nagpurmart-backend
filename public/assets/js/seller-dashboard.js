document.addEventListener("DOMContentLoaded", function () {
    // Initialize charts when data is available
    if (typeof dashboardData !== 'undefined') {
        initializeRevenueChart(dashboardData.monthlyRevenueData);
        initializeStoreOrdersChart(dashboardData.storeOrderTotals);
        initializeDailyPurchaseChart(dashboardData.dailyPurchaseHistory);
        // Recent orders are now displayed statically in the view

        // Initialize revenue background chart
        if (typeof initializeRevenueBackgroundChart === 'function') {
            initializeRevenueBackgroundChart(dashboardData.monthlyRevenueData);
        }

        // Initialize wallet chart
        if (typeof initializeWalletChart === 'function') {
            initializeWalletChart();
        }
    } else {
        // Fetch data via AJAX if not provided inline
        axios.get(`/${panel}/dashboard/chart-data`)
            .then(response => {
                const data = response.data;
                initializeRevenueChart(data.revenue_data);
                initializeStoreOrdersChart(data.store_order_totals);
                initializeDailyPurchaseChart(data.daily_purchase_history);
                // Recent orders are now displayed statically in the view

                // Initialize revenue background chart
                if (typeof initializeRevenueBackgroundChart === 'function') {
                    initializeRevenueBackgroundChart(data.revenue_data);
                }

                // Initialize wallet chart
                if (typeof initializeWalletChart === 'function') {
                    initializeWalletChart();
                }
            })
            .catch(error => console.error('Error loading dashboard data:', error));
    }

    // Initialize star ratings
    initializeStarRatings();

    // Initialize sparkline activity chart
    initializeSparklineActivityChart();

    // Handle dynamic data loading for dropdowns
    document.querySelectorAll('.dropdown-menu a[data-period]').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            // Get the parent dropdown
            const dropdown = this.closest('.dropdown');
            if (!dropdown) return;

            // Get the toggle element
            const toggle = dropdown.querySelector('.dropdown-toggle');
            if (!toggle) return;

            // Update active state
            dropdown.querySelectorAll('.dropdown-item').forEach(el => {
                el.classList.remove('active');
            });
            this.classList.add('active');

            // Update toggle text
            toggle.textContent = this.textContent;

            // Get the period and section type
            const period = this.getAttribute('data-period');
            let type = '';

            if (toggle.classList.contains('sales-period')) {
                type = 'sales';
            } else if (toggle.classList.contains('revenue-period')) {
                type = 'revenue';
            } else if (toggle.classList.contains('store-revenue-period')) {
                type = 'store_revenue';
            } else if (toggle.classList.contains('active-users-period')) {
                type = 'active_users';
            }

            // Update the toggle's data-period attribute
            toggle.setAttribute('data-period', period);

            // Make the AJAX request
            fetchDashboardData(type, period);
        });
    });
});

function initializeSparklineActivityChart(storeRevenueData = null) {
    if (!window.ApexCharts || !document.getElementById("chart-stores-revenue")) {
        return;
    }

    // Use provided data or default from global dashboardData
    const data = storeRevenueData || (typeof dashboardData !== 'undefined' ? dashboardData.storeRevenueData : null);

    if (!data || !data.stores) {
        return;
    }

    // Prepare chart data from store revenue data
    const stores = data.stores.slice(0, 10); // Show top 10 stores
    const storeNames = stores.map(store => store.name);
    const revenueValues = stores.map(store => Number(store.revenue) || 0);
    const formattedRevenueValues = stores.map(store => store.formatted_revenue);
    const hasRevenueData = revenueValues.some(value => value > 0);

    new ApexCharts(document.getElementById("chart-stores-revenue"), {
        chart: {
            height: 328,
            type: 'bar',
            zoom: {
                enabled: false
            },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                const index = opts.dataPointIndex;
                return formattedRevenueValues[index] || new Intl.NumberFormat().format(val);
            }
        },
        series: [{
            name: "Revenue",
            data: revenueValues
        }],
        title: {
            text: 'Store Revenue',
            align: 'left',
            offsetY: 25,
            offsetX: 20
        },
        subtitle: {
            text: `Last ${data.days} days`,
            offsetY: 55,
            offsetX: 20
        },
        xaxis: {
            categories: storeNames,
            min: 0,
            max: hasRevenueData ? undefined : 1,
            labels: {
                formatter: function (val, opts) {
                    // For x-axis labels in horizontal bar chart, val is the revenue value
                    const index = revenueValues.indexOf(val);
                    return index >= 0 ? formattedRevenueValues[index] : new Intl.NumberFormat().format(val);
                }
            }
        },
        yaxis: {
            labels: {
                maxWidth: 120
            }
        },
        grid: {
            show: true,
            padding: {
                bottom: 0
            }
        },
        tooltip: {
            y: {
                formatter: function (val, opts) {
                    const index = opts.dataPointIndex;
                    return formattedRevenueValues[index] || new Intl.NumberFormat().format(val);
                }
            }
        }
    }).render();
}

// Function to fetch dashboard data and update charts
function fetchDashboardData(type, period) {
    // Show loading state if needed
    const url = `/${panel}/dashboard/data`;

    axios.get(url, {
        params: {
            type: type,
            days: period
        }
    })
        .then(response => {
            const data = response.data;
            // Update chart based on type
            if (type === 'store_revenue') {
                // Clear existing chart
                const chartElement = document.getElementById("chart-stores-revenue");
                if (chartElement) {
                    chartElement.innerHTML = '';
                    initializeSparklineActivityChart(data);
                }
            } else if (type === 'sales') {
                updateSalesData(data);
            } else if (type === 'revenue') {
                updateRevenueData(data);
            } else if (type === 'active_users') {
                updateActiveUsersData(data);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}

