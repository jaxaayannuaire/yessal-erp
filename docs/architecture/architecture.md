# Yessal ERP / Yessal Caisse — Architecture de référence

**Version : 0.2**  
**Statut : Document de référence évolutif**  
**Dernière mise à jour : 28 août 2026**

> Ce document consolide l’architecture issue de `Architecture_Globale_Yessal_Caisse_v0.1` et les éléments tarifaires actuellement disponibles.  
> **Les tarifs commerciaux ne sont pas définitifs** et doivent être validés dans l’étude commerciale et financière dédiée.

## 1. Vision

Yessal Caisse est une application Flutter Android, offline-first, intégrée à l’écosystème Yessal :

```text
YESSAL PLATFORM
├── Yessal Caisse
├── Yessal Web
├── Yessal Core API
├── Bot Telegram
├── Dolibarr
├── Marketplace (phase ultérieure)
├── Yessal Livraison (phase ultérieure)
└── Connecteurs externes
```

Architecture centrale :

```text
Flutter App
    ↓
SQLite / Drift
    ↓
Sync Queue
    ↓
Yessal Sync API
    ↓
Yessal Core
    ├── Auth
    ├── Tenants
    ├── Devices
    ├── Subscriptions
    ├── Entitlements
    ├── Notifications
    └── Connecteurs
```

Flutter ne communique pas directement avec toutes les instances Dolibarr :

```text
Flutter → Yessal API → Connecteur Dolibarr → Instance client
```

## 2. Architecture fonctionnelle

Le modèle cible est :

```text
YESSAL SAAS
    ↓
Organisation / Tenant
    ↓
Abonnement
    ↓
Pack / Plan
    ↓
Modules + Entitlements + Limites
    ↓
Services / Connecteurs / Provisioning
```

Le système doit permettre de vendre un même socle logiciel à plusieurs profils d’activité sans créer une application différente pour chaque secteur.

## 3. Packs actuellement référencés

Le tableau tarifaire fourni contient actuellement six packs :

| Pack source | Catégorie | Users max | Produits max | Installation | Mensuel indicatif | Annuel indicatif | Total Pack indicatif |
|---|---|---:|---:|---:|---:|---:|---:|
| Yessal Tambali | Facturation | 2 | 50 | 5 000 CFA | 833 CFA | 10 000 CFA | 15 000 CFA |
| Yessal POS | Resto/Boutique | 5 | 100 | 10 000 CFA | 2 167 CFA | 26 000 CFA | 36 000 CFA |
| Yessal Asso | Asso/Syndicat | 5 | 50 | 10 000 CFA | 3 333 CFA | 40 000 CFA | 50 000 CFA |
| Yessal Daara | Daara/École | 5 | 50 | 10 000 CFA | 3 333 CFA | 40 000 CFA | 50 000 CFA |
| Yessal Pro | PME | 5 | 200 | 10 000 CFA | 5 417 CFA | 65 000 CFA | 75 000 CFA |
| Yessal Immo | Immobilier | 5 | 50 | 15 000 CFA | 8 750 CFA | 105 000 CFA | 120 000 CFA |

**Important :** ces montants sont ceux du fichier tarifaire fourni, mais ils ne sont **pas considérés comme définitifs**. La validation commerciale et financière se fera dans la discussion dédiée.

Une nomenclature de travail avec les packs **Free, Caisse, Business, Association, Pro** a également été utilisée dans les discussions. Elle ne doit pas être considérée comme équivalente automatiquement à Tambali/POS/Asso/Daara/Pro/Immo tant que la matrice commerciale n’a pas été validée.

## 4. Entitlements et limites

Le pack doit déterminer au minimum :

```text
users_limit
terminals_limit
shops_limit
products_limit
customers_limit
storage_limit
modules[]
addons[]
dolibarr_enabled
sync_enabled
```

Des addons sont prévus notamment pour :

- utilisateur ;
- terminal ;
- boutique ;
- import contacts ;
- Google Sheets ;
- exports avancés ;
- connecteur comptable ;
- équipe / performance.

## 5. Modules fonctionnels

Modules de base identifiés :

- Caisse
- Ventes
- Achats
- Produits & Stock
- Clients
- Fournisseurs
- Facturation
- CRM
- Rapports
- Utilisateurs

Modules avancés / premium identifiés :

- lots ;
- multi-société ;
- multi-devise ;
- salaires ;
- tickets ;
- automatisation ;
- étiquetage ;
- workflow ;
- API / intégrations ;
- IA.

La matrice définitive **Pack → Modules → Entitlements → Limites** reste à valider commercialement.

## 6. Backend et modèle de données

Le backend actuel utilise Laravel API avec Sanctum.

Tables déjà présentes :

```text
users
organizations
organization_user
plans
subscriptions
payments
modules
module_plan
```

Relations principales :

```text
User ↔ Organization
Organization → Subscriptions
Subscription → Plan
Plan ↔ Module
Subscription → Payments
```

Le champ `plans.features` existe déjà et reste disponible pour les informations descriptives. Les autorisations fonctionnelles doivent progressivement utiliser la relation explicite `Plan → Modules`.

## 7. Authentification et accès

Sanctum protège l’API.

Le login doit rester accessible même lorsqu’un abonnement est expiré afin de permettre :

- connexion ;
- consultation du compte ;
- consultation de l’abonnement ;
- consultation des paiements ;
- renouvellement.

Un abonnement expiré ne doit donc pas désactiver le compte utilisateur.

Le middleware :

```text
app/Http/Middleware/EnsureSubscriptionActive.php
```

a été créé et testé.

Comportement validé :

```text
abonnement active  → accès autorisé
abonnement expired → HTTP 403
login /me          → restent accessibles
```

La prochaine évolution est le contrôle **par module**, plutôt qu’un simple contrôle global de l’abonnement.

## 8. Abonnements

Cycle fonctionnel validé :

```text
active
   ↓ échéance
past_due
   ↓ 3 jours de grâce
expired
```

Configuration :

```env
SUBSCRIPTION_GRACE_PERIOD_DAYS=3
```

La durée est volontairement configurable.

Le scheduler Laravel gère :

```text
subscriptions:expire
subscriptions:renew
```

Planification actuelle :

```text
subscriptions:expire → hourly
subscriptions:renew  → dailyAt('00:05')
```

Windows Task Scheduler exécute :

```text
php artisan schedule:run
```

chaque minute.

## 9. Paiements Wave

Le flux Wave est opérationnel et a été testé :

```text
Payment pending
    ↓
Initiate
    ↓
Wave checkout
    ↓
Webhook
    ├── checkout.session.completed → paid
    └── checkout.session.failed    → failed
```

Les paiements supportent notamment :

```text
type = initial
type = renewal
```

Un renouvellement payé réactive l’abonnement et recalcule sa date de fin selon le cycle de facturation.

## 10. Yessal Caisse — architecture Flutter

Socle retenu :

- Flutter / Dart ;
- Riverpod ;
- Drift ;
- SQLite ;
- Dio ;
- Flutter Secure Storage.

Structure cible :

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

## 11. Offline-first

Flux :

```text
UI
 ↓
SQLite locale
 ↓
Logique métier
 ↓
Sync Queue
 ↓
API
 ↓
Base centrale
```

Les opérations importantes deviennent des événements idempotents :

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
├── tenant_id
├── device_id
├── entity_type
├── entity_id
├── action
├── payload
├── created_at
└── sync_status
```

## 12. Sécurité utilisateur et appareils

Chaque utilisateur peut disposer d’un PIN personnel de 4 chiffres pour :

- déverrouillage ;
- changement rapide de caissier ;
- confirmation des actions sensibles.

Verrouillage par défaut : 10 minutes.

Options :

```text
5 / 10 / 15 / 30 minutes
```

Actions sensibles :

- annulation ;
- remboursement ;
- remise importante ;
- retrait de caisse ;
- fermeture de caisse ;
- modification de stock ;
- modification de prix.

Les appareils sont suivis avec :

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

## 13. Association par QR Code

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
- confirmé ;
- journalisé ;
- dépourvu de secrets permanents.

## 14. Flux de vente

```text
CAISSE
→ CLIENT
→ PRODUITS
→ PANIER
→ PROMOTIONS / PRIVILÈGES
→ PAIEMENT
→ TICKET
```

Sans client identifié :

```text
CLIENT PAR DÉFAUT
```

## 15. Paiements métier

Moyens prévus :

- espèces ;
- Wave ;
- Orange Money ;
- Free Money ;
- carte ;
- virement ;
- paiement manuel ;
- autres configurables.

Paiement mixte :

```text
ORDER
├── CASH
├── WAVE
└── OTHER
```

Statuts :

```text
UNPAID
PARTIALLY_PAID
PAID
OVERPAID
REFUNDED
```

## 16. Caisse

Ouverture :

```text
Connexion
→ Terminal
→ Fonds initial
→ Session ACTIVE
```

Mouvements :

- entrée ;
- retrait ;
- dépense ;
- correction exceptionnelle.

Fermeture :

```text
Montant théorique
vs
Montant compté
→ Écart
→ Justification
→ Validation
→ Responsable
```

## 17. Retours, remboursements et avoirs

```text
VENTE
→ RETOUR
→ REMBOURSEMENT
  OU AVOIR
  OU ÉCHANGE
```

## 18. Promotions, fidélité et objectifs

Ordre de calcul :

```text
Prix catalogue
→ Promotion
→ Coupon
→ Remise
→ Avoir
→ Total final
→ Paiement
```

Objectifs possibles :

- nombre de ventes ;
- chiffre d’affaires ;
- produit ;
- marque ;
- fournisseur ;
- jour/semaine/mois.

## 19. Audit et fraude

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

Surveillance :

- connexions ;
- changements de caissier ;
- ouvertures/fermetures ;
- remises ;
- annulations ;
- remboursements ;
- avoirs ;
- stock ;
- prix ;
- paiements ;
- réimpressions.

## 20. Dolibarr

Le connecteur Dolibarr est optionnel selon le pack / les fonctionnalités.

Principe :

```text
Yessal Caisse
    ↓
Yessal API
    ↓
Connecteur Dolibarr
    ↓
Instance Dolibarr client
```

Synchronisations envisagées :

- produits ;
- clients ;
- commandes ;
- factures ;
- stocks ;
- paiements ;
- utilisateurs.

## 21. Notifications et connecteurs

Canaux prévus :

- Telegram ;
- email ;
- temps réel ;
- Google Sheets ;
- API externes.

Après fermeture validée :

- Telegram immédiat ;
- email regroupé ;
- email critique prioritaire.

## 22. Rapports et exports

Formats :

- CSV ;
- Excel ;
- PDF ;
- impression ;
- Google Sheets ;
- API REST.

## 23. Impression

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

## 24. Politique offline

```text
0–24 h   → normal
24–72 h  → alertes
72 h+    → restrictions sensibles
7 jours+ → blocage nouvelles ventes
faible connectivité → exception possible jusqu’à 14 jours
```

## 25. Sauvegardes et mises à jour

Sauvegardes :

```text
SQLite locale
├── Backup local
├── Sync serveur
└── Google Drive optionnel
```

Conservation recommandée :

```text
7 quotidiennes
4 hebdomadaires
3 mensuelles
```

Mise à jour :

```text
Backup SQLite
→ Nouvelle version
→ Migration
→ Vérification
→ Reprise
```

Les migrations ne doivent pas supprimer les opérations non synchronisées.

## 26. Roadmap technique

### Fondation
- API Laravel ;
- Sanctum ;
- organisations ;
- plans ;
- abonnements ;
- paiements Wave ;
- scheduler.

### Monétisation
- expiration ;
- délai de grâce ;
- renouvellement ;
- contrôle d’accès ;
- historique des événements.

### Modules
- modules ;
- `module_plan` ;
- matrice Pack → Modules ;
- entitlements ;
- limites ;
- contrôle par module.

### Provisioning
- activation de services ;
- intégration Dolibarr ;
- upgrade / downgrade ;
- provisioning automatique.

### Frontend
- Flutter ;
- authentification ;
- organisation ;
- dashboard ;
- modules ;
- paiement ;
- synchronisation offline.

### Production
- tests ;
- sécurité ;
- monitoring ;
- sauvegardes ;
- CI/CD ;
- documentation API.

## 27. Décisions à ne pas figer

Les éléments suivants restent ouverts :

1. tarifs définitifs ;
2. nomenclature commerciale définitive des packs ;
3. correspondance exacte entre les packs commerciaux et les packs techniques ;
4. limites finales par pack ;
5. modules inclus par pack ;
6. addons et leurs prix ;
7. niveau exact d’intégration Dolibarr par pack.

Ces décisions seront validées dans l’étude commerciale et financière dédiée.
