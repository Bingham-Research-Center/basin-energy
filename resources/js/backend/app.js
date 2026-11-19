// import 'alpinejs'
// import './bootstrap';

// window.$ = window.jQuery = require('jquery');
// window.Swal = require('sweetalert2');

// // CoreUI
// require('@coreui/coreui');

// // Boilerplate
// require('../plugins');

import 'alpinejs';
import '../bootstrap';

window.$ = window.jQuery = require('jquery');
window.Swal = require('sweetalert2');

require('@coreui/coreui');
require('../plugins');

import '../dashboard/charts';
import '../dashboard/admin-overview';
import '../dashboard/horsepool';
import '../dashboard/realtime-ozone';

// Shared authenticated research dashboards (also used by frontend Users).
// Modules initialize only when their page-specific DOM element exists.
import '../frontend/emission-trends';
import '../frontend/produced-water';
import '../frontend/carbon-mapper-map';
import '../frontend/subsurface-natural-gas-leaks';
import '../frontend/oil-well-pad-emissions';