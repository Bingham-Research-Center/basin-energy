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

        function removeRecovery(kind, id) {
            // Clear both the pre-autosave ('new') and persistent draft key.
            // Other browser devices cannot be accessed, but their copies
            // cannot update a deleted draft on the server.
            try {
                localStorage.removeItem('brc-broadcast-recovery:' + kind + ':new');
                if (id) localStorage.removeItem('brc-broadcast-recovery:' + kind + ':' + id);
            } catch (error) {
                // Storage can be disabled, independently of server deletion.
            }
        }

        function alertConfirm(title, message, danger) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    title: title,
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: danger ? '#d33' : '#3085d6',
                    confirmButtonText: danger ? 'Discard Draft' : 'Continue',
                    cancelButtonText: 'Cancel'
                }).then(function (result) { return Boolean(result.isConfirmed || result.value); });
            }
            return Promise.resolve(window.confirm(title + '\n' + message));
        }

        function notify(message, icon) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon || 'success',
                    title: message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
            return Promise.resolve();
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

        function recover(state, serverUpdatedAt) {
            // Recovery belongs *inside an already-visible editor*, not in a
            // blocking native dialog over the table behind the popup.
            var recoveryKey = key(state);
            var backupData;
            try {
                var raw = localStorage.getItem(recoveryKey);
                if (!raw) return Promise.resolve(false);
                backupData = JSON.parse(raw);
                if (!backupData || !backupData.values) return Promise.resolve(false);
            } catch (error) {
                return Promise.resolve(false);
            }

            var serverValues = read(state);
            var savedAt = Number(backupData.savedAt) || 0;
            var serverAt = serverUpdatedAt ? Date.parse(serverUpdatedAt) : NaN;
            var identical = JSON.stringify(backupData.values) === JSON.stringify(serverValues);

            // Older autosave backups and identical copies are not unsaved
            // changes. Clear them rather than asking on every Edit click.
            if (identical || (Number.isFinite(serverAt) && savedAt <= serverAt)) {
                clearBackup(state, recoveryKey);
                return Promise.resolve(false);
            }

            var token = state.openToken;
            function offer() {
                if (state.openToken !== token || state.closed || state.discarding) {
                    return Promise.resolve(false);
                }
                if (!window.BrcToasts || typeof window.BrcToasts.confirm !== 'function') {
                    // Do not throw a native window.confirm while the editor is
                    // opening. Keep the browser copy for another attempt.
                    status(state, 'Unsaved browser changes are available for recovery.', true);
                    return Promise.resolve(false);
                }
                return window.BrcToasts.confirm({
                    title: 'Recover unsaved draft?',
                    text: 'This browser has newer changes than the saved draft. Restore them?',
                    icon: 'question',
                    confirmButtonText: 'Recover changes'
                }).then(function (accepted) {
                    if (state.openToken !== token || state.closed) return false;
                    if (accepted) {
                        state.muting = true;
                        try { applyData(state, backupData.values); }
                        finally { state.muting = false; }
                        change(state);
                    } else {
                        // Cancel means use the server version; do not prompt
                        // again for the same stale recovery entry.
                        clearBackup(state, recoveryKey);
                    }
                    return Boolean(accepted);
                });
            }

            if (state.modalShown) return offer();
            return new Promise(function (resolve) {
                $(state.modal).one('shown.bs.modal', function () {
                    Promise.resolve(offer()).then(resolve, function () { resolve(false); });
                });
            });
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
            if (state.muting || state.closed || state.discarding || state.submitting) return;
            state.version++;
            state.validationBlocked = false;
            state.dirty = true;
            backup(state, read(state));
            status(state, 'Unsaved changes — saving shortly…');
            clearTimeout(state.timer);
            state.timer = setTimeout(function () { save(state); }, 1300);
        }

        function save(state) {
            clearTimeout(state.timer);
            if (state.discarding || state.submitting || state.validationBlocked) return Promise.resolve(false);
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
                    if (response.status === 422) {
                        // Laravel returns the field-level validation reason.
                        // A rejected input should not retry every five seconds.
                        return response.json().catch(function () { return {}; }).then(function (body) {
                            var errors = body.errors || {};
                            var keys = Object.keys(errors);
                            var field = keys.length ? keys[0] : null;
                            var detail = field && errors[field] && errors[field].length
                                ? errors[field][0] : (body.message || 'Check the issue month and newsletter content.');
                            var problem = new Error('Cannot autosave: ' + detail);
                            problem.validation = true;
                            throw problem;
                        });
                    }
                    if (response.status === 409) {
                        throw new Error('This issue is already scheduled or belongs to another draft. Open the existing issue instead.');
                    }
                    throw new Error('Autosave failed (HTTP ' + response.status + ').');
                }
                return response.json();
            }).then(function (result) {
                if (state.discarding) {
                    // Discard waits for this request, then deletes the saved
                    // record; never launch another autosave in the interim.
                    state.id = result.id;
                    return true;
                }
                state.id = result.id;
                var discard = state.modal.querySelector('[data-broadcast-discard]');
                if (discard) discard.disabled = false;
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
                state.validationBlocked = Boolean(error.validation) && state.version === version;
                backup(state, read(state));
                status(state, error.message + ' Changes kept in this browser.', true);
                return false;
            }).finally(function () {
                state.inFlight = null;
                if (state.dirty && !state.closed && !state.discarding && !state.validationBlocked) {
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
            state.discarding = false;
            state.closed = false;
            state.submitting = false;
            state.confirming = false;
            state.confirmingSchedule = false;
            state.pendingSubmitter = null;
            state.id = null;
            state.openToken = (state.openToken || 0) + 1;
            state.modalShown = false;
            var discard = state.modal.querySelector('[data-broadcast-discard]');
            if (discard) discard.disabled = false; // A new, browser-only draft can be discarded.
            state.version = 0;
            state.dirty = false;
            state.validationBlocked = false;
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
            var token = state.openToken;
            status(state, 'Loading saved draft…');
            fetch(url, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'},
                cache: 'no-store'
            }).then(function (r) {
                if (!r.ok) throw new Error('Cannot load draft.');
                return r.json();
            }).then(function (data) {
                if (token !== state.openToken || state.closed) return;
                if (!data.editable) {
                    window.location.assign(url);
                    return;
                }
                state.muting = true;
                state.id = data.id;
                var discard = state.modal.querySelector('[data-broadcast-discard]');
                // Scheduled issues may still be edited, but Discard Draft is
                // intentionally reserved for unscheduled drafts.
                if (discard) discard.disabled = !data.delete_url;
                state.form.action = data.update_url;
                var method = state.form.querySelector('[data-broadcast-method]');
                if (method) method.disabled = false;
                var editId = state.form.elements.namedItem('_broadcast_edit_id');
                if (editId) editId.value = String(data.id);
                var render = function () {
                    if (token !== state.openToken || state.closed) return;
                    // Summernote must initialize in the *visible* modal so
                    // toolbar size and the editable area are calculated.
                    var rich = state.form.querySelector('textarea.js-broadcast-rich-editor');
                    if (rich) initEditor(rich, state);
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
                    recover(state, data.updated_at);
                };
                if (state.modalShown) render();
                else $(state.modal).one('shown.bs.modal', render);
            }).catch(function () {
                window.location.assign(url);
            });
        }

        document.querySelectorAll('[data-broadcast-modal]').forEach(function (modal) {
            var form = modal.querySelector('[data-broadcast-editor-form]');
            if (!form) return;
            var state = {modal:modal, form:form, id:null, version:0,
                dirty:false, timer:null, inFlight:null, muting:false,
                closed:false, discarding:false, submitting:false,
                validationBlocked:false, confirming:false, confirmingSchedule:false, pendingSubmitter:null,
                modalShown:false, openToken:0};
            states.set(modal, state);
            var select = form.querySelector('select[name="audience"]');
            if (select) select.addEventListener('change', function () { toggleRoles(form); change(state); });
            // Use one native form submission after any background autosave.
            // Calling requestSubmit() from a submit listener re-enters that
            // listener and used to swallow the administrator's first click.
            // Always preserve intent=publish/intent=draft for Laravel.
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (state.submitting) return;

                var submitter = event.submitter || state.pendingSubmitter || null;
                var publishing = Boolean(submitter && submitter.value === 'publish');
                var confirmation = publishing
                    ? (window.BrcToasts ? window.BrcToasts.confirm({
                        title: 'Publish announcement?',
                        text: 'Publish this announcement to the selected Users now?',
                        icon: 'question',
                        confirmButtonText: 'Publish announcement'
                    }) : alertConfirm('Publish announcement?', 'Publish this announcement to the selected Users now?', false))
                    : Promise.resolve(true);

                state.submitting = true;
                clearTimeout(state.timer);
                Promise.resolve(confirmation).then(function (accepted) {
                    if (!accepted) {
                        state.submitting = false;
                        return;
                    }
                    // A previous autosave must finish first to avoid racing
                    // the final POST/PUT against an older database write.
                    return Promise.resolve(state.inFlight).then(function () {
                        // Summernote's editor is contenteditable, while Laravel
                        // reads the hidden textarea's submitted HTML value.
                        var rich = form.querySelector('textarea.js-broadcast-rich-editor');
                        if (rich && $.fn.summernote && $(rich).next('.note-editor').length) {
                            rich.value = $(rich).summernote('code');
                        }
                        var oldIntent = form.querySelector('input[data-broadcast-submit-intent]');
                        if (oldIntent) oldIntent.remove();
                        if (form.dataset.broadcastEditorForm === 'announcement') {
                            var intent = document.createElement('input');
                            intent.type = 'hidden';
                            intent.name = 'intent';
                            intent.value = publishing ? 'publish' : 'draft';
                            intent.setAttribute('data-broadcast-submit-intent', '');
                            form.appendChild(intent);
                        }
                        state.closed = true;
                        // Native submit does not re-fire this handler, so one
                        // click leads to exactly one request.
                        HTMLFormElement.prototype.submit.call(form);
                    });
                }).catch(function () {
                    state.submitting = false;
                    status(state, 'Could not submit. Your draft is retained for recovery.', true);
                });
            });
            // Track external form-associated buttons for older browsers that
            // do not expose SubmitEvent.submitter.
            state.modal.querySelectorAll('button[form="' + form.id + '"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    state.pendingSubmitter = button;
                });
            });
            form.addEventListener('input', function (event) {
                if (!event.target.classList.contains('js-broadcast-rich-editor')) change(state);
            });
            form.addEventListener('change', function (event) {
                if (!event.target.classList.contains('js-broadcast-rich-editor')) change(state);
            });
            toggleRoles(form);

            $(modal).on('shown.bs.modal', function () {
                state.modalShown = true;
                var el = form.querySelector('textarea.js-broadcast-rich-editor');
                if (el) {
                    initEditor(el, state);
                    // Layout after the Bootstrap transition restores toolbar
                    // measurements on reopened and freshly loaded drafts.
                    if ($.fn.summernote && $(el).next('.note-editor').length) {
                        $(el).summernote('code', $(el).summernote('code'));
                    }
                }
            });
            $(modal).on('hide.bs.modal', function () {
                // Only explicit Close dismisses this static-backdrop modal.
                if (state.discarding) return;
                if (state.dirty) backup(state, read(state));
                save(state);
            });
            $(modal).on('hidden.bs.modal', function () {
                state.closed = true;
                state.modalShown = false;
                state.openToken++;
            });

            var discardButton = modal.querySelector('[data-broadcast-discard]');
            if (discardButton) {
                discardButton.addEventListener('click', function () { discard(state); });
            }

            var schedule = modal.querySelector('[data-broadcast-schedule-form]');
            if (schedule) {
                schedule.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (state.confirmingSchedule) return;
                    state.confirmingSchedule = true;
                    var ask = window.BrcToasts
                        ? window.BrcToasts.confirm({
                            title: 'Schedule newsletter?',
                            text: 'Schedule this newsletter for delivery at the end of its month?',
                            icon: 'question',
                            confirmButtonText: 'Schedule newsletter'
                        })
                        : alertConfirm('Schedule newsletter?', 'Schedule this newsletter for month-end delivery?', false);
                    ask.then(function (confirmed) {
                        if (!confirmed) return false;
                        return (state.dirty || state.inFlight ? save(state) : Promise.resolve(true))
                            .then(function (ok) {
                                if (ok) schedule.submit();
                            });
                    }).catch(function () {
                        status(state, 'Scheduling failed. Draft remains saved.', true);
                    }).finally(function () {
                        state.confirmingSchedule = false;
                    });
                });
            }

            if (window.brcBroadcastReopen === modal.dataset.broadcastModal) {
                $(modal).modal({backdrop:'static', keyboard:false, show:true});
            }
        });


        function discard(state) {
            var kind = state.modal.dataset.broadcastModal;
            return alertConfirm('Discard this draft?', 'This permanently deletes the draft, its local recovery copy, and any unshared draft images. This cannot be undone.', true)
                .then(function (confirmed) {
                    if (!confirmed) return;
                    state.discarding = true;
                    state.closed = true;
                    clearTimeout(state.timer);
                    status(state, 'Discarding draft…');
                    // Allow an already-running autosave to finish, then delete
                    // its resulting row. No autosave is allowed to recreate it.
                    var running = state.inFlight;
                    return Promise.resolve(running).then(function () {
                        var id = state.id;
                        if (!id) {
                            removeRecovery(kind, null);
                            state.dirty = false;
                            $(state.modal).modal('hide');
                            notify('Unsaved draft discarded', 'success');
                            return;
                        }

                        var endpoint = state.form.dataset.broadcastDeleteTemplate
                            .replace('__ID__', encodeURIComponent(id));
                        return fetch(endpoint, {
                            method: 'DELETE',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            }
                        }).then(function (response) {
                            if (!response.ok) throw new Error('Discard failed (HTTP ' + response.status + ').');
                            return response.json();
                        }).then(function () {
                            removeRecovery(kind, id);
                            state.dirty = false;
                            $(state.modal).modal('hide');
                            notify('Draft permanently discarded', 'success');
                            // Refresh the Livewire table. Do not keep a stale
                            // row whose edit link could no longer resolve.
                            window.setTimeout(function () { window.location.reload(); }, 600);
                        });
                    }).catch(function (error) {
                        state.discarding = false;
                        state.closed = false;
                        status(state, error.message + ' Draft kept for recovery.', true);
                        notify(error.message, 'error');
                    });
                });
        }

        function deleteFromTable(form) {
            var kind = form.dataset.broadcastDelete;
            var id = form.dataset.broadcastId;
            return alertConfirm('Delete this draft?', 'This removes the saved draft and its unshared images. You cannot undo this action.', true)
                .then(function (confirmed) {
                    if (!confirmed) return;
                    return fetch(form.action, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    }).then(function (response) {
                        if (!response.ok) throw new Error('Delete failed (HTTP ' + response.status + ').');
                        return response.json();
                    }).then(function () {
                        removeRecovery(kind, id);
                        return notify('Draft deleted', 'success');
                    }).then(function () {
                        window.location.reload();
                    }).catch(function (error) {
                        notify(error.message, 'error');
                    });
                });
        }

        // Delegated because Livewire replaces table rows after search/sort.
        document.addEventListener('submit', function (event) {
            var form = event.target.closest('form[data-broadcast-delete]');
            if (!form) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            deleteFromTable(form);
        }, true);

        document.addEventListener('click', function (event) {
            var create = event.target.closest('[data-broadcast-create]');
            if (create) {
                var modal = document.querySelector('[data-broadcast-modal="' +
                    create.dataset.broadcastCreate + '"]');
                if (!modal) return;
                var state = states.get(modal);
                if (state) {
                    reset(state);
                    $(modal).modal('show');
                    recover(state, null);
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
                $(current).modal('show');
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
