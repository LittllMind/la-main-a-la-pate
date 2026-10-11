import assert from 'node:assert';
import { JSDOM } from 'jsdom';
import { buildMarkdownRenderer, escapeHtml } from '../../resources/js/markdown-renderer.js';

function parseHtml(html) {
    return new JSDOM(html).window.document.body;
}

function strictCount(node, selector) {
    return node.querySelectorAll(selector).length;
}

function main() {
    // Échappement HTML
    const escaped = buildMarkdownRenderer('<script>alert(1)</script>');
    const escapedDoc = parseHtml(escaped);
    assert.strictEqual(strictCount(escapedDoc, 'script'), 0, 'script injecté doit être échappé');
    assert(escaped.includes('&lt;script&gt;'), 'script doit être présent sous forme échappée');

    // Paragraphe après une liste
    const paraAfterList = buildMarkdownRenderer('- Alpha\n- Beta\n\nParagraphe suivant.');
    const paraDoc = parseHtml(paraAfterList);
    assert.strictEqual(strictCount(paraDoc, ':scope > ul'), 1, 'une seule liste principale');
    assert.strictEqual(strictCount(paraDoc, ':scope > p'), 1, 'un paragraphe après la liste');
    assert(paraDoc.querySelector('p').textContent.includes('Paragraphe suivant'));

    // Citation indentée
    const quote = buildMarkdownRenderer('  > Une citation indentée\n\nTexte après');
    const quoteDoc = parseHtml(quote);
    assert.strictEqual(strictCount(quoteDoc, ':scope > blockquote'), 1, 'une citation à la racine');
    assert.strictEqual(strictCount(quoteDoc, ':scope > p'), 1, 'un paragraphe après citation');
    assert(quoteDoc.querySelector('blockquote').textContent.includes('Une citation indentée'));

    // Bloc de code
    const code = buildMarkdownRenderer('```php\n    $x = 1;\n```');
    const codeDoc = parseHtml(code);
    assert.strictEqual(strictCount(codeDoc, ':scope > pre'), 1, 'un bloc préformaté');
    assert.strictEqual(strictCount(codeDoc, 'pre code'), 1, 'code à l\'intérieur du pré');
    assert(codeDoc.querySelector('pre code').textContent.includes('$x = 1;'));

    // Tableau avec cellule vide
    const table = buildMarkdownRenderer('| A | B |\n|---|---|\n| a |  |');
    const tableDoc = parseHtml(table);
    assert.strictEqual(strictCount(tableDoc, ':scope > table'), 1, 'un tableau');
    assert.strictEqual(strictCount(tableDoc, 'table tr'), 2, 'ligne d\'en-tête + ligne de corps');
    assert.strictEqual(strictCount(tableDoc, 'table td'), 4, 'quatre cellules produites');
    const emptyCells = Array.from(tableDoc.querySelectorAll('table td')).filter((td) => td.textContent.trim() === '');
    assert.strictEqual(emptyCells.length, 1, 'une cellule vide');

    // Séparateur
    const sep = buildMarkdownRenderer('---');
    assert(sep.includes('<hr'));

    // Hiérarchie mixte obligatoire
    const mixed = buildMarkdownRenderer('- Alpha\n  1. Bravo\n  - Charlie\n- Delta');
    const mixedDoc = parseHtml(mixed);
    assert.strictEqual(strictCount(mixedDoc, ':scope > ul'), 1, 'une seule liste racine');
    const rootUl = mixedDoc.querySelector(':scope > ul');
    const rootLi = rootUl.querySelectorAll(':scope > li');
    assert.strictEqual(rootLi.length, 2, 'deux éléments de premier niveau');
    const alphaItem = rootLi[0];
    const deltaItem = rootLi[1];
    assert(alphaItem.textContent.includes('Alpha'));
    assert(deltaItem.textContent.includes('Delta'));

    // Alpha doit contenir directement le ol et le ul
    const alphaOl = alphaItem.querySelectorAll(':scope > ol');
    const alphaUl = alphaItem.querySelectorAll(':scope > ul');
    assert.strictEqual(alphaOl.length, 1, 'Alpha contient un ol direct');
    assert.strictEqual(alphaUl.length, 1, 'Alpha contient un ul direct');
    assert.strictEqual(alphaOl[0].querySelectorAll(':scope > li').length, 1, 'ol contient un li');
    assert.strictEqual(alphaUl[0].querySelectorAll(':scope > li').length, 1, 'ul contient un li');
    assert(alphaOl[0].textContent.includes('Bravo'));
    assert(alphaUl[0].textContent.includes('Charlie'));

    // Progression du parseur : pas de bloc figé sur nombreuses itérations
    const big = buildMarkdownRenderer('- A\n  > nested quote\n- B'.repeat(50));
    assert(big.includes('<li>A'));
    assert(big.includes('<li>B'));

    // Protocoles autorisés dans les liens et images
    const links = buildMarkdownRenderer('[site](https://example.com)\n[local](/path)\n[image](http://image.fr/a.png)\n![légende](https://example.com/i.jpg)');
    const linksDoc = parseHtml(links);
    assert.strictEqual(strictCount(linksDoc, 'a[href^="https://"]'), 1);
    assert.strictEqual(strictCount(linksDoc, 'a[href^="/path"]'), 1);
    assert.strictEqual(strictCount(linksDoc, 'a[href^="http://"]'), 1);
    assert.strictEqual(strictCount(linksDoc, 'img[src^="https://"]'), 1);

    // Gras et italique
    const inline = buildMarkdownRenderer('**gras** et *italique*');
    const inlineDoc = parseHtml(inline);
    assert.strictEqual(strictCount(inlineDoc, 'strong'), 1);
    assert.strictEqual(strictCount(inlineDoc, 'em'), 1);

    console.log('All markdown renderer checks passed');
}

main();
