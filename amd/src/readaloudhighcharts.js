define(['jquery', 'core/config', 'core/str'],
    function($, config, Str) {
    "use strict";

    var hcGlobal;
    var courseId;
    var classId;
    var classType;

    var strings = {};
    var fontFamily = '"Open Sans",-apple-system,BlinkMacSystemFont,"Segoe UI",' +
        'Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"';

    /**
     * When the user hovers over a bar, we show the bar details and learner photo.
     * @param formatter
     * @returns {string}
     */
    var tooltipContent = function(formatter) {
        var str = '';
        if (formatter.point.isDrillDown || formatter.point.isUserChart) {
            str += '<div class="text-center mt-1">' + formatter.point.label + '</div>';
        } else {
                str += '<div class="text-center mt-1">' +
                    '<img src="' + formatter.point.photoUrl
                    + '" class="userpicture defaultuserpic" role="presentation" width="50" height="50">' + '</div>'
                    + '<div class="text-center mt-1">' + formatter.point.name + '</div>'
                    + '<div class="text-center mt-1">' + formatter.point.label + '</div>';
            str += '<div class="text-center mt-1">' + strings.clickformore + '</div>';
        }
        return str;
    };

    /**
     * Draw the chart in the specified container, based on the data series and optional drilldown data series.
     * @param containerId
     * @param dataSeries
     * @param yAxisLabel
     * @param drilldownSeries
     * @param chartType
     */
    var drawChart = function(containerId, dataSeries, yAxisLabel, drilldownSeries, chartType) {
        var chartParams = {
            chart: {
                type: chartType === undefined ? 'column' : chartType
            },
            title: {text: dataSeries[0].name, useHTML: true},
            xAxis: {
                type: 'category', labels: {
                    rotation: 90,
                    style: {
                        fontSize: '11px',
                        fontFamily: fontFamily,
                        width: '200px',
                        whiteSpace: 'nowrap',
                        color: '#898989'
                    },
                },
            },
            yAxis: {min: 0, title: {text: yAxisLabel}, plotLines: []},
            legend: {
                floating: true,
                layout: 'vertical',
                align: 'left',
                verticalAlign: 'top',
                x: 60,
                y: 0
            },
            tooltip: {
                useHTML: true,
                backgroundColor: '#f5f5f5',
                borderColor: '#8c8c8c',
                borderRadius: 10,
                borderWidth: 2,
                formatter: function() {
                    return tooltipContent(this);
                }
            },
            series: dataSeries, // this includes both sets of data if we have both bars and points on the chart.
            plotOptions: {series: {cursor: 'pointer', states: {hover: {enabled: false}}}, showInLegend: 0}
        };

        if (chartType === 'readingscompleted') {
            chartParams.plotOptions.series.point = {
                events: {
                    click: function (e) {
                        location.href = config.wwwroot + '/blocks/readaloudteacher/klasses/userreports.php?userid='
                            + e.point.xid + '&courseid=' + courseId + "&returnklassid=" + classId + "&returnklasstype=" + classType;
                    }
                }
            };
        } else if (drilldownSeries !== undefined && drilldownSeries) {
            chartParams.drilldown = {series: drilldownSeries, type: 'line'};
            chartParams.chart.events = {
                drilldown: function (e) {
                    var photo = '<img src="'
                        + e.point.photoUrl + '" class="userpicture defaultuserpic" role="presentation" width="50" height="50">';
                    this.setTitle({text: photo + e.point.name});
                },
                drillup: function (e) {
                    this.setTitle({text: e.seriesOptions.name});
                }
            };
        }
        if (chartType === 'wpm') {
            var benchmarks = $("#pagedata").attr("data-wpmbenchmarks");
            if (benchmarks !== undefined) {
                benchmarks = JSON.parse(benchmarks);
                var benchmarkssubtitle = '<b>' + strings.benchmarks + '</b>: ';
                benchmarks.forEach(function(bench) {
                    if (bench.value !== "0") {
                        chartParams.yAxis.plotLines.push({
                            color: 'lightblue',
                            value: bench.value,
                            dashStyle: 'dash',
                            width: '2',
                            zIndex: 5,
                            label: {text: bench.name,  align: 'right', x: -5}
                        });
                        benchmarkssubtitle += bench.name + ' ' + bench.value + ' ' + strings.wpm + ' | ';
                    }
                });
                chartParams.subtitle = {text: benchmarkssubtitle};
            }
        }
        if (chartType === 'quiz' || chartType === 'accuracy') {
            // Tjhese are percentage charts.
            chartParams.yAxis.max = 100;
        }
        hcGlobal.chart(containerId, chartParams);
    };

    /**
     * Get the primary data for the charts on this page from the PHP page (JSON).
     * @returns {[]}
     */
    var get_primary_dataseries = function(userOnly) {
        var dataSeries = [];
        var dataDivs = $(".highcharts-data").not(".drilldown-dataseries").map(function(index, div) {
            div = $(div);
            return div !== undefined && div.attr('data-highcharts-data') !== undefined
                ? {
                    id: div.attr('id'),
                    chartclass: div.attr('data-chart-class'),
                    seriestype: div.attr('data-series-type'),
                    json: JSON.parse(div.attr('data-highcharts-data'))
                } : {};
        });
        dataDivs.each(function(index, div) {
            if (div.chartclass === 'readingscompleted' || userOnly) {
                dataSeries[div.chartclass] = div;
            } else {
                dataSeries[div.chartclass + '-' + div.seriestype] = div;
            }
        });
        return dataSeries;
    };

    /**
     * Get the secondary (drilldown) data for the charts on this page from the PHP page (JSON).
     * @returns {[]}
     */
    var get_secondary_dataseries = function() {
        var dataSeries = [];
        var dataDivs = $(".drilldown-dataseries").map(function(index, div) {
            div = $(div);
            return div !== undefined && div.attr('data-highcharts-data') !== undefined
                ? {
                    id: div.attr('id'),
                    chartclass: div.attr('data-chart-class'),
                    data: JSON.parse(div.attr('data-highcharts-data'))
                } : {};
        });
        dataDivs.each(function(index, div) {
            if (div.chartclass !== 'readingscompleted') {
                if (dataSeries[div.chartclass] === undefined) {
                    dataSeries[div.chartclass] = [];
                }
                dataSeries[div.chartclass].push({
                    id: div.id,
                    type: div.data.type,
                    name: div.data.name,
                    data: div.data.data,
                    fillColor: div.data.fillColor,
                    // yaxis: {min: Math.round(0.75 * Math.max.apply(Math, div.data.data.map(function(elem){return elem.y;})))}
                });
            }
        });
        return dataSeries;
    };

    return {
        init: function(courseIdInit, theKlass, userOnly) {

            require.config({
                packages: [{
                    name: 'highcharts',
                    main: 'highcharts'
                }],
                paths: {
                    highcharts: config.wwwroot + '/blocks/readaloudteacher/plugins/highcharts/code'
                }
            });
            courseId = courseIdInit;
            classId = theKlass.id;
            classType = theKlass.type;

            // Load the dependencies for highcharts.
            require(
                [
                    'highcharts',
                    'highcharts/modules/exporting',
                    'highcharts/modules/accessibility',
                    'highcharts/modules/drilldown'
                ],

                // This function runs when the above files have been loaded.
                function(Highcharts, ExportingModule, AccessibilityModule, Drilldown) {

                    hcGlobal = Highcharts;

                    $(document).ready(function() {
                        // We need to initialize module files and pass in Highcharts.
                        ExportingModule(Highcharts);
                        AccessibilityModule(Highcharts);
                        Drilldown(Highcharts);

                        Str.get_strings([
                            {key:  'clickformore', component: 'block_readaloudteacher'},
                            {key:  'percent', component: 'block_readaloudteacher'},
                            {key:  'colwpm', component: 'block_readaloudteacher'},
                            {key:  'readingscompleted', component: 'block_readaloudteacher'},
                            {key:  'benchmarks', component: 'block_readaloudteacher'}
                        ]).done(function(result) {
                            strings.clickformore = result[0];
                            strings.percent = result[1];
                            strings.wpm = result[2];
                            strings.readingscompleted = result[3];
                            strings.benchmarks = result[4];

                            // First the primary data series (not drilldown data).
                            var primaryDataSeries = get_primary_dataseries(userOnly);

                            if (!userOnly) {

                                // We are doing a class report (not user) - they have drilldown data on users.
                                var drillDownSeries = get_secondary_dataseries();

                                // They also have a readings completed by user chart first.
                                drawChart(
                                    'highchart-readings-completed',
                                    [primaryDataSeries.readingscompleted.json],
                                    strings.readingscompleted,
                                    [],
                                    'readingscompleted'
                                );

                                drawChart(
                                    'highchart-wpm',
                                    [
                                        primaryDataSeries['wordsperminute-average'].json,
                                        primaryDataSeries['wordsperminute-latest'].json
                                    ],
                                    strings.wpm,
                                    drillDownSeries.wordsperminute,
                                    'wpm'
                                );

                                drawChart(
                                    'highchart-accuracy',
                                    [primaryDataSeries['accuracy-average'].json, primaryDataSeries['accuracy-latest'].json],
                                    strings.percent,
                                    drillDownSeries.accuracy,
                                    'accuracy'
                                );

                                drawChart(
                                    'highchart-quiz',
                                    [primaryDataSeries['quiz-average'].json, primaryDataSeries['quiz-latest'].json],
                                    strings.percent,
                                    drillDownSeries.quiz,
                                    'quiz'
                                );

                            } else {
                                // We are doing charts for one user only.
                                drawChart(
                                    'highchart-wpm',
                                    [primaryDataSeries.wordsperminute.json],
                                    strings.wpm,
                                    [],
                                    'wpm'
                                );
                                drawChart(
                                    'highchart-quiz',
                                    [primaryDataSeries.quiz.json],
                                    strings.percent,
                                    [],
                                    'quiz'
                                );
                                drawChart(
                                    'highchart-accuracy',
                                    [primaryDataSeries.accuracy.json],
                                    strings.percent,
                                    [],
                                    'accuracy'
                                );
                            }
                        });
                    });
                }
            );
        }
    };
});