# AGENTS.md — Yessal ERP SaaS

## 1. Projet

Yessal ERP est une plateforme SaaS de gestion destinée principalement aux TPE, PME, commerces, associations, dahiras, GIE et autres organisations, notamment en Afrique de l'Ouest.

Le projet doit rester modulaire, multi-tenant, sécurisé, testable, évolutif et utilisable par des interfaces Web et mobiles.

Le domaine actuellement le plus avancé est **Yessal Caisse**.

## 2. Stack technique

### Backend
- Laravel
- PHP
- PostgreSQL
- Laravel Sanctum
- API REST versionnée sous `/api/v1`

### Clients
- application Flutter ;
- interface Web ;
- intégrations externes ;
- bots et services automatisés si nécessaire.

Le backend doit rester indépendant des clients.

## 3. Architecture SaaS

L'architecture repose notamment sur :
- User
- Organization
- Plan
- Subscription
- Module
- Entitlement
- RBAC
- Caisse

Les domaines métier doivent rester séparés autant que possible de la couche SaaS.

## 4. Multi-tenant

`Organization` est la frontière principale d'isolation des données.

Le middleware `ResolveOrganizationContext` résout notamment :
- `currentOrganization`
- `organization_id`
- `currentSubscription`
- `currentPlan`

Ne jamais faire confiance à un `organization_id` fourni arbitrairement par le client lorsque le contexte peut être obtenu depuis `currentOrganization`.

Préférer :

```php
$organization = $request->attributes->get('currentOrganization');
```

Les tests d'isolation tenant sont obligatoires pour toute fonctionnalité sensible.

## 5. Authentification

L'API utilise Laravel Sanctum.

Respecter :
- `401` : utilisateur non authentifié ;
- `403` : utilisateur authentifié mais accès interdit.

## 6. Abonnements

Comportement actuellement validé :
- actif : accès autorisé ;
- `past_due` pendant la période de grâce : accès autorisé ;
- `past_due` après la grâce : accès refusé ;
- expiré : accès refusé ;
- pending : accès refusé ;
- absence d'abonnement : accès refusé.

`ResolveOrganizationContext` ne doit pas devenir implicitement un middleware de validation d'abonnement.

## 7. Entitlements et quotas

Respecter notamment :
- `EntitlementService`
- `PlanLimitService`
- `QuotaService`
- `EnsureEntitlement`

Avant d'ajouter une restriction dans un contrôleur, vérifier si elle appartient plutôt à :

```text
Plan -> Module -> Entitlement -> Quota
```

## 8. RBAC

Le système RBAC repose notamment sur :
- `Role`
- `Permission`
- `OrganizationUserRole`
- `PermissionService`
- `CheckPermission`

Tables principales :
- `roles`
- `permissions`
- `role_permissions`
- `organization_user_roles`
- `user_permissions`

Toute nouvelle permission doit être définie, seedée, attribuée aux rôles appropriés, utilisée sur les routes concernées et couverte par des tests.

Ne jamais utiliser un slug `permission:*` sans vérifier son existence dans le référentiel RBAC.

## 9. Owner legacy et RBAC

Ne pas confondre :
- `organization_user.role = owner`
- les rôles RBAC stockés dans `organization_user_roles`

Cette coexistence est une décision d'architecture et toute évolution doit être couverte par des tests explicites.

## 10. PermissionService

La résolution des permissions doit rester centralisée dans :

```text
App\Services\Rbac\PermissionService
```

Ne pas recopier cette logique dans les contrôleurs.

## 11. Yessal Caisse

Le domaine comprend actuellement ou progressivement :
- Shops
- Terminals
- Devices
- Cash Sessions
- Sales
- Payments
- Stock
- Sync
- Wave

Chaque ressource métier doit respecter la chaîne de sécurité adaptée :

```text
Authentication
→ Organization Context
→ Subscription
→ Entitlement
→ Permission
→ Business Logic
```

## 12. Paiements

Règles obligatoires :
- isolation stricte par organisation ;
- validation des montants côté serveur ;
- ne jamais faire confiance à l'état de paiement envoyé par le client ;
- préserver l'idempotence ;
- préserver la réconciliation ;
- tester les scénarios d'échec et de répétition ;
- ne jamais exposer de secrets, clés API ou tokens.

## 13. Synchronisation

Les endpoints de synchronisation doivent :
- préserver l'idempotence ;
- éviter les doublons ;
- contrôler le tenant ;
- contrôler les droits ;
- tolérer les retries réseau.

## 14. Base de données

Avant de créer une migration :
1. examiner les migrations existantes ;
2. examiner les modèles concernés ;
3. vérifier les relations ;
4. vérifier les contraintes tenant ;
5. vérifier les index ;
6. vérifier les contraintes d'unicité ;
7. vérifier les clés étrangères.

Ne pas modifier une migration historique partagée sans justification explicite. Pour une évolution de schéma, préférer une nouvelle migration.

## 15. Contrôleurs et services

Les contrôleurs doivent rester aussi fins que possible.

Éviter d'y placer directement :
- logique RBAC complexe ;
- calcul des entitlements ;
- quotas ;
- logique de paiement ;
- logique métier réutilisable.

## 16. Routes API

Avant d'ajouter ou modifier une route :
1. vérifier l'authentification ;
2. vérifier `organization.context` ;
3. vérifier l'abonnement si nécessaire ;
4. vérifier l'entitlement si nécessaire ;
5. vérifier la permission RBAC ;
6. vérifier l'isolation tenant ;
7. ajouter les tests correspondants.

La sécurité doit être appliquée côté API, pas seulement dans Flutter ou le Web.

## 17. Tests

Ordre de validation :
1. tests directement concernés ;
2. tests du domaine ;
3. suite complète avant checkpoint important.

Baseline actuelle :

```text
Branche : develop
Commit : 4d3375e
269 tests
706 assertions
0 échec
```

Une régression doit être expliquée et corrigée.

## 18. Tests de permissions

Pour chaque ressource protégée, tester lorsque pertinent :
- administrateur ;
- manager ;
- caissier ;
- utilisateur sans permission ;
- utilisateur d'une autre organisation ;
- utilisateur non authentifié.

Tester le comportement réel de l'API, pas uniquement `PermissionService`.

## 19. Audit RBAC permanent

Comparer systématiquement :
- permissions utilisées dans `routes/api.php` ;
- permissions créées par `RbacSeeder` ;
- permissions attribuées aux rôles système ;
- tests Feature qui les couvrent réellement.

Points à surveiller au prochain audit :
- `devices.view`
- `devices.manage`
- `cash.view`
- `cash.open`
- `cash.close`
- `cash.movements.view`
- `sync.push`

## 20. Qualité et périmètre

Avant toute modification :
- rechercher l'implémentation existante ;
- éviter les duplications ;
- respecter les conventions Laravel ;
- vérifier les effets sur le multi-tenant, les abonnements et les permissions ;
- éviter les refactorings massifs hors périmètre.

## 21. Missions d'audit

Si la mission est en lecture seule :
- ne modifier aucun fichier ;
- ne créer aucun fichier ;
- ne modifier aucune dépendance ;
- ne lancer aucune commande destructive ;
- ne faire aucun commit, push, merge ou rebase.

Présenter : constat, fichiers concernés, risques, recommandations et ordre de priorité.

## 22. Git

La branche de développement actuelle est `develop`.

Avant toute opération Git importante :

```bash
git status
git branch --show-current
```

Sans instruction explicite, ne jamais exécuter :
- `git commit`
- `git push`
- `git merge`
- `git rebase`
- `git reset --hard`
- `git clean`

Ne jamais écraser des modifications utilisateur non commitées.

## 23. Messages de commit

Les messages de commit doivent être **rédigés en français**.

Utiliser Conventional Commits :

```text
type(scope): description en français
```

Exemples :

```text
feat(rbac): ajouter les permissions des appareils
fix(caisse): empêcher l'accès aux boutiques d'une autre organisation
test(sync): ajouter les tests d'isolation multi-tenant
refactor(organization): centraliser la résolution des permissions
docs(api): documenter le contexte d'organisation
```

À la fin d'une tâche, proposer un message de commit en français, mais ne pas exécuter le commit sans autorisation.

## 24. Push et GitHub

Workflow attendu :

```text
Modification
→ Tests ciblés
→ Tests complémentaires
→ git diff
→ Validation utilisateur
→ Commit autorisé
→ Push autorisé
```

Ne jamais pousser automatiquement.

## 25. Documentation

Lorsque l'architecture change, vérifier si README, Roadmap, documentation API, documentation architecture ou AGENTS.md doivent être mis à jour.

## 26. Sécurité

Ne jamais :
- désactiver une protection tenant pour faire passer un test ;
- retirer un middleware de sécurité sans justification ;
- accepter un identifiant d'organisation sans vérifier l'appartenance ;
- stocker un secret dans Git ;
- contourner les permissions dans un contrôleur ;
- faire confiance aux montants ou statuts de paiement du client ;
- supprimer un test de sécurité simplement parce qu'il échoue.

## 27. Compatibilité API

Éviter de modifier sans nécessité :
- structure JSON ;
- codes HTTP ;
- noms des champs ;
- routes ;
- comportements d'erreur.

Tout changement incompatible doit être signalé avant implémentation.

## 28. Dépendances

Ne jamais ajouter automatiquement une dépendance Composer, npm ou infrastructure sans justification et autorisation explicite.

Si Laravel Boost est déjà installé et configuré, il peut être utilisé. Sinon, ne pas l'installer automatiquement.

## 29. Règles spécifiques pour Codex

Codex doit privilégier :

```text
comprendre
→ modifier peu
→ tester
→ inspecter
→ expliquer
```

Avant de coder, rechercher les patterns déjà utilisés dans Yessal ERP.

Pour les tâches sensibles (RBAC, multi-tenant, paiements, synchronisation, migrations), augmenter la profondeur d'analyse avant modification.

## 30. État de référence

Checkpoint stable de départ :

```text
Branche : develop
Commit : 4d3375e
269 tests réussis
706 assertions
origin/develop synchronisé
```

Le commit historique `4d3375e` est en anglais. Les nouveaux messages de commit doivent être rédigés en français.

## 31. Première mission Codex

La première mission Codex doit être un audit en lecture seule couvrant en priorité :
1. multi-tenant ;
2. `ResolveOrganizationContext` ;
3. abonnements et entitlements ;
4. RBAC ;
5. `PermissionService` ;
6. `RbacSeeder` ;
7. permissions utilisées dans `routes/api.php` ;
8. tests de permissions ;
9. isolation tenant ;
10. Yessal Caisse.

Vérifier notamment si tous les slugs utilisés par les routes existent dans le seeder et sont correctement attribués aux rôles.

Ne modifier aucun fichier pendant cette première mission.
