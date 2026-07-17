require('../bootstrap');
require('../plugins');
require('./emission-trends');
require('./produced-water');
require('./carbon-mapper-map.js');
require('./subsurface-natural-gas-leaks.js');
require('./oil-well-pad-emissions.js');

import Vue from 'vue';

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

const app = new Vue({
    el: '#app',
});

import '../dashboard/theme';
import '../dashboard/charts';
import '../dashboard/admin-overview';
import '../dashboard/horsepool';
import '../dashboard/realtime-ozone';