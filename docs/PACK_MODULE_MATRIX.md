# Yessal ERP — PACK / MODULE / ENTITLEMENT MATRIX

> État technique. Tarifs et associations commerciales définitives à valider.

## Packs
- Free
- Caisse
- Business
- Association
- Pro

## Modules techniques
| Module | Slug |
|---|---|
| Caisse | `caisse` |
| Ventes | `ventes` |
| Produits & Stock | `stock` |
| Clients | `clients` |
| Fournisseurs & Achats | `achats` |
| Facturation | `facturation` |
| Rapports | `rapports` |
| Utilisateurs & Équipes | `utilisateurs` |
| CRM | `crm` |
| Promotions & Fidélité | `marketing` |
| Objectifs & Commissions | `performance` |
| Audit & Sécurité | `audit` |
| Synchronisation | `sync` |
| Intégration Dolibarr | `dolibarr` |
| API & Intégrations | `api` |
| Notifications | `notifications` |

## Entitlements définis

### Caisse
`pos.sell`, `pos.refund`, `pos.discount`, `cash.open`, `cash.close`, `cash.withdraw`

### Ventes
`sales.create`, `sales.quote`, `sales.order`, `sales.returns`

### Produits & Stock
`products.create`, `products.import`, `stock.adjust`, `stock.inventory`

### Clients
`customers.create`, `customers.export`

### Fournisseurs & Achats
`suppliers.manage`, `purchases.create`, `purchases.order`

### Facturation
`invoices.create`, `invoices.credit_note`

### Rapports
`reports.basic`, `reports.advanced`, `reports.export`

### Utilisateurs & Équipes
`users.manage`, `teams.manage`, `users.roles`

### CRM
`crm.leads`, `crm.activities`

### Promotions & Fidélité
`marketing.promotions`, `marketing.loyalty`

### Objectifs & Commissions
`performance.objectives`, `performance.commissions`

### Audit
`audit.view`

### Synchronisation
`sync.offline`, `sync.multi_device`

### Dolibarr
`dolibarr.sync`

### API
`api.access`

### Notifications
`notifications.telegram`, `notifications.email`

## Association de test
```text
Pack Tambali → Caisse → pos.sell
```
Cette association est uniquement destinée aux tests et ne constitue pas la matrice commerciale définitive.

## Limites
Les plans disposent actuellement de `max_users` et `max_products`. Les valeurs définitives restent à valider.
