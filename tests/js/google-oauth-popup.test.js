/* Node-only browser simulation for the OAuth popup.
 * This guards the central security invariant: no same-session HTTP polling
 * while Socialite's stateful Google redirect/Terms/2FA flow is running.
 */
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const script = fs.readFileSync(
    path.join(__dirname, '../../public/frontend/js/google-oauth-popup.js'), 'utf8'
);

function environment(options = {}) {
    let now = 1000000;
    let interval = null;
    let fetchCalls = [];
    let navigations = [];
    let outcome = { complete: false, redirect: null };
    const events = {};
    const broadcast = { channel: null };
    const child = {
        closed: false,
        focus() {},
        close() { this.closed = true; }
    };

    const cancel = {
        setAttribute() {},
        addEventListener(name, callback) { this[name] = callback; }
    };
    const messageNode = { textContent: '' };
    const errorNode = { textContent: '' };
    const panel = {
        hidden: true,
        innerHTML: '',
        className: '',
        setAttribute() {},
        classList: { toggle() {} },
        querySelector(selector) {
            if (selector === '.google-oauth-message') return messageNode;
            if (selector === '.google-oauth-error') return errorNode;
            if (selector === '.google-oauth-cancel') return cancel;
            return null;
        }
    };

    const url = 'https://basinenergy.info';
    const location = {
        origin: url,
        href: url + '/login',
        assign(value) { navigations.push(value); }
    };
    const document = {
        body: { appendChild() {} },
        createElement() { return panel; },
        addEventListener(name, callback) { events['document:' + name] = callback; }
    };
    const window = {
        location,
        screenX: 0,
        screenY: 0,
        outerWidth: 1280,
        outerHeight: 800,
        open() { return options.popupBlocked ? null : child; },
        setInterval(callback) { interval = callback; return 1; },
        clearInterval() { interval = null; },
        setTimeout() { return 1; },
        addEventListener(name, callback) { events['window:' + name] = callback; },
        BroadcastChannel: class {
            constructor(name) {
                assert.equal(name, 'basinenergy-google-oauth');
                broadcast.channel = this;
            }
        }
    };
    class FakeDate extends Date {
        static now() { return now; }
    }
    const context = {
        window, document, location, URL,
        Date: FakeDate, console,
        setTimeout() { return 1; },
        clearTimeout() {},
        fetch: async url => {
            fetchCalls.push(url);
            return { ok: true, json: async () => outcome };
        }
    };
    vm.runInNewContext(script, context, { filename: 'google-oauth-popup.js' });

    const anchor = {
        href: url + '/login/google',
        getAttribute(name) {
            return name === 'data-google-oauth-status'
                ? url + '/login/google/popup/status' : null;
        }
    };
    const click = () => events['document:click']({
        target: { closest: () => anchor },
        button: 0,
        defaultPrevented: false,
        preventDefault() {}
    });
    const signal = (success, method = 'opener') => {
        const data = { type: 'basinenergy.google.oauth.result', success };
        if (method === 'broadcast') {
            assert.ok(broadcast.channel, 'BroadcastChannel should be available');
            broadcast.channel.onmessage({ data });
        } else {
            events['window:message']({
                origin: url,
                source: child,
                data
            });
        }
    };
    return {
        click, signal,
        async tick(ms = 400) {
            now += ms;
            if (interval) await interval();
        },
        child, panel, messageNode, errorNode, fetchCalls, navigations,
        setOutcome(value) { outcome = value; }
    };
}

(async function main() {
    const success = environment();
    success.click();
    for (let n = 0; n < 10; n += 1) await success.tick(400);
    assert.equal(success.fetchCalls.length, 0,
        'must not poll session during Google consent/Terms/2FA');

    success.setOutcome({ complete: false, redirect: null });
    success.signal(true);
    await success.tick(200);
    assert.equal(success.fetchCalls.length, 0, 'callback needs cookie grace period');
    await success.tick(400);
    assert.equal(success.fetchCalls.length, 1);
    assert.equal(success.navigations.length, 0);

    success.setOutcome({
        complete: true,
        redirect: 'https://basinenergy.info/dashboard'
    });
    await success.tick(1000);
    assert.equal(success.fetchCalls.length, 2);
    assert.equal(success.navigations[0], 'https://basinenergy.info/dashboard');
    assert.equal(success.child.closed, true);

    const cancelled = environment();
    cancelled.click();
    cancelled.signal(false);
    await cancelled.tick(1000);
    assert.equal(cancelled.fetchCalls.length, 0);
    assert.equal(cancelled.panel.hidden, false);
    assert.match(cancelled.errorNode.textContent, /not completed/i);

    const coop = environment();
    coop.click();
    coop.setOutcome({
        complete: true,
        redirect: 'https://basinenergy.info/admin/dashboard'
    });
    coop.signal(true, 'broadcast');
    await coop.tick(700);
    assert.equal(coop.navigations[0], 'https://basinenergy.info/admin/dashboard');

    const blocked = environment({ popupBlocked: true });
    blocked.click();
    assert.equal(blocked.navigations[0], 'https://basinenergy.info/login/google');
    await blocked.tick(1500);
    assert.equal(blocked.fetchCalls.length, 0);

    console.log('Google OAuth popup: callback-only polling, cancellation, COOP fallback and redirect checks passed.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
