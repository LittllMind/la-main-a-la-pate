# LMALP — HANDOFF SESSION 8/9

## État fonctionnel actuel

Architecture : un `Subject` = un dossier ; trois représentations parallèles :

- Travail → `body`
- Citoyen → `citizen_body`
- Public → `public_body`

Principe métier fondamental :

**Public n'est pas la version finale d'un sujet.**

Public et Citoyen sont des représentations éditoriales destinées à des audiences différentes. Les trois versions peuvent évoluer indépendamment pendant toute la vie du dossier.

## Publication

- `citizen_status` : `draft` / `published` / `hidden`
- `public_status` : `draft` / `published` / `hidden`
- `citizen_published_at`
- `public_published_at`

Actions UI : Publier aux citoyens, Masquer aux citoyens, Publier au public, Masquer au public.

Une modification de contenu ne modifie jamais implicitement un statut de diffusion.
`hidden` conserve le contenu.

## Visibilité

`VisibilityLevel` centralise `working`, `citizen`, `public`. Le même modèle s'applique aux documents.

Règle de sécurité :

- Guest → uniquement Public publié
- Citoyen identifié → Citoyen publié, avec accès Public
- Admin/modérateur/owner/collaborateur → Travail + Citoyen + Public selon leurs droits

Aucun fallback ne doit permettre de révéler du contenu interne à un niveau inférieur.

## Documents

`subject_documents.visibility` existe avec défaut sécurisé `working`.
Filtrage appliqué aux vues sujet, index documents, téléchargement, arbre documentaire, PDF, recherche.

## Recherche

Recherche fulltext scopée par niveau d'accès. Aucun contenu interne ne doit servir à produire un résultat accessible à un guest.

## Sécurité

Tests de non-fuite présents avec marqueurs dédiés `WORKING_SECRET_8F93X`, `CITIZEN_SECRET_72ABC`, `PUBLIC_VISIBLE_39ZZ`.

## UX

Les onglets affichent :

- Travail : Interne
- Citoyen : Brouillon / Publié / Masqué
- Public : Brouillon / Publié / Masqué

Dates de publication visibles. Index admin/modérateur affiche les badges Citoyen et Public. Publication publique avec confirmation. Impossible de publier une représentation vide.

## Tests de clôture

Run final : **144 tests / 471 assertions verts** — `Duration: 16.71s`.

## Dette technique / décisions à reprendre

### A. Workflow historique de publication

Ancienne méthode `publish()` avec vote des collaborateurs encore présente sous la route `publish.old`. Ambiguë avec le nouveau modèle à trois représentations. **Ne pas supprimer sans analyser le workflow métier historique et écrire les tests correspondants.**

### B. `Subject.status`

Reste historiquement un statut `draft | published`. La nouvelle architecture suggère qu'il devrait représenter le cycle de vie du dossier (active / on_hold / closed / archived), indépendamment de la diffusion. Aucune migration avant décision métier explicite.

### C. Commentaires anonymes

Fonctionnalité volontairement reportée. Ne pas lancer avant stabilisation du workflow éditorial, réflexion modération, confidentialité/RGPD et modèle de contribution.

## Règles de développement à conserver

- TDD strict : test avant correction ou fonctionnalité.
- Pas de refactor opportuniste hors du lot courant.
- Séparation stricte Travail / Citoyen / Public.
- Visibilité centralisée via `VisibilityLevel`.
- Politique conservatrice : en cas d'ambiguïté de visibilité, ne pas exposer.
- Suite complète verte avant clôture d'un lot.

## État Git

Branche courante : `v2-plateforme`.
Working tree non commité. Modifications locales de cette session couvrant routes, contrôleurs, modèles, vues, factories, migrations et tests. Aucun conflit détecté. Suite complète verte. Migration `2026_08_08_093632`, `2026_08_08_101534` et `2026_08_08_103707` appliquées.
