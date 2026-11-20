'use strict';

// Browser-free regression test for the popup's successful reset-link request.
// The HTTP endpoint and email notification are independently tested in PHPUnit.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(
    path.join(__dirname, '../../public/frontend/js/popup-auth.js'),
    'utf8'
);
const handlers = {};
const confirmationText = 'If an account exists with that email address, you will receive a password reset link shortly. Please check your inbox and spam folder.';
const confirmationMessage = { textContent: '' };
const confirmation = {
    hidden: true,
    querySelector(selector) {
        return selector === '[data-password-reset-message]' ? confirmationMessage : null;
    }
};
const classes = new Set(['show-reset']);
function classList(store) {
    return {
        contains(name) { return store.has(name); },
        add(name) { store.add(name); },
        remove(...names) { names.forEach(name => store.delete(name)); }
    };
}
const buttonClasses = new Set();
const button = {
    classList: classList(buttonClasses),
    dataset: {},
    disabled: false,
    innerHTML: 'Send Reset Link',
    getAttribute(name) {
        return name === 'data-loading-text' ? 'Sending reset link...' : null;
    }
};
const form = {
    action: 'https://basinenergy.info/password/email',
    method: 'POST',
    hidden: false,
    hasAttribute(name) { return name === 'data-password-reset-form'; },
    querySelector(selector) {
        return selector === '.auth-submit-btn' ? button : null;
    },
    querySelectorAll() { return []; },
    reset() {}
};
const popup = {
    classList: classList(classes),
    querySelector(selector) {
        if (selector === '#password-reset-confirmation') return confirmation;
        if (selector === '.form-box.reset form[data-password-reset-form]') return form;
        return null;
    },
    querySelectorAll(selector) {
        return selector === '.auth-submit-btn' ? [button] : [];
    },
    addEventListener(name, fn) { handlers['popup:' + name] = fn; }
};
const bodyClasses = new Set(['show-popup']);
const document = {
    body: { classList: classList(bodyClasses) },
    querySelector(selector) {
        if (selector === '.form-popup') return popup;
        if (selector === 'meta[name="csrf-token"]') return { content: 'test-csrf' };
        return null;
    },
    querySelectorAll() { return []; },
    getElementById() { return null; },
    addEventListener(name, fn) {
        // DOM supports multiple listeners per event, including separate
        // handlers for view navigation and verification-email resend.
        const key = 'document:' + name;
        if (!handlers[key]) handlers[key] = [];
        handlers[key].push(fn);
    }
};
const location = { href: 'https://basinenergy.info/' };
const window = { location };
let response = {
    ok: true, status: 200,
    async json() { return { message: confirmationText }; }
};

vm.runInNewContext(source, {
    document,
    window,
    FormData: class { constructor(element) { assert.equal(element, form); } },
    fetch: async (url, options) => {
        assert.equal(url, form.action);
        assert.equal(options.method, 'POST');
        assert.equal(options.headers['Accept'], 'application/json');
        return response;
    },
    setTimeout() {},
    clearTimeout() {},
    console
}, { filename: 'popup-auth.js' });

handlers['document:DOMContentLoaded'].forEach(listener => listener());

async function submit() {
    await handlers['popup:submit']({
        target: { closest(selector) { return selector === 'form' ? form : null; } },
        preventDefault() {}
    });
}

(async () => {
    await submit();

    assert.equal(form.hidden, true, 'successful request hides only reset form');
    assert.equal(confirmation.hidden, false, 'confirmation is visible');
    assert.equal(confirmationMessage.textContent, confirmationText);
    assert.equal(bodyClasses.has('show-popup'), true, 'dialog remains open');
    assert.equal(classes.has('show-reset'), true, 'reset view remains selected');
    assert.equal(window.location.href, 'https://basinenergy.info/', 'does not navigate');
    assert.equal(button.disabled, false, 'button is reset after processing');

    // Returning to the forgot-password form hides the old message.
    const click = {
        target: { matches(selector) { return selector === '.forgot-pass-link'; } },
        preventDefault() {}
    };
    for (const listener of handlers['document:click']) {
        await listener(click);
    }
    assert.equal(form.hidden, false, 'new attempt restores form');
    assert.equal(confirmation.hidden, true, 'new attempt clears old result');

    console.log('Forgot-password popup: success stays visible and reopening restores form.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
