import { test, expect } from '@playwright/test';
import fs from 'node:fs/promises';
import path from 'node:path';

const bundlePath = path.resolve('tests/js/subject-editor.bundle.mjs');

async function mountEditor(page, values = {}) {
    await page.setContent(`
        <style>
            .hidden { display: none !important; }
            .tab-panel.active { display: block; }
        </style>
        <form id="subject-form">
        <main data-markdown-editor>
            <div role="tablist" aria-label="Audiences">
                <button type="button" data-editor-tab="body" role="tab" aria-controls="body-panel" aria-selected="true">Instruction</button>
                <button type="button" data-editor-tab="citizen_body" role="tab" aria-controls="citizen_body-panel" aria-selected="false">Public connecté</button>
                <button type="button" data-editor-tab="public_body" role="tab" aria-controls="public_body-panel" aria-selected="false">Public déconnecté</button>
            </div>
            <div id="body-panel" data-editor-panel="body" class="tab-panel active" role="tabpanel">
                <textarea id="body" data-editor-field data-audience-label="Instruction">${values.body ?? ''}</textarea>
            </div>
            <div id="citizen_body-panel" data-editor-panel="citizen_body" class="tab-panel hidden" role="tabpanel">
                <textarea id="citizen_body" data-editor-field data-audience-label="Public connecté">${values.citizen_body ?? ''}</textarea>
            </div>
            <div id="public_body-panel" data-editor-panel="public_body" class="tab-panel hidden" role="tabpanel">
                <textarea id="public_body" data-editor-field data-audience-label="Public déconnecté">${values.public_body ?? ''}</textarea>
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
        </form>`);
    const bundle = await fs.readFile(bundlePath, 'utf-8');
    await page.evaluate((src) => {
        // Injection synchrone pour forcer l'exécution immédiate de l'IIFE.
        eval(src);
    }, bundle);
    const loaded = await page.evaluate(() => window.__editorLoaded || false);
    if (!loaded) throw new Error('subject-editor bundle did not execute in browser');
}

test('audience active seule et texte conservé entre les onglets', async ({ page }) => {
    await mountEditor(page, { citizen_body: 'Connecté' });
    await page.locator('[data-editor-tab="citizen_body"]').click();
    await expect(page.locator('#citizen_body-panel')).toBeVisible();
    await page.locator('#citizen_body').fill('Texte non enregistré');
    await page.locator('[data-editor-tab="body"]').click();
    await page.locator('[data-editor-tab="citizen_body"]').click();
    await expect(page.locator('#citizen_body')).toHaveValue('Texte non enregistré');
    await expect(page.locator('[data-preview-audience]')).toContainText('Public connecté');
});

test('outils markdown enveloppent la sélection et insèrent des retours à la ligne', async ({ page }) => {
    await mountEditor(page, { body: 'Bonjour' });
    const body = page.locator('#body');

    await body.selectText();
    await page.getByRole('button', { name: 'Gras' }).click();
    await expect(body).toHaveValue('**Bonjour**');

    await body.focus();
    await body.fill('**Bonjour**');
    await body.evaluate((el) => { el.selectionStart = el.selectionEnd = el.value.length; });
    await page.getByRole('button', { name: 'Liste' }).click();
    await expect(body).toHaveValue('**Bonjour**\n- élément\n');

    await body.focus();
    await body.fill('Première ligne');
    await page.getByRole('button', { name: 'Italique' }).click();
    await expect(body).toHaveValue('*Première ligne*');
});

test('toolbar marque le formulaire dirty', async ({ page }) => {
    await mountEditor(page, { body: '' });
    const indicator = page.locator('[data-unsaved-indicator]');
    await expect(indicator).toBeHidden();
    await page.getByRole('button', { name: 'Tableau' }).click();
    await expect(indicator).toBeVisible();
});

test('aperçu rend markdown complet', async ({ page }) => {
    await mountEditor(page, { body: '# Titre\nParagraphe\n\n- Parent\n  - Enfant\n\n> Citation\n\n```js\ncode\n```\n\n---\n\n| A | B | C |\n| --- | --- | --- |\n| a |  | c |' });
    const preview = page.locator('[data-markdown-preview]');
    await expect(preview.locator('h1')).toHaveText('Titre');
    await expect(preview.locator('ul ul li')).toHaveText('Enfant');
    await expect(preview.locator('blockquote')).toContainText('Citation');
    await expect(preview.locator('pre code')).toContainText('code');
    await expect(preview.locator('hr')).toHaveCount(1);
    await expect(preview.locator('table tr')).toHaveCount(2);
    await expect(preview.locator('table td').nth(1)).toHaveText('');
});

test('aperçu ignore les titres Markdown temporairement vides', async ({ page }) => {
    await mountEditor(page, { body: '## \n### \nTexte conservé' });
    const preview = page.locator('[data-markdown-preview]');
    await expect(preview.locator('h2, h3')).toHaveCount(0);
    await expect(preview).toContainText('Texte conservé');
});

test('listes imbriquées mixtes produisent le DOM attendu', async ({ page }) => {
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

test('pas de boucle MutationObserver sur le libellé d\'audience', async ({ page }) => {
    await mountEditor(page, { body: 'x' });
    const count = await page.evaluate(() => {
        const previewAudience = document.querySelector('[data-preview-audience]');
        if (!previewAudience) throw new Error('Missing preview audience node');

        let mutations = 0;
        const observer = new MutationObserver(() => { mutations += 1; });
        observer.observe(previewAudience, { childList: true, subtree: true });

        document.querySelector('[data-editor-tab="citizen_body"]').click();
        document.querySelector('[data-editor-tab="public_body"]').click();

        return new Promise((resolve) => {
            // Laisser le temps à une boucle éventuelle de se manifester sans compter sur un simple setTimeout.
            const start = performance.now();
            const check = () => {
                if (performance.now() - start > 50) {
                    observer.disconnect();
                    resolve(mutations);
                } else {
                    requestAnimationFrame(check);
                }
            };
            requestAnimationFrame(check);
        });
    });
    expect(count).toBeLessThanOrEqual(2);
});

test('submit valide supprime le beforeunload', async ({ page }) => {
    await mountEditor(page, { body: 'x' });
    let dialogSeen = false;
    page.once('dialog', async (dialog) => {
        dialogSeen = true;
        await dialog.dismiss().catch(() => {});
    });
    await page.locator('button[type="submit"]').click();
    await page.waitForTimeout(200);
    expect(dialogSeen).toBe(false);
});

test('lien Annuler demande confirmation si formulaire dirty', async ({ page }) => {
    await mountEditor(page, { body: 'x' });
    page.on('dialog', (dialog) => {
        if (dialog.type() === 'confirm') {
            dialog.accept();
        } else {
            dialog.dismiss().catch(() => {});
        }
    });
    await page.locator('#body').fill('y');
    let clicked = false;
    await page.evaluate(() => {
        document.querySelector('[data-confirm-leave]').addEventListener('click', () => {
            window.__cancelClicked__ = true;
        });
    });
    await page.locator('[data-confirm-leave]').click();
    clicked = await page.evaluate(() => window.__cancelClicked__ || false);
    expect(clicked).toBe(true);
});

test('lien Annuler ne bloque pas si formulaire non modifié', async ({ page }) => {
    await mountEditor(page, { body: 'x' });
    let dialogSeen = false;
    page.once('dialog', async (dialog) => {
        dialogSeen = true;
        await dialog.dismiss().catch(() => {});
    });
    await page.locator('[data-confirm-leave]').click();
    await page.waitForTimeout(200);
    expect(dialogSeen).toBe(false);
});
