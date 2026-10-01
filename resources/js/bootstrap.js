window._ = require('lodash');
window.Swal = require('sweetalert2');

try {
    window.Popper = require('popper.js').default;
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) {}

window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: process.env.MIX_REVERB_APP_KEY,
    wsHost: process.env.MIX_REVERB_HOST,
    wsPort: Number(process.env.MIX_REVERB_PORT || 8082),
    wssPort: Number(process.env.MIX_REVERB_PORT || 443),
    forceTLS: (process.env.MIX_REVERB_SCHEME || 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});