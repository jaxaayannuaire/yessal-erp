# Yessal ERP — ROADMAP

> Mise à jour : 2026-08-29.

## Architecture validée

```text
Utilisateur → Organisation → Abonnement → Plan → Modules → Entitlements → Autorisation API
```

## Réalisé
- Abonnements et période de grâce de 3 jours.
- Expiration automatique via `subscriptions:expire`.
- Relations `Plan ↔ Module` et `Module ↔ Entitlement`.
- Tables `entitlements` et `module_entitlement`.
- Seeders modules/entitlements.
- `EntitlementService`.
- Middleware `EnsureEntitlement` et alias `entitlement`.
- API `GET /api/v1/organization/entitlements`.
- `PlanLimitService`.
- `QuotaService`.
- Tests d'autorisation positive/négative et quota utilisateurs.

## Tests validés
```text
pos.sell   → HTTP 200
crm.leads  → HTTP 403
users      → usage 1, limit null, unlimited true
```

## Prochaines étapes
1. Finaliser le moteur de quotas.
2. Créer les modèles métier, notamment Produits & Stock.
3. Ajouter le comptage réel des ressources.
4. Valider commercialement Free, Caisse, Business, Association et Pro.
5. Associer packs, modules et entitlements définitifs.
6. Protéger les endpoints métier.
7. Préparer l'API Flutter / Yessal Caisse.
8. Ajouter les tests automatisés.

Les tarifs et limites commerciales définitifs restent hors code tant qu'ils ne sont pas validés dans l'étude commerciale.
