# YESSAL CAISSE — SQL SCHEMA v0.2

**Statut : proposition technique à valider avant migrations Laravel**  
**Base : ERD métier v0.2 + API Contract v0.1**

## 1. Conventions

- PostgreSQL.
- PK internes : `bigint`.
- Identifiants opérationnels/offline : UUID.
- Tous les montants monétaires : `bigint` en unité monétaire, avec devise.
- Quantités : `numeric(12,3)`.
- Dates : `timestamptz`.
- Suppression physique déconseillée pour les données transactionnelles.
- Les tables métier sont isolées par `organization_id` directement ou via une relation contrôlée.

## 2. `shops`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
name varchar(150) not null
code varchar(50) not null
address text null
phone varchar(50) null
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

Contraintes :

```text
unique (organization_id, code)
index (organization_id, status)
```

## 3. `register_profiles`

```sql
id bigint primary key generated always as identity
shop_id bigint not null references shops(id)
name varchar(150) not null
default_customer_id bigint null references customers(id)
settings jsonb null
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

## 4. `terminals`

```sql
id bigint primary key generated always as identity
shop_id bigint not null references shops(id)
register_profile_id bigint null references register_profiles(id)
name varchar(150) not null
code varchar(50) not null
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

Contraintes :

```text
unique (shop_id, code)
index (shop_id, status)
```

Règle métier : un terminal ne possède qu'une session `open`.

## 5. `devices`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
shop_id bigint null references shops(id)
terminal_id bigint null references terminals(id)
device_uuid uuid not null
name varchar(150) null
platform varchar(30) null
app_version varchar(50) null
status varchar(30) not null default 'active'
last_seen_at timestamptz null
last_sync_at timestamptz null
paired_at timestamptz null
revoked_at timestamptz null
created_at timestamptz not null
updated_at timestamptz not null
```

Contraintes :

```text
unique (organization_id, device_uuid)
index (organization_id, status)
index (terminal_id, status)
```

## 6. `categories`

```sql
id bigint primary key generated always as identity
shop_id bigint not null references shops(id)
name varchar(150) not null
slug varchar(180) not null
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

```text
unique (shop_id, slug)
```

## 7. `products`

```sql
id bigint primary key generated always as identity
shop_id bigint not null references shops(id)
category_id bigint null references categories(id)
name varchar(200) not null
sku varchar(100) null
barcode varchar(100) null
unit varchar(30) not null default 'unit'
purchase_price bigint null
sale_price bigint not null
tax_rate numeric(8,4) null
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

Index/contraintes :

```text
index (shop_id, status)
index (shop_id, barcode)
unique (shop_id, sku) where sku is not null
unique (shop_id, barcode) where barcode is not null
```

## 8. `product_variants`

```sql
id bigint primary key generated always as identity
product_id bigint not null references products(id)
name varchar(200) not null
sku varchar(100) null
barcode varchar(100) null
purchase_price bigint null
sale_price bigint not null
attributes jsonb null
created_at timestamptz not null
updated_at timestamptz not null
```

Les variantes restent optionnelles fonctionnellement en MVP.

## 9. `stock_locations`

```sql
id bigint primary key generated always as identity
shop_id bigint not null references shops(id)
name varchar(150) not null
type varchar(30) not null default 'store'
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

## 10. `stock_levels`

```sql
id bigint primary key generated always as identity
stock_location_id bigint not null references stock_locations(id)
product_id bigint null references products(id)
product_variant_id bigint null references product_variants(id)
quantity numeric(12,3) not null default 0
reserved_quantity numeric(12,3) not null default 0
created_at timestamptz not null
updated_at timestamptz not null
```

Contrainte métier :

```text
exactement un des deux :
product_id XOR product_variant_id
```

Une contrainte SQL `CHECK` peut matérialiser cette règle.

## 11. `stock_movements`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
stock_location_id bigint not null references stock_locations(id)
product_id bigint null references products(id)
product_variant_id bigint null references product_variants(id)
type varchar(40) not null
quantity numeric(12,3) not null
unit_cost bigint null
reference_type varchar(100) null
reference_id bigint null
reason text null
created_by bigint null references users(id)
created_at timestamptz not null
```

Index :

```text
(organization_id, stock_location_id, created_at)
(stock_location_id, product_id, created_at)
```

## 12. `customers`

```sql
id bigint primary key generated always as identity
shop_id bigint not null references shops(id)
name varchar(200) not null
phone varchar(50) null
email varchar(150) null
address text null
credit_enabled boolean not null default false
status varchar(30) not null default 'active'
created_at timestamptz not null
updated_at timestamptz not null
```

Le client comptant peut être un client système associé au Shop.

## 13. `cash_sessions`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
shop_id bigint not null references shops(id)
terminal_id bigint not null references terminals(id)
device_id bigint null references devices(id)
opened_by bigint not null references users(id)
closed_by bigint null references users(id)
opening_amount bigint not null default 0
expected_amount bigint null
counted_amount bigint null
variance_amount bigint null
variance_reason text null
status varchar(30) not null default 'open'
opened_at timestamptz not null
closed_at timestamptz null
created_at timestamptz not null
updated_at timestamptz not null
```

Contrainte critique :

```text
unique partial index terminal_id where status = 'open'
```

## 14. `cash_movements`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
cash_session_id bigint not null references cash_sessions(id)
type varchar(40) not null
amount bigint not null
reason text null
reference varchar(150) null
created_by bigint null references users(id)
created_at timestamptz not null
```

Les montants sont positifs ; le type détermine le sens comptable.

## 15. `sales`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
shop_id bigint not null references shops(id)
terminal_id bigint not null references terminals(id)
cash_session_id bigint not null references cash_sessions(id)
device_id bigint null references devices(id)
cashier_user_id bigint not null references users(id)
seller_user_id bigint null references users(id)
customer_id bigint null references customers(id)
local_uuid uuid not null
receipt_number varchar(100) not null
status varchar(40) not null default 'draft'
subtotal bigint not null default 0
discount_amount bigint not null default 0
tax_amount bigint not null default 0
total_amount bigint not null default 0
paid_amount bigint not null default 0
due_amount bigint not null default 0
currency char(3) not null
finalized_at timestamptz null
created_at timestamptz not null
updated_at timestamptz not null
```

Contraintes :

```text
unique (organization_id, local_uuid)
unique (organization_id, receipt_number)
check(total_amount >= 0)
check(paid_amount >= 0)
check(due_amount >= 0)
```

## 16. `sale_lines`

```sql
id bigint primary key generated always as identity
sale_id bigint not null references sales(id)
product_id bigint null references products(id)
product_variant_id bigint null references product_variants(id)
product_name_snapshot varchar(200) not null
sku_snapshot varchar(100) null
barcode_snapshot varchar(100) null
quantity numeric(12,3) not null
unit_price bigint not null
discount_amount bigint not null default 0
tax_amount bigint not null default 0
total_amount bigint not null
created_at timestamptz not null
updated_at timestamptz not null
```

Les valeurs commerciales de la vente sont des snapshots.

## 17. `sale_payments`

```sql
id bigint primary key generated always as identity
sale_id bigint not null references sales(id)
payment_method varchar(40) not null
provider varchar(50) null
amount bigint not null
change_amount bigint not null default 0
status varchar(30) not null default 'pending'
external_reference varchar(150) null
declared_at timestamptz null
confirmed_at timestamptz null
created_at timestamptz not null
updated_at timestamptz not null
```

## 18. `sale_returns`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
sale_id bigint not null references sales(id)
reference_number varchar(100) not null
reason text null
amount bigint not null
refund_method varchar(40) null
status varchar(30) not null default 'pending'
created_by bigint null references users(id)
created_at timestamptz not null
updated_at timestamptz not null
```

## 19. `customer_credits`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
customer_id bigint not null references customers(id)
sale_id bigint null references sales(id)
original_amount bigint not null
paid_amount bigint not null default 0
remaining_amount bigint not null
due_date date null
status varchar(30) not null default 'open'
created_at timestamptz not null
updated_at timestamptz not null
```

## 20. `sync_events`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
shop_id bigint null references shops(id)
device_id bigint not null references devices(id)
event_uuid uuid not null
entity_type varchar(100) not null
entity_id varchar(100) not null
action varchar(50) not null
payload jsonb not null
status varchar(30) not null default 'pending'
error_code varchar(100) null
created_at timestamptz not null
processed_at timestamptz null
```

Contrainte :

```text
unique (organization_id, event_uuid)
```

## 21. `sync_conflicts`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
device_id bigint not null references devices(id)
entity_type varchar(100) not null
entity_id varchar(100) not null
local_version varchar(100) null
server_version varchar(100) null
conflict_type varchar(50) not null
local_payload jsonb null
server_payload jsonb null
resolution varchar(50) null
resolved_by bigint null references users(id)
resolved_at timestamptz null
created_at timestamptz not null
updated_at timestamptz not null
```

## 22. `audit_logs`

```sql
id bigint primary key generated always as identity
organization_id bigint not null references organizations(id)
shop_id bigint null references shops(id)
user_id bigint null references users(id)
device_id bigint null references devices(id)
action varchar(100) not null
entity_type varchar(100) not null
entity_id varchar(100) not null
before_payload jsonb null
after_payload jsonb null
reason text null
ip_address inet null
created_at timestamptz not null
```

## 23. Isolation tenant

Pour chaque relation reçue par l'API, vérifier la chaîne :

```text
organization
 → shop
 → terminal
 → device
 → resource
```

Les FK seules ne suffisent pas à garantir que deux ressources appartiennent au même tenant. Les services/policies doivent effectuer cette vérification.

## 24. Index prioritaires

```text
shops: (organization_id, status)
terminals: (shop_id, status)
devices: (organization_id, status)
products: (shop_id, status), (shop_id, barcode)
customers: (shop_id, phone)
cash_sessions: (terminal_id, status)
sales: (organization_id, created_at), (shop_id, created_at), (cash_session_id)
sale_lines: (sale_id)
sale_payments: (sale_id, status)
stock_levels: (stock_location_id, product_id), (stock_location_id, product_variant_id)
stock_movements: (organization_id, created_at)
sync_events: (organization_id, status), (device_id, status)
audit_logs: (organization_id, created_at)
```

## 25. Points nécessitant revue avant migrations

1. `customers` est utilisé par `register_profiles` avant sa définition : les migrations devront être ordonnées ou la FK ajoutée après création.
2. Les règles XOR produit/variante doivent être matérialisées par `CHECK`.
3. Les FK tenant-safe devront être complétées par validation applicative.
4. Les statuts définitifs doivent être constants côté code.
5. Les taxes nécessitent encore une décision détaillée.
6. La stratégie UUID/BIGINT doit être conservée comme décision d'architecture.
7. Les retours devront éventuellement avoir des lignes de retour plutôt qu'un montant global.
8. Les paiements de dette nécessiteront probablement une table de mouvements dédiée si le crédit devient avancé.

## 26. Statut

**PROPOSÉ — À VALIDER.**

Ce document doit être validé avant :
- création des migrations ;
- création des modèles Eloquent ;
- génération des endpoints transactionnels.
