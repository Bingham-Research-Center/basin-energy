/* Run with: node tests/js/role-default-switch.test.js */
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function load(script, context) {
    vm.runInNewContext(
        fs.readFileSync(path.join(__dirname, '../../public/js/', script), 'utf8'),
        context,
        {filename: script}
    );
}

async function run() {
    const fires = [];
    const dashboard = {
        window: {
            Swal: {
                fire: function (config) {
                    fires.push(config);
                    return Promise.resolve({isConfirmed: true});
                }
            }
        },
        document: {getElementById: () => null},
        setTimeout: setTimeout
    };

    load('dashboard-toasts.js', dashboard);
    await dashboard.window.BrcToasts.show('Regular User is now default', 'success', {
        position: 'top-start',
        timer: 3000
    });

    assert.equal(fires[0].toast, true);
    assert.equal(fires[0].position, 'top-start');
    assert.equal(fires[0].timer, 3000);
    assert.equal(fires[0].titleText, 'Regular User is now default');
    assert.equal(fires[0].showConfirmButton, false);

    // Existing dashboards keep the original top-right/3500 ms default.
    await dashboard.window.BrcToasts.show('Normal dashboard toast', 'success');
    assert.equal(fires[1].position, 'top-end');
    assert.equal(fires[1].timer, 3500);

    const listeners = {};
    const events = {};
    const shown = [];
    const page = {
        window: {
            Livewire: {
                on: (event, callback) => {
                    assert.equal(event, 'brc-default-role-updated');
                    listeners[event] = callback;
                }
            },
            BrcToasts: {
                show: (message, icon, options) => shown.push({message, icon, options})
            }
        },
        document: {
            addEventListener: (event, handler) => { events[event] = handler; }
        }
    };

    load('role-default-switch.js', page);
    assert.equal(typeof listeners['brc-default-role-updated'], 'function');
    listeners['brc-default-role-updated']({
        message: 'Researcher is now the default user role.'
    });
    listeners['brc-default-role-updated']({
        detail: {message: 'Default user role cleared.'}
    });
    assert.equal(shown.length, 2);
    assert.equal(shown[0].message, 'Researcher is now the default user role.');
    assert.equal(shown[0].icon, 'success');
    assert.equal(shown[0].options.position, 'top-start');
    assert.equal(shown[0].options.timer, 3000);
    assert.equal(shown[1].message, 'Default user role cleared.');
    events['livewire:init'](); // no duplicate registration
    assert.equal(Object.keys(listeners).length, 1);

    const later = {window: {}, document: {addEventListener: (event, cb) => {later.init = cb;}}};
    load('role-default-switch.js', later);
    later.window.Livewire = {on: (name, cb) => {later.listener = cb;}};
    later.init();
    assert.equal(typeof later.listener, 'function');

    // SweetAlert-less dashboards still use the same requested corner.
    const fallback = [];
    const durations = [];
    const noSwal = {
        window: {},
        document: {
            getElementById: () => null,
            createElement: () => ({
                setAttribute: () => {},
                remove: () => {}
            }),
            body: {appendChild: el => fallback.push(el)}
        },
        setTimeout: (callback, duration) => {durations.push(duration); callback();}
    };
    load('dashboard-toasts.js', noSwal);
    await noSwal.window.BrcToasts.show('Saved', 'success', {
        position: 'top-start', timer: 3000
    });
    assert.match(fallback[0].style.cssText, /left:20px;/);
    assert.equal(durations[0], 3000);

    console.log('Role toggle live toast and top-left timing regression checks passed.');
}

run().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
