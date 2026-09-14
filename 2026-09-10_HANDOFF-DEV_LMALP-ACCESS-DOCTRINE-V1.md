# Handoff DEV LMALP — Doctrine d'accès V1

Date : 2026-09-10
Projet : La Main à la Pâte
Branche : v2-plateforme
Commit : 7a0ddf3

## 1. Doctrine implémentée

Les représentations Subject sont parallèles :

- INSTRUCTION : `body`
- CITOYEN : `citizen_body`
- PUBLIC : `public_body`

La représentation par défaut dépend du niveau d'accès :

- Guest : PUBLIC uniquement si `public_status = published` et `public_body` non vide.
- Citoyen : CITOYEN publié si disponible, sinon PUBLIC publié.
- Instruction : INSTRUCTION (`body`) par défaut.
- Admin/modérateur : accès complet inchangé.

La Séraphothèque ACL exception removed by PILOTAGE decision.

## 2. Matrice finale

| Audience | Défaut | Accès inférieur | Accès supérieur |
|---|---|---|---|
| Guest | PUBLIC publié | — | — |
| CITOYEN | CITOYEN publié, sinon PUBLIC | PUBLIC | INSTRUCTION interdit |
| INSTRUCTION | INSTRUCTION | CITOYEN, PUBLIC | — |
| ADMIN/MODÉRATEUR | INSTRUCTION | CITOYEN, PUBLIC | — |

## 3. Fichiers modifiés

- `app/Models/Subject.php`
- `app/Http/Controllers/SubjectController.php`
- `app/Http/Controllers/DashboardController.php`
- Tests Feature de visibilité, documents, découvrabilité et dashboard.

## 4. bodyFor / bodyAtLevel

`SubjectController::show()` utilise désormais `canBeViewedBy()` puis `bodyFor()` au lieu de forcer `public_body`.

`bodyFor()` ne contient plus aucune exception ACL Séraphothèque.

`bodyAtLevel()` et les previews existants sont conservés pour les représentations explicites PUBLIC/CITOYEN.

## 5. Dashboard

`DashboardController::index()` applique `Subject::visibleTo($user)` avant l'activité récente. Un sujet non visible ne peut plus être découvert par son titre dans le dashboard.

## 6. public_is_listed

`public_is_listed` reste réservé aux surfaces de catalogue/découverte. Il n'est pas utilisé par `visibleTo()`, afin de ne pas masquer le travail autorisé d'un auteur ou d'un utilisateur Instruction.

## 7. Exceptions

`isSeraphothequeDossier()` est conservée pour le rendu narratif et la classification documentaire. Son effet ACL a été supprimé.

## 8. Tests

- Tests ciblés : GREEN — 26 tests, 104 assertions.
- Suite complète : GREEN — 501 tests, 2529 assertions.
- Build Vite : GREEN.
- Syntaxe PHP : GREEN.
- `git diff --check` : GREEN.

## 9. QA

Façade publique PROD :

- `/` : 200
- `/seraphotheque` : 200
- `/sujets/seraphotheque-situation-2026` : 200

Surfaces internes PROD en guest :

- `/sujets/arbre` : 302 vers `/`
- `/documents/arbre` : 302 vers `/`
- `/dashboard` : 302 vers `/`

Aucune navigation publique nouvelle, aucun catalogue public créé.

## 10. Commit / push / production

Commit : `7a0ddf3 fix(lmalp): align subject access hierarchy across audiences`

Push : `origin/v2-plateforme` effectué.

Déploiement PROD : effectué via rsync SSH port 65002. Composer et caches Laravel reconstruits avec succès.

QA PROD : PASS.

## 11. Dette volontaire restante

### ERGONOMIE MULTI-NIVEAUX — À TRAITER PLUS TARD

Créer ultérieurement une interface permettant à un utilisateur ayant plusieurs niveaux d'accès de basculer facilement entre les représentations disponibles d'un même sujet.

INSTRUCTION : `Instruction | Citoyen | Public`

CITOYEN : `Citoyen | Public`

Cette fonctionnalité n'appartient pas à la présente mission.

### NAVIGATION / CATALOGUE — À TRAITER PLUS TARD

- rationalisation `/sujets` vs `/sujets/arbre` ;
- articulation `/documents/arbre` ;
- ergonomie recherche ;
- dashboard ;
- nettoyage legacy `theme` / ancien `status`.

Aucun nouveau sujet publié. Aucun statut éditorial modifié. Aucun `public_body` créé.
