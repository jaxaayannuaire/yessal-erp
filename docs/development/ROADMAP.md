# Yessal ERP SAAS — Feuille de route

## Phase 0 — Fondations
- [x] Laravel API
- [x] Authentification Sanctum
- [x] Organisations / utilisateurs
- [x] Plans
- [x] Abonnements
- [x] Paiements
- [x] Intégration Wave
- [x] Scheduler Laravel
- [x] Windows Task Scheduler

## Phase 1 — Abonnements et paiements
- [x] Paiement initial
- [x] Paiement de renouvellement
- [x] Webhooks Wave
- [x] `pending / paid / failed`
- [x] Expiration automatique
- [x] `past_due`
- [x] Délai de grâce configurable à 3 jours
- [x] Réactivation après paiement
- [ ] Historique complet des événements d'abonnement
- [ ] Protection renforcée contre les doublons concurrents

## Phase 2 — Packs, modules et entitlements
- [x] Table `modules`
- [x] Modèle `Module`
- [x] Table pivot `module_plan`
- [x] Relation Plan ↔ Modules
- [ ] Seed des modules officiels
- [ ] Matrice officielle Pack → Modules
- [ ] Entitlements
- [ ] Limites par pack
- [ ] Addons
- [x] Middleware global d'abonnement
- [ ] Middleware / Gate d'accès par module
- [ ] API des droits de l'organisation

## Phase 3 — Provisioning
- [ ] Provisionnement automatique selon le pack
- [ ] Intégration Dolibarr selon les besoins
- [ ] Activation / désactivation de modules
- [ ] Upgrade
- [ ] Downgrade
- [ ] Gestion des changements de pack

## Phase 4 — Modules métier
- [ ] Caisse
- [ ] Ventes
- [ ] Achats
- [ ] Stock
- [ ] Clients
- [ ] Fournisseurs
- [ ] Facturation
- [ ] CRM
- [ ] Rapports
- [ ] Utilisateurs

## Phase 5 — Flutter / Yessal Caisse
- [ ] Architecture Flutter
- [ ] SQLite / Drift
- [ ] Authentification
- [ ] PIN
- [ ] Gestion des appareils
- [ ] QR pairing
- [ ] Offline-first
- [ ] Sync Queue
- [ ] Synchronisation idempotente
- [ ] POS
- [ ] Paiements
- [ ] Caisse
- [ ] Impression

## Phase 6 — Fonctionnalités avancées
- [ ] Promotions
- [ ] Fidélité
- [ ] Objectifs
- [ ] Commissions
- [ ] Audit
- [ ] Notifications Telegram
- [ ] Emails
- [ ] Rapports avancés
- [ ] Exports
- [ ] Google Sheets

## Phase 7 — Intégrations
- [ ] Connecteur Dolibarr
- [ ] Connecteurs comptables
- [ ] API externes
- [ ] Provisioning automatique
- [ ] Marketplace
- [ ] Yessal Livraison

## Phase 8 — Production
- [ ] Tests automatisés
- [ ] Tests offline/synchronisation
- [ ] Sécurité API
- [ ] Rate limiting
- [ ] Monitoring
- [ ] Sauvegardes
- [ ] CI/CD
- [ ] Documentation API
- [ ] Documentation exploitation

## Point de décision commercial

La matrice technique ne doit être considérée comme définitive qu'après validation dans l'étude commerciale et financière :

```text
PACK
 ↓
PRIX
 ↓
MODULES
 ↓
LIMITES
 ↓
ADDONS
 ↓
PROVISIONING
```
