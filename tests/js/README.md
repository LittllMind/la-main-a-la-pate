# Bundles de test générés

Les fichiers `.bundle.mjs` dans ce dossier sont des artefacts produits par esbuild
à partir des sources de `resources/js/`. Ils ne doivent pas être commités.

## Regénération

```bash
# Bundle pour les tests Node.js (JSDOM)
node_modules/.bin/esbuild resources/js/subject-editor.js --bundle --format=esm --outfile=tests/js/subject-editor.bundle.mjs --platform=node

# Bundle pour les tests Playwright (injection dans le navigateur)
node_modules/.bin/esbuild resources/js/subject-editor.js --bundle --format=esm --outfile=tests/browser/subject-editor.bundle.mjs --platform=browser
```

Le bundle Node inclut dynamiquement `jsdom` si un test l'instancie ; le bundle
navigateur est conçu pour être injecté via `page.evaluate(src => eval(src), bundle)`
dans les specs Playwright.
