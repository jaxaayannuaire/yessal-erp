# Architecture Globale — Yessal Caisse

**Version : 0.1**  
**Statut : Document de travail évolutif**  
**Date : 25 août 2026**

## 1. Vision

Yessal Caisse est une application Flutter Android, offline-first, intégrée à l'écosystème :

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

## 2. Architecture centrale

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

## 3. Architecture Flutter

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

Socle retenu :

- Flutter / Dart
- Riverpod
- Drift
- SQLite
- Dio
- Flutter Secure Storage

## 4. Offline-First

```text
UI → SQLite locale → logique métier → Sync Queue → API → base centrale
```

Une vente validée localement ne doit jamais disparaître à cause d'une perte Internet, d'un crash ou d'une erreur de synchronisation.

Chaque opération importante devient un événement :

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

Structure :

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

Les événements doivent être idempotents.

## 5. Sécurité utilisateur

Chaque utilisateur possède :

- compte principal ;
- mot de passe ;
- PIN personnel de 4 chiffres.

Le PIN sert à :

- déverrouiller l'application ;
- changer rapidement de caissier ;
- confirmer les actions sensibles.

Verrouillage par défaut : **10 minutes**.

Options prévues :

- 5 minutes ;
- 10 minutes ;
- 15 minutes ;
- 30 minutes.

Actions sensibles :

- annulation ;
- remboursement ;
- remise importante ;
- retrait de caisse ;
- fermeture de caisse ;
- modification de stock ;
- modification de prix.

## 6. Politique Offline

```text
0–24 h → normal
24–72 h → alertes
Après 72 h → restrictions sensibles
Après 7 jours → blocage nouvelles ventes
Exception faible connectivité → jusqu'à 14 jours
```

## 7. Appareils et sessions

```text
Paramètres → Appareils & Sessions
```

Données suivies :

- device_id ;
- nom/type ;
- utilisateur ;
- rôle ;
- dernière activité ;
- date de connexion ;
- dernière synchronisation ;
- statut.

Statuts :

```text
ACTIF
BLOQUÉ
RÉVOQUÉ
```

Le commerçant peut voir, déconnecter, bloquer ou révoquer les appareils.

## 8. Association par QR Code

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

Le QR contient un jeton temporaire uniquement.

Sécurité :

- usage unique ;
- expiration courte ;
- confirmation ;
- journalisation ;
- aucun mot de passe ou secret dans le QR.

## 9. Hiérarchie

```text
YESSAL SAAS
↓
TENANT / COMMERÇANT
↓
ENTREPRISE
↓
BOUTIQUE
↓
TERMINAL
↓
UTILISATEUR
```

## 10. Utilisateurs et permissions

Rôles possibles :

- Administrateur ;
- Responsable ;
- Caissier ;
- Commercial ;
- Revendeur ;
- Livreur.

Le commerçant peut gérer groupes, permissions, tags, objectifs et équipes.

Avec Dolibarr :

```text
Yessal User
↓
Mapping
↓
Dolibarr User
↓
Dolibarr Groups
↓
Dolibarr Permissions
```

## 11. Flux de vente

Le client est sélectionné avant les produits :

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

Conséquences possibles :

- pas de coupon ;
- pas d'avoir ;
- privilèges limités ;
- fidélité non appliquée.

## 12. Produits

Tableau principal :

| Libellé | Qté | Tag/Catégorie | CA |
|---|---:|---|---:|

Filtres :

- catégorie ;
- tag ;
- promotion ;
- vente rapide ;
- à bazarder ;
- marque ;
- fournisseur.

## 13. Paiements

Moyens :

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

Pour les paiements mobiles manuels :

```text
Client paie
→ Caissier vérifie
→ Montant
→ Référence transaction
→ Validation
```

## 14. Caisse

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
→ Envoyer au responsable
```

## 15. Notifications

Après fermeture validée :

- Telegram : immédiat ;
- email : regroupement hebdomadaire/mensuel ;
- email critique : prioritaire.

Email Budget Engine :

```text
daily_sent
monthly_sent
daily_limit
monthly_limit
reserved_critical
```

## 16. Retours, remboursements et avoirs

```text
VENTE
→ RETOUR
→ REMBOURSEMENT
  OU AVOIR
  OU ÉCHANGE
```

L'avoir est visible dans :

- espace web ;
- bot Telegram ;
- notifications.

Le Coffre/Wallet reste reporté à une phase ultérieure.

## 17. Promotions et fidélité

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

## 18. Objectifs et commissions

Objectifs :

- nombre de ventes ;
- chiffre d'affaires ;
- produit ;
- marque ;
- fournisseur ;
- jour/semaine/mois.

```text
OBJECTIF → RÉSULTAT → % → POINTS → CLASSEMENT → COMMISSION
```

Rappels par Telegram et email selon quotas.

## 19. Audit et fraude

Chaque action importante conserve :

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
- changements caissier ;
- ouvertures/fermetures ;
- remises ;
- annulations ;
- remboursements ;
- avoirs ;
- stock ;
- prix ;
- paiements ;
- réimpressions.

Alertes :

- annulations inhabituelles ;
- remises inhabituelles ;
- écarts répétés ;
- réimpressions nombreuses.

## 20. Sauvegardes

```text
SQLite locale
├── Backup local
├── Sync serveur
└── Google Drive optionnel
```

Conservation recommandée :

- 7 quotidiennes ;
- 4 hebdomadaires ;
- 3 mensuelles.

## 21. Mises à jour

```text
Backup SQLite
→ Nouvelle version
→ Migration
→ Vérification
→ Reprise
```

Les migrations ne doivent jamais supprimer les opérations non synchronisées.

## 22. Packs et entitlements

```text
ABONNEMENT
↓
PACK
↓
LIMITES + MODULES + ADDONS
```

Exemples :

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

Addons :

- utilisateur ;
- terminal ;
- boutique ;
- import contacts ;
- Google Sheets ;
- exports avancés ;
- connecteur comptable ;
- équipe/performance.

## 23. Dolibarr

Synchronisations envisagées :

- produits ;
- clients ;
- commandes ;
- factures ;
- stocks ;
- paiements ;
- utilisateurs.

## 24. Rapports et exports

Exports :

- CSV ;
- Excel ;
- PDF ;
- impression ;
- Google Sheets ;
- API REST.

Connecteurs futurs :

```text
Yessal Caisse
↓
Accounting Connector Layer
├── Sage
├── ERP partenaires
├── APIs comptables
└── Formats import/export
```

Cela prépare également un programme futur de partenaires avec les cabinets comptables.

## 25. Impression

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

Formats prioritaires : **58 mm et 80 mm**.

## 26. Support

Diagnostic :

```text
App version
SQLite version
Device ID
Dernière synchronisation
Opérations en attente
Erreurs
Espace disponible
Connexion
```

Support :

```text
Niveau 1 → Documentation / Bot
Niveau 2 → Support technique
Niveau 3 → Développeur / Incident critique
```

## 27. Sécurité API

```text
Flutter
↓ HTTPS
API Gateway
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
- Tenant ID ;
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

Aucun secret maître ne doit être stocké dans Flutter.

## 28. MVP

### MVP 1 — Fondation

- Flutter ;
- authentification ;
- PIN ;
- QR pairing ;
- SQLite ;
- offline ;
- synchronisation ;
- clients ;
- produits.

### MVP 2 — Caisse

```text
Client
→ Produits
→ Panier
→ Paiement
→ Ticket
```

### MVP 3 — Opérations

- caisse ;
- stock ;
- retours ;
- avoirs ;
- rapports ;
- audit.

### MVP 4 — Équipe

- utilisateurs ;
- permissions ;
- objectifs ;
- commissions ;
- rappels.

### MVP 5 — Sync avancée

- multi-terminaux ;
- conflits ;
- sauvegardes ;
- Google Drive ;
- blocage appareil.

### MVP 6 — Intégrations

- Dolibarr ;
- Google Sheets ;
- import contacts ;
- CSV/Excel ;
- impression.

## 29. Phases ultérieures

### Marketplace

- tags promotionnels ;
- filtres ;
- milliers de produits ;
- gamification ;
- vendeurs et partenaires.

### Yessal Livraison

- livreurs ;
- géolocalisation dynamique ;
- carte temps réel ;
- attribution de commande ;
- partenariat commerçant/livreur.

Le bot Telegram pourra servir de première interface livreur.

### Coffre / Wallet

Reporté à une phase ultérieure :

- crédit ;
- paiement ;
- remboursement ;
- alertes ;
- abonnements.

## 30. Prochaine phase

```text
PHASE 27.36
Architecture des notifications,
Bot Telegram,
Email et temps réel
```

Puis :

```text
Architecture backend détaillée
→ Yessal Core
→ API
→ Sync Engine
→ Base centrale
→ Provisioning
```

## 31. Principe directeur

```text
OFFLINE-FIRST
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

Ce document est destiné à être mis à jour au fur et à mesure des décisions fonctionnelles, techniques et commerciales.
