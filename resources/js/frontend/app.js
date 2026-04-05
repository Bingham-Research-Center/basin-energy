require('../bootstrap');
require('../plugins');
require('./emission-trends');
require('./produced-water');
require('./carbon-mapper-map.js');

import Vue from 'vue';

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

const app = new Vue({
    el: '#app',
});