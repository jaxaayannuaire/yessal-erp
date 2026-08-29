# YESSAL CAISSE — ERD MÉTIER v0.1

## 1. Hiérarchie

```text
Organization
 └── Shop
      ├── RegisterProfile
      ├── Terminal
      │    └── CashSession
      │         └── CashMovement
      └── Device

Shop
 ├── Product
 │    └── ProductVariant
 ├── Category
 ├── StockLocation
 │    ├── StockLevel
 │    └── StockMovement
 └── Customer

Sale
 ├── SaleLine
 ├── SalePayment
 └── SaleReturn

CustomerCredit
AuditLog
SyncEvent
SyncConflict
```

## 2. Entités principales

### organizations
Tenant SaaS existant.

- id
- name
- slug
- country
- currency
- status

### shops
Boutique / point de vente.

- id
- organization_id
- name
- code
- address
- phone
- status

### register_profiles
Configuration logique de caisse.

- id
- shop_id
- name
- default_customer_id
- settings
- status

### terminals
Caisse logique.

- id
- shop_id
- register_profile_id
- name
- code
- status

Contrainte : un terminal ne possède qu'une session de caisse ouverte.

### devices
Téléphone/tablette physique.

- id
- organization_id
- shop_id
- terminal_id nullable
- device_uuid
- name
- platform
- app_version
- status
- last_seen_at
- last_sync_at
- paired_at
- revoked_at

## 3. Catalogue

### categories
- id
- shop_id nullable
- name
- slug
- status

### products
- id
- shop_id
- category_id nullable
- name
- sku
- barcode
- unit
- purchase_price
- sale_price
- tax_rate nullable
- status

### product_variants
À activer lorsque nécessaire.

- id
- product_id
- name
- sku
- barcode
- purchase_price
- sale_price
- attributes

## 4. Stock

### stock_locations
- id
- shop_id
- name
- type
- status

### stock_levels
- id
- stock_location_id
- product_variant_id nullable
- product_id nullable
- quantity
- reserved_quantity

Une ligne référence soit un produit simple soit une variante.

### stock_movements
- id
- stock_location_id
- product_id
- product_variant_id nullable
- type
- quantity
- unit_cost nullable
- reference_type
- reference_id
- reason nullable
- created_by
- created_at

Principe : le stock est construit à partir des mouvements ; `stock_levels` sert d'état courant performant.

## 5. Clients

### customers
- id
- shop_id
- name
- phone
- email nullable
- address nullable
- credit_enabled
- status

Le client comptant peut être représenté par un client système/default.

## 6. Ventes

### sales
- id
- organization_id
- shop_id
- terminal_id
- cash_session_id
- device_id
- cashier_user_id
- seller_user_id nullable
- customer_id nullable
- local_uuid
- receipt_number
- status
- subtotal
- discount_amount
- tax_amount
- total_amount
- paid_amount
- due_amount
- currency
- finalized_at

Statuts proposés :

```text
draft
pending_payment
paid
partially_paid
credit
cancelled
refunded
```

Une vente finalisée est immuable.

### sale_lines
- id
- sale_id
- product_id
- product_variant_id nullable
- product_name_snapshot
- sku_snapshot
- barcode_snapshot nullable
- quantity
- unit_price
- discount_amount
- tax_amount
- total_amount

Les snapshots garantissent la fidélité historique.

### sale_payments
- id
- sale_id
- payment_method
- provider nullable
- amount
- change_amount
- status
- external_reference nullable
- declared_at nullable
- confirmed_at nullable

Statuts :

```text
pending
declared
confirmed
failed
refunded
```

### sale_returns
- id
- sale_id
- reference_number
- reason
- amount
- refund_method
- status
- created_by
- created_at

## 7. Crédit client

### customer_credits
- id
- customer_id
- sale_id nullable
- original_amount
- paid_amount
- remaining_amount
- due_date nullable
- status

Statuts :

```text
open
partially_paid
paid
written_off
```

## 8. Sessions de caisse

### cash_sessions
- id
- terminal_id
- device_id
- opened_by
- closed_by nullable
- opening_amount
- expected_amount nullable
- counted_amount nullable
- variance_amount nullable
- variance_reason nullable
- status
- opened_at
- closed_at nullable

Statuts :

```text
open
closing
closed
```

### cash_movements
- id
- cash_session_id
- type
- amount
- reason
- reference nullable
- created_by
- created_at

Types :

```text
sale
cash_in
cash_out
expense
refund
adjustment
```

## 9. Synchronisation

### sync_events
- id
- organization_id
- shop_id
- device_id
- event_uuid
- entity_type
- entity_id
- action
- payload
- status
- error_code nullable
- created_at
- processed_at nullable

Statuts :

```text
pending
sent
acked
failed
rejected
quarantined
```

Contrainte critique : `event_uuid` unique par organisation.

### sync_conflicts
- id
- organization_id
- device_id
- entity_type
- entity_id
- local_version
- server_version
- conflict_type
- local_payload
- server_payload
- resolution nullable
- resolved_by nullable
- resolved_at nullable

Les conflits financiers ne doivent jamais être écrasés silencieusement.

## 10. Audit

### audit_logs
- id
- organization_id
- shop_id nullable
- user_id nullable
- device_id nullable
- action
- entity_type
- entity_id
- before_payload nullable
- after_payload nullable
- reason nullable
- ip_address nullable
- created_at

Actions prioritaires :
- vente annulée
- remboursement
- remise importante
- changement prix
- retrait caisse
- fermeture avec écart
- ajustement stock
- changement caissier
- modification dette
- révocation appareil

## 11. Relations

```text
Organization 1──N Shop
Shop 1──N RegisterProfile
Shop 1──N Terminal
Terminal 1──N CashSession
CashSession 1──N CashMovement

Shop 1──N Product
Product 1──N ProductVariant
Shop 1──N Category
Shop 1──N StockLocation
StockLocation 1──N StockLevel
StockLocation 1──N StockMovement

Shop 1──N Customer

Sale N──1 Shop
Sale N──1 Terminal
Sale N──1 CashSession
Sale N──1 Device
Sale N──1 Customer
Sale 1──N SaleLine
Sale 1──N SalePayment
Sale 1──N SaleReturn

Customer 1──N CustomerCredit

Organization 1──N Device
Organization 1──N SyncEvent
Organization 1──N AuditLog
```

## 12. Contraintes critiques

1. Toutes les données métier sont rattachées à l'organisation.
2. Les relations organisation/boutique sont contrôlées côté serveur.
3. Une vente finalisée ne peut pas être modifiée directement.
4. Les corrections passent par annulation, retour, remboursement ou avoir.
5. Chaque vente offline possède un `local_uuid`.
6. Les événements de synchronisation sont idempotents.
7. Un terminal ne peut avoir qu'une session `open`.
8. Une session fermée ne reçoit plus de mouvements.
9. Les montants sont stockés en entiers avec devise.
10. Les snapshots de vente sont obligatoires.
11. Toute opération sensible produit un audit.
12. Les conflits financiers sont conservés pour résolution explicite.

## 13. Décisions encore ouvertes

- Autorisation du stock négatif.
- Précision des quantités et unités décimales.
- Fiscalité et règles de TVA.
- Multi-device simultané sur un terminal.
- Politique exacte de blocage offline.
- Plafond monétaire offline.
- Numérotation locale des tickets.
- Modèle définitif des retours/avoirs.
- Gestion détaillée des variantes.
- Quotas Shop/Terminal/Device.
- Politique de résolution des conflits.

## 14. Séquence suivante

```text
ERD v0.1
 ↓
Validation contraintes
 ↓
API Contract v0.1
 ↓
Migrations Laravel
 ↓
Services métier
 ↓
Tests
 ↓
Flutter / Drift
 ↓
Sync Engine
```

**Statut : modèle proposé pour validation avant création des migrations métier.**
