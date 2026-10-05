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
                if (items.length === 0) return;
                event.preventDefault();
                for (const item of items) await uploadInlineImage(item.getAsFile(), textarea, subjectId);
            });

            textarea.addEventListener('drop', async event => {
                const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
                if (files.length === 0) return;
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
    const alt = file.name.replace(/\.[^/.]+$/, '');
    form.append('alt', alt);

    try {
        const response = await fetch(`/sujets/${subjectId}/upload-image`, {
            method: 'POST',
            body: form,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        });
        if (! response.ok) throw new Error('Erreur serveur');
        const data = await response.json();
        insertTextAtCursor(textarea, `\n![${data.alt || alt}](${data.url})\n`);
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
        const renderer = buildMarkdownRenderer();
        if (textareas.length === 0 || ! preview) return;

        let activeTextarea = textareas.find(textarea => ! textarea.closest('.hidden')) || textareas[0];
        const refresh = () => {
            const value = activeTextarea.value.trim();
            preview.innerHTML = value ? renderer.render(value) : '<p class="text-slate-500 italic">Aucun contenu pour cette version</p>';
            if (previewAudience) previewAudience.textContent = activeTextarea.dataset.audienceLabel || 'Travail';
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
                const shouldHide = mode === 'preview'
                    || (mode === 'write-preview' && panel.dataset.editorPanel !== activeTextarea.id);
                panel.classList.toggle('hidden', shouldHide);
            });
            container.querySelectorAll('[data-preview-mode]').forEach(item => {
                item.setAttribute('aria-selected', item.dataset.previewMode === mode ? 'true' : 'false');
            });
        };

        textareas.forEach(textarea => {
            textarea.addEventListener('focus', () => setActive(textarea));
            textarea.addEventListener('input', () => {
                setActive(textarea);
                refresh();
            });
        });

        container.querySelectorAll('[data-insert]').forEach(button => {
            button.title = button.dataset.tip || button.title;
            button.addEventListener('click', () => {
                insertTextAtCursor(activeTextarea, button.dataset.insert);
                activeTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        container.querySelectorAll('[data-editor-tab]').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = container.querySelector(`#${tab.dataset.editorTab}`);
                if (! target) return;
                container.querySelectorAll('[data-editor-tab]').forEach(item => {
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                    item.classList.toggle('border-emerald-600', item === tab);
                    item.classList.toggle('text-slate-700', item === tab);
                    item.classList.toggle('border-transparent', item !== tab);
                    item.classList.toggle('text-slate-500', item !== tab);
                });
                container.querySelectorAll('[data-editor-panel]').forEach(panel => panel.classList.toggle('hidden', panel.dataset.editorPanel !== target.id));
                setActive(target);
                target.focus();
            });
        });

        container.querySelectorAll('[data-preview-mode]').forEach(button => {
            button.addEventListener('click', () => {
                applyPreviewMode(button.dataset.previewMode);
            });
        });

        refresh();
        applyPreviewMode('write-preview');
    });
}

function buildMarkdownRenderer() {
    return {
        render(text) {
            const escaped = escapeHtml(text);
            return escaped.split(/\n{2,}/).map(block => {
                if (/^\s*\|/.test(block)) return renderMarkdownTable(block);
                const lines = block.split('\n');
                if (lines.every(line => /^\s*[-*+]\s+/.test(line))) {
                    return `<ul>${lines.map(line => `<li>${inlineMarkdown(line.replace(/^\s*[-*+]\s+/, ''))}</li>`).join('')}</ul>`;
                }
                if (lines.every(line => /^\s*\d+[.)]\s+/.test(line))) {
                    return `<ol>${lines.map(line => `<li>${inlineMarkdown(line.replace(/^\s*\d+[.)]\s+/, ''))}</li>`).join('')}</ol>`;
                }
                if (lines.every(line => /^\s*>\s?/.test(line))) {
                    return `<blockquote class="subject-quote">${lines.map(line => inlineMarkdown(line.replace(/^\s*>\s?/, ''))).join('<br>')}</blockquote>`;
                }
                if (lines.length === 1 && /^(#{1,6})\s+/.test(lines[0])) {
                    const match = lines[0].match(/^(#{1,6})\s+(.*)$/);
                    return `<h${match[1].length}>${inlineMarkdown(match[2])}</h${match[1].length}>`;
                }
                return `<p>${lines.map(inlineMarkdown).join('<br>')}</p>`;
            }).join('');
        },
    };
}

function inlineMarkdown(value) {
    return value
        .replace(/!\[([^\]]*)\]\((https?:\/\/[^\)]+)\)/g, '<img src="$2" alt="$1" class="subject-image">')
        .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-emerald-700 underline">$1</a>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="bg-slate-100 rounded px-1 text-sm">$1</code>');
}

function renderMarkdownTable(block) {
    const rows = block.trim().split(/\n+/).filter(Boolean);
    if (rows.length < 2) return `<p>${block}</p>`;
    const body = rows.filter((row, index) => !(index === 1 && /^\s*\|[-\s:|]+\|\s*$/.test(row)));
    return `<table class="subject-table"><thead><tr>${tableCells(body[0], 'th')}</tr></thead><tbody>${body.slice(1).map(row => `<tr>${tableCells(row, 'td')}</tr>`).join('')}</tbody></table>`;
}

function tableCells(row, tag) {
    return row.split('|').filter(cell => cell.trim() !== '').map(cell => `<${tag}>${cell.trim()}</${tag}>`).join('');
}

function getActiveTextarea(editor) {
    return editor.querySelector(`textarea#${editor.dataset.activeEditor}`) || editor.querySelector('textarea[data-editor-field]');
}

function insertTextAtCursor(textarea, text) {
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
