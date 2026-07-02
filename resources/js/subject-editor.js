document.addEventListener('DOMContentLoaded', () => {
    setupThemeToggle();
    setupMarkdownEditors();
    setupInlineImageUploads();
});

function setupInlineImageUploads() {
    document.querySelectorAll('[data-inline-upload]').forEach(input => {
        const subjectId = input.dataset.subjectId;
        if (! subjectId) return;

        const textarea = input.closest('[data-markdown-editor]').querySelector('textarea[id="body"]');
        if (! textarea) return;

        input.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (! file) return;

            const form = new FormData();
            form.append('file', file);
            const alt = file.name.replace(/\.[^/.]+$/, '');
            form.append('alt', alt);

            const btn = input.closest('label');
            const originalText = btn ? btn.innerHTML : '';
            if (btn) btn.innerHTML = 'Envoi...';

            try {
                const response = await fetch(`/sujets/${subjectId}/upload-image`, {
                    method: 'POST',
                    body: form,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });

                if (! response.ok) throw new Error('Erreur serveur');
                const data = await response.json();
                insertTextAtCursor(textarea, `\n![${data.alt || alt}](${data.url})\n`);
                const editor = textarea.closest('[data-markdown-editor]');
                if (editor) editor.querySelector('#preview')?.dispatchEvent(new Event('forceRefresh'));
            } catch (err) {
                alert('L\'image n\'a pas pu être ajoutée.');
            } finally {
                if (btn) btn.innerHTML = originalText;
                input.value = '';
            }
        });
    });

    document.querySelectorAll('textarea[id="body"]').forEach(textarea => {
        const container = textarea.closest('[data-markdown-editor]');
        if (! container) return;
        const subjectId = container.querySelector('[data-inline-upload]')?.dataset.subjectId;
        if (! subjectId) return;

        textarea.addEventListener('paste', async (event) => {
            const items = Array.from(event.clipboardData.items).filter(item => item.type.startsWith('image/'));
            if (items.length === 0) return;
            event.preventDefault();

            for (const item of items) {
                const file = item.getAsFile();
                await uploadInlineImage(file, textarea, subjectId);
            }
        });

        textarea.addEventListener('drop', async (event) => {
            const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
            if (files.length === 0) return;
            event.preventDefault();

            for (const file of files) {
                await uploadInlineImage(file, textarea, subjectId);
            }
        });
    });
}

async function uploadInlineImage(file, textarea, subjectId) {
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
        textarea.dispatchEvent(new Event('input'));
    } catch (err) {
        alert('L\'image n\'a pas pu être ajoutée.');
    }
}

function setupThemeToggle() {
    const selects = document.querySelectorAll('[data-theme-toggle]');
    selects.forEach(select => {
        const wrapper = document.getElementById('theme-other-wrapper');
        if (! wrapper) return;

        select.addEventListener('change', () => {
            wrapper.classList.toggle('hidden', select.value !== '__new__');
        });
    });
}

function setupMarkdownEditors() {
    document.querySelectorAll('[data-markdown-editor]').forEach(container => {
        const textarea = container.querySelector('textarea[id="body"]');
        const preview = container.querySelector('#preview');
        const toggleBtn = container.querySelector('#toggle-preview');
        if (! textarea || ! preview) return;

        const renderer = buildMarkdownRenderer();
        const refresh = () => {
            preview.innerHTML = renderer.render(textarea.value);
            preview.style.display = toggleBtn && toggleBtn.textContent === 'Masquer' ? 'block' : 'none';
        };

        textarea.addEventListener('input', refresh);
        if (textarea.value) refresh();

        container.querySelectorAll('[data-insert]').forEach(btn => {
            btn.addEventListener('click', () => {
                insertTextAtCursor(textarea, btn.dataset.insert);
                refresh();
            });

            if (btn.dataset.tip) {
                btn.title = btn.dataset.tip;
            }
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const hidden = preview.style.display === 'none';
                preview.style.display = hidden ? 'block' : 'none';
                toggleBtn.textContent = hidden ? 'Masquer' : 'Afficher';
            });
        }
    });
}

function buildMarkdownRenderer() {
    const rules = [
        { regex: /^#{6}\s+(.*)$/gm, replace: '<h6>$1</h6>' },
        { regex: /^#{5}\s+(.*)$/gm, replace: '<h5>$1</h5>' },
        { regex: /^#{4}\s+(.*)$/gm, replace: '<h4>$1</h4>' },
        { regex: /^#{3}\s+(.*)$/gm, replace: '<h3>$1</h3>' },
        { regex: /^#{2}\s+(.*)$/gm, replace: '<h2>$1</h2>' },
        { regex: /^#{1}\s+(.*)$/gm, replace: '<h1>$1</h1>' },
        { regex: /\*\*(.+?)\*\*/g, replace: '<strong>$1</strong>' },
        { regex: /\*(.+?)\*/g, replace: '\u003cem\u003e$1\u003c/em\u003e' },
        { regex: /^\u003e\s+(.*)$/gm, replace: '<blockquote class="subject-quote"\u003e$1</blockquote\u003e' },
        { regex: /`([^`]+)`/g, replace: '<code class="bg-slate-100 rounded px-1 text-sm"\u003e$1</code\u003e' },
        { regex: /\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, replace: '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-emerald-700 underline">$1</a\u003e' },
        { regex: /!\[([^\]]*)\]\((https?:\/\/[^\)]+)\)/g, replace: '<figure class="subject-figure"\u003e<img src="$2" alt="$1" class="subject-image"\u003e<figcaption\u003e$1</figcaption\u003e</figure\u003e' },
    ];

    return {
        render(text) {
            let html = escapeHtml(text)
                .split(/\n{2,}/)
                .map(block => {
                    if (block.startsWith('|')) {
                        return renderMarkdownTable(block);
                    }
                    rules.forEach(rule => {
                        if (rule.regex.test(block)) {
                            block = block.replace(rule.regex, rule.replace);
                        }
                    });
                    return block.trim() ? `<p>${block.replace(/\n/g, '<br>')}</p>` : '';
                })
                .join('\n');

            return html.replace(/\n/g, '');
        },
    };
}

function renderMarkdownTable(block) {
    const rows = block.trim().split(/\n+/).filter(Boolean);
    if (rows.length < 2) return `<p>${block}</p>`;

    let html = '<table class="subject-table"\u003e<tbody\u003e';
    rows.forEach((row, index) => {
        if (index === 1 && /^\s*\|[-\s:|]+\|\s*$/.test(row)) return;
        const cells = row.split('|').filter(c => c !== '');
        const tag = index === 0 ? 'th' : 'td';
        html += '<tr\u003e' + cells.map(c => `<${tag}\u003e${c.trim()}</${tag}\u003e`).join('') + '</tr\u003e';
    });
    html += '</tbody\u003e</table\u003e';

    return html;
}

function insertTextAtCursor(textarea, text) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const before = textarea.value.substring(0, start);
    const after = textarea.value.substring(end);

    textarea.value = before + text + after;
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
