document.addEventListener("DOMContentLoaded", function () {
    // Initialize category product weightage pie chart
    if (window.ApexCharts && document.getElementById("category-chart") && dashboardData.categoryProductWeightage) {
        const categoryData = dashboardData.categoryProductWeightage;

        // Only render the chart if we have data
        if (categoryData.series.length > 0) {
            new ApexCharts(document.getElementById("category-chart"), {
                chart: {
                    type: "donut",
                    fontFamily: "inherit",
                    height: 240,
                    sparkline: {
                        enabled: true,
                    },
                    animations: {
                        enabled: false,
                    },
                },
                series: categoryData.series,
                labels: categoryData.labels,
                tooltip: {
                    theme: "dark",
                    y: {
                        formatter: function (value) {
                            return value + " products";
                        }
                    }
                },
                grid: {
                    strokeDashArray: 4,
                },
                colors: [
                    "color-mix(in srgb, transparent, var(--tblr-primary) 100%)",
                    "color-mix(in srgb, transparent, var(--tblr-primary) 80%)",
                    "color-mix(in srgb, transparent, var(--tblr-primary) 60%)",
                    "color-mix(in srgb, transparent, var(--tblr-primary) 40%)",
                    "color-mix(in srgb, transparent, var(--tblr-green) 80%)",
                    "color-mix(in srgb, transparent, var(--tblr-green) 60%)",
                    "color-mix(in srgb, transparent, var(--tblr-yellow) 80%)",
                    "color-mix(in srgb, transparent, var(--tblr-yellow) 60%)",
                    "color-mix(in srgb, transparent, var(--tblr-red) 60%)",
                    "color-mix(in srgb, transparent, var(--tblr-gray-300) 100%)",
                ],
                legend: {
                    show: true,
                    position: "bottom",
                    offsetY: 6,
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 100,
                    },
                    itemMargin: {
                        horizontal: 8,
                        vertical: 8,
                    },
                },
                tooltip: {
                    fillSeriesColor: false,
                },
            }).render();
        } else {
            // If no data, display a message
            document.getElementById("category-chart").innerHTML = '<div class="text-center p-3">No categories with products found</div>';
        }
    }
    // Initialize new users chart
    if (window.ApexCharts && document.getElementById("chart-new-users")) {
        const newUsersData = dashboardData.newUserRegistrationsData;

        // Extract data for chart
        const dates = newUsersData.daily.map(item => item.date);
        const counts = newUsersData.daily.map(item => item.count);

        new ApexCharts(document.getElementById("chart-new-users"), {
            chart: {
                type: "line",
                fontFamily: "inherit",
                height: 60,
                sparkline: {
                    enabled: true,
                },
                animations: {
                    enabled: false,
                },
            },
            fill: {
                opacity: 1,
            },
            stroke: {
                width: 2,
                lineCap: "round",
                curve: "stepline",
            },
            series: [{
                name: "New Users",
                data: counts
            }],
            tooltip: {
                theme: "dark"
            },
            grid: {
                strokeDashArray: 4,
            },
            xaxis: {
                labels: {
                    padding: 0,
                },
                tooltip: {
                    enabled: false
                },
                type: 'datetime',
                categories: dates,
            },
            yaxis: {
                labels: {
                    padding: 4
                },
            },
            labels: dates,
            colors: ["color-mix(in srgb, transparent, var(--tblr-orange) 100%)"],
            legend: {
                show: false,
            },
        }).render();
    }

    // Handle dynamic data loading for new user registrations
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

            if (toggle.classList.contains('new-users-period')) {
                type = 'new_users';
            } else if (toggle.classList.contains('sales-period')) {
                type = 'sales';
            } else if (toggle.classList.contains('revenue-period')) {
                type = 'revenue';
            } else if (toggle.classList.contains('commission-period')) {
                type = 'commissions';
            } else if (toggle.classList.contains('top-sellers-period')) {
                type = 'top_sellers';
            } else if (toggle.classList.contains('top-products-period')) {
                type = 'top_products';
            } else if (toggle.classList.contains('top-delivery-boys-period')) {
                type = 'top_delivery_boys';
            }

            // Update the toggle's data-period attribute
            toggle.setAttribute('data-period', period);

            // Make the AJAX request
            fetchDashboardData(type, period);
        });
    });


    // Categories Filters
    $('#categories-filter').parent().find('.dropdown-item').on('click', function (e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        const filterText = $(this).text();

        $('#categories-filter').text(filterText);
        $('#categories-filter').parent().find('.dropdown-item').removeClass('active');
        $(this).addClass('active');

        const sortBy = $('#categories-sort').parent().find('.dropdown-item.active').data('sort') || 'name';
        loadCategories(sortBy, filter);
    });

    $('#categories-sort').parent().find('.dropdown-item').on('click', function (e) {
        e.preventDefault();
        const sort = $(this).data('sort');
        const sortText = $(this).text();

        $('#categories-sort').text(sortText);
        $('#categories-sort').parent().find('.dropdown-item').removeClass('active');
        $(this).addClass('active');

        const filterBy = $('#categories-filter').parent().find('.dropdown-item.active').data('filter') || 'all';
        loadCategories(sort, filterBy);
    });

    // Initialize Commission Chart
    if (document.getElementById('commission-chart')) {

        window.commissionChart = new ApexCharts(document.getElementById('commission-chart'), {
            chart: {
                type: "area",
                fontFamily: 'inherit',
                height: 240,
                parentHeightOffset: 0,
                toolbar: {
                    show: false,
                },
                animations: {
                    enabled: false
                },
                stacked: true,
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%',
                }
            },
            dataLabels: {
                enabled: false,
            },
            fill: {
                opacity: .16,
                type: 'solid'
            },
            stroke: {
                width: 2,
                lineCap: "round",
                curve: "smooth",
            },
            series: [{
                name: "Commission",
                data: commissionData.map(item => ({
                    x: item.date,
                    y: item.commission
                }))
            }],
            tooltip: {
                theme: 'dark'
            },
            grid: {
                padding: {
                    top: -20,
                    right: 0,
                    left: -4,
                    bottom: -4
                },
                strokeDashArray: 4,
            },
            xaxis: {
                labels: {
                    padding: 0,
                },
                tooltip: {
                    enabled: false
                },
                axisBorder: {
                    show: false,
                },
                type: 'datetime',
            },
            yaxis: {
                labels: {
                    padding: 4
                },
            },
            colors: ["#2fb344"],
            legend: {
                show: false,
            },
        });
        window.commissionChart.render();
    }
});

document.addEventListener("DOMContentLoaded", function () {
    // Initialize charts when data is available
    initializeRevenueChart(dashboardData.monthlyRevenueData);
    initializeDailyPurchaseChart(dashboardData.dailyPurchaseHistory);

    // Initialize revenue background chart
    if (typeof initializeRevenueBackgroundChart === 'function') {
        initializeRevenueBackgroundChart(dashboardData.revenueDataBg);
    }
});

function loadCategories(sortBy = 'name', filterBy = 'all') {
    const type = 'categories';
    axios.get(`/admin/dashboard/data?type=${type}&sort_by=${sortBy}&filter_by=${filterBy}`)
        .then(function (response) {
            updateCategoriesGrid(response.data);
        })
        .catch(function (error) {
            console.error('Error loading categories:', error);
        });
}

// Update Functions
function updateTopSellersList(data) {
    let html = '';
    if (data.length === 0) {
        // Show not found SVG full width
        html = `
        <div class="text-center w-100 py-5">
            <img src="${base_url}/assets/theme/img/not-found.svg" alt="No data found" class="w-100" style="max-width: 400px; height: auto; margin: 0 auto; display: block;">
        </div>
    `;
    } else {
        data.forEach((seller, index) => {
            const initials = seller.name ? seller.name.substring(0, 2).toUpperCase() : "NA";

            const avatar = seller.avatar
                ? `<span class="avatar avatar-sm" style="background-image: url('${seller.avatar}');"></span>`
                : `<span class="avatar avatar-sm bg-primary text-white">${initials}</span>`;

            html += `
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-teal-lt me-3">${index + 1}</span>
                <div class="me-3">${avatar}</div>
                <div class="flex-fill">
                    <div class="font-weight-medium">${seller.name}</div>
                    <div class="text-secondary">${seller.total_orders} Orders</div>
                </div>
                <div class="text-end">
                    <div class="font-weight-medium">${seller.total_revenue}</div>
                </div>
            </div>
        `;
        });
    }
    $('#top-sellers-list').html(html);
}

function updateTopProductsList(data) {
    let html = '';

    if (!data || data.length === 0) {
        html = `
            <div class="text-center w-100 py-5">
                <img src="${base_url}/assets/theme/img/not-found.svg" alt="No products found"
                     class="w-100" style="max-width: 400px; height: auto; margin: 0 auto; display: block;">
            </div>
        `;
    } else {
        data.forEach((product, index) => {
            const initial = product.name
                ? product.name.substring(0, 1).toUpperCase()
                : 'P';

            const avatar = product.image
                ? `<span class="avatar avatar-sm" style="background-image: url('${product.image}');"></span>`
                : `<span class="avatar avatar-sm bg-primary text-white">${initial}</span>`;

            const productName = product.name?.length > 25
                ? product.name.substring(0, 25) + '...'
                : product.name;

            html += `
                <div class="list-group-item d-flex align-items-center">
                    <span class="badge bg-primary-lt me-3">${index + 1}</span>
                    <div class="me-3">${avatar}</div>
                    <div class="flex-fill">
                        <div class="font-weight-medium">
                            <a href="${base_url}/admin/products/${product.id}" class="text-decoration-none text-body">
                                ${productName}
                            </a>
                        </div>
                        <div class="text-secondary">${product.category}</div>
                        <div class="text-secondary">${product.total_quantity} sold</div>
                    </div>
                    <div class="text-end">
                        <div class="font-weight-medium">${product.total_revenue}</div>
                    </div>
                </div>
            `;
        });
    }

    document.getElementById('top-products-list').innerHTML = html;
}


function updateTopDeliveryBoysList(data) {
    let html = '';

    if (!data || data.length === 0) {
        html = `
            <div class="text-center w-100 py-5">
                <img src="${base_url}/assets/theme/img/not-found.svg" alt="No delivery boys found"
                     class="w-100" style="max-width: 400px; height: auto; margin: 0 auto; display: block;">
            </div>
        `;
    } else {
        data.forEach((deliveryBoy, index) => {
            const initials = deliveryBoy.name
                ? deliveryBoy.name.substring(0, 2).toUpperCase()
                : 'DB';

            const avatar = deliveryBoy.avatar
                ? `<span class="avatar avatar-sm" style="background-image: url('${deliveryBoy.avatar}');"></span>`
                : `<span class="avatar avatar-sm bg-warning text-white">${initials}</span>`;

            html += `
                <div class="list-group-item d-flex align-items-center">
                    <span class="badge bg-warning-lt me-3">${index + 1}</span>
                    <div class="me-3">${avatar}</div>
                    <div class="flex-fill">
                        <div class="font-weight-medium">${deliveryBoy.name}</div>
                        <div class="text-secondary">${deliveryBoy.total_deliveries} deliveries</div>
                    </div>
                    <div class="text-end">
                        <div class="font-weight-medium">${deliveryBoy.total_revenue}</div>
                    </div>
                </div>
            `;
        });
    }

    document.getElementById('top-delivery-boys-list').innerHTML = html;
}


function updateCommissionsData(data) {
    $('#commission-total').text(data.total_commission);
    $('#commission-orders').text(data.total_orders);
    $('#commission-avg').text(data.avg_commission);

    // Update commission chart if exists
    if (window.commissionChart) {
        const chartData = data.daily_data.map(item => ({
            x: item.date,
            y: item.commission
        }));

        window.commissionChart.updateSeries([{
            name: 'Commission',
            data: chartData
        }]);
    }
}

function updateCategoriesGrid(data) {
    let html = '';
    data.forEach((category) => {
        const image = category.image ?
            `<img src="${category.image}" alt="${category.title}" class="avatar avatar-lg object-contain mb-2">` :
            `<div class="avatar avatar-lg mb-2 object-contain avatar-placeholder">${category.title?.substr(0, 2)}</div>`;

        const totalSold = category.total_sold ?
            `<div class="text-success">${category.total_sold} sold</div>` : '';

        html += `
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                            <div class="card card-sm">
                                <div class="card-body text-center">
                                    ${image}
                                    <h4 class="card-title">${category.title}</h4>
                                    <div class="text-secondary">${category.products_count} Products</div>
                                    ${totalSold}
                                </div>
                            </div>
                        </div>
                    `;
    });
    $('#categories-grid').html(html);
}

// Function to fetch dashboard data via AJAX
function fetchDashboardData(type, days) {
    axios.get(`/admin/dashboard/data?type=${type}&days=${days}`)
        .then(function (response) {
            const data = response.data;

            if (type === 'new_users') {
                updateNewUsersData(data);
            } else if (type === 'sales') {
                updateSalesData(data);
            } else if (type === 'revenue') {
                updateRevenueData(data);
            } else if (type === 'commissions') {
                updateCommissionsData(data);
            } else if (type === 'top_sellers') {
                updateTopSellersList(data);
            } else if (type === 'top_products') {
                updateTopProductsList(data);
            } else if (type === 'top_delivery_boys') {
                updateTopDeliveryBoysList(data);
            }
        })
        .catch(function (error) {
            console.error('Error fetching dashboard data:', error);
        });
}

// Function to update new users data
function updateNewUsersData(data) {
    // Update count
    const newUsersCountElement = document.getElementById('new-users-count');
    if (newUsersCountElement) {
        newUsersCountElement.textContent = data.count;
    }

    // Update trend
    const trendElement = document.getElementById('new-users-trend');
    if (trendElement) {
        trendElement.textContent = Math.abs(data.percentage_change) + '%';
        trendElement.className = `text-${data.is_increase ? 'green' : 'red'} d-inline-flex align-items-center lh-1`;

        // Update trend icon
        const trendIcon = trendElement.querySelector('svg');
        if (trendIcon) {
            const path1 = trendIcon.querySelector('path:nth-child(1)');
            const path2 = trendIcon.querySelector('path:nth-child(2)');

            if (path1 && path2) {
                if (data.is_increase) {
                    path1.setAttribute('d', 'M3 17l6 -6l4 4l8 -8');
                    path2.setAttribute('d', 'M14 7l7 0l0 7');
                } else {
                    path1.setAttribute('d', 'M3 7l6 6l4 -4l8 8');
                    path2.setAttribute('d', 'M21 7l0 7l-7 0');
                }
            }
        }
    }

    // Update chart
    if (window.ApexCharts) {
        const chartElement = document.getElementById("chart-new-users");
        if (chartElement) {
            // Extract data for chart
            const dates = data.daily.map(item => item.date);
            const counts = data.daily.map(item => item.count);

            // Destroy existing chart
            while (chartElement.firstChild) {
                chartElement.removeChild(chartElement.firstChild);
            }

            // Create new chart
            new ApexCharts(chartElement, {
                chart: {
                    type: "line",
                    fontFamily: "inherit",
                    height: 40,
                    sparkline: {
                        enabled: true,
                    },
                    animations: {
                        enabled: false,
                    },
                },
                fill: {
                    opacity: 1,
                },
                stroke: {
                    width: 2,
                    lineCap: "round",
                    curve: "smooth",
                },
                series: [{
                    name: "New Users",
                    data: counts
                }],
                tooltip: {
                    theme: "dark"
                },
                grid: {
                    strokeDashArray: 4,
                },
                xaxis: {
                    labels: {
                        padding: 0,
                    },
                    tooltip: {
                        enabled: false
                    },
                    type: 'datetime',
                    categories: dates,
                },
                yaxis: {
                    labels: {
                        padding: 4
                    },
                },
                labels: dates,
                colors: ["#206bc4"],
                legend: {
                    show: false,
                },
            }).render();
        }
    }
}
