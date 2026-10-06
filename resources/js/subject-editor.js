document.addEventListener('DOMContentLoaded', () => {
    setupThemeToggle();
    setupMarkdownEditors();
    setupInlineImageUploads();
});

function setupInlineImageUploads() {
    document.querySelectorAll('[data-inline-upload]').forEach(input => {
        const subjectId = input.dataset.subjectId;
        const editor = input.closest('[data-markdown-editor]');
        if (! subjectId || ! editor) return;
        input.addEventListener('change', async event => {
            const file = event.target.files[0];
            if (! file) return;
            await uploadInlineImage(file, getActiveTextarea(editor), subjectId);
            input.value = '';
        });
    });
    document.querySelectorAll('[data-markdown-editor]').forEach(editor => {
        const subjectId = editor.querySelector('[data-inline-upload]')?.dataset.subjectId;
        if (! subjectId) return;
        editor.querySelectorAll('textarea[data-editor-field]').forEach(textarea => {
            textarea.addEventListener('paste', async event => {
                const items = Array.from(event.clipboardData.items).filter(item => item.type.startsWith('image/'));
                if (! items.length) return;
                event.preventDefault();
                for (const item of items) await uploadInlineImage(item.getAsFile(), textarea, subjectId);
            });
            textarea.addEventListener('drop', async event => {
                const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
                if (! files.length) return;
                event.preventDefault();
                for (const file of files) await uploadInlineImage(file, textarea, subjectId);
            });
        });
    });
}

async function uploadInlineImage(file, textarea, subjectId) {
    if (! file || ! textarea) return;
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
    } catch (err) {
        alert("L'image n'a pas pu être ajoutée.");
    }
}

function setupThemeToggle() {
    document.querySelectorAll('[data-theme-toggle]').forEach(select => {
        const wrapper = document.getElementById('theme-other-wrapper');
        if (! wrapper) return;
        select.addEventListener('change', () => wrapper.classList.toggle('hidden', select.value !== '__new__'));
    });
}

function setupMarkdownEditors() {
    document.querySelectorAll('[data-markdown-editor]').forEach(container => {
        const textareas = [...container.querySelectorAll('textarea[data-editor-field]')];
        const preview = container.querySelector('[data-markdown-preview]');
        const previewAudience = container.querySelector('[data-preview-audience]');
        if (! textareas.length || ! preview) return;

        let activeTextarea = textareas.find(textarea => ! textarea.closest('[data-editor-panel].hidden, .tab-panel.hidden')) || textareas[0];
        const refresh = () => {
            const value = activeTextarea.value;
            preview.innerHTML = value.trim()
                ? buildMarkdownRenderer().render(value)
                : '<p class="text-slate-500 italic">Aucun contenu pour cette version</p>';
            if (previewAudience) previewAudience.textContent = activeTextarea.dataset.audienceLabel || 'Instruction';
        };
        const setActive = textarea => {
            activeTextarea = textarea;
            container.dataset.activeEditor = textarea.id;
            refresh();
            applyPreviewMode(container.dataset.previewMode || 'write-preview');
        };
        const applyPreviewMode = mode => {
            container.dataset.previewMode = mode;
            const showPreview = mode !== 'write';
            preview.closest('[data-preview-panel]')?.classList.toggle('hidden', ! showPreview);
            container.querySelectorAll('[data-editor-panel]').forEach(panel => {
                const isActive = panel.dataset.editorPanel === activeTextarea.id;
                panel.classList.toggle('hidden', mode === 'preview' || ! isActive);
            });
            container.querySelectorAll('[data-preview-mode]').forEach(item => {
                item.setAttribute('aria-selected', item.dataset.previewMode === mode ? 'true' : 'false');
            });
        };

        textareas.forEach(textarea => {
            textarea.addEventListener('focus', () => setActive(textarea));
            textarea.addEventListener('input', () => { setActive(textarea); refresh(); });
        });
        container.querySelectorAll('[data-insert]').forEach(button => {
            button.title = button.dataset.tip || button.title;
            button.addEventListener('click', () => {
                const command = button.dataset.command;
                insertToolbarText(activeTextarea, decodeInsertTemplate(button.dataset.insert || ''), command);
                activeTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
        container.querySelectorAll('[data-editor-tab]').forEach(tab => {
            tab.setAttribute('role', 'tab');
            tab.addEventListener('click', () => {
                const target = container.querySelector(`#${CSS.escape(tab.dataset.editorTab)}`);
                if (! target) return;
                container.querySelectorAll('[data-editor-tab]').forEach(item => item.setAttribute('aria-selected', item === tab ? 'true' : 'false'));
                container.querySelectorAll('[data-editor-panel]').forEach(panel => panel.classList.toggle('hidden', panel.dataset.editorPanel !== target.id));
                setActive(target);
                target.focus();
            });
        });
        container.querySelectorAll('[data-preview-mode]').forEach(button => {
            button.setAttribute('role', 'tab');
            button.addEventListener('click', () => applyPreviewMode(button.dataset.previewMode));
        });
        refresh();
        applyPreviewMode(container.dataset.previewMode || 'write-preview');
    });
}

function decodeInsertTemplate(text) {
    return text.replaceAll('\\n', '\n').replaceAll('\\r', '\r');
}

function insertToolbarText(textarea, text, command) {
    if (command === 'bold' || command === 'italic') {
        const marker = command === 'bold' ? '**' : '*';
        const start = textarea.selectionStart ?? textarea.value.length;
        const end = textarea.selectionEnd ?? start;
        const selected = textarea.value.slice(start, end);
        const content = selected || (command === 'bold' ? 'texte' : 'texte');
        insertTextAtCursor(textarea, `${marker}${content}${marker}`, selected ? start + marker.length : start + marker.length);
        if (! selected) textarea.selectionStart = textarea.selectionEnd = start + marker.length;
        return;
    }
    insertTextAtCursor(textarea, text);
}

function buildMarkdownRenderer() {
    return {
        render(text) {
            const lines = String(text).replace(/\r\n?/g, '\n').split('\n');
            const blocks = [];
            let i = 0;
            while (i < lines.length) {
                if (! lines[i].trim()) { i++; continue; }
                const heading = lines[i].match(/^ {0,3}(#{1,6})(?:\s+(.*?)\s*#*)?$/);
                if (heading) {
                    if (heading[2]?.trim()) {
                        blocks.push(`<h${heading[1].length}>${inlineMarkdown(heading[2].trim())}</h${heading[1].length}>`);
                    }
                    i++; continue;
                }
                if (/^ {0,3}([-*_])(?:\s*\1){2,}\s*$/.test(lines[i])) { blocks.push('<hr>'); i++; continue; }
                if (/^\s*>/.test(lines[i])) {
                    const quote = [];
                    while (i < lines.length && /^\s*>/.test(lines[i])) { quote.push(lines[i].replace(/^\s*>\s?/, '')); i++; }
                    blocks.push(`<blockquote class="subject-quote">${buildMarkdownRenderer().render(quote.join('\n'))}</blockquote>`); continue;
                }
                if (isTableStart(lines, i)) {
                    const table = [lines[i], lines[i + 1]]; i += 2;
                    while (i < lines.length && /^\s*\|/.test(lines[i])) table.push(lines[i++]);
                    blocks.push(renderMarkdownTable(table.join('\n'))); continue;
                }
                if (/^\s*(?:[-*+] |\d+[.)] )/.test(lines[i])) {
                    const list = consumeList(lines, i);
                    blocks.push(list.html); i = list.next; continue;
                }
                const paragraph = [lines[i++]];
                while (i < lines.length && lines[i].trim() && !isBlockStart(lines[i])) paragraph.push(lines[i++]);
                blocks.push(`<p>${paragraph.map(inlineMarkdown).join('<br>')}</p>`);
            }
            return blocks.join('');
        },
    };
}

function isBlockStart(line) {
    return /^ {0,3}#{1,6}(?:\s+.*)?$/.test(line) || /^ {0,3}([-*_])(?:\s*\1){2,}\s*$/.test(line)
        || /^\s*>/.test(line) || /^\s*(?:[-*+] |\d+[.)] )/.test(line) || /^\s*\|/.test(line);
}

function isTableStart(lines, index) {
    return /^\s*\|/.test(lines[index]) && index + 1 < lines.length && /^\s*\|?\s*:?-{3,}/.test(lines[index + 1]);
}

function consumeList(lines, start) {
    const root = []; let i = start;
    while (i < lines.length && lines[i].trim()) {
        const match = lines[i].match(/^(\s*)([-*+] |\d+[.)] )(.*)$/);
        if (! match) break;
        root.push({ indent: match[1].length, ordered: /^\d/.test(match[2]), text: match[3] }); i++;
    }
    const renderLevel = (index, indent) => {
        const ordered = root[index]?.ordered;
        const tag = ordered ? 'ol' : 'ul'; let html = `<${tag}>`;
        while (index < root.length && root[index].indent === indent && root[index].ordered === ordered) {
            html += `<li>${inlineMarkdown(root[index].text)}`;
            if (root[index + 1] && root[index + 1].indent > indent) {
                let nestedHtml = '';
                let nestedIndex = index + 1;
                while (nestedIndex < root.length && root[nestedIndex].indent > indent) {
                    const nested = renderLevel(nestedIndex, root[nestedIndex].indent);
                    nestedHtml += nested.html;
                    nestedIndex = nested.next;
                }
                html += nestedHtml;
                index = nestedIndex;
            } else index++;
            html += '</li>';
        }
        return { html: html + `</${tag}>`, next: index };
    };
    let index = 0;
    let html = '';
    while (index < root.length) {
        const segment = renderLevel(index, root[index].indent);
        html += segment.html;
        index = segment.next;
    }
    return { html, next: i };
}

function inlineMarkdown(value) {
    return escapeHtml(value)
        .replace(/!\[([^\]]*)\]\((https?:\/\/[^\)]+)\)/g, '<img src="$2" alt="$1" class="subject-image">')
        .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-emerald-700 underline">$1</a>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="bg-slate-100 rounded px-1 text-sm">$1</code>');
}

function renderMarkdownTable(block) {
    const rows = block.split(/\n+/).filter(Boolean);
    if (rows.length < 2) return `<p>${inlineMarkdown(block)}</p>`;
    const cells = row => {
        const trimmed = row.trim().replace(/^\|/, '').replace(/\|$/, '');
        return trimmed.split('|').map(cell => cell.trim());
    };
    const header = cells(rows[0]);
    const body = rows.slice(2).map(cells);
    return `<div class="overflow-x-auto"><table class="subject-table"><thead><tr>${header.map(cell => `<th>${inlineMarkdown(cell)}</th>`).join('')}</tr></thead><tbody>${body.map(row => `<tr>${header.map((_, index) => `<td>${inlineMarkdown(row[index] || '')}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
}

function getActiveTextarea(editor) {
    return editor.querySelector(`textarea#${CSS.escape(editor.dataset.activeEditor || '')}`) || editor.querySelector('textarea[data-editor-field]');
}

function insertTextAtCursor(textarea, text, selectionStart = null) {
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
    const caret = selectionStart ?? start + text.length;
    textarea.selectionStart = textarea.selectionEnd = caret;
    textarea.focus();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
