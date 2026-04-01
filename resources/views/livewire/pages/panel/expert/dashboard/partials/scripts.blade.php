<script>
(function () {
    const chartInstances = {};

    const ensureNumber = (value, fallback = 0) => {
        const numeric = Number(value);
        return Number.isFinite(numeric) ? numeric : fallback;
    };

    const normaliseSeries = (input) => {
        if (!Array.isArray(input)) {
            return [];
        }

        return input.map((value) => ensureNumber(value));
    };

    const formatCurrency = (value) => {
        const numeric = ensureNumber(value);
        return '$' + numeric.toLocaleString();
    };

    const formatInteger = (value) => {
        const numeric = ensureNumber(value);
        return numeric.toLocaleString();
    };

    const resolve = (source, path, fallback) => {
        let cursor = source;
        for (let index = 0; index < path.length; index++) {
            const key = path[index];
            if (!cursor || typeof cursor !== 'object' || !(key in cursor)) {
                return fallback;
            }
            cursor = cursor[key];
        }

        return cursor;
    };

    const getMetrics = () => {
        const metricsEl = document.getElementById('dashboard-metrics-data');
        if (!metricsEl) {
            return null;
        }

        try {
            return JSON.parse(metricsEl.textContent || '{}');
        } catch (error) {
            console.error('Failed to parse dashboard metrics payload', error);
            return null;
        }
    };

    const createChart = (selector, options) => {
        const element = document.querySelector(selector);
        if (!element || typeof ApexCharts === 'undefined') {
            return;
        }

        if (chartInstances[selector]) {
            chartInstances[selector].destroy();
            delete chartInstances[selector];
        }

        const chart = new ApexCharts(element, options);
        chart.render();
        chartInstances[selector] = chart;
    };

    const renderCharts = (data) => {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        const configColors = window.config && window.config.colors ? window.config.colors : {};
        const palette = {
            primary: configColors.primary || '#696cff',
            success: configColors.success || '#71dd37',
            warning: configColors.warning || '#ffab00',
            info: configColors.info || '#03c3ec',
            danger: configColors.danger || '#ff3e1d',
            secondary: configColors.secondary || '#8592a3',
        };
        const fallbackNoDataColor = palette.secondary || '#8592a3';
        const noData = {
            text: 'No data available',
            align: 'center',
            verticalAlign: 'middle',
            style: {
                color: fallbackNoDataColor,
                fontWeight: 500,
            }
        };

        const revenueLabels = resolve(data, ['revenue', 'labels'], []);
        const revenueSeries = normaliseSeries(resolve(data, ['revenue', 'revenue'], []));
        const contractsSeries = normaliseSeries(resolve(data, ['revenue', 'contracts'], []));

        createChart('#revenueTrendChart', {
            chart: {
                height: 320,
                type: 'line',
                stacked: false,
                toolbar: { show: false }
            },
            noData,
            series: [
                {
                    name: 'Revenue',
                    type: 'area',
                    data: revenueSeries
                },
                {
                    name: 'Contracts',
                    type: 'line',
                    data: contractsSeries
                }
            ],
            stroke: {
                width: [3, 3],
                curve: 'smooth'
            },
            dataLabels: { enabled: false },
            colors: [palette.primary, palette.info],
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            markers: {
                size: 4,
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { sizeOffset: 2 }
            },
            labels: revenueLabels,
            xaxis: {
                categories: revenueLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    labels: {
                        formatter: (val) => formatCurrency(val)
                    }
                },
                {
                    opposite: true,
                    labels: {
                        formatter: (val) => formatInteger(val)
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    {
                        formatter: (val) => formatCurrency(val)
                    },
                    {
                        formatter: (val) => formatInteger(val)
                    }
                ]
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4,
                padding: { left: 12, right: 12 }
            },
            legend: {
                show: true,
                horizontalAlign: 'left',
                offsetY: 8
            }
        });

        const statusSeries = resolve(data, ['statusTrend'], []).map((series) => ({
            name: series && series.name ? series.name : '',
            data: normaliseSeries(series && series.data ? series.data : [])
        }));
        createChart('#statusTrendChart', {
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,
                toolbar: { show: false }
            },
            noData,
            series: statusSeries,
            colors: [palette.primary, palette.success, palette.warning, palette.info, palette.danger],
            plotOptions: {
                bar: {
                    columnWidth: '45%',
                    borderRadius: 8,
                    endingShape: 'rounded'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: revenueLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: (val) => formatInteger(val)
                }
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => formatInteger(val)
                }
            }
        });

        const discountLabels = resolve(data, ['discount', 'labels'], []);
        const discountCreated = normaliseSeries(resolve(data, ['discount', 'created'], []));
        const discountUsed = normaliseSeries(resolve(data, ['discount', 'used'], []));
        createChart('#discountUsageChart', {
            chart: {
                type: 'line',
                height: 280,
                toolbar: { show: false }
            },
            noData,
            series: [
                {
                    name: 'Created',
                    type: 'area',
                    data: discountCreated
                },
                {
                    name: 'Used',
                    type: 'line',
                    data: discountUsed
                }
            ],
            colors: [palette.warning, palette.success],
            stroke: {
                width: [2, 3],
                curve: 'smooth'
            },
            dataLabels: { enabled: false },
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 0.2,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: discountLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: (val) => formatInteger(val)
                }
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => formatInteger(val)
                }
            }
        });

        const fleetSeries = normaliseSeries(resolve(data, ['fleet', 'series'], []));
        const fleetLabels = resolve(data, ['fleet', 'labels'], []);
        createChart('#fleetDistributionChart', {
            chart: {
                type: 'donut',
                height: 300
            },
            noData,
            series: fleetSeries,
            labels: fleetLabels,
            colors: [palette.success, palette.info, palette.warning],
            stroke: {
                colors: ['transparent']
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => ensureNumber(val).toFixed(1) + '%'
            },
            legend: {
                position: 'bottom'
            }
        });

        const topLabels = resolve(data, ['topBrands', 'labels'], []);
        const topSeries = normaliseSeries(resolve(data, ['topBrands', 'series'], []));
        createChart('#topModelsChart', {
            chart: {
                type: 'bar',
                height: 260,
                toolbar: { show: false }
            },
            noData,
            series: [
                {
                    name: 'Contracts',
                    data: topSeries
                }
            ],
            colors: [palette.primary],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: true,
                    barHeight: '60%'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: topLabels,
                labels: {
                    formatter: (val) => formatInteger(val)
                }
            },
            grid: {
                borderColor: (palette.secondary || '#8592a3') + '33',
                strokeDashArray: 4
            }
        });
    };

    const boot = () => {
        const metrics = getMetrics();
        if (!metrics) {
            return;
        }

        renderCharts(metrics);
    };

    const registerLivewireHook = () => {
        if (!window.Livewire || registerLivewireHook.initialized) {
            return;
        }

        Livewire.hook('message.processed', (message, component) => {
            if (component && component.fingerprint && component.fingerprint.name === 'pages.panel.expert.dashboard') {
                boot();
            }
        });

        registerLivewireHook.initialized = true;
    };
    registerLivewireHook.initialized = false;

    const init = () => {
        boot();
        registerLivewireHook();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('livewire:init', init);
    document.addEventListener('livewire:navigated', init);
})();
</script>
