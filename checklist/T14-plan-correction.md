# Plan de Correction T14 — Mode Marché

## 🔴 Problème principal

Les tests utilisent `Vente::factory()` mais le Mode Marché utilise `Order` avec `source='marche'`.

### Fichiers à corriger

1. **VentesJourTest.php** — Adapter pour Order
2. **ModeMarcheTest.php** — Déjà corrigé 'carte' → 'cb_terminal' + adapter Order
3. **AnnulationTest.php** — Adapter pour Order (si existe)
4. **ExportTest.php** — Adapter pour Order (si existe)

### Changements nécessaires

```php
// AVANT (tests actuels)
Vente::factory()->create(['date' => today(), 'total' => 25.50]);

// APRÈS
try {
    Order::factory()->create([
        'source' => 'marche',
        'statut' => 'payee',
        'total' => 25.50,
        'created_at' => now(),
    ]);
} catch (\Exception $e) {
    // Order a beaucoup de champs required
}
```

### Problème : Order nécessite beaucoup de champs

Le modèle `Order` a des champs obligatoires (`nom`, `prenom`, `email`, etc.) que `Vente` n'a pas.

**Solution** : Utiliser une factory spécifique ou définir des valeurs par défaut.

---

## 🎯 Décision à prendre

| Option | Avantage | Inconvénient |
|--------|----------|--------------|
| **A** Modifier tests pour Order | Teste le vrai système | Factory complexe |
| **B** Modifier contrôleur pour Vente | Simple | N'utilise pas l'archi existante |
| **C** Créer factory Order simplifiée | Réutilisable | À créer |

**Recommandation** : Option A — Adapter les tests à l'archi existante (Order source=marche)

---

## 📋 Checklist correction

- [ ] Analyse factory Order
- [ ] Vérifier si fields nullable ou required
- [ ] Adapter VentesJourTest
- [ ] Adapter ModeMarcheTest
- [ ] Adapter autres tests T14
- [ ] Exécuter tests T14
- [ ] Corriger échecs restants


# Corrections T14 appliquées le 13 mars 2026

## VentesJourTest.php
- ✅ Réécrit complet avec Order::factory() source='marche'
- ✅ Helper createMarcheOrder() pour standardiser
- ✅ Tests JSON avec ?view=json

## ModeMarcheTest.php
- ⚠️ Nécessite ajout 'source' => 'marche' aux 3 premiers tests
- Tests T14.1 lignes 35, 63, 89

## À tester après corrections
```bash
php artisan test tests/Feature/ModeMarche/VentesJourTest.php --testdox
php artisan test tests/Feature/ModeMarche/ModeMarcheTest.php --testdox
```
