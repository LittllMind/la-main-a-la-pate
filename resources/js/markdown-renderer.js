/* --------------------------------------------------------------------------
 * Petit moteur Markdown ciblé pour l'aperçu de l'éditeur de sujets.
 * -------------------------------------------------------------------------- */

export function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function buildMarkdownRenderer(md) {
    const lines = md.replace(/\r\n/g, '\n').split('\n');
    const out = [];
    let i = 0;
    let safety = 0;
    const maxIterations = lines.length * 5 + 1000;

    function isHeading(ln) { return /^#{1,6}\s+/.test(ln); }
    function isSeparator(ln) { return /^-{3,}\s*$/.test(ln); }
    function isTableStart(ln) { return /^\|.*\|$/.test(ln.trim()); }
    function isList(ln) { return /^(\s*)([-*]|\d+\.)\s+/.test(ln); }
    function isBlockquote(ln) { return /^(\s*)>(\s?)(.*)$/.test(ln); }
    function isCodeFence(ln) { return /^```/.test(ln); }

    function hasSafeUrl(href) { return /^(https?:\/\/|\/)/.test(href); }

    function parseInline(text) {
        return escapeHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/!\[([^\]]*)\]\(([^\)]+)\)/g, (match, alt, src) => {
                const safeUrl = hasSafeUrl(src) ? src : '#';
                return `<img src="${safeUrl}" alt="${alt}" class="rounded max-w-full" loading="lazy">`;
            })
            .replace(/\[([^\]]+)\]\(([^\)]+)\)/g, (match, label, href) => {
                const safeUrl = hasSafeUrl(href) ? href : '#';
                return `<a href="${safeUrl}" target="_blank" rel="noopener" class="text-emerald-700 underline">${label}</a>`;
            })
            .replace(/\n/g, '<br>');
    }

    function readParagraph(startIdx) {
        const paraLines = [];
        let k = startIdx;
        while (k < lines.length) {
            const ln = lines[k];
            if (ln.trim() === '') break;
            if (isHeading(ln) || isSeparator(ln) || isList(ln) || isBlockquote(ln) || isCodeFence(ln) || isTableStart(ln)) break;
            paraLines.push(ln);
            k++;
        }
        return { html: '<p>' + parseInline(paraLines.join(' ')) + '</p>', next: k };
    }

    function readHeading(startIdx) {
        const match = lines[startIdx].match(/^(#{1,6})\s+(.*)$/);
        if (!match) return { html: '', next: startIdx + 1 };
        const level = match[1].length;
        const text = match[2].trim();
        if (text === '') return { html: '', next: startIdx + 1 };
        return { html: `<h${level}>${parseInline(text)}</h${level}>`, next: startIdx + 1 };
    }

    function readList(startIdx, requiredBaseIndent = null) {
        const baseIndent = requiredBaseIndent ?? lines[startIdx].match(/^(\s*)/)[1].length;
        let k = startIdx;
        let currentType = null;
        let currentContent = [];
        let pendingSublists = [];
        const currentItems = [];
        let html = '';

        function flushList() {
            if (!currentType) return;
            const cls = currentType === 'ol' ? 'list-decimal pl-5 my-2' : 'list-disc pl-5 my-2';
            html += `<${currentType} class="${cls}">${currentItems.join('')}</${currentType}>`;
        }

        function flushItem() {
            if (currentContent.length === 0 && pendingSublists.length === 0) {
                currentContent = [''];
            }
            const inline = currentContent.length ? parseInline(currentContent.join(' ')) : '';
            let li = `<li>${inline}`;
            pendingSublists.forEach((sub) => { li += sub; });
            li += '</li>';
            currentItems.push(li);
            currentContent = [];
            pendingSublists = [];
        }

        while (k < lines.length) {
            if (safety++ > maxIterations) break;
            const line = lines[k];
            if (line.trim() === '') { k++; continue; }

            const m = line.match(/^(\s*)([-*]|\d+\.)\s+(.*)$/);
            if (!m) {
                const leading = line.match(/^(\s*)/)[1].length;
                if (leading > baseIndent && currentContent.length + pendingSublists.length > 0) {
                    currentContent.push(line.trim());
                    k++;
                    continue;
                }
                break;
            }

            const indent = m[1].length;
            const type = /^\d+\./.test(m[2]) ? 'ol' : 'ul';
            const content = m[3];

            if (indent < baseIndent) break;

            if (indent === baseIndent) {
                if (currentContent.length > 0 || pendingSublists.length > 0) {
                    flushItem();
                }
                if (currentType && currentType !== type) {
                    flushList();
                    currentItems.length = 0;
                }
                currentType = type;
                currentContent = [content];
                k++;
                continue;
            }

            // indent > baseIndent : sous-liste(s) rattachées à l'élément courant.
            const sub = readList(k, indent);
            if (sub.html) {
                pendingSublists.push(sub.html);
                k = sub.next;
            } else {
                currentContent.push(content);
                k++;
            }
        }

        if (currentContent.length > 0 || pendingSublists.length > 0) {
            flushItem();
        }
        flushList();

        return { html: html, next: k };
    }

    function readBlockquote(startIdx) {
        const quote = [];
        let k = startIdx;
        while (k < lines.length) {
            const ln = lines[k];
            const match = ln.match(/^(\s*)>(\s?)(.*)$/);
            if (!match) break;
            quote.push(match[3]);
            k++;
            if (safety++ > maxIterations) break;
        }
        const inner = buildMarkdownRenderer(quote.join('\n'));
        return { html: `<blockquote class="border-l-4 border-emerald-300 pl-4 italic text-slate-700 my-2">${inner}</blockquote>`, next: k };
    }

    function readCodeBlock(startIdx) {
        let k = startIdx + 1;
        const content = [];
        while (k < lines.length) {
            if (lines[k].startsWith('```')) { k++; break; }
            content.push(lines[k]);
            k++;
            if (safety++ > maxIterations) break;
        }
        return { html: `<pre class="bg-slate-100 p-3 rounded overflow-x-auto my-2"><code>${escapeHtml(content.join('\n'))}</code></pre>`, next: k };
    }

    function readTable(startIdx) {
        const header = [];
        const rows = [];
        let k = startIdx;
        while (k < lines.length) {
            const ln = lines[k].trim();
            if (!/^\|.*\|$/.test(ln)) break;
            const rawCells = ln.slice(1, -1).split('|').map((c) => c.trim());
            if (k === startIdx + 1) {
                // separator row dropped
            } else if (k === startIdx) {
                header.push(`<tr>${rawCells.map((c) => `<th class="border border-slate-300 px-2 py-1 bg-slate-100 font-semibold">${parseInline(c)}</th>`).join('')}</tr>`);
            } else {
                rows.push(`<tr>${rawCells.map((c) => `<td class="border border-slate-300 px-2 py-1">${parseInline(c)}</td>`).join('')}</tr>`);
            }
            k++;
            if (safety++ > maxIterations) break;
        }

        const allRows = [...header, ...rows];
        return { html: allRows.length ? `<table class="w-full border-collapse border border-slate-300 my-2">${allRows.join('')}</table>` : '', next: k };
    }

    while (i < lines.length) {
        if (safety++ > maxIterations) {
            out.push(`<!-- markdown parser safety stop -->`);
            break;
        }

        const raw = lines[i];
        const trimmed = raw.trim();

        let consumed = 0;

        if (isCodeFence(raw)) {
            const block = readCodeBlock(i);
            out.push(block.html);
            consumed = block.next - i;
        } else if (isTableStart(raw)) {
            const next = lines[i + 1];
            if (next !== undefined && /^\s*\|?\s*:?-{3,}/.test(next)) {
                const block = readTable(i);
                out.push(block.html);
                consumed = block.next - i;
            } else {
                const p = readParagraph(i);
                out.push(p.html);
                consumed = p.next - i;
            }
        } else if (isHeading(raw)) {
            const h = readHeading(i);
            out.push(h.html);
            consumed = h.next - i;
        } else if (isSeparator(raw)) {
            out.push('<hr class="my-4 border-slate-200" />');
            consumed = 1;
        } else if (isList(raw)) {
            const l = readList(i);
            out.push(l.html);
            consumed = l.next - i;
        } else if (isBlockquote(raw)) {
            const q = readBlockquote(i);
            out.push(q.html);
            consumed = q.next - i;
        } else if (trimmed === '') {
            consumed = 1;
        } else {
            const p = readParagraph(i);
            out.push(p.html);
            consumed = p.next - i;
        }

        i += Math.max(consumed, 1);
    }

    return out.join('\n');
}
