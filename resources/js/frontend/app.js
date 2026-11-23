require('../bootstrap');
require('../plugins');

import Vue from 'vue';

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

const app = new Vue({
    el: '#app',
});

import '../dashboard/charts';
import '../dashboard/admin-overview';
import '../dashboard/horsepool';
import '../dashboard/realtime-ozone';