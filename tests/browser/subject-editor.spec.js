import { test, expect } from '@playwright/test';
import path from 'node:path';

const scriptPath = path.resolve('resources/js/subject-editor.js');

async function mountEditor(page, values = {}) {
    await page.setContent(`
        <style>.hidden { display: none !important; }</style>
        <main data-markdown-editor>
            <div role="tablist" aria-label="Audiences">
                <button type="button" data-editor-tab="body" role="tab" aria-controls="body-panel" aria-selected="true">Travail</button>
                <button type="button" data-editor-tab="citizen_body" role="tab" aria-controls="citizen_body-panel" aria-selected="false">Citoyen</button>
                <button type="button" data-editor-tab="public_body" role="tab" aria-controls="public_body-panel" aria-selected="false">Public</button>
            </div>
            <div id="body-panel" data-editor-panel="body" class="tab-panel">
                <label for="body">Travail</label>
                <textarea id="body" data-editor-field data-audience-label="Travail">${values.body ?? ''}</textarea>
            </div>
            <div id="citizen_body-panel" data-editor-panel="citizen_body" class="tab-panel hidden">
                <label for="citizen_body">Citoyen</label>
                <textarea id="citizen_body" data-editor-field data-audience-label="Citoyen">${values.citizen_body ?? ''}</textarea>
            </div>
            <div id="public_body-panel" data-editor-panel="public_body" class="tab-panel hidden">
                <label for="public_body">Public</label>
                <textarea id="public_body" data-editor-field data-audience-label="Public">${values.public_body ?? ''}</textarea>
            </div>
            <div role="toolbar">
                <button type="button" data-insert="**texte**">Gras</button>
                <button type="button" data-insert="\\n- élément\\n">Liste</button>
                <button type="button" data-insert="\\n> ">Citation</button>
                <button type="button" data-insert="\\n| A | B |\\n| --- | --- |\\n| a |  |\\n">Tableau</button>
            </div>
            <div role="group" aria-label="Mode d'aperçu">
                <button type="button" data-preview-mode="write" aria-selected="true">Écrire</button>
                <button type="button" data-preview-mode="write-preview" aria-selected="false">Écrire + aperçu</button>
                <button type="button" data-preview-mode="preview" aria-selected="false">Aperçu</button>
            </div>
            <div data-markdown-preview data-preview-panel class="hidden"></div>
        </main>`);
    await page.addScriptTag({ path: scriptPath });
    await page.evaluate(() => window.setupMarkdownEditors());
}

test('audience active seule et texte conservé dans les trois modes', async ({ page }) => {
    await mountEditor(page, { body: 'Instruction', citizen_body: 'Connecté', public_body: '' });
    await page.locator('[data-preview-mode="write"]').click();
    await expect(page.locator('[data-editor-panel="body"]')).toBeVisible();
    await expect(page.locator('[data-editor-panel="citizen_body"]')).toBeHidden();
    await expect(page.locator('[data-editor-panel="public_body"]')).toBeHidden();
    await page.locator('[data-editor-tab="citizen_body"]').click();
    await expect(page.locator('#citizen_body')).toHaveValue('Connecté');
    await page.locator('#citizen_body').fill('Texte non enregistré');
    await page.locator('[data-editor-tab="body"]').click();
    await page.locator('[data-editor-tab="citizen_body"]').click();
    await expect(page.locator('#citizen_body')).toHaveValue('Texte non enregistré');
});

test('outils préservent la sélection et convertissent les retours à la ligne', async ({ page }) => {
    await mountEditor(page, { body: 'Bonjour' });
    const body = page.locator('#body');
    await body.selectText();
    await page.getByRole('button', { name: 'Gras' }).click();
    await expect(body).toHaveValue('**Bonjour**');
    await body.focus();
    await body.press('End');
    await page.getByRole('button', { name: 'Liste' }).click();
    await expect(body).toHaveValue('**Bonjour**\n- élément\n');
});

test('aperçu conserve les constructions Markdown de lecture', async ({ page }) => {
    await mountEditor(page, { body: '# Titre\nParagraphe\n\n- Parent\n  - Enfant\n\n> Citation\n\n---\n\n| A | B | C |\n| --- | --- | --- |\n| a |  | c |' });
    const preview = page.locator('[data-markdown-preview]');
    await expect(preview.locator('h1')).toHaveText('Titre');
    await expect(preview.locator('ul ul li')).toHaveText('Enfant');
    await expect(preview.locator('blockquote')).toContainText('Citation');
    await expect(preview.locator('hr')).toHaveCount(1);
    await expect(preview.locator('table tbody tr td')).toHaveCount(3);
    await expect(preview.locator('table tbody tr td').nth(1)).toHaveText('');
});

test('aperçu ignore les titres Markdown temporairement vides', async ({ page }) => {
    await mountEditor(page, { body: '## \n### \nTexte conservé' });
    const preview = page.locator('[data-markdown-preview]');

    await expect(preview.locator('h2, h3')).toHaveCount(0);
    await expect(preview).toContainText('Texte conservé');
});

test('aperçu conserve la hiérarchie lors d’une transition de type imbriquée', async ({ page }) => {
    await mountEditor(page, { body: '- Alpha\n  1. Bravo\n  - Charlie\n- Delta' });
    const preview = page.locator('[data-markdown-preview]');
    const mainList = preview.locator(':scope > ul');
    const alpha = mainList.locator(':scope > li').first();

    await expect(mainList).toHaveCount(1);
    await expect(mainList.locator(':scope > li')).toHaveCount(2);
    await expect(alpha.locator(':scope > ol > li')).toHaveCount(1);
    await expect(alpha.locator(':scope > ul > li')).toHaveCount(1);
    await expect(mainList.locator(':scope > li').nth(1)).toContainText('Delta');
    await expect(preview).toContainText('Bravo');
    await expect(preview).toContainText('Charlie');
});
