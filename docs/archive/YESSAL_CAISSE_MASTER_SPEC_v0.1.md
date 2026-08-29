# YESSAL CAISSE — MASTER SPECIFICATION v0.1

**Date : 29 août 2026**  
**Statut : Synthèse critique — à valider avant migrations**

## 1. Objet

Consolidation des trois études fournies : Google DeepSearch, ChatGPT Phase 1 et Claude Opus.

## 2. Convergences

Les études convergent sur :
- POS mobile/tablette ;
- Flutter ;
- offline-first ;
- SQLite/Drift ;
- Yessal API comme intermédiaire ;
- multi-tenant strict ;
- séparation Terminal / Device ;
- sessions de caisse ;
- ventes, lignes et paiements séparés ;
- stock fondé sur les mouvements ;
- synchronisation idempotente ;
- ventes finalisées immuables ;
- intégration Dolibarr ultérieure.

## 3. Positionnement recommandé

Yessal Caisse doit privilégier un noyau simple, fiable et offline-first adapté aux petits commerces ouest-africains, plutôt que reproduire la richesse d'un ERP complet.

## 4. Arbitrages

### Organization / Business
**PROPOSÉ :** ne pas ajouter `Business` pour l'instant. `Organization` reste le tenant SaaS.

### Shop
**PROPOSÉ :**
```text
Organization
  └── Shop
       ├── Terminal
       └── Device
```

### Terminal / Device
**PROPOSÉ :** séparation obligatoire. Terminal = caisse logique ; Device = téléphone/tablette physique.

### RegisterProfile
**PROPOSÉ :** conserver un profil de caisse centralisant configuration, moyens de paiement, taxes, client par défaut et utilisateurs autorisés.

### Stock négatif
**À VALIDER :** proposition d'autoriser la vente avec stock insuffisant, avec alerte et contrôle par rôle/entitlement.

## 5. MVP

- Produits simples, catégories, prix, prix d'achat, SKU/code-barres, unité.
- Stock par boutique, mouvements, ajustements motivés, alertes.
- Client comptant + clients nom/téléphone.
- Crédit client et règlement ultérieur.
- Panier, quantités, remises, ticket.
- Espèces, Wave, Orange Money, Free Money, autre.
- Paiement mixte et rendu monnaie.
- Paiements mobiles pouvant être `declared` puis `confirmed`.
- Ouverture/fermeture de caisse, fonds initial, entrées/sorties, dépenses, comptage, écart et justification.
- Rôles propriétaire/gérant/caissier, PIN et changement rapide.
- Rapports de session, ventes, produits, vendeurs, caisse, dettes.
- Impression 58/80 mm.
- Fonctionnement complet sans réseau.

## 6. V1

Variantes, multi-boutiques, multi-caisses, transferts de stock, achats/fournisseurs, inventaires, retours/remboursements, avoirs, taxes paramétrables, appairage QR, gestion appareils, exports et rapprochement des paiements mobiles.

## 7. V2

Promotions, coupons, fidélité, objectifs, commissions, dashboard multi-boutiques, connecteur Dolibarr, exports comptables, lots/péremptions/séries.

## 8. Modèle métier cible

```text
Organization
 └── Shop
      ├── RegisterProfile
      ├── Terminal
      │    └── CashSession
      │         └── CashMovement
      └── Device

Product
 ├── Category
 └── ProductVariant

StockLocation
 ├── StockLevel
 └── StockMovement

Customer

Sale
 ├── SaleLine
 ├── SalePayment
 └── SaleReturn

CustomerCredit

AuditLog
SyncEvent
SyncConflict
```

## 9. Règles structurantes proposées

R1. Une vente finalisée est immuable.  
R2. Paiements + dette couvrent le total.  
R3. Une vente finalisée génère les mouvements de stock correspondants.  
R4. Un terminal n'a qu'une session ouverte.  
R5. Une session ne se ferme pas avec des ventes brouillon non traitées.  
R6. Un écart de caisse exige une justification.  
R7. Stock négatif : décision encore à valider.  
R8. Prix et informations commerciales sont figés dans la ligne de vente.  
R9. Remise au-delà d'un seuil : autorisation.  
R10. Numérotation fonctionnelle hors ligne.  
R11. Toute écriture synchronisée est idempotente.  
R12. Tenant vérifié à la lecture et à l'écriture.  
R13. Serveur autoritaire sur le référentiel.  
R14. Les conflits financiers ne sont jamais silencieusement écrasés.

## 10. Argent

**PROPOSÉ :** entiers dans l'unité mineure avec `currency_exponent`. Pour XOF : exposant 0.

## 11. Vente et paiement

Séparer `cashier_user_id` et `seller_user_id`.

`SaleLine` conserve les snapshots du nom, SKU et prix afin que l'historique reste fidèle.

`SalePayment` doit supporter moyen, montant, rendu, statut, fournisseur, référence et rapprochement.

## 12. Offline / synchronisation

```text
Flutter
 ↓
SQLite / Drift
 ↓
Logique métier locale
 ↓
Sync Queue
 ↓
Yessal API
 ↓
Laravel Core
 ↓
PostgreSQL
```

Chaque opération possède un identifiant client unique.

```text
pending → sent → acked
             ↘ rejected → quarantined
pending → failed → retry
```

Les conflits importants sont détectés, journalisés et traités explicitement.

### Politique proposée, à valider

```text
0–24 h   normal
24–72 h  alertes
72 h+    restrictions sensibles
7 jours+ blocage nouvelles ventes
exception possible jusqu'à 14 jours
```

Un plafond monétaire cumulé hors ligne est également proposé, à valider.

## 13. Bootstrap

Un endpoint de bootstrap doit fournir en un appel :
shop, terminal, profil, moyens de paiement, catalogue, clients, taxes, droits, quotas et curseur de synchronisation.

## 14. API cible

```text
/api/v1/caisse/bootstrap
/api/v1/caisse/products
/api/v1/caisse/categories
/api/v1/caisse/partners
/api/v1/caisse/cash-sessions
/api/v1/caisse/cash-sessions/{id}/movements
/api/v1/caisse/cash-sessions/{id}/close
/api/v1/caisse/sales
/api/v1/caisse/sales/{id}/finalize
/api/v1/caisse/sales/{id}/payments
/api/v1/caisse/sales/{id}/void
/api/v1/caisse/sales/{id}/returns
/api/v1/caisse/stock/adjustments
/api/v1/caisse/inventories
/api/v1/caisse/sync/push
/api/v1/caisse/sync/pull
/api/v1/caisse/sync/status
```

Le contrat API détaillé reste à produire.

## 15. Entitlements

Réutiliser en priorité les droits existants : `pos.sell`, `pos.refund`, `pos.discount`, `cash.open`, `cash.close`, `cash.withdraw`, `sales.create`, `sales.returns`, `products.create`, `products.import`, `stock.adjust`, `stock.inventory`, `customers.create`, `reports.basic`, `reports.advanced`, `reports.export`, `sync.offline`, `sync.multi_device`, `dolibarr.sync`, etc.

Extensions proposées, **À VALIDER** :
`cash.deposit`, `pos.void`, `pos.price_override`, `pos.reprint`, `pos.negative_stock`, `pos.session.transfer`, `credit.grant`, `credit.collect`, `credit.write_off`, `shops.manage`, `terminals.manage`, `devices.pair`, `devices.revoke`, `stock.transfer`, `stock.view_cost`, `reports.z_report`, `payments.reconcile`.

Aucun tarif ou quota commercial définitif n'est déduit ici.

## 16. Sécurité et audit

Auditer notamment : annulation, remboursement, remise importante, changement de prix, retrait de caisse, fermeture avec écart, ajustement stock, changement caissier, effacement dette et révocation appareil.

## 17. Dolibarr

```text
Flutter → Yessal API → Yessal Core → Dolibarr Connector → Dolibarr API
```

Jamais Flutter → Dolibarr directement.

Mapping à étudier : Customer → ThirdParty, Product → Product, Sale → Order, Invoice → Invoice, Payment → Payment, StockMovement → mouvement de stock.

Le connecteur reste hors dépendance du MVP.

## 18. Décisions restant à valider

1. Politique offline et plafond monétaire.
2. Stock négatif.
3. Fiscalité.
4. Unités/décimales de quantité.
5. Variantes.
6. Crédit et échéances.
7. Retours/avoirs.
8. Multi-device simultané.
9. Résolution des conflits.
10. Entitlements définitifs.
11. Quotas shops/terminals/devices.
12. Paiements mobiles offline.
13. Numérotation tickets.
14. ERD définitif.

## 19. Séquence de développement

```text
Master Spec
 ↓
Validation décisions
 ↓
ERD
 ↓
Contraintes/index/statuts
 ↓
API Contract
 ↓
Validation technique
 ↓
Migrations Laravel
 ↓
Services métier
 ↓
API
 ↓
Flutter
 ↓
Sync
 ↓
Dolibarr
```

**Aucune migration métier Caisse avant validation du modèle.**
