# Yessal ERP

Plateforme SaaS modulaire destinée aux PME, commerçants et organisations.

Yessal ERP est conçu autour d’un Core SaaS multi-tenant permettant d’activer progressivement différents modules métier, notamment **Yessal Caisse**.

---

## Statut du projet

**Développement actif — branche `develop`**

Dernière synchronisation documentaire : **30 août 2026**

### État actuel

| Domaine | État |
|---|---|
| Core SaaS | Implémenté |
| Organizations | Implémenté |
| Plans | Implémenté |
| Subscriptions | Implémenté |
| Entitlements | Implémenté |
| Quotas | Implémenté |
| Yessal Caisse — Shop | Implémenté |
| Yessal Caisse — Terminal | Implémenté |
| Yessal Caisse — Device | Implémenté |
| Yessal Caisse — Stock | Implémenté |
| Yessal Caisse — Vente | Implémenté |
| Yessal Caisse — Paiement | Implémenté |
| Yessal Caisse — Cash Session | Implémenté |
| Synchronisation | Implémentée |
| Sécurité multi-tenant | Implémentée |
| Application Flutter | En développement |
| Provisioning / déploiement SaaS | À finaliser |
| Commercialisation / tarifs définitifs | À valider |

> Les éléments indiqués comme « implémentés » correspondent au code actuellement présent dans le dépôt. Les éléments commerciaux ne doivent pas être considérés comme définitifs tant qu’ils n’ont pas été validés séparément.

---

## Architecture

Yessal ERP repose sur une architecture SaaS modulaire :

```text
Utilisateur
    │
    ▼
Organisation
    │
    ▼
Abonnement
    │
    ▼
Plan
    │
    ├── Modules
    │     │
    │     ▼
    │  Entitlements
    │
    └── Quotas
            │
            ▼
       Autorisation API
```

Pour Yessal Caisse :

```text
Organisation
    │
    ├── Shops
    │     ├── Terminals
    │     │      └── Cash Sessions
    │     ├── Products
    │     ├── Categories
    │     ├── Stock
    │     └── Customers
    │
    └── Devices
           │
           └── Synchronisation
```

---

## Structure du monorepo

```text
yessal-erp/
│
├── apps/
│   ├── api/
│   │   └── Backend Laravel
│   │
│   └── mobile/
│       └── Application Flutter
│
├── docs/
│   ├── architecture/
│   ├── API/
│   ├── CHANGELOG.md
│   ├── ROADMAP.md
│   └── documents techniques
│
├── infrastructure/
│   └── Déploiement et scripts
│
└── README.md
```

---

## Backend API

Le backend est développé avec :

- **PHP 8.3**
- **Laravel 13.17**
- **Laravel Sanctum 4**
- **PostgreSQL**

Les dépendances et versions supportées sont définies dans `apps/api/composer.json`.

---

## Yessal Caisse

Yessal Caisse constitue le premier domaine métier majeur du projet.

### Gestion des boutiques

Une organisation peut gérer plusieurs boutiques :

```text
Organization
    └── Shop
```

### Terminaux

Chaque boutique peut posséder plusieurs terminaux :

```text
Shop
    └── Terminal
```

Les sessions de caisse sont rattachées aux terminaux.

### Appareils

Les appareils mobiles ou postes de caisse sont identifiés par un UUID propre à l’organisation.

Le système gère :

- création ;
- activation ;
- révocation ;
- réactivation ;
- journal d’activité ;
- limitation du nombre d’appareils ;
- isolation entre organisations.

---

## Quotas appareils

Le nombre d’appareils actifs peut être limité par le plan.

Une révocation libère une place et permet une nouvelle activation.

Une limite `NULL` représente un nombre d’appareils illimité.

Le contrôle du quota est réalisé côté serveur et protégé par transaction/verrouillage lorsque nécessaire.

---

## Entitlements

Les fonctionnalités sont contrôlées par des entitlements.

Un endpoint métier peut vérifier qu’une organisation possède le droit nécessaire avant d’exécuter l’opération.

---

## Isolation multi-tenant

Une ressource appartenant à une organisation ne doit jamais être accessible par une autre organisation.

Les contrôles sont réalisés au niveau :

- middleware ;
- requêtes Eloquent ;
- services métier ;
- contrôleurs ;
- validations des relations ;
- tests automatisés.

---

## Stock

Yessal Caisse dispose d’une base de gestion du stock permettant notamment de gérer :

- emplacements ;
- produits ;
- variantes ;
- niveaux de stock ;
- mouvements de stock ;
- relations avec les ventes.

---

## Ventes

Le domaine vente prend en charge notamment :

- création de vente ;
- lignes de vente ;
- snapshots des informations commerciales ;
- paiements ;
- montants dus ;
- finalisation ;
- identifiants locaux ;
- idempotence.

---

## Paiements

Le système de paiement prend en charge l’intégration avec le domaine Caisse et le suivi des états de paiement.

Les paiements sont séparés des ventes afin de permettre plusieurs paiements pour une même vente.

Les montants financiers sont stockés sous forme entière plutôt qu’en `float`.

---

## Sessions de caisse

Une session de caisse est associée à un terminal.

```text
Ouverture
   │
   ▼
Session ouverte
   │
   ├── mouvements
   ├── ventes
   └── paiements
   │
   ▼
Fermeture
```

Une contrainte PostgreSQL empêche plusieurs sessions ouvertes simultanément pour un même terminal.

---

## Synchronisation offline

Yessal Caisse est conçu pour permettre le fonctionnement avec une connectivité intermittente.

Les événements de synchronisation utilisent un identifiant unique permettant de garantir l’idempotence.

Un même événement ne doit pas produire deux fois la même opération métier.

---

## Journal d’activité des appareils

Les opérations importantes liées aux appareils peuvent être enregistrées dans un journal d’activité.

Les anciens logs d’activité peuvent être nettoyés automatiquement.

Commande :

```bash
php artisan device-activity:cleanup
```

---

## Scheduler

Les tâches Laravel actuellement configurées comprennent notamment :

```text
subscriptions:expire
device-activity:cleanup
subscriptions:renew
```

Vérification :

```bash
php artisan schedule:list
```

---

## Tests automatisés

La suite Caisse actuellement validée comprend :

```text
143 tests
430 assertions
0 échec
```

Les tests couvrent notamment :

- Shops ;
- Terminals ;
- Devices ;
- Device Activity ;
- Device Quotas ;
- Device Lifecycle ;
- Device Concurrency ;
- Stock ;
- Sales ;
- Payments ;
- Cash Sessions ;
- Sync ;
- isolation multi-tenant ;
- abonnements et entitlements.

Exécution :

```bash
php artisan test tests/Feature/Caisse
```

Une modification ne doit pas être considérée comme terminée si elle provoque une régression dans la suite existante.

---

## Documentation

La documentation principale se trouve dans :

```text
docs/
```

Documents importants :

```text
docs/ROADMAP.md
docs/CHANGELOG.md
docs/architecture/
docs/YESSAL_CAISSE_MIGRATION_PLAN_v0.1.md
docs/YESSAL_CAISSE_SQL_SCHEMA_v0.2.md
```

La documentation doit distinguer :

- implémenté ;
- testé ;
- en cours ;
- planifié ;
- proposition ;
- décision commerciale non définitive.

---

## Développement local

### Backend

```bash
cd apps/api
composer install
cp .env.example .env
php artisan key:generate
```

Configurer PostgreSQL dans `.env`, puis :

```bash
php artisan migrate
```

Vérification :

```bash
php artisan about
```

Driver de base de données :

```bash
php artisan tinker --execute="echo DB::getDriverName();"
```

---

## Base de données

La base actuellement utilisée pour l’API est :

```text
PostgreSQL
```

Conventions principales :

- clés internes `bigint` ;
- UUID pour les identifiants opérationnels/offline ;
- montants monétaires en entiers ;
- quantités en précision décimale ;
- contraintes SQL pour les invariants critiques.

---

## Sécurité

Les principes actuellement appliqués comprennent :

- authentification Sanctum ;
- isolation multi-tenant ;
- validation serveur ;
- vérification des relations entre ressources ;
- contrôle des entitlements ;
- contrôle des quotas ;
- idempotence des événements de synchronisation ;
- journalisation des opérations sensibles.

Le serveur ne doit jamais faire confiance aux montants financiers calculés uniquement côté client.

---

## Git et stratégie de développement

Les branches principales sont :

```text
main
develop
```

Le développement courant se fait sur :

```text
develop
```

La branche `main` représente la branche de référence stable.

### Cycle recommandé

```text
Développement
     │
     ▼
Tests
     │
     ▼
Revue du code
     │
     ▼
Documentation
     │
     ├── README
     ├── CHANGELOG
     └── ROADMAP
     │
     ▼
Commit
     │
     ▼
Push develop
```

---

## Règle de synchronisation documentaire

Pour éviter que le code et la documentation divergent :

```text
1. Implémentation
2. Tests
3. Validation
4. Documentation
5. CHANGELOG
6. ROADMAP
7. Revue Git
8. Commit
9. Push
```

Une synchronisation documentaire doit être réalisée périodiquement lorsque plusieurs fonctionnalités ont été développées sans mise à jour documentaire.

---

## Roadmap

La feuille de route officielle est :

```text
docs/ROADMAP.md
```

Elle constitue la référence pour l’état d’avancement du projet.

Les prochaines étapes couvrent notamment :

- consolidation des modules métier ;
- synchronisation offline avancée ;
- application Flutter ;
- provisioning SaaS ;
- intégrations externes ;
- infrastructure de production ;
- validation commerciale des packs, modules, limites et addons.

---

## État de production

Le projet est actuellement en **développement**.

Il ne doit pas être considéré comme une version SaaS de production finale.

Avant une mise en production, il faudra notamment finaliser :

- infrastructure VPS ;
- CI/CD ;
- sauvegardes ;
- supervision ;
- logs ;
- sécurité serveur ;
- gestion des secrets ;
- configuration du scheduler ;
- stratégie de déploiement ;
- stratégie de rollback ;
- tests de charge ;
- tests de concurrence réels ;
- documentation d’exploitation.

---

## Versioning

Le versioning fonctionnel et les changements significatifs doivent être documentés dans :

```text
docs/CHANGELOG.md
```

Les décisions d’architecture importantes doivent être documentées dans :

```text
docs/architecture/
```

La progression fonctionnelle doit être suivie dans :

```text
docs/ROADMAP.md
```

---

## Licence

Le projet est actuellement destiné au développement de l’écosystème Yessal ERP.

Les conditions de distribution et de licence définitives devront être précisées avant une distribution publique du logiciel.
