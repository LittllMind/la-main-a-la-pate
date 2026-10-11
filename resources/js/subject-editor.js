/* --------------------------------------------------------------------------
 * Subject Markdown editor — Lot 1 UX fixes
 * -------------------------------------------------------------------------- */
import { buildMarkdownRenderer } from './markdown-renderer';

(function () {
    'use strict';

    const editorRoot = document.querySelector('[data-markdown-editor]');
    if (!editorRoot) return;

    const wrapper = editorRoot.closest('form#subject-form');
    const saveButton = wrapper?.querySelector('[data-save-button]');
    const unsavedIndicator = wrapper?.querySelector('[data-unsaved-indicator]');
    const formHasError = wrapper?.dataset.formHasErrors !== undefined;

    const tabButtons = editorRoot.querySelectorAll('[data-editor-tab]');
    const panels = editorRoot.querySelectorAll('[data-editor-panel]');
    const previewAudience = editorRoot.querySelector('[data-preview-audience]');
    const previewEl = editorRoot.querySelector('[data-markdown-preview]');
    const previewModeButtons = editorRoot.querySelectorAll('[data-preview-mode]');

    const fields = {
        title: { el: wrapper?.querySelector('input[name="title"]') },
        category_id: { el: wrapper?.querySelector('select[name="category_id"]') },
        sub_category_id: { el: wrapper?.querySelector('select[name="sub_category_id"]') },
        body: { textareaEl: editorRoot.querySelector('textarea#body') },
        citizen_body: { textareaEl: editorRoot.querySelector('#citizen_body') },
        public_body: { textareaEl: editorRoot.querySelector('#public_body') },
        change_summary: { el: wrapper?.querySelector('input[name="change_summary"]') },
    };

    const audienceLabels = {
        body: 'Instruction',
        citizen_body: 'Public connecté',
        public_body: 'Public déconnecté',
    };

    let currentMode = 'write-preview';
    try {
        currentMode = localStorage.getItem('editor-preview-mode') || 'write-preview';
    } catch {
        // Origine opaque (about:blank, tests isolés) : fallback sur le mode par défaut.
    }

    /* ---------------- Helpers ---------------- */
    function getInitialValue(el) {
        if (!el) return '';
        return el.type === 'checkbox' ? (el.checked ? el.value : '') : el.value;
    }

    function recordInitialValues() {
        Object.values(fields).forEach((field) => {
            if (field.el) field.initial = getInitialValue(field.el);
            if (field.textareaEl) field.initial = getInitialValue(field.textareaEl);
        });
    }

    function getActivePanelName() {
        for (const panel of panels) {
            if (panel.classList.contains('active') && !panel.classList.contains('hidden')) {
                return panel.dataset.editorPanel;
            }
        }
        return 'body';
    }

    function getActiveTextarea() {
        return fields[getActivePanelName()]?.textareaEl || fields.body.textareaEl;
    }

    function isFormDirty() {
        let dirty = formHasError;
        Object.values(fields).forEach((field) => {
            if (field.el && getInitialValue(field.el) !== String(field.initial || '')) dirty = true;
            if (field.textareaEl && getInitialValue(field.textareaEl) !== String(field.initial || '')) dirty = true;
        });
        return dirty;
    }

    function updateDirtyState() {
        if (!unsavedIndicator) return;
        const dirty = isFormDirty();
        unsavedIndicator.classList.toggle('hidden', !dirty);
        unsavedIndicator.setAttribute('aria-hidden', String(!dirty));
        if (saveButton) saveButton.classList.toggle('ring-2', dirty);
    }

    function updatePreviewAudience(name) {
        if (previewAudience) previewAudience.textContent = audienceLabels[name] || name;
    }

    function updatePreviewContent() {
        if (!previewEl) return;
        const active = getActiveTextarea();
        if (!active) return;
        const rendered = buildMarkdownRenderer(active.value);
        previewEl.innerHTML = rendered;
    }

    /* ---------------- Markdown renderer ---------------- */
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ---------------- Tab handling ---------------- */
    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.editorTab;

            tabButtons.forEach((b) => {
                b.classList.remove('border-emerald-600', 'text-slate-700');
                b.classList.add('border-transparent', 'text-slate-500');
                b.removeAttribute('aria-selected');
            });
            btn.classList.add('border-emerald-600', 'text-slate-700');
            btn.classList.remove('border-transparent', 'text-slate-500');
            btn.setAttribute('aria-selected', 'true');

            panels.forEach((p) => {
                const active = p.dataset.editorPanel === target;
                p.classList.toggle('hidden', !active);
                p.classList.toggle('active', active);
            });

            updatePreviewAudience(target);
            updatePreviewContent();
            updateDirtyState();
        });
    });

    /* ---------------- Preview mode ---------------- */
        function setPreviewMode(mode) {
        currentMode = mode;
        try {
            localStorage.setItem('editor-preview-mode', mode);
        } catch {
            // Silencieux sur origine opaque.
        }
        if (!previewEl) return;
        const textareas = editorRoot.querySelectorAll('textarea[data-editor-field]');
        previewModeButtons.forEach((btn) => {
            const selected = btn.dataset.previewMode === mode;
            btn.setAttribute('aria-selected', String(selected));
            btn.classList.toggle('underline', selected);
        });

        if (mode === 'write') {
            previewEl.classList.add('hidden');
            textareas.forEach((t) => t.classList.remove('hidden'));
        } else if (mode === 'preview') {
            previewEl.classList.remove('hidden');
            textareas.forEach((t) => t.classList.add('hidden'));
            updatePreviewContent();
        } else {
            previewEl.classList.remove('hidden');
            textareas.forEach((t) => t.classList.remove('hidden'));
            updatePreviewContent();
        }
    }

    previewModeButtons.forEach((btn) => {
        btn.addEventListener('click', () => setPreviewMode(btn.dataset.previewMode));
    });

    /* ---------------- Toolbar insertion ---------------- */
    function decodeInsertValue(value) {
        return value.replace(/\\n/g, '\n');
    }

    editorRoot.querySelectorAll('button[data-insert]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const textarea = getActiveTextarea();
            if (!textarea) return;
            const raw = btn.dataset.insert;
            insertTextAtCursor(textarea, decodeInsertValue(raw));
            updatePreviewContent();
            updateDirtyState();
        });
    });

    function insertTextAtCursor(textarea, template) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const before = textarea.value.substring(0, start);
        const selected = textarea.value.substring(start, end);
        const after = textarea.value.substring(end);

        const placeholder = /%s/.test(template) ? '%s' : null;
        let text;
        let cursorOffset;
        if (placeholder) {
            text = template.replace(placeholder, selected || 'texte');
            cursorOffset = selected ? selected.length : 'texte'.length;
        } else if (/texte/i.test(template)) {
            // Remplacement litéral du mot "texte" par la sélection si elle existe, sinon curseur au milieu.
            if (selected) {
                text = template.replace(/texte/i, selected);
                cursorOffset = selected.length;
            } else {
                text = template;
                const idx = /texte/i.exec(template).index;
                cursorOffset = 0;
                const len = 'texte'.length;
                // On place le curseur au milieu du placeholder
                textarea.selectionStart = start + idx;
                textarea.selectionEnd = start + idx + len;
            }
        } else {
            text = template;
            cursorOffset = 0;
        }

        textarea.value = before + text + after;

        if (placeholder || selected) {
            const newCursor = start + text.length;
            textarea.selectionStart = textarea.selectionEnd = newCursor;
        } else if (/texte/i.test(template)) {
            const idx = /texte/i.exec(template).index;
            const wordLen = 'texte'.length;
            // Sélectionne le placeholder pour remplacement immédiat par la frappe.
            textarea.selectionStart = start + idx;
            textarea.selectionEnd = start + idx + wordLen;
        }

        textarea.focus();
        const event = new Event('input', { bubbles: true });
        textarea.dispatchEvent(event);
    }

    /* ---------------- Upload ---------------- */
    editorRoot.querySelectorAll('[data-inline-upload]').forEach((input) => {
        input.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const subjectId = input.dataset.subjectId;
            if (!subjectId) return;
            const textarea = getActiveTextarea();
            if (!textarea) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('alt', file.name);

            try {
                const res = await fetch(`/sujets/${subjectId}/upload-image`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: formData,
                });
                const data = await res.json();
                if (data.url) {
                    insertTextAtCursor(textarea, `\n![${data.alt || ''}](${data.url})\n`);
                    updatePreviewContent();
                    updateDirtyState();
                } else {
                    alert('Échec de l\'upload de l\'image.');
                }
            } catch (err) {
                console.error(err);
                alert('Échec de l\'upload de l\'image. Vérifiez la taille et le format.');
            }
            input.value = '';
        });
    });

    /* ---------------- Paste / drag-drop images ---------------- */
    const dropTargets = new Set();
    editorRoot.querySelectorAll('textarea[data-editor-field]').forEach((textarea) => {
        textarea.addEventListener('paste', async (e) => {
            const items = Array.from(e.clipboardData.items || []);
            const image = items.find((it) => it.type.startsWith('image/'));
            if (!image) return;
            e.preventDefault();
            const file = image.getAsFile();
            const subjectId = textarea.closest('[data-markdown-editor]')?.querySelector('[data-inline-upload]')?.dataset.subjectId;
            if (!subjectId || !file) return;
            await uploadAndInsert(textarea, file, subjectId);
        });

        textarea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropTargets.add(textarea);
        });
        textarea.addEventListener('dragleave', (e) => {
            dropTargets.delete(textarea);
        });
        textarea.addEventListener('drop', async (e) => {
            e.preventDefault();
            dropTargets.delete(textarea);
            const file = e.dataTransfer.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            const subjectId = textarea.closest('[data-markdown-editor]')?.querySelector('[data-inline-upload]')?.dataset.subjectId;
            if (!subjectId) return;
            await uploadAndInsert(textarea, file, subjectId);
        });
    });

    async function uploadAndInsert(textarea, file, subjectId) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('alt', file.name);

        try {
            const res = await fetch(`/sujets/${subjectId}/upload-image`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: formData,
            });
            const data = await res.json();
            if (data.url) {
                insertTextAtCursor(textarea, `\n![${data.alt || ''}](${data.url})\n`);
                updatePreviewContent();
                updateDirtyState();
            }
        } catch (err) {
            console.error(err);
            alert('Échec de l\'upload de l\'image.');
        }
    }

    /* ---------------- Dirty state ---------------- */
    wrapper?.querySelectorAll('input, select, textarea').forEach((el) => {
        ['input', 'change'].forEach((eventName) => {
            el.addEventListener(eventName, () => {
                updateDirtyState();
                if (el.matches('textarea[data-editor-field]')) updatePreviewContent();
            });
        });
    });

    /* ---------------- Before unload ---------------- */
    let isSubmitting = false;

    wrapper?.addEventListener('submit', () => {
        isSubmitting = true;
        if (saveButton) saveButton.disabled = true;
        // L'indicateur reste tel quel ; il sera recalculé au retour si erreur,
        // ou la page sera rechargée après succès serveur.
    });

    wrapper?.addEventListener('formdata', () => {
        isSubmitting = true;
        if (saveButton) saveButton.disabled = true;
    });

    window.addEventListener('beforeunload', (e) => {
        if (!isSubmitting && isFormDirty()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    editorRoot.querySelectorAll('[data-confirm-leave]').forEach((link) => {
        link.addEventListener('click', (e) => {
            if (isFormDirty()) {
                const message = 'Des modifications non enregistrées seront perdues. Continuer \u00e0 quitter sans enregistrer ?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                }
            }
        });
    });

    window.addEventListener('pageshow', () => {
        isSubmitting = false;
        if (saveButton) saveButton.disabled = false;
        updateDirtyState();
    });

    if (wrapper?.dataset.formHasErrors !== undefined) {
        updateDirtyState();
    }

    /* ---------------- Init ---------------- */
    recordInitialValues();
    updatePreviewAudience(getActivePanelName());
    updatePreviewContent();
    setPreviewMode(currentMode);
    updateDirtyState();

    // Recalcule le dirty state au retour navigateur (bouton back)
    window.addEventListener('pageshow', updateDirtyState);
    window.__editorLoaded = true;
})();
