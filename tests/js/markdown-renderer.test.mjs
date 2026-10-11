import assert from 'node:assert';
import { buildMarkdownRenderer, escapeHtml } from '../../resources/js/markdown-renderer.js';

function runWithTimeout(fn, ms = 500) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Timeout — possible infinite loop')), ms);
        try {
            resolve(fn());
        } catch (e) {
            reject(e);
        } finally {
            clearTimeout(timer);
        }
    });
}

async function main() {
    // Citation indentée et simple
    const q1 = buildMarkdownRenderer('  > Une citation indentée');
    assert(q1.includes('Une citation indentée'));
    assert(q1.includes('blockquote'));

    // Mélange titres / listes / paragraphes
    const mixed = buildMarkdownRenderer('# Titre\n\nPara A\n\n- Alpha\n  1. Bravo\n  - Charlie\n- Delta\n\n> Quote');
    assert(mixed.includes('<h1>Titre</h1>'));
    assert(mixed.includes('Alpha'));
    assert(mixed.includes('Bravo'));
    assert(mixed.includes('Charlie'));
    assert(mixed.includes('Delta'));
    assert(mixed.includes('<blockquote'));

    // Tableau
    const table = buildMarkdownRenderer('| A | B |\n|---|---|\n| 1 | 2 |');
    assert(table.includes('<table'));

    // Échappement HTML
    const escaped = buildMarkdownRenderer('<script>alert(1)</script>');
    assert(!escaped.includes('<script>'));

    // Séparateur
    const sep = buildMarkdownRenderer('---');
    assert(sep.includes('<hr'));

    // Bloc code
    const code = buildMarkdownRenderer('```\n$x = 1;\n```');
    assert(code.includes('<code>$x = 1;</code>'));

    // Pas de boucle infinie
    await runWithTimeout(() => buildMarkdownRenderer('  > Une citation indentée\n\nTexte après'), 300);
    await runWithTimeout(() => buildMarkdownRenderer('- A\n  > nested quote\n- B'.repeat(50)), 300);

    console.log('All markdown renderer checks passed');
}

main().catch((e) => { console.error(e); process.exit(1); });
