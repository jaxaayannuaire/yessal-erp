# Yessal ERP — CHANGELOG

Toutes les évolutions importantes du projet sont documentées ici.

> Règle : une fonctionnalité n'est présentée comme **validée** que lorsque son implémentation et ses tests correspondants ont été vérifiés. Les éléments commerciaux et les intégrations encore en cours restent explicitement identifiés comme tels.

---

## 2026-09-04 — Sync offline incrémental

### Protocole de synchronisation

- Ajout de `GET /api/v1/caisse/sync/pull` avec curseur monotone, pagination et isolation stricte par organisation.
- Le journal append-only `sync_changes` propage les créations et mises à jour de catégories, produits et clients sous forme d'`upsert`.
- Le bootstrap avec `cursor=0` renvoie uniquement les changements conservés dans le journal ; il ne reconstruit pas les données antérieures.

### Push et limites

- Le push conserve l'unicité `organization_id + event_uuid` et marque les retries comme doublons.
- Seuls les événements `sale.create` et `sale.created` sont actuellement acceptés et restent `pending` : aucun replay métier de vente offline n'est encore appliqué.
- Les conflits de données de référence suivent une convention server-wins par ordre de curseur ; variantes, ventes, paiements, stock et caisse restent à journaliser dans un lot ultérieur.
- Validation : 382 tests, 1 200 assertions, 0 échec.

---

## 2026-09-04 — Remboursements Caisse

### Remboursements financiers

- Ajout des remboursements cash total ou partiel d'une vente finalisée ou annulée, tracés par `SaleReturn` et rattachés au paiement source.
- Un remboursement cash crée un mouvement `refund` dans la session de caisse ouverte ; les sessions fermées ne sont jamais rouvertes.
- L'annulation et la restauration de stock restent indépendantes du remboursement financier. Une vente finalisée totalement remboursée devient `refunded` ; une vente annulée conserve son statut.

### Sécurité et reporting

- Ajout de la permission `sales.refund`, attribuée aux administrateurs et managers, avec isolation stricte par organisation et paiement source.
- Le reporting expose le brut, les remboursements et le net de période. Les moyens non cash restent refusés tant qu'aucune confirmation fournisseur n'est disponible.
- Validation : 377 tests, 1 162 assertions, 0 échec.

---

## 2026-09-04 — Mouvements de caisse métier

### Mouvements et sessions

- Ajout des entrées et sorties manuelles de caisse, rattachées à une session ouverte avec montant positif, motif obligatoire et auteur serveur.
- Les mouvements sont protégés par `cash.movements.view` en lecture et `cash.movements.manage` en écriture, avec isolation stricte par organisation.
- Le montant théorique d'une session inclut le fonds d'ouverture, les paiements espèces et les mouvements manuels ; la clôture conserve son calcul d'écart existant.

### Reporting et tests

- Le reporting Caisse expose les entrées, sorties, nombre et solde net des mouvements manuels, filtrables par période et boutique.
- Validation : 369 tests, 1 120 assertions, 0 échec.
- Les remboursements financiers et l'idempotence offline des mouvements manuels restent hors périmètre.

---

## 2026-09-04 — Reporting Caisse minimal

### Synthèse opérationnelle

- Ajout de `GET /api/v1/caisse/reports/overview`, avec filtres de période et de boutique.
- La synthèse couvre les ventes finalisées, les paiements confirmés par moyen de paiement, les sessions de caisse, le stock courant, les boutiques et le nombre de clients actifs.
- Le chiffre d'affaires et l'encaissé excluent les ventes annulées ; celles-ci sont comptées séparément.

### Sécurité et tests

- L'accès est protégé par `reports.view` et toutes les agrégations restent isolées par organisation.
- Validation : 363 tests, 1 083 assertions, 0 échec.

---

## 2026-09-04 — Clients / Tiers Caisse

### API et ventes

- Ajout de l'API Client Caisse : CRUD, recherche par nom, téléphone ou e-mail, et historique paginé des ventes.
- Le rattachement d'un client à une vente est facultatif ; les ventes comptoir restent prises en charge.
- Seuls les clients actifs de l'organisation courante peuvent être associés à une nouvelle vente ; les ventes existantes, y compris annulées, restent visibles dans l'historique.

### Sécurité et tests

- Les routes utilisent `customers.view` et `customers.manage`, avec isolation stricte par organisation et boutique.
- Validation : 359 tests, 1 034 assertions, 0 échec.

---

## 2026-09-03 — Catégories et variantes Produits

### API catalogue

- Ajout des API Catégorie et Variante Produit Caisse, protégées par `products.view` en lecture et `products.manage` en écriture.
- Les catégories et variantes sont strictement isolées par organisation, boutique et produit parent.
- Les variantes exposent leurs prix et attributs existants ; leur SKU et code-barres sont validés par produit côté API.

### Stock et ventes

- Une variante créée par API peut être approvisionnée par les ajustements de stock, vendue, décrémentée à la finalisation et restaurée à l'annulation.
- Aucun stock initial n'est créé automatiquement avec une variante.
- Validation : 353 tests, 985 assertions, 0 échec.

---

## 2026-09-03 — Annulation des ventes et restauration du stock

### Annulation contrôlée

- Ajout de `POST /api/v1/caisse/sales/{sale}/cancel`, protégé par `sales.cancel`.
- Une vente finalisée peut passer à `cancelled` ; l'opération restaure exactement les sorties `sale_out` dans leurs emplacements de stock d'origine.
- Les restaurations créent des mouvements explicites `sale_cancel_in`, pour les produits comme pour les variantes.

### Cohérence et périmètre

- Vente, niveaux de stock et mouvements inverses sont traités dans une transaction unique ; les retries ne restaurent jamais deux fois le stock.
- Les paiements existants sont conservés. Les remboursements financiers et avoirs restent hors périmètre.
- Validation : 346 tests, 941 assertions, 0 échec.

---

## 2026-09-03 — Intégration ventes et stock

### Finalisation des ventes

- La finalisation d'une vente payée décrémente désormais les niveaux de stock des produits et variantes vendus.
- Les sorties créent des mouvements `sale_out` liés à la vente et à la localisation de stock consommée.
- La vente, les niveaux et les mouvements sont traités dans une même transaction ; un stock insuffisant annule intégralement la finalisation.
- Les retries de finalisation restent protégés : une vente déjà finalisée ne recrée pas de mouvement ni de décrément.

### Sécurité et tests

- Les produits, variantes et localisations sont vérifiés dans le périmètre de l'organisation et de la boutique de la vente.
- Validation : 338 tests, 902 assertions, 0 échec.

---

## 2026-09-03 — Catalogue Produits Caisse

### API catalogue

- Ajout de l'API Produit Caisse : liste paginée, consultation, création et mise à jour, incluant la désactivation via le statut existant.
- Les accès sont isolés par organisation et protégés par la chaîne Caisse, avec `products.view` en lecture et `products.manage` en écriture.
- La recherche couvre le nom, le SKU et le code-barres ; SKU et code-barres restent uniques par boutique.

### Quotas et intégrations

- `max_products` est désormais appliqué à la création ; `NULL` conserve le comportement illimité.
- Les produits créés par API sont utilisables par les ajustements de stock et les ventes.
- Le stock initial reste géré par les ajustements de stock ; les catégories et variantes existantes sont conservées, sans nouvelle API dédiée dans ce lot.

### Tests

Validation : 331 tests, 863 assertions, 0 échec.

---

## 2026-09-03 — Consolidation des quotas SaaS

### Limites et usages

- Ajout de `max_shops` aux plans, en complément de `max_users`, `max_products` et `max_devices`.
- L'endpoint des entitlements expose les limites et usages `users`, `products`, `devices` et `shops`.
- Le calcul des produits compte désormais les produits réels de l'organisation ; les compteurs restent isolés par tenant.

### Application des quotas

- La création de boutiques applique `max_shops` et refuse les dépassements.
- Les quotas produits et utilisateurs sont exposés et calculés ; leur application attend une route dédiée de création de produit ou d'ajout de membre.

### Tests

Validation : 318 tests, 810 assertions, 0 échec.

---

## 2026-09-03 — Sécurisation RBAC, abonnements, Wave, Sync et administration plateforme

### RBAC Caisse

- Ajout des permissions `devices.view`, `devices.manage`, `cash.view` et `sync.push`, du chargement de `RbacSeeder` par le seeder principal, ainsi que des tests de cohérence et HTTP RBAC Caisse.

### Sécurité et accès Caisse

- Protection des routes Caisse par la chaîne `auth:sanctum → organization.context → subscription → entitlement:pos.sell → permission:*`.
- Correction de la résolution multi-organisation des entitlements via l'organisation courante.
- Blocage des actions SaaS sensibles pour les utilisateurs normaux, y compris la balance Wave, l'activation manuelle d'abonnement, la confirmation de paiement et la mutation directe du statut d'abonnement.

### Synchronisation et Wave

- Durcissement de l'idempotence Sync : unicité tenant `organization_id + event_uuid`, réponse de doublon stable et tests d'isolation inter-organisation.
- Durcissement des webhooks Wave : réduction des données journalisées, contrôle de fraîcheur de 300 secondes et protection des paiements d'un autre provider.

### Administration plateforme

- Ajout de `users.is_platform_admin`, de `User::isPlatformAdmin()`, de `UserFactory::platformAdmin()` et du middleware `EnsurePlatformAdmin` (`platform.admin`).
- Les administrateurs plateforme peuvent gérer les plans, consulter la balance Wave, activer manuellement un abonnement et confirmer un paiement.
- Distinction explicite entre owner d'organisation, admin RBAC d'organisation et admin plateforme.

### Tests

Validation : 316 tests, 799 assertions, 0 échec.

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

## 2026-08-31 — Module de paiement Wave : intégration et tests

### Validé

L'intégration Checkout Wave a été validée avec la configuration de test dédiée.

Flux validés :

- création d'un paiement Yessal ;
- initiation d'une session Checkout Wave ;
- récupération du `provider_transaction_id` ;
- récupération de l'URL Checkout ;
- transmission du `client_reference` ;
- traitement du webhook signé ;
- `checkout.session.completed` → `paid` ;
- `checkout.session.failed` → `failed` ;
- `checkout.session.cancelled` → `cancelled` ;
- `checkout.session.expired` → `expired` ;
- paiement `renewal` ;
- protection d'un paiement déjà `paid` ;
- signature invalide refusée ;
- signature absente refusée pour les événements réels ;
- healthcheck sans signature accepté ;
- événement inconnu ignoré ;
- paiement Wave introuvable ignoré.

### Tests automatisés

Ajout :

```text
tests/Feature/Caisse/WavePaymentApiTest.php
```

Résultat validé :

```text
16 tests
59 assertions
0 échec
```

La suite complète Caisse a ensuite été exécutée :

```text
159 tests
489 assertions
0 échec
```

### Validation API Wave

Un appel réel à l'API Wave de test a confirmé :

```text
HTTP 200
checkout_status = open
payment_status  = processing
wave_launch_url = générée
```

Le premier échec d'authentification provenait de l'absence de `WAVE_API_KEY` dans `.env.testing`. Après ajout de la configuration, l'initiation Checkout a fonctionné.

### Idempotence

Le comportement de répétition des callbacks a été vérifié manuellement.

`completed` est protégé lorsqu'un paiement est déjà `paid`. Les événements `failed/cancelled/expired` peuvent toutefois être retraités au niveau du webhook. Une idempotence persistante par identifiant d'événement reste à évaluer avant production.

### Limite actuelle

`WavePaymentProvider::verify()` reste non implémenté.

### Commit

```text
12a4c14 test: ajouter les tests d'intégration Wave
```

> L'intégration Wave est validée pour les flux actuellement couverts, mais n'est pas encore déclarée prête pour la production.

---

## 2026-08-30 — Device Management, Quotas et consolidation Caisse

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
