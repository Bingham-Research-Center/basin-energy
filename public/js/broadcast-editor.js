(function () {
    'use strict';

    function start() {
        if (!window.jQuery) return;
        var $ = window.jQuery;
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var token = csrf ? csrf.content : '';
        var states = new WeakMap();

        function status(state, text, isError) {
            var el = state.modal.querySelector('[data-broadcast-save-status]');
            if (!el) return;
            el.textContent = text;
            el.classList.toggle('text-danger', Boolean(isError));
            el.classList.toggle('text-muted', !isError);
        }

        function key(state) {
            var kind = state.modal.dataset.broadcastModal;
            return 'brc-broadcast-recovery:' + kind + ':' +
                (state.id || 'new');
        }

        function fieldValue(form, name) {
            var el = form.elements.namedItem(name);
            return el ? el.value : '';
        }

        function read(state) {
            var form = state.form;
            var editor = form.querySelector('.js-broadcast-rich-editor');
            var body = editor && $.fn.summernote && $(editor).next('.note-editor').length
                ? $(editor).summernote('code') : (editor ? editor.value : '');
            return {
                title: fieldValue(form, 'title'),
                issue_month: fieldValue(form, 'issue_month'),
                audience: fieldValue(form, 'audience') || 'all_users',
                role_ids: Array.from(form.querySelectorAll('input[name="role_ids[]"]:checked'))
                    .map(function (el) { return el.value; }),
                body: body
            };
        }

        function setBody(form, html) {
            var editor = form.querySelector('.js-broadcast-rich-editor');
            if (!editor) return;
            if ($.fn.summernote && $(editor).next('.note-editor').length) {
                $(editor).summernote('code', html || '');
            } else {
                editor.value = html || '';
            }
        }

        function toggleRoles(form) {
            var select = form.querySelector('select[name="audience"]');
            var group = form.querySelector('[data-broadcast-role-options]');
            if (select && group) group.style.display = select.value === 'roles' ? '' : 'none';
        }

        function applyData(state, data) {
            var form = state.form;
            ['title', 'issue_month', 'audience'].forEach(function (name) {
                if (data[name] !== undefined && form.elements.namedItem(name)) {
                    form.elements.namedItem(name).value = data[name];
                }
            });
            form.querySelectorAll('input[name="role_ids[]"]').forEach(function (box) {
                box.checked = (data.role_ids || []).map(Number).includes(Number(box.value));
            });
            setBody(form, data.body !== undefined ? data.body : data.body_html || '');
            toggleRoles(form);
        }

        function recover(state) {
            try {
                var raw = localStorage.getItem(key(state));
                if (!raw) return;
                var data = JSON.parse(raw);
                if (!data || !data.values) return;
                if (window.confirm('Recover your unsaved Broadcast editor changes from this browser?')) {
                    state.muting = true;
                    applyData(state, data.values);
                    state.muting = false;
                    change(state);
                }
            } catch (error) {
                // Storage is optional. The server is the primary draft store.
            }
        }

        function backup(state, data) {
            try {
                localStorage.setItem(key(state), JSON.stringify({
                    values: data, savedAt: Date.now()
                }));
            } catch (error) {
                status(state, 'Browser recovery storage is unavailable. Keep this page open.', true);
            }
        }

        function clearBackup(state, savedKey) {
            try { localStorage.removeItem(savedKey || key(state)); } catch (error) {}
        }

        function change(state) {
            if (state.muting || state.closed) return;
            state.version++;
            state.dirty = true;
            backup(state, read(state));
            status(state, 'Unsaved changes — saving shortly…');
            clearTimeout(state.timer);
            state.timer = setTimeout(function () { save(state); }, 1300);
        }

        function save(state) {
            clearTimeout(state.timer);
            if (state.inFlight) {
                return state.inFlight.then(function () {
                    return state.dirty ? save(state) : true;
                });
            }
            if (!state.dirty) return Promise.resolve(true);
            var data = read(state);
            if (state.modal.dataset.broadcastModal === 'newsletter' && !data.issue_month) {
                status(state, 'Saved in this browser. Choose an issue month to save to the server.');
                return Promise.resolve(false);
            }

            var version = state.version;
            var previousKey = key(state);
            var payload = Object.assign({}, data, state.id ? {id: state.id} : {});
            state.dirty = false;
            status(state, 'Saving draft…');
            state.inFlight = fetch(state.form.dataset.autosaveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(function (response) {
                if (!response.ok) {
                    if (response.status === 409) {
                        throw new Error('This draft is scheduled or published. Reload before editing.');
                    }
                    throw new Error('Autosave failed (HTTP ' + response.status + ').');
                }
                return response.json();
            }).then(function (result) {
                state.id = result.id;
                var editId = state.form.elements.namedItem('_broadcast_edit_id');
                if (editId) editId.value = String(result.id);
                var method = state.form.querySelector('[data-broadcast-method]');
                if (method) method.disabled = false;

                // Explicit Save Draft now updates the existing record.
                state.form.action = result.edit_url.replace(/\/edit$/, '');
                var schedule = state.modal.querySelector('[data-broadcast-schedule-form]');
                if (schedule && result.schedule_url) {
                    schedule.action = result.schedule_url;
                    schedule.classList.remove('d-none');
                }
                if (state.version === version) {
                    clearBackup(state, previousKey);
                    clearBackup(state);
                    status(state, 'Saved automatically at ' +
                        new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}));
                } else {
                    // A new edit arrived during the request; keep its backup.
                    backup(state, read(state));
                    state.dirty = true;
                    status(state, 'Saving recent changes…');
                }
                return true;
            }).catch(function (error) {
                state.dirty = true;
                backup(state, read(state));
                status(state, error.message + ' Changes kept in this browser.', true);
                return false;
            }).finally(function () {
                state.inFlight = null;
                if (state.dirty && !state.closed) {
                    clearTimeout(state.timer);
                    state.timer = setTimeout(function () { save(state); }, 5000);
                }
            });
            return state.inFlight;
        }

        function csrfUpload(file, state, editor) {
            var payload = new FormData();
            payload.append('image', file);
            status(state, 'Uploading image…');
            return fetch(state.form.dataset.imageUploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
                body: payload
            }).then(function (res) {
                if (!res.ok) throw new Error('Image upload failed (HTTP ' + res.status +
                    '). Choose a JPG, PNG, GIF or WebP image under 3 MB.');
                return res.json();
            }).then(function (result) {
                editor.summernote('insertImage', result.url);
                change(state);
            }).catch(function (err) {
                status(state, err.message, true);
                window.alert(err.message);
            });
        }

        function initEditor(element, state) {
            var editor = $(element);
            if (!$.fn.summernote || editor.next('.note-editor').length) return;
            editor.summernote({
                height: 300,
                placeholder: 'Write here...',
                dialogsInBody: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onChange: function () { if (state) change(state); },
                    onImageUpload: function (files) {
                        if (!files || !files.length) return;
                        if (!state) {
                            window.alert('Use the Broadcast popup to upload images.');
                            return;
                        }
                        Array.from(files).forEach(function (file) {
                            csrfUpload(file, state, editor);
                        });
                    }
                }
            });
        }

        function reset(state) {
            state.muting = true;
            state.closed = false;
            state.id = null;
            state.version = 0;
            state.dirty = false;
            clearTimeout(state.timer);
            state.form.reset();
            state.form.action = state.form.dataset.createUrl;
            var method = state.form.querySelector('[data-broadcast-method]');
            if (method) method.disabled = true;
            var editId = state.form.elements.namedItem('_broadcast_edit_id');
            if (editId) editId.value = '';
            setBody(state.form, '');
            var schedule = state.modal.querySelector('[data-broadcast-schedule-form]');
            if (schedule) {
                schedule.classList.add('d-none');
                schedule.action = '#';
            }
            var title = state.modal.querySelector('[data-broadcast-modal-title]');
            if (title) title.textContent = state.modal.dataset.broadcastModal === 'newsletter'
                ? 'Create monthly newsletter' : 'Create announcement';
            toggleRoles(state.form);
            status(state, 'Changes are saved automatically while you work.');
            state.muting = false;
        }

        function load(state, url) {
            reset(state);
            status(state, 'Loading saved draft…');
            fetch(url, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'},
                cache: 'no-store'
            }).then(function (r) {
                if (!r.ok) throw new Error('Cannot load draft.');
                return r.json();
            }).then(function (data) {
                if (!data.editable) {
                    window.location.assign(url);
                    return;
                }
                state.muting = true;
                state.id = data.id;
                state.form.action = data.update_url;
                var method = state.form.querySelector('[data-broadcast-method]');
                if (method) method.disabled = false;
                var editId = state.form.elements.namedItem('_broadcast_edit_id');
                if (editId) editId.value = String(data.id);
                applyData(state, data);
                var schedule = state.modal.querySelector('[data-broadcast-schedule-form]');
                if (schedule && data.schedule_url) {
                    schedule.action = data.schedule_url;
                    schedule.classList.remove('d-none');
                }
                var title = state.modal.querySelector('[data-broadcast-modal-title]');
                if (title) title.textContent = 'Edit draft: ' + (data.title || 'Untitled');
                state.muting = false;
                status(state, 'Saved draft loaded. Changes will autosave.');
                recover(state);
            }).catch(function () {
                window.location.assign(url);
            });
        }

        document.querySelectorAll('[data-broadcast-modal]').forEach(function (modal) {
            var form = modal.querySelector('[data-broadcast-editor-form]');
            if (!form) return;
            var state = {modal:modal, form:form, id:null, version:0,
                dirty:false, timer:null, inFlight:null, muting:false, closed:false};
            states.set(modal, state);
            var select = form.querySelector('select[name="audience"]');
            if (select) select.addEventListener('change', function () { toggleRoles(form); change(state); });
            form.addEventListener('input', function (event) {
                if (!event.target.classList.contains('js-broadcast-rich-editor')) change(state);
            });
            form.addEventListener('change', function (event) {
                if (!event.target.classList.contains('js-broadcast-rich-editor')) change(state);
            });
            toggleRoles(form);

            $(modal).on('shown.bs.modal', function () {
                var el = form.querySelector('textarea.js-broadcast-rich-editor');
                if (el) initEditor(el, state);
            });
            $(modal).on('hide.bs.modal', function () {
                // A static backdrop + disabled Escape means only explicit Close
                // buttons dismiss the popup. Keep a local backup if saving fails.
                if (state.dirty) backup(state, read(state));
                save(state);
            });
            $(modal).on('hidden.bs.modal', function () {
                state.closed = true;
            });

            var schedule = modal.querySelector('[data-broadcast-schedule-form]');
            if (schedule) {
                schedule.addEventListener('submit', function (event) {
                    if (state.dirty || state.inFlight) {
                        event.preventDefault();
                        save(state).then(function (ok) {
                            if (ok) schedule.submit();
                        });
                    }
                });
            }

            if (window.brcBroadcastReopen === modal.dataset.broadcastModal) {
                $(modal).modal({backdrop:'static', keyboard:false, show:true});
            }
        });

        document.addEventListener('click', function (event) {
            var create = event.target.closest('[data-broadcast-create]');
            if (create) {
                var modal = document.querySelector('[data-broadcast-modal="' +
                    create.dataset.broadcastCreate + '"]');
                if (!modal) return;
                var state = states.get(modal);
                if (state) {
                    reset(state);
                    recover(state);
                    $(modal).modal({backdrop:'static', keyboard:false});
                }
                event.preventDefault();
                return;
            }
            var edit = event.target.closest('[data-broadcast-edit]');
            if (!edit) return;
            var current = document.querySelector('[data-broadcast-modal="' +
                edit.dataset.broadcastEdit + '"]');
            if (!current) return;
            event.preventDefault();
            var context = states.get(current);
            if (context) {
                $(current).modal({backdrop:'static', keyboard:false});
                load(context, edit.href);
            }
        });

        // Before a navigation/reload, keep a local copy without delaying exit.
        window.addEventListener('pagehide', function () {
            document.querySelectorAll('[data-broadcast-modal]').forEach(function (modal) {
                var state = states.get(modal);
                if (state && state.dirty) backup(state, read(state));
            });
        });

        document.querySelectorAll('textarea.js-broadcast-rich-editor').forEach(function (el) {
            if (!el.closest('.broadcast-editor-modal')) initEditor(el, null);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, {once:true});
    } else {
        start();
    }
}());
