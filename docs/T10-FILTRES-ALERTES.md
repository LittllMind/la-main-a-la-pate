## T10 - Filtres Alertes Stock Avancés

### Objectif
Améliorer la gestion des alertes de stock avec des filtres multicritères pour faciliter la prise de décision.

### Filtres Implémentés

| Filtre | Type | Description |
|----------|------|-------------|
| **Type d'alerte** | Select | Rupture, Faible, Tous |
| **Type de produit** | Select | Vinyle, Fond, Tous |
| **Statut** | Select | Actif, Résolu, Tous |
| **Date début/fin** | Date | Plage horaire |
| **Recherche** | Texte | Nom produit, référence |
| **Trier par** | Select | Date, Type, Produit |

### Routes

```php
GET /stock-alerts?type=rupture&produit=vinyle&statut=actif
GET /stock-alerts?date_debut=2026-03-01&date_fin=2026-03-09
```

### UI

- Section filtres rétractable (violet/rose Fundisc)
- Badges de filtres actifs
- Reset rapide
- Export rapide vers mouvements stock

### Commit
`feat/T10: Filtres avancés alertes stock - multicritères avec export rapide`
