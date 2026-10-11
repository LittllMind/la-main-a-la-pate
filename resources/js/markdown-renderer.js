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

    function parseInline(text) {
        return escapeHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/!\[([^\]]*)\]\(([^\)]+)\)/g, '<img src="$2" alt="$1" class="rounded max-w-full" loading="lazy">')
            .replace(/\[([^\]]+)\]\(([^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" class="text-emerald-700 underline">$1</a>')
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
        return { html: `<h${level}>${parseInline(match[2])}</h${level}>`, next: startIdx + 1 };
    }

    function readList(startIdx) {
        const items = [];
        let k = startIdx;
        const baseIndent = lines[startIdx].match(/^(\s*)/)[1].length;
        const stack = [{ indent: baseIndent, html: '', items: [] }];

        function top() { return stack[stack.length - 1]; }
        function flushItem() {
            const t = top();
            if (t.html.trim()) {
                t.items.push('<li>' + parseInline(t.html.trim()) + '</li>');
                t.html = '';
            }
        }
        function closeTo(indent) {
            while (stack.length > 1 && top().indent > indent) {
                flushItem();
                const inner = top().items.length ? '\n<ul>' + top().items.join('') + '</ul>' : '';
                const t = stack.pop();
                top().html += inner;
            }
        }

        while (k < lines.length) {
            const line = lines[k];
            if (line.trim() === '') { k++; continue; }
            const m = line.match(/^(\s*)([-*]|\d+\.)\s+(.*)$/);
            if (!m) {
                if (isHeading(line) || isSeparator(line) || isBlockquote(line) || isCodeFence(line) || isTableStart(line)) break;
                if (top().html) top().html += ' ' + line.trim();
                k++;
                continue;
            }
            const indent = m[1].length;
            const marker = m[2];
            const content = m[3];

            if (indent > top().indent + 3) {
                flushItem();
                stack.push({ indent, html: content, items: [], ordered: /^\d+\./.test(marker) });
            } else if (indent < top().indent) {
                closeTo(indent);
                flushItem();
                top().html = content;
            } else {
                flushItem();
                top().html = content;
            }

            k++;
            if (safety++ > maxIterations) break;
        }

        while (stack.length > 1) {
            flushItem();
            const inner = top().items.length ? `<ol>${top().items.join('')}</ol>` : '';
            const t = stack.pop();
            top().html += inner;
        }
        flushItem();
        const ordered = /^\d+\./.test(lines[startIdx].match(/^(\s*)([-*]|\d+\.)\s+/)[2]);
        const tag = ordered ? 'ol' : 'ul';
        return { html: `<${tag} class="list-disc pl-5 my-2">${stack[0].items.join('')}</${tag}>`, next: k };
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
        const rows = [];
        let k = startIdx;
        while (k < lines.length) {
            const ln = lines[k].trim();
            if (!/^\|.*\|$/.test(ln)) break;
            const cells = ln.slice(1, -1).split('|').map((c) => parseInline(c.trim()));
            if (k === startIdx + 1) {
                // separator row is dropped
            } else {
                rows.push(`<tr>${cells.map((c) => `<td class="border border-slate-300 px-2 py-1">${c}</td>`).join('')}</tr>`);
            }
            k++;
            if (safety++ > maxIterations) break;
        }

        return { html: rows.length ? `<table class="w-full border-collapse border border-slate-300 my-2">${rows.join('')}</table>` : '', next: k };
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
