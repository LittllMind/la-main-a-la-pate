document.addEventListener('DOMContentLoaded', () => {
    setupThemeToggle();
    setupMarkdownEditors();
    setupInlineImageUploads();
});

function setupThemeToggle() {
    document.querySelectorAll('[data-theme-toggle]').forEach(select => {
        const wrapper = document.getElementById('theme-other-wrapper');
        if (! wrapper) return;
        select.addEventListener('change', () => {
            wrapper.classList.toggle('hidden', select.value !== '__new__');
        });
    });
}

function setupMarkdownEditors() {
    document.querySelectorAll('[data-markdown-editor]').forEach(container => {
        const textareas = [...container.querySelectorAll('textarea[data-editor-field]')];
        const preview = container.querySelector('[data-markdown-preview]');
        if (! textareas.length || ! preview) return;

        const form = container.closest('form');
        const renderer = buildMarkdownRenderer();
        let activeTextarea = textareas.find(textarea => ! textarea.closest('.hidden')) || textareas[0];
        let dirty = form?.dataset.formHasErrors !== undefined;

        // Track all editable fields in the parent form so that a change in the
        // title, categories, audience selects, change summary or any textarea
        // sets the dirty state.
        const trackedFields = form
            ? [...form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]), textarea, select')]
            : textareas;
        const initialValues = new Map(trackedFields.map(field => {
            const value = field.type === 'checkbox' || field.type === 'radio' ? field.checked : field.value;
            return [field, value];
        }));

        const indicator = container.querySelector('[data-unsaved-indicator]') || form?.querySelector('[data-unsaved-indicator]');
        const saveButton = container.querySelector('[data-save-button]') || form?.querySelector('[data-save-button]');

        const isDirtyNow = () => trackedFields.some(field => {
            const current = field.type === 'checkbox' || field.type === 'radio' ? field.checked : field.value;
            return current !== initialValues.get(field);
        });

        const refresh = () => {
            preview.innerHTML = activeTextarea.value.trim()
                ? renderer.render(activeTextarea.value)
                : '<p class="text-slate-500 italic">Aucun contenu pour cette version</p>';
            preview.closest('[data-preview-panel]')?.classList.remove('hidden');
        };

        const setDirty = value => {
            dirty = value;
            indicator?.classList.toggle('hidden', ! value);
            indicator?.setAttribute('aria-hidden', value ? 'false' : 'true');
            saveButton?.classList.toggle('ring-2', value);
            saveButton?.classList.toggle('ring-amber-300', value);
        };

        const setActive = textarea => {
            activeTextarea = textarea;
            container.dataset.activeEditor = textarea.id;
            refresh();
        };

        textareas.forEach(textarea => {
            textarea.addEventListener('focus', () => setActive(textarea));
            textarea.addEventListener('input', () => {
                setActive(textarea);
                setDirty(isDirtyNow());
            });
        });

        if (form) {
            // Track non-textarea fields (title, selects, change summary, etc.)
            form.addEventListener('change', () => setDirty(isDirtyNow()), true);
            form.addEventListener('submit', () => {
                if (! form.dataset.formHasErrors) {
                    setDirty(false);
                }
            });
        }

        container.querySelectorAll('[data-insert]').forEach(button => {
            button.title = button.dataset.tip || button.title;
            button.addEventListener('click', () => insertToolbarText(activeTextarea, button.dataset.insert));
        });

        container.querySelectorAll('[data-editor-tab]').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = container.querySelector(`#${CSS.escape(tab.dataset.editorTab)}-panel`);
                if (! target) return;
                const mode = container.dataset.previewMode || 'write';
                container.querySelectorAll('[data-editor-tab]').forEach(item => {
                    const selected = item === tab;
                    item.setAttribute('aria-selected', selected ? 'true' : 'false');
                    item.classList.toggle('border-emerald-600', selected);
                    item.classList.toggle('text-slate-700', selected);
                    item.classList.toggle('border-transparent', ! selected);
                    item.classList.toggle('text-slate-500', ! selected);
                });
                container.querySelectorAll('[data-editor-panel]').forEach(panel => {
                    panel.classList.toggle('hidden', panel.dataset.editorPanel !== target.dataset.editorPanel || mode === 'preview');
                });
                setActive(target.querySelector('[data-editor-field]'));
                target.querySelector('[data-editor-field]').focus();
            });
        });

        container.querySelectorAll('[data-preview-mode]').forEach(button => {
            button.addEventListener('click', () => {
                const mode = button.dataset.previewMode;
                container.dataset.previewMode = mode;
                container.querySelectorAll('[data-preview-mode]').forEach(item => {
                    item.setAttribute('aria-selected', item === button ? 'true' : 'false');
                });
                preview.closest('[data-preview-panel]')?.classList.toggle('hidden', mode === 'write');
                const isSingleEditor = container.querySelectorAll('[data-editor-field]').length === 1;
                container.querySelectorAll('[data-editor-panel]').forEach(panel => {
                    const isActive = panel.dataset.editorPanel === activeTextarea.id;
                    panel.classList.toggle('hidden', (isSingleEditor ? mode === 'preview' : (mode === 'preview' || ! isActive)));
                });
            });
        });

        (form?.querySelector('[data-cancel-link]') || container.querySelector('[data-cancel-link]'))?.addEventListener('click', event => {
            if (dirty && ! window.confirm('Modifications non enregistrées. Quitter sans enregistrer ?')) {
                event.preventDefault();
            }
        });
        window.addEventListener('beforeunload', event => {
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        refresh();
        setDirty(dirty);
    });
}

function setupInlineImageUploads() {
    document.querySelectorAll('[data-inline-upload]').forEach(input => {
        const subjectId = input.dataset.subjectId;
        const editor = input.closest('[data-markdown-editor]');
        const textarea = editor?.querySelector('textarea[data-editor-field]');
        if (! subjectId || ! textarea) return;
        input.addEventListener('change', async event => {
            const file = event.target.files[0];
            if (! file) return;
            await uploadInlineImage(file, textarea, subjectId);
            input.value = '';
        });
    });
}

async function uploadInlineImage(file, textarea, subjectId) {
    const form = new FormData();
    form.append('file', file);
    form.append('alt', file.name.replace(/\.[^/.]+$/, ''));
    try {
        const response = await fetch(`/sujets/${subjectId}/upload-image`, {
            method: 'POST',
            body: form,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        });
        if (! response.ok) throw new Error('Erreur serveur');
        const data = await response.json();
        insertTextAtCursor(textarea, `\n![${data.alt || file.name}](${data.url})\n`);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    } catch (error) {
        alert("L'image n'a pas pu être ajoutée.");
    }
}

function buildMarkdownRenderer() {
    return {
        render(text) {
            const lines = text.replace(/\r\n/g, '\n').split('\n');
            const blocks = [];
            let index = 0;
            while (index < lines.length) {
                if (! lines[index].trim()) { index++; continue; }
                const list = renderList(lines, index, countIndent(lines[index]));
                if (list) { blocks.push(list.html); index = list.next; continue; }
                const table = renderTable(lines, index);
                if (table) { blocks.push(table.html); index = table.next; continue; }
                // Thematic break (---, ***, ___)
                if (/^ {0,3}([-_*])(?:\s*\1){2,}\s*$/.test(lines[index])) {
                    blocks.push('<hr>');
                    index++; continue;
                }

                const heading = lines[index].match(/^(#{1,6})\s+(.*)$/);
                if (heading) {
                    const headingText = heading[2].trim();
                    if (headingText) {
                        blocks.push(`<h${heading[1].length}>${inlineMarkdown(headingText)}</h${heading[1].length}>`);
                    }
                    index++; continue;
                }
                if (/^>\s?/.test(lines[index])) {
                    const quote = [];
                    while (index < lines.length && /^>\s?/.test(lines[index])) quote.push(inlineMarkdown(lines[index++].replace(/^>\s?/, '')));
                    blocks.push(`<blockquote class="subject-quote">${quote.join('<br>')}</blockquote>`);
                    continue;
                }
                const paragraph = [];
                while (index < lines.length && lines[index].trim() && !/^(#{1,6})\s+/.test(lines[index]) && !/^\s*(?:[-*+]\s+|\d+[.)]\s+|>\s?)/.test(lines[index])) paragraph.push(inlineMarkdown(lines[index++]));
                blocks.push(`<p>${paragraph.join('<br>')}</p>`);
            }
            return blocks.join('');
        },
    };
}

function renderList(lines, start, baseIndent) {
    const match = lines[start].match(/^(\s*)([-*+]\s+|\d+[.)]\s+)(.*)$/);
    if (! match || countIndent(lines[start]) !== baseIndent) return null;
    const ordered = /^\d/.test(match[2]);
    const tag = ordered ? 'ol' : 'ul';
    const items = [];
    let index = start;
    while (index < lines.length) {
        const item = lines[index].match(/^(\s*)([-*+]\s+|\d+[.)]\s+)(.*)$/);
        if (! item || countIndent(lines[index]) !== baseIndent || /^\d/.test(item[2]) !== ordered) break;
        let content = inlineMarkdown(item[3]);
        index++;
        while (index < lines.length && lines[index].trim()) {
            const childIndent = countIndent(lines[index]);
            if (childIndent <= baseIndent) break;
            const child = renderList(lines, index, childIndent);
            if (child) { content += child.html; index = child.next; } else break;
        }
        items.push(`<li>${content}</li>`);
    }
    return { html: `<${tag}>${items.join('')}</${tag}>`, next: index };
}

function renderTable(lines, start) {
    if (!/^\s*\|/.test(lines[start]) || ! lines[start + 1]?.match(/^\s*\|?\s*:?-{3,}/)) return null;
    const rows = [];
    let index = start;
    while (index < lines.length && /^\s*\|/.test(lines[index])) rows.push(lines[index++]);
    const cells = row => row.split('|').slice(1, -1).map(cell => `<td>${inlineMarkdown(cell.trim())}</td>`).join('');
    return { html: `<div class="subject-table-wrap"><table class="subject-table"><thead><tr>${rowCells(rows[0], 'th')}</tr></thead><tbody>${rows.slice(2).map(row => `<tr>${cells(row)}</tr>`).join('')}</tbody></table></div>`, next: index };
}

function rowCells(row, tag) {
    return row.split('|').slice(1, -1).map(cell => `<${tag}>${inlineMarkdown(cell.trim())}</${tag}>`).join('');
}

function inlineMarkdown(value) {
    const escaped = escapeHtml(value);
    return escaped
        .replace(/!\[([^\]]*)\]\((https?:\/\/[^\)]+)\)/g, '<img src="$2" alt="$1" class="subject-image">')
        .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-emerald-700 underline">$1</a>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code>$1</code>');
}

function countIndent(line) {
    return (line.match(/^\s*/) || [''])[0].replace(/\t/g, '    ').length;
}

function insertToolbarText(textarea, template) {
    const text = decodeInsertTemplate(template);
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    const selected = textarea.value.slice(start, end);

    const wrapped = selected ? wrapSelection(text, selected) : text;
    const wrapMarker = wrapped !== text ? detectMarker(text) : '';

    textarea.value = textarea.value.slice(0, start) + wrapped + textarea.value.slice(end);
    if (wrapMarker) {
        textarea.selectionStart = start + wrapMarker.length;
        textarea.selectionEnd = start + wrapMarker.length + selected.length;
    } else {
        textarea.selectionStart = textarea.selectionEnd = start + wrapped.length;
    }
    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function detectMarker(template) {
    if (/^\*\*/.test(template)) return '**';
    if (/^\*/.test(template)) return '*';
    if (/^\[/.test(template)) return '[';
    return '';
}

function wrapSelection(template, selected) {
    if (/^\*\*(.+?)\*\*$/.test(template)) return `**${selected}**`;
    if (/^\*(.+?)\*$/.test(template) && ! template.startsWith('**')) return `*${selected}*`;
    if (/^\[(.+?)\]\(https:\/\/\)$/.test(template)) return `[${selected}](https://)`;
    return template;
}

function insertTextAtCursor(textarea, text) {
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function decodeInsertTemplate(text) {
    return String(text ?? '').replace(/\\n/g, '\n').replace(/\\r/g, '\r').replace(/\\t/g, '\t');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
