# HEARTBEAT.md - Workflow Bougies-Stock

## 🎯 Mission

Transformer ce projet Laravel en site de vente de bougies artisanales, une tâche par heartbeat, en TDD strict.

**URL locale:** http://127.0.0.1:8000  
**Commande serveur:** `php artisan serve`  
**Build:** `npm run dev`  

---

## 📋 Workflow par Heartbeat

### Vérification état

- Y a-t-il une tâche en cours ?
- Tests verts de la tâche précédente ?
- Attente validation humaine ?

### Si nouvelle tâche

```
1. Lire PLAN-ROUTE-BOUGIES-COMPLET.md (dans ~/.openclaw/workspace/)
2. Identifier prochaine tâche
3. git checkout -b feature/T-X.Y-nom-tache
4. Commencer par le test (TDD)
```

### Développement

```
Pour chaque sous-tâche:
  a. Écrire test
  b. Vérifier test rouge
  c. Implémenter code minimal
  d. php artisan test --filter=NomTest
  e. Si rouge → corriger (pas de sous-agent)
  f. Si vert → sous-tâche suivante
```

### Finalisation (tests verts)

```
1. git commit -m "T-X.Y: Description"
2. php artisan test (tous les tests)
3. Si 100% verts → rapport détaillé → attente validation
4. Si rouges → mini-rapport → correction
```

### Après validation humaine

```
git checkout main
git merge feature/T-X.Y-nom
git push origin main
git branch -d feature/T-X.Y-nom
Mettre à jour FEUILLE_DE_ROUTE.md
Nouvelle branche → tâche suivante
```

---

## 📝 Format rapports

### Rapport complet (tests verts)

```
🎉 TÂCHE X.Y COMPLÉTÉE — En attente validation

📊 Tests: X/X passés (100%)
🌿 Branche: feature/T-X.Y-nom

📝 Fichiers:
- chemin/fichier1.php
- chemin/fichier2.blade.php

🎯 Résumé: [2-3 phrases]

🔗 À vérifier: http://127.0.0.1:8000/[route]

⏳ Action requise: Validation pour merge
```

### Mini-rapport (tests rouges)

```
⚠️ TÂCHE X.Y — Tests en correction

📊 Tests: X/Y passés

❌ Échecs:
- NomTest::methode - message

🔧 Correction: [action en cours]
```

---

## 📁 Références

- `~/.openclaw/workspace/PLAN-ROUTE-BOUGIES-COMPLET.md` — Plan détaillé
- `SOUL.md` — Qui je suis
- `AGENTS.md` — Commandes techniques
- `FEUILLE_DE_ROUTE.md` — Suivi (à créer)

---

## 🎯 Rappels

- Une tâche = une branche = un objectif
- Tests verts avant rapport
- Pas de sous-agent pour les tests
- Qualité > vitesse
- Jamais de référence à "vinyle"

---
*Tâche actuelle: Attente lancement T1.1*
