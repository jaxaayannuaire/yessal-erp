# Étude comparative Yessal Caisse — Phase 1

**Projet :** Yessal ERP SAAS / Yessal Caisse  
**Version :** Phase 1  
**Date :** 29 août 2026  
**Statut :** Étude de conception — aucune migration métier autorisée avant validation

---

## 0. Objet et règles de l'étude

Yessal Caisse est le futur domaine métier POS de Yessal ERP SAAS, destiné notamment aux petits commerces et PME d'Afrique de l'Ouest.

Priorités issues du brief de recherche :

1. Simplicité.
2. Flutter mobile/tablette.
3. Offline-first.
4. Multi-tenant strict.
5. Paiements locaux.
6. Fiabilité de la caisse.
7. Produits et stock.
8. Synchronisation.
9. Évolutivité vers ERP/Dolibarr.

**Règle fondamentale : aucune migration Laravel métier Caisse ne doit être créée avant validation du modèle métier.**

Référence : `CAISSE_RESEARCH_BRIEF.md`.

---

# 1. Positionnement recommandé

## Décision — PROPOSÉE

> **Yessal Caisse = POS mobile/tablette offline-first, extrêmement simple, conçu pour les petits commerces ouest-africains, mais construit sur un modèle métier suffisamment robuste pour évoluer vers un ERP.**

Yessal Caisse ne doit pas chercher à reproduire toute la richesse d'Odoo ou d'ERPNext dans son MVP.

Les références sont utilisées pour identifier les meilleures pratiques :

- **Loyverse** : simplicité, caisse, stock, shifts et offline.
- **Kyte** : expérience mobile destinée aux petits commerces.
- **Square** : rigueur des paiements, offline et réconciliation.
- **Dolibarr / TakePOS** : intégration ERP et logique POS.
- **ERPNext** : séparation POS, stock et comptabilité.
- **Odoo** : architecture ERP, multi-sites et intégration.
- **Lightspeed Retail** : gestion avancée du retail et multi-sites.
- **Ultimate POS** : richesse fonctionnelle d'un POS basé sur Laravel/PHP.

---

# 2. Tableau comparatif des POS

| Fonction | Odoo | TakePOS | Loyverse | Square | Lightspeed | Kyte | Ultimate POS | ERPNext |
|---|---|---|---|---|---|---|---|---|
| POS | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Produits/variantes | ✓✓ | ✓ | ✓ | ✓✓ | ✓✓ | ✓ | ✓✓ | ✓✓ |
| Stock | ✓✓ | ✓ | ✓✓ | ✓✓ | ✓✓ | ✓ | ✓✓ | ✓✓ |
| Achats/fournisseurs | ✓✓ | ✓✓ ERP | ✓ | ✓✓ | ✓✓ | ✓ | ✓✓ | ✓✓ |
| Clients | ✓ | ✓ | ✓✓ | ✓✓ | ✓✓ | ✓✓ | ✓ | ✓ |
| Retours | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Multi-paiement | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Caisse/sessions | ✓ | ✓✓ | ✓✓ | ✓✓ | ✓✓ | ✓ | ✓ | ✓✓ |
| Multi-boutiques | ✓✓ | ✓ | ✓✓ | ✓✓ | ✓✓ | limité | ✓ | ✓✓ |
| Offline | ✓ | limité | ✓✓ | ✓ | selon configuration | ✓ | à vérifier | limité |
| Mobile natif | web responsive | web | ✓ | ✓ | ✓ | ✓ | web | web |
| Fidélité | ✓ | externe/ERP | ✓✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Rapports | ✓✓ | ✓ | ✓✓ | ✓✓ | ✓✓ | ✓ | ✓✓ | ✓✓ |
| Comptabilité | ✓✓ | ✓✓ | intégrations | ✓ | ✓✓ | limité | ✓ | ✓✓ |
| API | ✓✓ | ✓ | ✓ | ✓✓ | ✓✓ | ✓ | ✓ | ✓✓ |

**Lecture :** le tableau sert de synthèse de conception. Les capacités exactes doivent être vérifiées dans les documentations officielles avant toute décision technique spécifique.

---

# 3. Enseignements des références

## 3.1 Loyverse — PROPOSÉE

À reprendre :

- interface simple ;
- smartphone/tablette ;
- scanner caméra ;
- tickets ;
- shifts ;
- cash management ;
- stock ;
- inventaire ;
- fidélité ;
- multi-boutiques.

Point d'attention : les fonctions disponibles offline peuvent être limitées selon les opérations et les intégrations. Yessal doit donc définir explicitement ses propres règles offline.

## 3.2 Kyte — PROPOSÉE

À reprendre :

- approche petit commerçant ;
- mobile-first ;
- gestion simple ;
- vendeurs multiples ;
- inventaire ;
- fournisseurs ;
- fonctionnement offline ;
- synchronisation automatique au retour de connexion.

## 3.3 Square — PROPOSÉE

À reprendre :

- réconciliation ;
- contrôle des paiements ;
- gestion du risque offline ;
- distinction transaction locale / synchronisée ;
- remboursements ;
- inventaire multi-sites.

## 3.4 Dolibarr / TakePOS — PROPOSÉE

À reprendre conceptuellement :

```text
Produit
Client
Vente
Facture
Paiement
Stock
Caisse
```

TakePOS constitue surtout une référence pour l'intégration future avec Dolibarr.

## 3.5 ERPNext — PROPOSÉE

À reprendre :

```text
POS Transaction
      ↓
Session
      ↓
Stock / Accounting Posting
```

La séparation des responsabilités doit permettre à Yessal Caisse de rester léger tout en préparant l'intégration ERP.

## 3.6 Odoo / Lightspeed / Ultimate POS — PROPOSÉE

Ces solutions servent principalement de références pour :

- richesse du catalogue ;
- variantes ;
- multi-boutiques ;
- inventaires ;
- rôles ;
- promotions ;
- rapports ;
- intégrations ;
- extensibilité.

**Principe :** reprendre les concepts utiles sans transformer le MVP en ERP complet.

---

# 4. Périmètre fonctionnel

## 4.1 MVP — PROPOSÉE

### Catalogue

- produits ;
- catégories ;
- prix ;
- SKU ;
- code-barres ;
- variantes simples ;
- unités ;
- image ;
- actif/inactif.

### Vente

- panier ;
- recherche ;
- scan ;
- quantité ;
- remise ;
- taxe ;
- client ;
- paiement ;
- ticket ;
- paiement comptant ;
- paiement mixte.

### Caisse

- ouverture ;
- fonds initial ;
- session ;
- entrée ;
- sortie ;
- clôture ;
- comptage ;
- écart ;
- justification.

### Stock

- stock courant ;
- mouvements ;
- sortie par vente ;
- entrée ;
- ajustement ;
- inventaire simple.

### Utilisateurs

- administrateur ;
- responsable ;
- caissier.

### Offline

- ventes ;
- paiements espèces ;
- tickets ;
- mouvements ;
- sessions ;
- synchronisation.

---

# 5. V1 — PROPOSÉE

- clients avancés ;
- fournisseurs ;
- achats ;
- commandes fournisseurs ;
- retours ;
- remboursements ;
- avoirs ;
- inventaires avancés ;
- multi-boutiques ;
- multi-caisses ;
- promotions ;
- fidélité ;
- rapports avancés ;
- exports CSV/Excel/PDF ;
- imprimantes Bluetooth ;
- QR pairing ;
- plusieurs appareils.

---

# 6. V2 — PROPOSÉE

- objectifs ;
- commissions ;
- performance vendeurs ;
- lots ;
- dates d'expiration ;
- produits composés ;
- recettes ;
- transferts inter-boutiques ;
- commandes ;
- livraison ;
- catalogue en ligne ;
- WhatsApp ;
- Google Sheets ;
- API publique.

---

# 7. Fonctionnalités avancées — À VALIDER

- comptabilité native ;
- crédit client avancé ;
- wallet ;
- IA ;
- prévision de stock ;
- marketplace ;
- gestion restaurant avancée ;
- cuisine/KDS ;
- multi-devise ;
- multi-société ;
- paie.

---

# 8. Hors périmètre initial — PROPOSÉE

Ne pas construire initialement :

- comptabilité générale ;
- CRM complet ;
- RH ;
- paie ;
- marketplace ;
- wallet financier ;
- crédit ;
- microfinance ;
- ERP complet.

Ces fonctionnalités doivent rester des modules futurs et ne pas devenir des dépendances du cœur Caisse.

---

# 9. Architecture fonctionnelle cible

La couche SaaS existante suit :

```text
Utilisateur
  ↓
Organisation
  ↓
Abonnement
  ↓
Plan
  ↓
Modules
  ↓
Entitlements
  ↓
Quotas
  ↓
Yessal Caisse
```

Architecture métier proposée :

```text
YESSAL SAAS
    ↓
Organization / Tenant
    ↓
Business / Entreprise
    ↓
Store / Boutique
    ↓
Terminal
    ↓
Cash Session
```

Catalogue :

```text
Product
   ↓
ProductVariant
   ↓
StockLevel
   ↓
StockMovement
```

Vente :

```text
Sale
 ├── SaleLine
 ├── Payment
 └── Refund
```

---

# 10. Modèle métier proposé

## 10.1 Entités principales — PROPOSÉE

```text
Business
Store
Terminal
Device

Product
ProductVariant
Category
PriceList

Customer
Supplier

Sale
SaleLine
Payment
PaymentMethod
PaymentProvider
Refund
RefundLine

CashSession
CashMovement
CashCount
CashDifference

StockLocation
StockLevel
StockMovement
Inventory
InventoryLine

Promotion
Discount
LoyaltyAccount

AuditLog
SyncEvent
SyncConflict
```

---

# 11. Organisation et tenant

## 11.1 Principe — VALIDÉE

Le multi-tenant est strict.

Toute donnée métier doit être isolée par organisation.

Les entités principales doivent porter, directement ou indirectement :

```text
organization_id
```

et, selon leur nature :

```text
business_id
store_id
terminal_id
device_id
user_id
```

Exemple :

```text
Sale
 ├── organization_id
 ├── store_id
 ├── terminal_id
 ├── device_id
 └── user_id
```

## 11.2 Contrôle obligatoire

Le contexte :

```http
X-Organization-Id
```

doit être résolu et contrôlé avant toute opération métier.

Une ressource ne doit jamais être accessible uniquement parce que son UUID/ID est connu.

Flux :

```text
Sanctum
   ↓
ResolveOrganizationContext
   ↓
Subscription
   ↓
Entitlement
   ↓
Quota
   ↓
Authorization
   ↓
Business Service
```

---

# 12. Produits et stock

## 12.1 Stock — PROPOSÉE

Ne pas utiliser uniquement :

```text
products.quantity
```

Le modèle cible utilise :

```text
StockLevel
StockMovement
```

Un mouvement conserve notamment :

```text
movement_type
quantity
before_quantity
after_quantity
reference_type
reference_id
user_id
device_id
created_at
```

Flux :

```text
Sale
 ↓
SaleLine
 ↓
StockMovement
```

Inventaire :

```text
Inventory
 ↓
InventoryLine
 ↓
StockAdjustment
```

---

# 13. Vente

Flux :

```text
CAISSE
 → CLIENT
 → PRODUITS
 → PANIER
 → PROMOTIONS / PRIVILÈGES
 → PAIEMENT
 → TICKET
```

Sans client :

```text
CLIENT PAR DÉFAUT
```

Le comportement exact des remises, coupons, avoirs et fidélité devra être validé.

---

# 14. Paiements

## 14.1 Modèle — PROPOSÉE

Ne pas créer une table distincte par opérateur.

Utiliser :

```text
Payment
PaymentMethod
PaymentProvider
PaymentReference
```

Exemple :

```text
Payment
 ├── method = mobile_money
 ├── provider = wave
 ├── amount
 ├── reference
 └── status
```

Moyens envisagés :

```text
CASH
WAVE
ORANGE_MONEY
FREE_MONEY
CARD
BANK_TRANSFER
MANUAL
```

Le modèle doit rester extensible aux futurs moyens locaux.

## 14.2 Paiement mixte

```text
ORDER
 ├── CASH
 ├── WAVE
 └── OTHER
```

## 14.3 Statuts

```text
UNPAID
PARTIALLY_PAID
PAID
OVERPAID
REFUNDED
```

---

# 15. Caisse

## 15.1 Entité centrale — PROPOSÉE

```text
CashSession
```

Statuts :

```text
OPEN
ACTIVE
CLOSING
CLOSED
CANCELLED
```

Une session appartient à :

```text
Organization
Store
Terminal
User
Device
```

## 15.2 Ouverture

```text
Connexion
 → Terminal
 → Fonds initial
 → Session ACTIVE
```

## 15.3 Mouvements

- entrée ;
- retrait ;
- dépense ;
- correction exceptionnelle.

## 15.4 Fermeture

```text
Montant théorique
      VS
Montant compté
      ↓
Écart
      ↓
Justification
      ↓
Validation
```

---

# 16. Retours, remboursements et avoirs

## PROPOSÉE

```text
VENTE
 ↓
RETOUR
 ↓
REMBOURSEMENT
OU
AVOIR
OU
ÉCHANGE
```

Les règles exactes de validation et d'autorisation restent **À VALIDER**.

---

# 17. Promotions et fidélité

Ordre de calcul proposé :

```text
Prix catalogue
 ↓
Promotion
 ↓
Coupon
 ↓
Remise
 ↓
Avoir
 ↓
Total final
 ↓
Paiement
```

Fidélité future :

- achats ;
- ancienneté ;
- régularité ;
- activité.

Récompenses :

- points ;
- coupons ;
- réductions ;
- privilèges.

**Statut : PROPOSÉE.**

---

# 18. Utilisateurs, rôles et appareils

Rôles initiaux :

- administrateur ;
- responsable ;
- caissier.

Rôles futurs possibles :

- commercial ;
- revendeur ;
- livreur.

Les appareils sont suivis par :

```text
device_id
nom/type
utilisateur
rôle
dernière activité
dernière synchronisation
statut
```

Statuts :

```text
ACTIF
BLOQUÉ
RÉVOQUÉ
```

---

# 19. PIN et actions sensibles

## PROPOSÉE

PIN personnel de 4 chiffres pour :

- déverrouillage ;
- changement rapide de caissier ;
- confirmation d'actions sensibles.

Verrouillage par défaut :

```text
10 minutes
```

Options prévues :

```text
5 / 10 / 15 / 30 minutes
```

Actions sensibles :

- annulation ;
- remboursement ;
- remise importante ;
- retrait de caisse ;
- fermeture ;
- modification de stock ;
- modification de prix.

---

# 20. QR Pairing

## PROPOSÉE

```text
Appareil principal
 → Générer QR temporaire

Nouvel appareil
 → Scanner QR
 → Pairing API
 → Validation
 → Device ID + Tokens
 → Synchronisation
```

Le QR doit être :

- temporaire ;
- à usage unique ;
- à expiration courte ;
- confirmé ;
- journalisé ;
- sans secret permanent.

---

# 21. Architecture offline-first

## 21.1 Principe — VALIDÉE

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
Base centrale
```

Une vente validée localement ne doit pas disparaître à cause d'une perte Internet, d'un crash ou d'une erreur de synchronisation.

---

# 22. Événements de synchronisation

Événements envisagés :

```text
SALE_CREATED
SALE_PAID
ORDER_CREATED
STOCK_MOVED
CUSTOMER_UPDATED
CASH_MOVEMENT_CREATED
PAYMENT_CREATED
REFUND_CREATED
```

Structure cible :

```text
SYNC_EVENT
 ├── event_id
 ├── organization_id
 ├── device_id
 ├── entity_type
 ├── entity_id
 ├── action
 ├── payload
 ├── client_created_at
 ├── server_received_at
 ├── status
 └── retry_count
```

## Idempotence — PROPOSÉE

Chaque événement doit disposer d'un identifiant unique généré côté client.

Si :

```text
event_id déjà traité
```

alors le serveur ne doit pas appliquer l'opération une seconde fois.

---

# 23. Gestion des conflits

## PROPOSÉE

Priorité par niveau de risque :

### Risque faible
- création client ;
- modification profil ;
- notes.

### Risque moyen
- produit ;
- prix ;
- stock.

### Risque élevé
- vente ;
- paiement ;
- remboursement ;
- clôture caisse.

Les opérations financières ne doivent jamais être silencieusement écrasées.

Les conflits importants doivent être :

```text
détectés
 → journalisés
 → conservés
 → résolus selon une règle explicite
```

---

# 24. Politique offline

La version d'architecture existante propose :

```text
0–24 h   → normal
24–72 h  → alertes
72 h+    → restrictions sensibles
7 jours+ → blocage nouvelles ventes
faible connectivité → exception possible jusqu'à 14 jours
```

**Statut : À VALIDER.**

Cette politique doit être décidée avant implémentation complète du moteur offline.

---

# 25. API cible

```text
/api/v1/
    organizations/
    products/
    categories/
    customers/
    suppliers/

    sales/
    payments/
    refunds/

    cash/
       sessions/
       movements/
       counts/

    stock/
       levels/
       movements/
       inventories/

    stores/
    terminals/
    devices/

    sync/
       push
       pull
       acknowledge
       conflicts

    reports/
```

Le détail des endpoints reste à spécifier dans un contrat API séparé.

---

# 26. Entitlements

La matrice actuelle contient déjà :

```text
pos.sell
pos.refund
pos.discount

cash.open
cash.close
cash.withdraw

sales.create
sales.returns

products.create
products.import

stock.adjust
stock.inventory

customers.create
customers.export

reports.basic
reports.advanced
reports.export

users.manage
users.roles

marketing.promotions
marketing.loyalty

performance.objectives
performance.commissions

sync.offline
sync.multi_device

dolibarr.sync
api.access
```

## Extensions proposées — À VALIDER

```text
pos.split_payment
pos.credit_sale
pos.exchange

cash.expense
cash.deposit
cash.transfer

stock.transfer
stock.purchase_receipt
stock.writeoff

devices.manage
devices.pair

stores.manage
terminals.manage

refund.approve
discount.approve

audit.export
sync.force
sync.conflict_resolution
```

Les associations commerciales définitives Pack → Module → Entitlement → Limite ne sont pas encore figées.

---

# 27. Intégration Dolibarr

## Architecture — PROPOSÉE

```text
Flutter
 ↓
Yessal API
 ↓
Yessal Core
 ↓
Dolibarr Connector
 ↓
Dolibarr API
 ↓
Instance client
```

**Ne pas faire :**

```text
Flutter → Dolibarr directement
```

## Mapping initial

| Yessal | Dolibarr |
|---|---|
| Customer | ThirdParty |
| Product | Product |
| Sale | Order |
| Invoice | Invoice |
| Payment | Payment |
| StockMovement | Stock movement |
| Store | Warehouse / entité selon modèle |
| User | User |

Synchronisations envisagées :

- produits ;
- clients ;
- commandes ;
- factures ;
- stocks ;
- paiements ;
- utilisateurs.

**Statut : PROPOSÉE.**

Le sens exact des synchronisations, les règles de priorité et les mécanismes de réconciliation restent **À VALIDER**.

---

# 28. Rapports et exports

Formats envisagés :

- CSV ;
- Excel ;
- PDF ;
- impression ;
- Google Sheets ;
- API REST.

Rapports initiaux :

- ventes ;
- chiffre d'affaires ;
- paiements ;
- caisse ;
- stock ;
- produits ;
- vendeurs.

---

# 29. Impression

Documents :

- ticket ;
- facture ;
- reçu ;
- devis ;
- bon de commande ;
- bon de préparation ;
- avoir ;
- bon de livraison.

Supports :

- Bluetooth ;
- USB ;
- Wi-Fi ;
- impression système.

Formats prioritaires :

```text
58 mm
80 mm
```

**Statut : PROPOSÉE.**

---

# 30. Audit

Chaque action importante doit conserver :

```text
Qui
Quoi
Quand
Où
Avant
Après
Pourquoi
```

Actions prioritaires :

- connexions ;
- changements de caissier ;
- ouverture/fermeture ;
- remises ;
- annulations ;
- remboursements ;
- avoirs ;
- stock ;
- prix ;
- paiements ;
- réimpressions.

Alertes possibles :

- annulations inhabituelles ;
- remises inhabituelles ;
- écarts répétés ;
- réimpressions nombreuses.

---

# 31. Particularités Afrique de l'Ouest

## PROPOSÉE

Le modèle doit être conçu pour :

- FCFA/XOF ;
- smartphones Android ;
- tablettes ;
- connectivité intermittente ;
- paiements espèces ;
- Wave ;
- Orange Money ;
- Free Money et autres moyens locaux ;
- plusieurs vendeurs ;
- plusieurs points de vente ;
- paiements mixtes ;
- fonctionnement offline.

Le système de paiement doit rester abstrait afin d'intégrer progressivement d'autres opérateurs ou infrastructures de paiement.

---

# 32. Sécurité API

```text
Flutter
 ↓ HTTPS
API
 ↓
Yessal Core
 ├── Auth
 ├── Device
 ├── Sync
 ├── Subscription
 └── Notifications
```

Contrôles :

- Access Token ;
- Device ID ;
- Tenant/Organization ID ;
- permissions ;
- statut abonnement ;
- statut appareil.

Mesures :

- HTTPS ;
- rate limiting ;
- protection brute-force ;
- logs ;
- rotation des secrets ;
- sauvegardes ;
- monitoring.

**Règle : aucun secret maître ne doit être stocké dans Flutter.**

---

# 33. Architecture Flutter proposée

Socle déjà retenu :

- Flutter / Dart ;
- Riverpod ;
- Drift ;
- SQLite ;
- Dio ;
- Flutter Secure Storage.

Structure :

```text
lib/
├── core/
│   ├── database/
│   ├── sync/
│   ├── auth/
│   ├── api/
│   ├── security/
│   └── notifications/
├── features/
│   ├── pos/
│   ├── cart/
│   ├── customers/
│   ├── products/
│   ├── orders/
│   ├── payments/
│   ├── cash/
│   ├── promotions/
│   ├── stock/
│   ├── reports/
│   ├── objectives/
│   └── settings/
├── shared/
│   ├── widgets/
│   ├── models/
│   └── utils/
└── main.dart
```

**Statut : PROPOSÉE / basée sur l'architecture existante.**

---

# 34. Ordre de calcul commercial

## PROPOSÉE

```text
Prix catalogue
 → Promotion
 → Coupon
 → Remise
 → Avoir
 → Total
 → Paiement
```

Les règles de priorité et de cumul doivent être validées avant développement.

---

# 35. Principes de conception

## VALIDÉS / À PRÉSERVER

```text
OFFLINE-FIRST
+
MULTI-TENANT STRICT
+
SÉCURITÉ PAR APPAREIL
+
PIN UTILISATEUR
+
SYNCHRONISATION IDÉMPOTENTE
+
PACKS ET ENTITLEMENTS
+
INTÉGRATIONS MODULAIRES
+
ÉVOLUTION PROGRESSIVE
```

---

# 36. Décisions à valider avant les migrations

Les points suivants doivent être validés explicitement :

1. `Organization → Business` : nécessité ou simplification.
2. Hiérarchie exacte `Organization → Store → Terminal`.
3. Produit vs variante.
4. Gestion des unités.
5. Taxes.
6. Prix et listes de prix.
7. Autorisation du stock négatif.
8. Crédit client.
9. Remboursements.
10. Avoirs.
11. Session de caisse.
12. Paiements mixtes.
13. Règles offline.
14. Résolution des conflits.
15. Multi-device.
16. Synchronisation Dolibarr.
17. Journal d'audit.
18. Règles promotions/remises.
19. Rôles et permissions.
20. Entitlements supplémentaires.

---

# 37. Séquence de développement recommandée

```text
PHASE A
Étude comparative POS
        ↓
PHASE B
Périmètre MVP / V1 / V2
        ↓
PHASE C
Modèle métier
        ↓
PHASE D
ERD / Tables / Relations / Contraintes
        ↓
PHASE E
Contrat API
        ↓
PHASE F
Validation fonctionnelle et technique
        ↓
PHASE G
Migrations Laravel
        ↓
PHASE H
Services métier
        ↓
PHASE I
API
        ↓
PHASE J
Flutter + Offline
        ↓
PHASE K
Sync / Dolibarr / périphériques
```

---

# 38. Documentation Yessal à mettre à jour

## `ROADMAP.md` — PROPOSÉE

Ajouter une phase dédiée :

```text
Étude et conception Yessal Caisse
 → Comparatif POS
 → Périmètre fonctionnel
 → Modèle métier
 → ERD
 → API Contract
 → Validation
 → Migrations
```

La roadmap actuelle place déjà les modèles métier et l'API Flutter parmi les prochaines étapes. Cette étude doit devenir l'étape de validation préalable.

## `CHANGELOG.md` — PROPOSÉE

Ajouter une entrée :

```text
2026-08-29 — Étude Yessal Caisse Phase 1

- Étude comparative des POS de référence.
- Définition du périmètre MVP/V1/V2.
- Proposition du modèle métier Caisse.
- Définition de l'architecture offline-first.
- Définition du principe d'idempotence et de synchronisation.
- Préparation du modèle d'intégration Dolibarr.
- Aucune migration métier créée.
```

## `PACK_MODULE_MATRIX.md` — PROPOSÉE

Conserver les entitlements existants.

Ajouter les nouveaux entitlements uniquement après validation.

Ne pas modifier les tarifs ou limites commerciales sur la base de cette étude.

## `architecture.md` — PROPOSÉE

Faire évoluer progressivement l'architecture actuelle vers :

```text
SaaS Core
 ↓
Organization
 ↓
Store
 ↓
Terminal
 ↓
Device
 ↓
Caisse
 ↓
Sales / Payments / Stock
 ↓
Sync
 ↓
Connectors
```

---

# 39. Statut global des décisions

| Sujet | Statut |
|---|---|
| Flutter | VALIDÉE |
| Offline-first | VALIDÉE |
| SQLite / Drift | VALIDÉE |
| Riverpod | VALIDÉE |
| Yessal API comme intermédiaire | VALIDÉE |
| Multi-tenant strict | VALIDÉE |
| `X-Organization-Id` | VALIDÉE |
| Entitlements SaaS | VALIDÉE |
| Idempotence sync | PROPOSÉE |
| Store / Terminal | PROPOSÉE |
| Modèle métier Caisse | À VALIDER |
| ERD définitif | À VALIDER |
| Migrations métier | BLOQUÉES jusqu'à validation |
| Paiements locaux | PROPOSÉE |
| Wave / Orange Money | PROPOSÉE |
| Politique offline 7/14 jours | À VALIDER |
| Conflits sync | À VALIDER |
| Dolibarr Connector | PROPOSÉE |
| Mapping Dolibarr | À VALIDER |
| Nouveaux entitlements | À VALIDER |
| Tarifs commerciaux | À VALIDER |
| Limites commerciales | À VALIDER |

---

# 40. Conclusion

La priorité n'est pas de construire immédiatement un POS riche, mais de construire un **noyau Caisse fiable, offline-first et multi-tenant**, capable de fonctionner dans les contraintes réelles des petits commerces ouest-africains.

Le cœur recommandé est :

```text
Organization
    ↓
Store
    ↓
Terminal
    ↓
Device
    ↓
CashSession
    ↓
Sale
    ├── SaleLine
    ├── Payment
    └── StockMovement
```

Autour de ce noyau :

```text
Customers
Products
Stock
Refunds
Reports
Audit
Sync
Dolibarr Connector
```

La prochaine étape technique ne doit donc **pas** être une migration Laravel immédiate.

Elle doit être la validation de :

```text
Modèle métier
      ↓
ERD
      ↓
Relations
      ↓
Contraintes tenant
      ↓
Statuts
      ↓
Index
      ↓
Audit
      ↓
API Contract
      ↓
Migrations
```

**Statut final de cette Phase 1 : étude préparatoire — validation humaine requise avant passage au schéma SQL et aux migrations.**

---

## Sources internes de référence

- `CAISSE_RESEARCH_BRIEF.md`
- `architecture.md`
- `ROADMAP.md`
- `CHANGELOG.md`
- `PACK_MODULE_MATRIX.md`

Les tarifs et limites commerciales restent explicitement hors validation technique à ce stade.
