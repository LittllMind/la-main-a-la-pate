# API Documentation — vinyles-stock

## Base URL

```
Développement: http://localhost:8000
Production: https://vinyles.votredomaine.com
```

## Authentification

Toutes les routes API admin nécessitent authentification via session Laravel.

Headers requis:
```
Accept: application/json
X-CSRF-TOKEN: {token}
```

Ou paramètre URL: `?view=json` (pour certains endpoints)

---

## Endpoints Publics

### Liste Vinyles (Kiosque)

```http
GET /kiosque/api/vinyls?page={page}&search={query}
```

**Paramètres:**
| Nom | Type | Obligatoire | Description |
|-----|------|-------------|-------------|
| page | integer | Non | Numéro de page (défaut: 1) |
| search | string | Non | Recherche artiste/modèle/genre |

**Réponse 200:**
```json
{
  "data": [
    {
      "id": 1,
      "artiste": "Pink Floyd",
      "album": "The Dark Side of the Moon",
      "genre": "Rock",
      "prix": 25.99,
      "stock": 3,
      "cover_url": "/storage/media/1/cover.jpg",
      "thumb_url": "/storage/media/1/conversions/thumb.jpg"
    }
  ],
  "current_page": 1,
  "last_page": 12,
  "total": 287
}
```

---

## Endpoints Admin (Auth requise: admin/employé)

### Dashboard — Stats

```http
GET /admin/api/dashboard
```

**Réponse 200:**
```json
{
  "stats": {
    "total_vinyls": 287,
    "total_orders": 1452,
    "revenue_month": 3450.00,
    "stock_low": 12
  },
  "recent_orders": [
    {
      "id": 1234,
      "customer": "Jean Dupont",
      "total": 45.99,
      "status": "completed",
      "created_at": "2026-03-13 14:30:00"
    }
  ]
}
```

---

### Mode Marché — Ventes du Jour

```http
GET /admin/marche/ventes-jour?date={YYYY-MM-DD}&view=json
```

**Paramètres:**
| Nom | Type | Obligatoire | Description |
|-----|------|-------------|-------------|
| date | string | Non | Date format YYYY-MM-DD (défaut: aujourd'hui) |
| view | string | Non | `json` pour forcer réponse JSON |

**Réponse 200:**
```json
{
  "date": "2026-03-13",
  "orders": [
    {
      "id": 1234,
      "customer_name": "Jean Dupont",
      "customer_email": "jean@example.com",
      "total": 45.99,
      "items_count": 2,
      "status": "completed",
      "created_at": "14:30:00",
      "cancelled_at": null,
      "items": [
        {
          "vinyle": "Pink Floyd - The Wall",
          "quantity": 1,
          "price": 25.99
        },
        {
          "vinyle": "Daft Punk - Discovery",
          "quantity": 1,
          "price": 20.00
        }
      ]
    }
  ],
  "total_amount": 1452.50,
  "orders_count": 12
}
```

---

### Mode Marché — Annuler Vente

```http
POST /admin/marche/{order_id}/cancel
```

**Paramètres URL:**
| Nom | Type | Description |
|-----|------|-------------|
| order_id | integer | ID de la commande à annuler |

**Corps:**
```json
{
  "reason": "Erreur de caisse"  // Optionnel
}
```

**Réponse 200 (succès):**
```json
{
  "success": true,
  "message": "Vente #1234 annulée, stock restauré",
  "order": {
    "id": 1234,
    "status": "cancelled",
    "cancelled_at": "2026-03-13 15:45:00",
    "restocked_items": [
      {"vinyle_id": 1, "quantity": 1},
      {"vinyle_id": 5, "quantity": 1}
    ]
  }
}
```

**Réponse 400 (déjà annulée):**
```json
{
  "success": false,
  "message": "Cette vente est déjà annulée"
}
```

**Réponse 403 (non autorisé):**
```json
{
  "message": "Accès refusé"
}
```

---

### Mode Marché — Export CSV

```http
GET /admin/marche/export?format=csv&date={YYYY-MM-DD}
```

**Paramètres:**
| Nom | Type | Obligatoire | Description |
|-----|------|-------------|-------------|
| format | string | Oui | `csv` (unique option supportée) |
| date | string | Non | Date à exporter (défaut: aujourd'hui) |

**Réponse:**
```
Content-Type: text/csv; charset=utf-8
Content-Disposition: attachment; filename="ventes-2026-03-13.csv"

"ID","Client","Email","Total","Nombre articles","Heure","Statut"
"1234","Jean Dupont","jean@example.com","45.99","2","14:30:00","Terminée"
"1235","Marie Martin","marie@example.com","32.50","1","14:45:00","Terminée"
```

---

### Users — Liste (Admin uniquement)

```http
GET /admin/api/users?page={page}
```

**Réponse 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "email": "admin@vinylstock.com",
      "role": "admin",
      "created_at": "2026-01-15"
    },
    {
      "id": 2,
      "name": "Employé 1",
      "email": "employe@vinylstock.com",
      "role": "employe",
      "created_at": "2026-02-01"
    }
  ],
  "current_page": 1,
  "last_page": 3,
  "total": 45
}
```

---

### Rapports — Mensuel PDF

```http
GET /admin/reports/monthly?month={MM}&year={YYYY}
```

**Paramètres:**
| Nom | Type | Obligatoire | Description |
|-----|------|-------------|-------------|
| month | string | Non | Mois 01-12 (défaut: mois courant) |
| year | string | Non | Année (défaut: année courante) |

**Réponse:**
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="rapport-2026-03.pdf"
```

---

## Codes d'erreur

| Code | Signification | Cas courants |
|------|---------------|--------------|
| 200 | OK | Succès |
| 302 | Redirect | Non authentifié (redirection vers login/kiosque) |
| 400 | Bad Request | Paramètres invalides, commande déjà annulée |
| 401 | Unauthorized | Non authentifié (JSON API) |
| 403 | Forbidden | Rôle insuffisant (ex: client tente accès admin) |
| 404 | Not Found | Ressource inexistante |
| 422 | Validation Error | Données formulaire invalides |
| 500 | Server Error | Erreur applicative |

---

## Rate Limiting

Pas de rate limiting configuré actuellement.

Recommandé en production:
```php
// routes/api.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## Webhooks (Futur)

Pas de webhooks implémentés.

Potentiels événements:
- `order.created` — Nouvelle commande
- `order.cancelled` — Commande annulée
- `stock.low` — Stock faible alerte

---

*Dernière mise à jour: 2026-03-13*
