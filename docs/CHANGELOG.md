# Yessal ERP — CHANGELOG

Toutes les évolutions importantes du projet sont documentées ici.

> Règle : une fonctionnalité n'est présentée comme **validée** que lorsque son implémentation et ses tests correspondants ont été vérifiés. Les éléments commerciaux et les intégrations encore en cours restent explicitement identifiés comme tels.

---

## 2026-08-30 — Device Management, Quotas et consolidation Caisse

### Gestion des appareils

Ajout et validation d'une API complète de gestion des appareils Yessal Caisse :

- liste des appareils d'une organisation ;
- création ;
- consultation ;
- modification ;
- activation ;
- révocation ;
- réactivation ;
- consultation de l'activité d'un appareil.

Routes principales :

```text
GET    /api/v1/caisse/devices
POST   /api/v1/caisse/devices
GET    /api/v1/caisse/devices/{device}
PUT    /api/v1/caisse/devices/{device}
PATCH  /api/v1/caisse/devices/{device}
POST   /api/v1/caisse/devices/{device}/activate
GET    /api/v1/caisse/devices/{device}/activity
POST   /api/v1/caisse/devices/{device}/revoke
```

### Journal d'activité

Ajout de :

```text
DeviceActivityLog
```

Le journal conserve les événements importants du cycle de vie des appareils.

Ajout d'une commande de nettoyage :

```bash
php artisan device-activity:cleanup
```

Les logs dépassant la période de conservation prévue sont supprimés automatiquement.

### Quota appareils

Ajout de `max_devices` dans les plans.

Le quota porte sur les appareils actifs d'une organisation.

Comportements validés :

- les appareils inactifs ne consomment pas le quota ;
- la révocation libère une place ;
- la réactivation est refusée lorsque le quota est atteint ;
- une place libérée permet une nouvelle activation ;
- `NULL` permet un nombre illimité d'appareils ;
- les quotas sont isolés par organisation.

Composants principaux :

```text
app/Services/Caisse/DeviceQuotaService.php
app/Services/Caisse/DeviceQuotaExceededException.php
app/Services/Entitlements/PlanLimitService.php
app/Services/Entitlements/QuotaService.php
```

### Cycle de vie des appareils

Les transitions de statut sont contrôlées lorsque la transition vers `active` augmente effectivement l'utilisation du quota.

Transitions couvertes :

```text
inactive → active
active   → inactive
inactive → inactive
active   → active
```

### Concurrence

Le contrôle du quota est protégé contre les contournements liés aux opérations concurrentes.

Le test de concurrence validé couvre le verrouillage de l'organisation lors du contrôle du quota.

### Scheduler

La tâche quotidienne suivante a été ajoutée :

```text
device-activity:cleanup
```

Le scheduler comprend notamment :

```text
subscriptions:expire
device-activity:cleanup
subscriptions:renew
```

Vérification :

```bash
php artisan schedule:list
```

### Sécurité multi-tenant

Renforcement et validation de l'isolation des appareils entre organisations.

Les tests couvrent notamment :

- consultation d'un appareil d'une autre organisation ;
- révocation interdite pour une autre organisation ;
- activation interdite pour une autre organisation ;
- activité d'une autre organisation non exposée ;
- shop d'une autre organisation ;
- terminal d'une autre organisation ;
- cohérence shop / terminal.

### Tests

Tests ajoutés ou consolidés :

```text
DeviceApiTest
DeviceManagementApiTest
DeviceQuotaApiTest
DeviceLifecycleQuotaTest
DeviceQuotaConcurrencyTest
DeviceActivityCleanupTest
```

Résultat validé de la suite Caisse au 30/08/2026 :

```text
143 tests
430 assertions
0 échec
```

La suite couvre notamment :

- Shop ;
- Terminal ;
- Device ;
- Device Activity ;
- Device Quota ;
- Device Lifecycle ;
- Device Concurrency ;
- Stock ;
- Sale ;
- Payment ;
- Cash Session ;
- Sync ;
- isolation multi-tenant ;
- entitlements.

### Base de données

Migrations ajoutées :

```text
2026_08_30_151954_create_device_activity_logs_table.php
2026_08_30_184924_add_max_devices_to_plans_table.php
```

La base de données utilisée par l'API est PostgreSQL.

---

## 2026-08-30 — Module de paiement Wave

### État

Le paiement Wave constitue une étape importante du développement de Yessal Caisse.

L'historique du 29/08 confirme que les éléments suivants ont déjà été validés :

- initiation Wave ;
- checkout Wave ;
- webhook signé ;
- `checkout.session.completed` → `paid` ;
- `checkout.session.failed` → `failed` ;
- paiements `initial` et `renewal`.

### Prochaine consolidation

La prochaine étape consiste à consolider l'intégration Wave dans le domaine paiement de Yessal Caisse, notamment :

- séparation claire du fournisseur de paiement et du domaine métier ;
- gestion explicite des statuts ;
- référence externe du fournisseur ;
- idempotence des callbacks et confirmations ;
- validation serveur ;
- protection contre les doublons ;
- journalisation ;
- gestion des erreurs et transactions échouées ;
- tests d'intégration complets avant production.

> L'existence de validations Wave au 29/08 ne signifie pas que l'ensemble du module de paiement est déclaré prêt pour la production.

---

## 2026-08-29 — Entitlements, quotas et abonnements

### Ajouté

- `entitlements` et `module_entitlement` ;
- modèles et relations Eloquent ;
- seeders modules/entitlements ;
- `EntitlementService` ;
- middleware `EnsureEntitlement` ;
- alias `entitlement` ;
- endpoint `GET /api/v1/organization/entitlements` ;
- `PlanLimitService` ;
- `QuotaService`.

### Validé

- entitlement inclus `pos.sell` → autorisé HTTP 200 ;
- entitlement non inclus `crm.leads` → HTTP 403 ;
- retour des modules, entitlements et limites via API ;
- comptage des utilisateurs.

### Abonnements

Cycle validé :

```text
active → past_due → expired
```

Avec :

- période de grâce de 3 jours ;
- `grace_period_ends_at` ;
- passage `active → past_due` testé ;
- passage `past_due → expired` testé ;
- réactivation après paiement de renouvellement testée.

### Paiements Wave

Validations réalisées :

- initiation Wave ;
- checkout Wave ;
- webhook signé ;
- `checkout.session.completed` → `paid` ;
- `checkout.session.failed` → `failed` ;
- paiements `initial` et `renewal`.

### Scheduler

- `subscriptions:expire` : toutes les heures ;
- `subscriptions:renew` : tous les jours à 00:05 ;
- Windows Task Scheduler configuré pour lancer `php artisan schedule:run` chaque minute ;
- exécution testée avec `LastTaskResult = 0`.

### Contrôle d'accès

Ajout de :

```text
EnsureSubscriptionActive
```

avec alias :

```text
subscription
```

Test validé :

- utilisateur authentifié → accès possible ;
- abonnement `expired` → HTTP 403 ;
- login et `/auth/me` restent accessibles.

Les routes de test utilisées pour cette validation ont ensuite été supprimées.

---

## 2026-08-29 — Modules et architecture Caisse

### Modules

- table `modules` ;
- modèle `Module` ;
- table `module_plan` ;
- relation `Plan → modules` ;
- relation `Module → plans`.

### Consolidation Core Caisse

- services métier Caisse ;
- gestion des ventes ;
- gestion des paiements ;
- gestion des sessions de caisse ;
- gestion du stock ;
- synchronisation ;
- contrôles d'intégrité ;
- entitlements ;
- quotas ;
- isolation organisationnelle.

---

## 2026-08-28 — Consolidation architecture

### Documentation

- création de la structure documentaire `/docs` ;
- consolidation de l'architecture Yessal Caisse issue de la version 0.1 ;
- ajout de la feuille de route projet ;
- ajout du changelog ;
- tarifs explicitement marqués comme non définitifs.

### Tarification

Source tarifaire documentée :

- Yessal Tambali ;
- Yessal POS ;
- Yessal Asso ;
- Yessal Daara ;
- Yessal Pro ;
- Yessal Immo.

Les montants restent indicatifs / à valider.

Les nomenclatures Free / Caisse / Business / Association / Pro restent des appellations de travail jusqu'à validation commerciale.

---

## 2026-08-26 — Core SaaS

### Ajouté

- modèle d'organisation ;
- plans ;
- abonnements ;
- entitlements ;
- premières bases de la gestion multi-tenant.

---

## 2026-08-25 — Initialisation

### Ajouté

- initialisation du monorepo Yessal ERP ;
- backend Laravel ;
- application mobile Flutter ;
- documentation initiale ;
- structure de développement.

---

## Prochaines évolutions

Les prochaines évolutions sont suivies dans :

```text
docs/ROADMAP.md
```

Elles comprennent notamment :

1. consolidation du moteur de quotas ;
2. finalisation des modules métier Caisse ;
3. consolidation du module de paiement Wave ;
4. synchronisation offline avancée ;
5. développement de l'application Flutter ;
6. provisioning Yessal / Dolibarr ;
7. infrastructure VPS et production ;
8. validation commerciale des packs, modules, limites et addons.

---

## Gouvernance documentaire

Chaque évolution importante doit suivre le cycle :

```text
Implémentation
    ↓
Tests
    ↓
Validation
    ↓
Documentation
    ↓
CHANGELOG
    ↓
ROADMAP
    ↓
Revue Git
    ↓
Commit
    ↓
Push
```

Les fonctionnalités non validées ne doivent pas être présentées comme terminées.

Les décisions commerciales doivent rester séparées des hypothèses techniques tant qu'elles ne sont pas validées dans l'étude financière et commerciale.

---

## Versioning

Le projet est actuellement en phase de développement.

Les versions fonctionnelles officielles seront définies lorsque les principaux blocs métier et l'infrastructure de production seront suffisamment stabilisés.
