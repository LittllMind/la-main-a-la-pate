import { strict as assert } from 'node:assert';
import { JSDOM } from 'jsdom';

const FIXTURE = `
    <form id="subject-form">
    <main data-markdown-editor>
        <div role="tablist" aria-label="Audiences">
            <button type="button" data-editor-tab="body" role="tab" aria-selected="true">Instruction</button>
            <button type="button" data-editor-tab="citizen_body" role="tab" aria-selected="false">Public connecté</button>
            <button type="button" data-editor-tab="public_body" role="tab" aria-selected="false">Public déconnecté</button>
        </div>
        <div data-editor-panel="body" class="tab-panel active" role="tabpanel">
            <textarea id="body" data-editor-field data-audience-label="Instruction">Initial</textarea>
        </div>
        <div data-editor-panel="citizen_body" class="tab-panel hidden" role="tabpanel">
            <textarea id="citizen_body" data-editor-field data-audience-label="Public connecté">Citoyen</textarea>
        </div>
        <div data-editor-panel="public_body" class="tab-panel hidden" role="tabpanel">
            <textarea id="public_body" data-editor-field data-audience-label="Public déconnecté">Public</textarea>
        </div>
        <div role="toolbar">
            <button type="button" data-insert="**texte**">Gras</button>
            <button type="button" data-insert="*texte*">Italique</button>
            <button type="button" data-insert="\\n- élément\\n">Liste</button>
            <button type="button" data-insert="\\n> ">Citation</button>
            <button type="button" data-insert="\\n| A | B |\\n| --- | --- |\\n| a |  |\\n">Tableau</button>
        </div>
        <div role="group" aria-label="Mode d'aperçu">
            <button type="button" data-preview-mode="write" aria-selected="false">Écrire</button>
            <button type="button" data-preview-mode="write-preview" aria-selected="true">Écrire + aperçu</button>
            <button type="button" data-preview-mode="preview" aria-selected="false">Aperçu</button>
        </div>
        <span data-preview-audience>Instruction</span>
        <div data-markdown-preview data-preview-panel></div>
        <span data-unsaved-indicator class="hidden">Modifications non enregistrées</span>
        <button data-save-button type="submit">Enregistrer</button>
        <a href="/annuler" data-confirm-leave data-cancel-link>Annuler</a>
    </main>
    </form>
`;

function setup() {
    const dom = new JSDOM(FIXTURE, {
        url: 'http://localhost',
        pretendToBeVisual: true,
        resources: 'usable',
    });
    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.localStorage = dom.window.localStorage;
    globalThis.MutationObserver = dom.window.MutationObserver;
    globalThis.HTMLElement = dom.window.HTMLElement;
    globalThis.Event = dom.window.Event;
    globalThis.requestAnimationFrame = (cb) => setImmediate(cb);
    return dom;
}

async function loadModule() {
    await import('./subject-editor.bundle.mjs');
}

function click(element) {
    if (!element) throw new Error('click() appelé sur un élément null');
    const event = new Event('click', { bubbles: true, cancelable: true });
    element.dispatchEvent(event);
}

function input(element) {
    const event = new Event('input', { bubbles: true });
    element.dispatchEvent(event);
}

function setText(textarea, value) {
    textarea.value = value;
    textarea.selectionStart = textarea.selectionEnd = value.length;
    input(textarea);
}

async function run() {
    setup();
    await loadModule();

    const indicator = document.querySelector('[data-unsaved-indicator]');
    const saveButton = document.querySelector('[data-save-button]');
    const preview = document.querySelector('[data-markdown-preview]');
    const previewAudience = document.querySelector('[data-preview-audience]');
    const body = document.querySelector('#body');
    const citizenBody = document.querySelector('#citizen_body');
    const form = document.querySelector('#subject-form');

    const buttons = Array.from(document.querySelectorAll('button[data-insert]'));
    const grasBtn = buttons.find((b) => b.textContent.trim() === 'Gras');
    const italicBtn = buttons.find((b) => b.textContent.trim() === 'Italique');
    const listeBtn = buttons.find((b) => b.textContent.trim() === 'Liste');
    const citationBtn = buttons.find((b) => b.textContent.trim() === 'Citation');
    const tableauBtn = buttons.find((b) => b.textContent.trim() === 'Tableau');

    assert.ok(grasBtn, 'bouton gras présent');
    assert.ok(italicBtn, 'bouton italique présent');
    assert.ok(listeBtn, 'bouton liste présent');
    assert.ok(citationBtn, 'bouton citation présent');
    assert.ok(tableauBtn, 'bouton tableau présent');

    // ----- audience active et onglets
    assert.strictEqual(body.value, 'Initial', 'valeur initiale body');
    click(document.querySelector('[data-editor-tab="citizen_body"]'));
    assert.strictEqual(previewAudience.textContent, 'Public connecté', 'libellé actualisé explicitement');
    assert.ok(citizenBody.parentElement.classList.contains('active'), 'panel citoyen actif');

    // ----- insertion gras enveloppe la sélection
    click(document.querySelector('[data-editor-tab="body"]'));
    body.selectionStart = 0;
    body.selectionEnd = body.value.length;
    click(grasBtn);
    assert.strictEqual(body.value, '**Initial**', 'gras enveloppe le texte sélectionné');
    assert.ok(!indicator.classList.contains('hidden'), 'dirty après insertion');

    // ----- insertion liste décode \n et conserve le curseur
    setText(body, 'A');
    click(listeBtn);
    assert.strictEqual(body.value, 'A\n- élément\n', 'retours à la ligne vrais');

    // ----- insertion italique sans sélection sélectionne le placeholder
    setText(body, '');
    click(italicBtn);
    assert.strictEqual(body.value, '*texte*', 'italique insère placeholder');
    assert.strictEqual(body.selectionStart, 1, 'curseur au début du placeholder');
    assert.strictEqual(body.selectionEnd, 1 + 'texte'.length, 'placeholder sélectionné');

    // ----- preview markdown : listes imbriquées mixtes
    setText(body, '- Alpha\n  1. Bravo\n  - Charlie\n- Delta');
    const mainList = preview.querySelector(':scope > ul');
    assert.ok(mainList, 'liste racine générée');
    assert.strictEqual(mainList.children.length, 2, 'deux éléments de premier niveau');
    const alpha = mainList.children[0];
    assert.ok(alpha.querySelector(':scope > ol > li'), 'liste ordonnée imbriquée');
    assert.ok(alpha.querySelector(':scope > ul > li'), 'liste non ordonnée imbriquée');

    // ----- insertion tableau directe
    setText(body, '');
    click(tableauBtn);
    assert.ok(body.value.startsWith('\n| A | B |'), 'tableau inséré');

    // ----- citation
    setText(body, 'Phrase');
    click(citationBtn);
    assert.ok(body.value.includes('\n> '), 'citation insérée');

    // ----- pas de boucle MutationObserver
    let mutations = 0;
    const observer = new MutationObserver(() => { mutations += 1; });
    observer.observe(previewAudience, { childList: true, subtree: false });
    click(document.querySelector('[data-editor-tab="citizen_body"]'));
    click(document.querySelector('[data-editor-tab="public_body"]'));
    await new Promise((resolve) => setTimeout(resolve, 50));
    observer.disconnect();
    assert.ok(mutations <= 2, `pas de boucle synchrone (mutations=${mutations})`);

    // ----- beforeunload bloque si dirty
    let beforeUnloadBlocked = false;
    const beforeUnloadEvt = new Event('beforeunload', { bubbles: true, cancelable: true });
    beforeUnloadEvt.preventDefault = () => { beforeUnloadBlocked = true; };
    window.dispatchEvent(beforeUnloadEvt);
    assert.ok(beforeUnloadBlocked, 'beforeunload bloque quand dirty');

    // ----- submit valide : avertissement supprimé
    const submitEvt = new Event('submit', { bubbles: true, cancelable: true });
    submitEvt.preventDefault = () => {};
    form.dispatchEvent(submitEvt);
    assert.ok(saveButton.disabled, 'bouton désactivé à la soumission');

    const beforeUnloadAfterSubmit = new Event('beforeunload', { bubbles: true, cancelable: true });
    let stillBlocked = false;
    beforeUnloadAfterSubmit.preventDefault = () => { stillBlocked = true; };
    window.dispatchEvent(beforeUnloadAfterSubmit);
    assert.ok(!stillBlocked, 'pas de blocage beforeunload après submit valide');

    // ----- lien Annuler : confirmation si dirty
    let defaultPrevented = false;
    const clickEvt = new Event('click', { bubbles: true, cancelable: true });
    clickEvt.preventDefault = () => { defaultPrevented = true; };

    const originalConfirm = window.confirm;
    let confirmMessage = '';
    window.confirm = (msg) => {
        confirmMessage = msg;
        return false;
    };
    document.querySelector('[data-confirm-leave]').dispatchEvent(clickEvt);
    window.confirm = originalConfirm;
    assert.ok(confirmMessage.includes('non enregistrées'), 'message de confirmation affiché');
    assert.ok(defaultPrevented, 'navigation annulée par défaut si utilisateur refuse');

    console.log('Subject editor JS checks passed');
}

run().catch((err) => {
    console.error(err);
    process.exit(1);
});
