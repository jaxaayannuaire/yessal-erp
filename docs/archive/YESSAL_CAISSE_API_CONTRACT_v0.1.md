# YESSAL CAISSE — API CONTRACT v0.1

**Statut : Projet de contrat technique**  
**Base : ERD métier v0.1 + architecture Yessal Caisse v0.1**

## 1. Principes

- API REST JSON.
- Préfixe : `/api/v1`.
- Authentification : Laravel Sanctum.
- Contexte tenant : `X-Organization-Id`.
- Contexte caisse : Shop + Terminal + Device.
- Toute écriture doit être idempotente.
- Le serveur est autoritaire sur les données centrales.
- Les ventes finalisées sont immuables.
- Les opérations offline sont synchronisées par événements.
- Aucun accès Flutter direct à Dolibarr.

## 2. En-têtes

Requête authentifiée standard :

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
X-Organization-Id: 1
X-Device-Id: <device-uuid>
X-Idempotency-Key: <unique-operation-id>
```

`X-Organization-Id` est obligatoire pour les endpoints métier.

`X-Device-Id` est obligatoire pour les opérations provenant d'un appareil enregistré.

`X-Idempotency-Key` est obligatoire pour les créations et commandes métier sensibles.

## 3. Réponse standard

Succès :

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

Erreur :

```json
{
  "success": false,
  "message": "Message lisible",
  "code": "business_error",
  "errors": {}
}
```

Codes HTTP principaux :

| HTTP | Usage |
|---|---|
| 200 | Lecture/action réussie |
| 201 | Ressource créée |
| 202 | Opération acceptée pour traitement/synchronisation |
| 400 | Requête invalide |
| 401 | Authentification requise |
| 403 | Entitlement/contexte interdit |
| 404 | Ressource introuvable |
| 409 | Conflit métier |
| 422 | Validation |
| 429 | Limite dépassée |
| 500 | Erreur serveur |

## 4. Bootstrap

### GET `/caisse/bootstrap`

But : préparer un appareil en un appel.

Réponse attendue :

```json
{
  "success": true,
  "data": {
    "organization": {},
    "shop": {},
    "terminal": {},
    "device": {},
    "register_profile": {},
    "payment_methods": [],
    "categories": [],
    "products": [],
    "customers": [],
    "taxes": [],
    "entitlements": [],
    "quotas": {},
    "sync": {
      "cursor": null,
      "server_time": "..."
    }
  }
}
```

Le bootstrap doit être versionnable afin d'éviter de télécharger inutilement tout le catalogue.

## 5. Shops

### GET `/shops`

Liste les boutiques accessibles à l'utilisateur.

### GET `/shops/{shop}`

Détail d'une boutique accessible.

### POST `/shops`

Création soumise à `shops.manage`.

### PUT/PATCH `/shops/{shop}`

Modification soumise à `shops.manage`.

## 6. Devices

### GET `/devices`

Liste des appareils autorisés.

### POST `/devices/pair`

Associe un appareil à partir d'un jeton de pairing QR.

### POST `/devices/{device}/revoke`

Révoque un appareil.

### POST `/devices/{device}/block`

Bloque un appareil.

### POST `/devices/{device}/unblock`

Réactive un appareil.

### GET `/devices/{device}/status`

Retourne statut, version, dernière activité et synchronisation.

## 7. Terminals

### GET `/terminals`

Liste les caisses logiques du Shop courant.

### POST `/terminals`

Crée un terminal.

### GET `/terminals/{terminal}`

Retourne le terminal.

### PATCH `/terminals/{terminal}`

Modifie sa configuration.

### POST `/terminals/{terminal}/assign-device`

Associe un device autorisé.

## 8. Produits

### GET `/caisse/products`

Paramètres :

```text
search
category_id
barcode
sku
status
updated_since
page
per_page
```

### GET `/caisse/products/{product}`

### POST `/caisse/products`

Création selon `products.create`.

### PATCH `/caisse/products/{product}`

Modification selon les permissions.

### POST `/caisse/products/import`

Import CSV/Excel selon `products.import`.

## 9. Catégories

### GET `/caisse/categories`

### POST `/caisse/categories`

### PATCH `/caisse/categories/{category}`

### DELETE `/caisse/categories/{category}`

## 10. Clients

### GET `/caisse/customers`

Filtres : nom, téléphone, statut, `updated_since`.

### POST `/caisse/customers`

Entitlement : `customers.create`.

### GET `/caisse/customers/{customer}`

### PATCH `/caisse/customers/{customer}`

### GET `/caisse/customers/{customer}/credits`

Retourne les dettes ouvertes.

### POST `/caisse/customers/{customer}/credits/{credit}/payments`

Enregistre un règlement de dette.

## 11. Sessions de caisse

### GET `/caisse/cash-sessions/current`

Retourne la session ouverte du terminal.

### POST `/caisse/cash-sessions/open`

Payload :

```json
{
  "terminal_id": 1,
  "device_id": "device-uuid",
  "opening_amount": 50000
}
```

Entitlement : `cash.open`.

### POST `/caisse/cash-sessions/{session}/movements`

Payload :

```json
{
  "type": "cash_out",
  "amount": 5000,
  "reason": "Achat fournitures"
}
```

### GET `/caisse/cash-sessions/{session}/movements`

### POST `/caisse/cash-sessions/{session}/close`

Payload :

```json
{
  "counted_amount": 147500,
  "variance_reason": "..."
}
```

Entitlement : `cash.close`.

Une fermeture avec écart peut exiger une autorisation supplémentaire.

## 12. Ventes

### POST `/caisse/sales`

Création d'un brouillon ou d'une vente offline.

Payload minimal :

```json
{
  "local_uuid": "uuid",
  "shop_id": 1,
  "terminal_id": 1,
  "cash_session_id": 10,
  "device_id": "device-uuid",
  "seller_user_id": 4,
  "customer_id": null,
  "lines": [
    {
      "product_id": 15,
      "quantity": 2,
      "unit_price": 1000,
      "discount_amount": 0
    }
  ]
}
```

Le serveur doit recalculer les montants critiques et ne pas faire confiance aux totaux envoyés par le client.

### GET `/caisse/sales`

Filtres :
- date ;
- statut ;
- terminal ;
- caissier ;
- vendeur ;
- client ;
- `updated_since`.

### GET `/caisse/sales/{sale}`

### POST `/caisse/sales/{sale}/finalize`

Finalisation d'une vente.

Entitlement : `sales.create` + droits caisse applicables.

### POST `/caisse/sales/{sale}/void`

Annulation selon `pos.void` ou permission équivalente.

### POST `/caisse/sales/{sale}/returns`

Création d'un retour.

Entitlement : `sales.returns` / `pos.refund`.

## 13. Paiements

### POST `/caisse/sales/{sale}/payments`

Payload :

```json
{
  "payment_method": "cash",
  "provider": null,
  "amount": 2000,
  "change_amount": 0,
  "external_reference": null,
  "status": "confirmed"
}
```

### GET `/caisse/sales/{sale}/payments`

### POST `/caisse/payments/{payment}/confirm`

Pour les paiements nécessitant une confirmation serveur.

### POST `/caisse/payments/{payment}/refund`

Selon `pos.refund`.

## 14. Stock

### GET `/caisse/stock`

Filtres :
- produit ;
- emplacement ;
- stock bas ;
- `updated_since`.

### GET `/caisse/stock/{product}`

### POST `/caisse/stock/adjustments`

Payload :

```json
{
  "stock_location_id": 1,
  "product_id": 15,
  "quantity": -3,
  "reason": "Casse"
}
```

Entitlement : `stock.adjust`.

### POST `/caisse/stock/inventories`

Création d'un inventaire.

### POST `/caisse/stock/inventories/{inventory}/finalize`

Finalisation et génération des mouvements.

## 15. Synchronisation

### POST `/caisse/sync/push`

Le client transmet une liste d'événements.

```json
{
  "device_id": "device-uuid",
  "events": [
    {
      "event_uuid": "uuid",
      "entity_type": "sale",
      "entity_id": "local-uuid",
      "action": "create",
      "occurred_at": "...",
      "payload": {}
    }
  ]
}
```

Réponse :

```json
{
  "success": true,
  "data": {
    "accepted": [],
    "rejected": [],
    "conflicts": []
  }
}
```

### GET `/caisse/sync/pull`

Paramètres :

```text
cursor
limit
```

Retourne les changements serveur depuis le curseur.

### GET `/caisse/sync/status`

Retourne :
- dernière synchronisation ;
- événements pending ;
- événements rejetés ;
- conflits ;
- curseur serveur.

## 16. Idempotence

Toute écriture sensible doit pouvoir être rejouée sans duplication.

Clés recommandées :

```text
organization_id
+
device_id
+
idempotency_key
```

Pour une vente :

```text
organization_id + local_uuid
```

doit être unique.

Une répétition d'une requête déjà traitée doit retourner le résultat initial plutôt que créer une nouvelle opération.

## 17. Conflits

Cas possibles :

- produit modifié localement et serveur ;
- client modifié sur deux appareils ;
- stock modifié simultanément ;
- session caisse incompatible ;
- vente déjà créée ;
- paiement déjà confirmé.

Les conflits financiers doivent être conservés dans `sync_conflicts`.

Réponse type :

```json
{
  "success": false,
  "code": "sync_conflict",
  "data": {
    "conflict_id": 42
  }
}
```

## 18. Entitlements / quotas

Les endpoints doivent vérifier les droits avant exécution.

Exemples :

```text
pos.sell
pos.refund
pos.discount
cash.open
cash.close
cash.withdraw
products.create
products.import
stock.adjust
stock.inventory
customers.create
reports.export
sync.offline
sync.multi_device
```

Un dépassement de quota retourne `429` ou `403` selon la nature de la restriction.

Exemple :

```json
{
  "success": false,
  "message": "Limite de produits atteinte.",
  "code": "quota_exceeded",
  "data": {
    "resource": "products",
    "usage": 50,
    "limit": 50
  }
}
```

## 19. Sécurité tenant

Chaque requête métier doit vérifier :

```text
Authenticated User
 ↓
Organization
 ↓
Shop
 ↓
Terminal
 ↓
Device
 ↓
Resource
```

Il est interdit de faire confiance à un `shop_id`, `terminal_id` ou `device_id` envoyé par le client sans vérifier son appartenance au tenant.

Le comportement testé avec `X-Organization-Id: 999` doit rester un refus `403` lorsque l'organisation est inaccessible.

## 20. Offline

L'API ne doit pas supposer que le client est connecté en permanence.

Le client doit pouvoir conserver localement :
- catalogue nécessaire ;
- clients nécessaires ;
- configuration caisse ;
- session ;
- ventes ;
- paiements ;
- mouvements ;
- événements de sync.

Le serveur reste la source de vérité après synchronisation.

## 21. Reporting

Endpoints à prévoir :

```text
GET /caisse/reports/daily
GET /caisse/reports/sales
GET /caisse/reports/products
GET /caisse/reports/cash-session/{session}
GET /caisse/reports/customers/credits
GET /caisse/reports/export
```

Les rapports avancés et exports sont protégés par les entitlements correspondants.

## 22. Audit

Les opérations sensibles doivent produire un audit côté serveur.

Minimum :

```text
user_id
device_id
organization_id
shop_id
action
entity_type
entity_id
before
after
reason
created_at
```

## 23. Versionnement

Le contrat est versionné par :

```text
/api/v1
```

Les changements incompatibles nécessiteront `/api/v2`.

Les champs nouveaux doivent être ajoutés de manière rétrocompatible autant que possible.

## 24. Ce qui reste à figer avant migrations

1. Noms définitifs des tables.
2. Types exacts des colonnes.
3. UUID vs BIGINT pour les identifiants centraux.
4. Précision des quantités.
5. Fiscalité.
6. Statuts définitifs.
7. Modèle de retours/avoirs.
8. Paiements mobiles.
9. Politique offline.
10. Conflits multi-device.
11. Quotas définitifs.
12. Entitlements définitifs.

## 25. Séquence

```text
ERD v0.1
 ↓
API Contract v0.1
 ↓
Validation technique
 ↓
Migrations Laravel
 ↓
Models
 ↓
Services métier
 ↓
Policies / Entitlements
 ↓
Tests API
 ↓
Flutter / Drift
 ↓
Sync Engine
```

**Statut : prêt pour revue technique avant génération des migrations.**
