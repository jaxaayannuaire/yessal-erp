# Yessal ERP — ROADMAP

> **Mise à jour : 31 août 2026**
>
> Cette feuille de route est le document de pilotage du projet. Elle indique l’état des grands blocs, les priorités et les prochaines étapes. Les détails techniques et l’historique des changements sont documentés séparément dans `docs/CHANGELOG.md` et `docs/architecture/`.

---

## 1. Vision

Yessal ERP est une plateforme SaaS modulaire destinée aux PME, commerçants et organisations.

Architecture fonctionnelle de référence :

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
Limites / Quotas
    ↓
Autorisation API
    ↓
Modules métier
```

Premier domaine métier majeur :

```text
Yessal Caisse
```

---

## 2. État global

| Domaine | État |
|---|---|
| Core SaaS | ✅ Réalisé |
| Organizations | ✅ Réalisé |
| Plans | ✅ Réalisé |
| Subscriptions | ✅ Réalisé |
| Période de grâce | ✅ Réalisé |
| Expiration automatique | ✅ Réalisé |
| Modules / Entitlements | ✅ Réalisé |
| Quotas | ✅ Réalisé |
| Shop | ✅ Réalisé |
| Catalogue produits | ✅ Réalisé |
| Clients / Tiers Caisse | ✅ Réalisé |
| Reporting Caisse minimal | ✅ Réalisé |
| Terminal | ✅ Réalisé |
| Device | ✅ Réalisé |
| Device Activity | ✅ Réalisé |
| Device Quota | ✅ Réalisé |
| Device Lifecycle | ✅ Réalisé |
| Device Concurrency | ✅ Réalisé |
| Stock | ✅ Réalisé |
| Vente | ✅ Réalisé |
| Paiement | ✅ Réalisé |
| Cash Session | ✅ Réalisé |
| Mouvements de caisse | ✅ Réalisé |
| Remboursements Caisse | ✅ Réalisé |
| Synchronisation | ✅ Réalisé |
| Isolation multi-tenant | ✅ Réalisé |
| Scheduler | ✅ Réalisé |
| Cleanup des logs Device | ✅ Réalisé |
| Paiement Wave — Checkout + Webhook | 🧪 Validé |
| Paiement Wave — vérification serveur et idempotence persistante | 🔄 En cours |
| Application Flutter | 🔄 En développement |
| Provisioning SaaS | ⏳ Planifié |
| Provisioning Dolibarr | ⏳ Planifié |
| Infrastructure VPS production | ⏳ Planifié |
| CI/CD production | ⏳ Planifié |
| Validation commerciale définitive | ⚠️ À valider |

---

## 3. Réalisations majeures

### Core SaaS

Les fondations suivantes sont réalisées :

- organisations ;
- plans ;
- abonnements ;
- modules ;
- relations Plan ↔ Module ;
- entitlements ;
- contrôle d’accès ;
- limites et quotas.

### Abonnements

Cycle validé :

```text
active → past_due → expired
```

La période de grâce de 3 jours, l’expiration automatique et la réactivation après renouvellement sont prises en charge.

### Yessal Caisse

Les principaux domaines backend sont réalisés :

```text
Shop
Product Catalog
Terminal
Device
Stock
Sale
Payment
Cash Session
Sync
```

### Device

Le cycle de gestion des appareils est opérationnel :

```text
Création
   ↓
Activation
   ↓
Utilisation
   ↓
Révocation
   ↓
Réactivation
```

Le quota d’appareils actifs, le journal d’activité, le nettoyage automatique et la protection contre les opérations concurrentes sont couverts par les tests.

### Synchronisation

Le push idempotent et le pull incrémental tenant-scopé sont disponibles pour les catégories, produits et clients. Le curseur repose sur un journal append-only de changements.

Le replay métier des ventes offline, les variantes, les paiements, le stock, la caisse et la résolution avancée des conflits restent à approfondir.

---

## 4. Tests

La suite Caisse validée au **30/08/2026** :

```text
143 tests
430 assertions
0 échec
```

Les tests couvrent notamment :

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
- Entitlements ;
- isolation multi-tenant.

> Toute nouvelle fonctionnalité importante doit être accompagnée de tests automatisés.

---

# 5. Priorités actuelles

## P1 — Finaliser le paiement Wave

Les flux Checkout et Webhook actuellement implémentés sont validés.

Validé :

- initiation Checkout ;
- `client_reference` ;
- `provider_transaction_id` ;
- webhook signé ;
- `completed`, `failed`, `cancelled`, `expired` ;
- paiements `initial` et `renewal` ;
- gestion des erreurs ;
- tests automatisés Wave : 16 tests / 59 assertions ;
- suite Caisse complète : 159 tests / 489 assertions.

Reste à réaliser :

- implémenter `WavePaymentProvider::verify()` ;
- définir une idempotence persistante par événement ;
- renforcer les transitions de statuts ;
- finaliser les tests de production et de résilience ;
- préparer la configuration et la validation production.

**Statut : 🔄 En cours**

---

## P2 — Finaliser Yessal Caisse

Objectif :

- consolider les endpoints métier ;
- finaliser les flux de vente ;
- consolider paiements et sessions de caisse ;
- approfondir la synchronisation offline ;
- tester les scénarios de reprise et de conflit ;
- préparer les scénarios réels d’utilisation.

**Statut : 🔄 En cours**

---

## P3 — Développer l’application Flutter

Objectif :

```text
Authentification
      ↓
Organisation
      ↓
Boutique
      ↓
Terminal
      ↓
Device
      ↓
Caisse
      ↓
Stock
      ↓
Vente
      ↓
Paiement
      ↓
Synchronisation
```

Étapes principales :

1. architecture Flutter ;
2. authentification ;
3. contexte organisation ;
4. boutique ;
5. terminal ;
6. enregistrement Device ;
7. stockage local ;
8. fonctionnement offline ;
9. synchronisation ;
10. interface caisse ;
11. stock ;
12. ventes ;
13. paiements ;
14. Wave ;
15. tests mobiles.

**Statut : 🔄 En développement**

---

## P4 — Provisioning SaaS

Objectif :

```text
Client
   ↓
Abonnement
   ↓
Plan
   ↓
Modules / Entitlements / Quotas
   ↓
Provisioning
   ↓
Environnement client
```

À réaliser :

- automatisation du provisioning ;
- configuration de l’environnement ;
- rattachement du plan ;
- activation des modules ;
- configuration des limites ;
- domaines ;
- secrets ;
- supervision.

**Statut : ⏳ Planifié**

---

## P5 — Intégration Dolibarr

L’intégration Dolibarr est prévue pour les packs nécessitant certaines fonctionnalités ERP avancées.

À réaliser :

- détermination des modules requis ;
- provisioning ;
- configuration ;
- connexion Yessal ↔ Dolibarr ;
- synchronisation ;
- gestion des erreurs ;
- supervision ;
- maintenance.

**Statut : ⏳ Planifié**

---

## P6 — Infrastructure de production

Objectif :

```text
Yessal SaaS
    ↓
Infrastructure VPS
    ├── API
    ├── PostgreSQL
    ├── Workers
    ├── Scheduler
    ├── Monitoring
    ├── Logs
    └── Backups
```

À finaliser :

- architecture VPS ;
- déploiement automatisé ;
- sauvegardes ;
- monitoring ;
- logs ;
- alertes ;
- sécurité réseau ;
- gestion des secrets ;
- rollback ;
- CI/CD.

**Statut : ⏳ Planifié**

---

## P7 — Validation commerciale

Les packs, tarifs, limites, modules et addons doivent être validés avec l’étude commerciale et financière avant d’être considérés comme définitifs.

Matrice cible :

```text
Pack
 ↓
Modules
 ↓
Entitlements
 ↓
Limites
 ↓
Addons
 ↓
Services
```

**Statut : ⚠️ À valider**

---

# 6. Passage en production

Avant une première version de production, les critères suivants doivent être satisfaits :

- [ ] tests automatisés complets ;
- [ ] tests de concurrence ;
- [ ] tests offline et synchronisation ;
- [ ] tests d’intégration paiement ;
- [ ] audit de l’isolation multi-tenant ;
- [ ] sauvegardes opérationnelles ;
- [ ] monitoring opérationnel ;
- [ ] logs opérationnels ;
- [ ] scheduler opérationnel ;
- [ ] CI/CD opérationnel ;
- [ ] rollback documenté ;
- [ ] infrastructure VPS validée ;
- [ ] gestion des secrets validée ;
- [ ] documentation d’exploitation terminée ;
- [ ] packs commerciaux validés.

---

# 7. Gouvernance documentaire

La documentation officielle est organisée autour de :

```text
README.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/architecture/
```

Cycle recommandé :

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

La ROADMAP doit être mise à jour lorsqu’une fonctionnalité importante change de statut.

---

# 8. Statuts utilisés

```text
✅ Réalisé
🧪 Testé / validé
🔄 En cours
⏳ Planifié
⚠️ À valider
❌ Abandonné
```

---

# 9. Références

- `README.md` — présentation générale du projet ;
- `docs/CHANGELOG.md` — historique des évolutions ;
- `docs/architecture/` — architecture et décisions techniques ;
- `docs/YESSAL_CAISSE_MIGRATION_PLAN_v0.1.md` — plan de migration Caisse ;
- `docs/YESSAL_CAISSE_SQL_SCHEMA_v0.2.md` — schéma SQL de référence.

---

## Règle de pilotage

La ROADMAP constitue la référence pour déterminer **ce qui est fait, ce qui est en cours et ce qui doit être réalisé ensuite**.

Elle ne doit pas remplacer le CHANGELOG ni la documentation technique.
