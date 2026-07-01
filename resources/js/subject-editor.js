document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('editor');
    const textarea = document.getElementById('body');

    if (!editor || !textarea) return;

    const form = editor.closest('form');

    function exec(cmd, arg = null) {
        document.execCommand(cmd, false, arg);
        editor.focus();
        syncPlaceholder();
    }

    function insertLink() {
        const selection = document.getSelection();
        const selectedText = selection.toString().trim();
        const url = prompt('Adresse du lien (https://...)');
        if (!url) return;

        const linkText = selectedText || url;
        const linkHtml = `<a href="${url.replace(/"/g, '&quot;')}" rel="noopener noreferrer" target="_blank" class="text-emerald-700 hover:text-emerald-800 hover:underline">${linkText}</a>`;

        if (selectedText) {
            document.execCommand('insertHTML', false, linkHtml);
        } else {
            document.execCommand('insertHTML', false, linkHtml + '\u0026nbsp;');
        }

        editor.focus();
        syncPlaceholder();
    }

    function insertImage() {
        const url = prompt('Adresse de l\'image (https://...)');
        if (!url) return;

        const alt = prompt('Texte alternatif (optionnel)') || '';
        const imgHtml = `<img src="${encodeURI(url)}" alt="${alt.replace(/"/g, '&quot;')}" class="rounded-lg border border-slate-200 my-4 max-w-full h-auto"><br>`;

        document.execCommand('insertHTML', false, imgHtml);
        editor.focus();
        syncPlaceholder();
    }

    function insertTable() {
        let rows = parseInt(prompt('Nombre de lignes', '2'), 10) || 2;
        let cols = parseInt(prompt('Nombre de colonnes', '3'), 10) || 3;

        rows = Math.max(1, Math.min(rows, 10));
        cols = Math.max(1, Math.min(cols, 6));

        let rowsHtml = '';
        for (let r = 0; r < rows; r++) {
            rowsHtml += '<tr>';
            for (let c = 0; c < cols; c++) {
                rowsHtml += '<td class="border border-slate-300 px-3 py-2 empty-cell">\u0026nbsp;</td>';
            }
            rowsHtml += '</tr>';
        }

        const html = `<table class="wiki-table w-full border-collapse border border-slate-300 my-4 text-sm"><tbody>${rowsHtml}</tbody></table><br>`;

        document.execCommand('insertHTML', false, html);
        editor.focus();
        syncPlaceholder();
    }

    function insertQuote() {
        document.execCommand('formatBlock', false, 'blockquote');
        editor.focus();
        syncPlaceholder();
    }

    function updateToolbarState() {
        document.querySelectorAll('.toolbar-btn').forEach(btn => {
            const cmd = btn.dataset.cmd;
            const arg = btn.dataset.arg;
            let active = false;
            if (cmd === 'formatBlock') {
                const current = document.queryCommandValue('formatBlock');
                active = current === arg || current === arg.toUpperCase();
            } else if (cmd === 'insertQuote') {
                let node = document.getSelection().anchorNode;
                while (node && node !== editor) {
                    if (node.nodeName.toLowerCase() === 'blockquote') {
                        active = true;
                        break;
                    }
                    node = node.parentElement;
                }
            } else if (cmd === 'insertUnorderedList') {
                active = document.queryCommandState('insertUnorderedList');
            } else {
                active = document.queryCommandState(cmd);
            }
            btn.classList.toggle('toolbar-active', active);
        });
    }

    document.querySelectorAll('.toolbar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const cmd = btn.dataset.cmd;
            const arg = btn.dataset.arg || null;

            if (cmd === 'createLink') {
                insertLink();
            } else if (cmd === 'insertImage') {
                insertImage();
            } else if (cmd === 'insertTable') {
                insertTable();
            } else if (cmd === 'insertQuote') {
                insertQuote();
            } else {
                exec(cmd, arg);
            }

            updateToolbarState();
        });
    });

    function syncPlaceholder() {
        editor.classList.toggle('is-empty', editor.innerText.trim().length === 0);
    }

    editor.addEventListener('input', () => {
        syncPlaceholder();
        updateToolbarState();
    });
    editor.addEventListener('keyup', updateToolbarState);
    editor.addEventListener('mouseup', updateToolbarState);
    editor.addEventListener('focus', () => editor.classList.add('is-focused'));
    editor.addEventListener('blur', () => editor.classList.remove('is-focused'));

    syncPlaceholder();
    updateToolbarState();

    if (form) {
        form.addEventListener('submit', () => {
            textarea.value = editor.innerHTML;
        });
    }
});
