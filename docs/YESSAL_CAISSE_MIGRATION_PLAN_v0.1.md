# YESSAL CAISSE — PLAN DE MIGRATIONS LARAVEL v0.1

**Statut : validé comme prochaine étape de développement**
**Base : Master Spec v0.1, ERD v0.1, SQL Schema v0.2, API Contract v0.1**

## 1. Principe

Yessal Caisse s'ajoute au Core Yessal SaaS existant.

Les tables déjà présentes dans le Core ne doivent pas être recréées :
- `users`
- `organizations`
- `plans`
- `subscriptions`
- tables/modules existants selon le projet

Les migrations Caisse créent uniquement les nouvelles tables métier.

## 2. Ordre recommandé

```text
01 shops
02 register_profiles
03 terminals
04 devices
05 categories
06 products
07 product_variants
08 stock_locations
09 customers
10 stock_levels
11 stock_movements
12 cash_sessions
13 cash_movements
14 sales
15 sale_lines
16 sale_payments
17 sale_returns
18 customer_credits
19 sync_events
20 sync_conflicts
21 audit_logs
```

## 3. Dépendances

```text
organizations
 └── shops
      ├── register_profiles
      ├── terminals
      │    └── cash_sessions
      │         └── cash_movements
      ├── devices
      ├── categories
      ├── products
      │    └── product_variants
      ├── stock_locations
      │    ├── stock_levels
      │    └── stock_movements
      └── customers

sales
 ├── sale_lines
 ├── sale_payments
 └── sale_returns

customers
 └── customer_credits

organizations
 ├── sync_events
 ├── sync_conflicts
 └── audit_logs
```

## 4. Règles de migration

### Identifiants

- PK centrales : `bigint`.
- UUID opérationnels : `uuid`.
- `sales.local_uuid` unique par organisation.
- `sync_events.event_uuid` unique par organisation.

### Argent

Utiliser `bigint` pour les montants XOF.

Exemple :

```php
$table->bigInteger('total_amount')->default(0);
```

Ne pas utiliser `float` pour les montants.

### Quantités

```php
$table->decimal('quantity', 12, 3);
```

### JSON

PostgreSQL :

```php
$table->jsonb('settings')->nullable();
```

Si la compatibilité Laravel/database du projet impose `json`, conserver `json`.

## 5. Contraintes critiques

### Tenant

Toutes les tables métier doivent pouvoir être reliées au tenant.

Les FK ne remplacent pas les contrôles applicatifs :

```text
Organization
 → Shop
 → Terminal
 → Device
 → Resource
```

### Terminal

Un seul `cash_session` avec `status = open` par terminal.

PostgreSQL :

```sql
CREATE UNIQUE INDEX cash_sessions_one_open_per_terminal
ON cash_sessions (terminal_id)
WHERE status = 'open';
```

### Stock

`stock_levels` doit référencer exactement :

```text
product_id XOR product_variant_id
```

Prévoir un `CHECK`.

### Produits

Les SKU et codes-barres doivent être uniques dans leur boutique lorsqu'ils sont renseignés.

### Vente

```text
organization_id + local_uuid
```

doit être unique.

Le serveur ne doit jamais faire confiance aux totaux financiers envoyés par Flutter.

## 6. Retours

La version actuelle de `sale_returns` conserve un montant global.

Pour le MVP, cette structure peut être conservée.

Avant V1 avancée, prévoir :

```text
sale_returns
 └── sale_return_lines
      ├── product_id
      ├── quantity
      ├── amount
      └── stock_action
```

Cela permettra les retours partiels par article.

## 7. Crédit client

Le modèle MVP utilise `customer_credits`.

Si le crédit devient une fonctionnalité importante, V1/V2 devra introduire :

```text
customer_credit_transactions
```

Types :

```text
credit
payment
adjustment
write_off
```

Le solde sera alors calculable à partir des mouvements.

## 8. Sync

`sync_events` est une table d'idempotence et de traçabilité.

Cycle :

```text
pending
 ↓
sent
 ↓
acked
```

Échec :

```text
failed
 ↓
retry
```

Rejet :

```text
rejected
 ↓
quarantined
```

Aucun événement financier ne doit être supprimé automatiquement en cas d'erreur.

## 9. Audit

Les opérations sensibles doivent produire un `audit_log`.

Priorités :

```text
sale.void
sale.refund
sale.discount
sale.price_override
cash.withdraw
cash.close
stock.adjust
credit.write_off
device.revoke
```

## 10. Index prioritaires

Créer les index définis dans `YESSAL_CAISSE_SQL_SCHEMA_v0.2.md`.

Priorité absolue :

```text
organization_id
shop_id
terminal_id
device_id
created_at
status
barcode
sku
local_uuid
event_uuid
```

## 11. Factories / Seeders après migrations

Après validation des migrations :

```text
Organization existante
 ↓
Shop de test
 ↓
RegisterProfile
 ↓
Terminal
 ↓
Device
 ↓
Categories
 ↓
Products
 ↓
Customers
 ↓
Stock
 ↓
CashSession
 ↓
Sales
```

Le seeder doit permettre de reproduire rapidement le scénario de test actuel :

```text
Organization #1
Jaxaay Group
Pack Tambali
subscription active
```

Le Pack Tambali et ses entitlements restent gérés par le système SaaS existant.

## 12. Tests obligatoires avant API Caisse

### Tenant

```text
X-Organization-Id = 1 → autorisé
X-Organization-Id = 999 → 403
```

### Terminal

```text
ouvrir session → OK
ouvrir deuxième session → 409
```

### Vente

```text
vente locale → création
même local_uuid → aucune duplication
finalisation → immuable
```

### Paiement

```text
paiement partiel → dette
paiement complet → paid
paiement > total → règle de rendu
```

### Stock

```text
vente finalisée → mouvement stock
ajustement → mouvement stock
```

### Sync

```text
même event_uuid deux fois → une seule opération
```

## 13. Ne pas implémenter immédiatement

Les éléments suivants restent hors migrations MVP tant qu'ils ne sont pas spécifiés :

- promotions avancées ;
- fidélité ;
- objectifs ;
- commissions ;
- lots/péremptions ;
- séries ;
- achats fournisseurs avancés ;
- Dolibarr ;
- Wallet/Coffre ;
- marketplace ;
- livraison.

## 14. Séquence de développement

```text
SQL Schema v0.2
 ↓
Migration files
 ↓
Models Eloquent
 ↓
Factories / Seeders
 ↓
Policies
 ↓
Services métier
 ↓
Feature tests
 ↓
API Caisse
 ↓
Sync Engine
 ↓
Flutter / Drift
```

## 15. Statut

**PROCHAINE ACTION : créer les migrations Laravel Caisse.**

Les migrations doivent être ajoutées au projet existant sans modifier le fonctionnement actuel de :
- Auth/Sanctum ;
- Organizations ;
- Plans ;
- Subscriptions ;
- Entitlements ;
- Payments.
