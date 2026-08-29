# Yessal ERP — CHANGELOG

## 2026-08-29

### Ajouté
- `entitlements` et `module_entitlement`.
- Modèle et relations Eloquent.
- Seeders modules/entitlements.
- `EntitlementService`.
- Middleware `EnsureEntitlement`.
- Endpoint `GET /api/v1/organization/entitlements`.
- `PlanLimitService`.
- `QuotaService`.

### Validé
- Entitlement inclus `pos.sell` → autorisé HTTP 200.
- Entitlement non inclus `crm.leads` → HTTP 403.
- Retour des modules, entitlements et limites via API.
- Comptage des utilisateurs.

### Abonnements
- Grâce de 3 jours.
- Cycle `active → past_due → expired` validé.

### Gouvernance
- Tarifs et limites des packs non définitifs.
- Matrice commerciale à valider dans l'étude commerciale et financière.

## 2026-08-28
- Module de base et relation `Plan ↔ Module`.

## 2026-08-28 — Consolidation architecture

### Documentation
- Création de la structure documentaire `/docs`.
- Consolidation de l'architecture Yessal Caisse issue de la version 0.1.
- Ajout de la feuille de route projet.
- Ajout du présent changelog.
- Les tarifs sont explicitement marqués comme **non définitifs**.

### Abonnements
- Cycle validé :
  `active → past_due → expired`.
- Délai de grâce configuré à 3 jours.
- `grace_period_ends_at` ajouté aux abonnements.
- Passage `active → past_due` testé.
- Passage `past_due → expired` testé.
- Réactivation après paiement de renouvellement testée.

### Paiements
- Initiation Wave validée.
- Checkout Wave validé.
- Webhook signé validé.
- `checkout.session.completed` → `paid`.
- `checkout.session.failed` → `failed`.
- Paiements `initial` et `renewal` pris en charge.

### Scheduler
- `subscriptions:expire` : toutes les heures.
- `subscriptions:renew` : tous les jours à 00:05.
- Windows Task Scheduler configuré pour lancer `php artisan schedule:run` chaque minute.
- Exécution testée avec `LastTaskResult = 0`.

### Contrôle d'accès
- `EnsureSubscriptionActive` créé.
- Alias `subscription` enregistré.
- Test validé :
  - utilisateur authentifié → accès possible ;
  - abonnement `expired` → HTTP 403 ;
  - login et `/auth/me` restent accessibles.
- Routes de test supprimées après validation.

### Modules
- Table `modules` créée.
- Modèle `Module` créé.
- Table `module_plan` créée.
- Relation `Plan → modules` créée.
- Relation `Module → plans` créée.

### Tarification
- Source tarifaire actuelle documentée :
  - Yessal Tambali ;
  - Yessal POS ;
  - Yessal Asso ;
  - Yessal Daara ;
  - Yessal Pro ;
  - Yessal Immo.
- Les montants de la source sont conservés comme **indicatifs / à valider**.
- Les noms Free / Caisse / Business / Association / Pro restent une nomenclature de travail jusqu'à validation commerciale.

## Prochaine évolution majeure

1. Valider la matrice commerciale dans l'étude financière.
2. Finaliser Pack → Modules → Limites → Addons.
3. Seeder les modules officiels.
4. Implémenter les entitlements.
5. Implémenter l'accès par module.
6. Préparer le provisioning Yessal / Dolibarr.
