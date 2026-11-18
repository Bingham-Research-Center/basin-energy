(function () {
    'use strict';

    function start() {
        if (!window.jQuery) return;
        var $ = window.jQuery;

        function initRichEditor(element) {
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
                    onImageUpload: function () {
                        // Images must be linked using a hosted HTTPS image URL.
                        // Prevent embedding huge base64 data or uploading to
                        // a blog-only upload endpoint.
                        window.alert('Please use Insert image and paste a hosted HTTPS image URL.');
                    }
                }
            });
        }

        function initEditors(parent) {
            $(parent).find('textarea.js-broadcast-rich-editor').each(function () {
                initRichEditor(this);
            });
        }

        function toggleRoles(form) {
            var selection = form.querySelector('select[name="audience"]');
            var group = form.querySelector('[data-broadcast-role-options]');
            if (!selection || !group) return;
            group.style.display = selection.value === 'roles' ? '' : 'none';
        }

        function clearModal(modal) {
            var form = modal.querySelector('form[data-broadcast-editor-form]');
            if (!form) return;
            form.reset();
            form.action = form.dataset.createUrl;
            var method = form.querySelector('[data-broadcast-method]');
            if (method) method.disabled = true;
            var editId = form.querySelector('input[name="_broadcast_edit_id"]');
            if (editId) editId.value = '';
            var rich = form.querySelector('.js-broadcast-rich-editor');
            if (rich && $.fn.summernote && $(rich).next('.note-editor').length) {
                $(rich).summernote('code', '');
            } else if (rich) {
                rich.value = '';
            }
            var scheduleForm = modal.querySelector('[data-broadcast-schedule-form]');
            if (scheduleForm) {
                scheduleForm.action = '#';
                scheduleForm.classList.add('d-none');
            }
            var title = modal.querySelector('[data-broadcast-modal-title]');
            if (title) title.textContent = modal.dataset.broadcastModal === 'newsletter'
                ? 'Create monthly newsletter' : 'Create announcement';
            toggleRoles(form);
        }

        function loadExisting(modal, url) {
            clearModal(modal);
            var form = modal.querySelector('form[data-broadcast-editor-form]');
            if (!form) return;
            var title = modal.querySelector('[data-broadcast-modal-title]');
            if (title) title.textContent = 'Loading draft...';
            fetch(url, {
                credentials: 'same-origin',
                headers: {Accept: 'application/json'},
                cache: 'no-store'
            }).then(function (res) {
                if (!res.ok) throw new Error('Could not load this draft.');
                return res.json();
            }).then(function (data) {
                // If sending started while a table was open, fall back to
                // the read-only server-rendered page.
                if (!data.editable) {
                    window.location.assign(url);
                    return;
                }
                form.action = data.update_url;
                var method = form.querySelector('[data-broadcast-method]');
                if (method) method.disabled = false;
                var id = form.querySelector('input[name="_broadcast_edit_id"]');
                if (id) id.value = String(data.id);
                ['title', 'issue_month', 'audience'].forEach(function (name) {
                    var field = form.elements.namedItem(name);
                    if (field && data[name] != null) field.value = data[name];
                });
                form.querySelectorAll('input[name="role_ids[]"]').forEach(function (box) {
                    box.checked = (data.role_ids || []).map(Number).includes(Number(box.value));
                });
                var rich = form.querySelector('.js-broadcast-rich-editor');
                if (rich) {
                    if ($.fn.summernote) {
                        initRichEditor(rich);
                        $(rich).summernote('code', data.body_html || '');
                    } else {
                        rich.value = data.body_html || '';
                    }
                }
                var scheduleForm = modal.querySelector('[data-broadcast-schedule-form]');
                if (scheduleForm && data.schedule_url) {
                    scheduleForm.action = data.schedule_url;
                    scheduleForm.classList.remove('d-none');
                }
                if (title) title.textContent = 'Edit draft: ' + (data.title || 'Untitled');
                toggleRoles(form);
            }).catch(function () {
                window.location.assign(url);
            });
        }

        document.querySelectorAll('[data-broadcast-modal]').forEach(function (modal) {
            var form = modal.querySelector('form[data-broadcast-editor-form]');
            if (form) {
                var select = form.querySelector('select[name="audience"]');
                if (select) select.addEventListener('change', function () { toggleRoles(form); });
                toggleRoles(form);
            }

            $(modal).on('shown.bs.modal', function () {
                initEditors(modal);
            });

            // A validated form POST redirects back to the list; preserving
            // input on error allows the admin to fix it without losing work.
            if (window.brcBroadcastReopen === modal.dataset.broadcastModal) {
                $(modal).modal('show');
            }
        });

        document.addEventListener('click', function (event) {
            var create = event.target.closest('[data-broadcast-create]');
            if (create) {
                var modal = document.querySelector('[data-broadcast-modal="' +
                    create.dataset.broadcastCreate + '"]');
                if (modal) clearModal(modal);
                return;
            }
            var edit = event.target.closest('[data-broadcast-edit]');
            if (!edit) return;
            var modalEl = document.querySelector('[data-broadcast-modal="' +
                edit.dataset.broadcastEdit + '"]');
            if (!modalEl) return;
            event.preventDefault();
            loadExisting(modalEl, edit.href);
            $(modalEl).modal('show');
        });

        // Hidden modal editors initialize only when shown so Summernote can measure the toolbar.
        document.querySelectorAll('textarea.js-broadcast-rich-editor').forEach(function (element) {
            if (!element.closest('.broadcast-editor-modal')) initRichEditor(element);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, {once: true});
    } else {
        start();
    }
}());
