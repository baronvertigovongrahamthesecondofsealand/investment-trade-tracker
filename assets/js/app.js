// import 'bootstrap';
global.$ = global.jQuery = require('jquery');
require('../vendor/tablesorter-2.31.2/dist/js/jquery.tablesorter.min');
require('../vendor/tablesorter-2.31.2/dist/js/jquery.tablesorter.widgets.js');
require('../vendor/tablesorter-2.31.2/dist/js/widgets/widget-chart.min.js');

google.load("visualization", "1.1", {
    packages: ["bar", "corechart", "line"]
});

app = {};

$(function() {

    app.table           = $('table.trade-data.data-sortable');
    app.chartSelect     = $('#chartSelect');
    app.chartContainer  = $('#chart-container');
    app.gainCalc        = $('#targetGainCalc');

    app.chartSettings = {
        chart : $('#chart')[0],
        chartTitle : 'Returns (%)',
        axisTitle : '',
        type : 'hbar',
        processor: function(data) {
            let newData = [];
            $.each(data, function(idx, el) {
                if (idx === 0 || el[1] > 0) {
                    newData.push([
                        el[0],
                        el[7]
                    ]);
                }
            });
            return newData;
        },
    };
    app.chartTypes = {
        pie3D  : { in3D: true,  maxCol: 2, stack: false, type: 'pie',  titleStyle: { color: '#333' }, icon: 'fa-cube' },
        pie    : { in3D: false, maxCol: 2, stack: false, type: 'pie',  titleStyle: { color: '#333' }, icon: 'fa-pie-chart' },
        line   : { in3D: false, maxCol: 99,stack: false, type: 'line', titleStyle: { color: '#333' }, icon: 'fa-line-chart' },
        area   : { in3D: false, maxCol: 5, stack: false, type: 'area', titleStyle: { color: '#333' }, icon: 'fa-area-chart' },
        vbar   : { in3D: false, maxCol: 5, stack: false, type: 'vbar', titleStyle: { color: '#333' }, icon: 'fa-bar-chart' },
        vstack : { in3D: false, maxCol: 5, stack: true,  type: 'vbar', titleStyle: { color: '#333' }, icon: 'fa-tasks fa-rotate-90' },
        hbar   : { in3D: false, maxCol: 5, stack: false, type: 'hbar', titleStyle: { color: '#333' }, icon: 'fa-align-left' },
        hstack : { in3D: false, maxCol: 5, stack: true,  type: 'hbar', titleStyle: { color: '#333' }, icon: 'fa-tasks fa-rotate-180' }
    };

    app.drawChart = function() {
        if (!app.table[0].config) {
            return;
        }

        var options, chart, data,
            chartType   = app.chartTypes[app.chartSettings.type],
            rawdata     = app.table[0].config.chart.data;

        if ( $.isFunction( app.chartSettings.processor ) ) {
            rawdata = app.chartSettings.processor( rawdata );
        }
        if ( rawdata.length < 2 ) {
            return;
        }

        data = google.visualization.arrayToDataTable( rawdata );

        var numofcols = rawdata[1].length;
        if (numofcols > chartType.maxCol) {
            // default to line chart if too many columns selected
            chartType = app.types['line'];
        }

        options = {
            title: app.chartSettings.chartTitle,
            chart: {
                title: app.chartSettings.chartTitle
            },
            hAxis: {
                title: app.chartSettings.axisTitle,
                titleTextStyle: chartType.titleStyle
            },
            vAxis: {},
            is3D: chartType.in3D,
            isStacked: chartType.stack,
            width: document.body.clientWidth -200,
            height: 600
        };

        if (chartType.type === 'vbar' && !chartType.stack) {
            chart = new google.charts.Bar(app.chartSettings.chart);
        } else if (chartType.type === 'vbar') {
            chart = new google.visualization.ColumnChart(app.chartSettings.chart);
        } else if (chartType.type === 'hbar') {
            options.hAxis = {};
            options.vAxis = {
                title: app.chartSettings.axisTitle,
                titleTextStyle: chartType.titleStyle,
                minValue: 0
            };
            chart = new google.visualization.BarChart(app.chartSettings.chart);
        } else if (chartType.type === 'area') {
            chart = new google.visualization.AreaChart(app.chartSettings.chart);
        } else if (chartType.type === 'line') {
            chart = new google.charts.Line(app.chartSettings.chart);
        } else {
            chart = new google.visualization.PieChart(app.chartSettings.chart);
        }
        chart.draw(data, options);
    };

    app.table.tablesorter({
        widgets: ['chart'],
        widgetOptions: {
            chart_incRows: 'f',
            chart_useSelector: true
        }
    });

    app.chartSelect.change(function() {
        app.chartContainer.slideToggle(
            $(this).is(':checked')
        );

        app.table.trigger('chartData');
        app.drawChart();
    });

    if (app.chartSelect.prop('checked')) {
        app.chartSelect.trigger('change');
    }

    app.gainCalc.on('keyup', function(e) {
        let gainVal = $(this).val();

        if (!gainVal) {
            return false;
        }

        let gainMul = gainVal /100;

        let calcVal = $(this).data('buyprice') *gainMul;

        console.log(calcVal);

        $('#form_longTarget, #form_shortTarget, #form_callTarget').val(calcVal);
    });

});
