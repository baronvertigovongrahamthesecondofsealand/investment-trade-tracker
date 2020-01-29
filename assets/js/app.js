// import 'bootstrap';
global.$ = global.jQuery = require('jquery');
require('../vendor/tablesorter-2.31.2/dist/js/jquery.tablesorter.min');

$(function() {

    let app = {};

    $('table.trade-data').tablesorter();

});
