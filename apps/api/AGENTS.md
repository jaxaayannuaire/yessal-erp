# AGENTS.md — API Laravel Yessal ERP

Ces instructions complètent le fichier `AGENTS.md` situé à la racine du dépôt. Lire et respecter d'abord les règles globales du projet.

## 1. Périmètre

Ce répertoire contient l'API Laravel de Yessal ERP.

Domaines principaux :
- authentification Sanctum ;
- organisations et multi-tenant ;
- abonnements ;
- plans, modules et entitlements ;
- quotas ;
- RBAC ;
- Yessal Caisse ;
- paiements et Wave ;
- synchronisation.

## 2. Laravel Boost

Laravel Boost peut être utilisé uniquement s'il est déjà installé et configuré dans ce projet.

**Ne jamais installer Laravel Boost automatiquement.**

Ne jamais exécuter sans autorisation explicite :

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Ne jamais ajouter ou modifier une dépendance Composer simplement pour satisfaire une instruction générique d'agent.

## 3. Conventions Laravel

Respecter les patterns déjà présents avant d'introduire une nouvelle abstraction.

Préférer les services pour la logique métier réutilisable et garder les contrôleurs fins.

## 4. Multi-tenant

La frontière tenant est `Organization`.

Utiliser `currentOrganization` résolu par `ResolveOrganizationContext` lorsque le contexte est disponible.

Ne pas accepter un `organization_id` du client comme preuve d'autorisation.

Toute ressource chargée par identifiant doit être vérifiée contre l'organisation courante.

## 5. Chaîne de sécurité

Selon la route, vérifier l'application correcte de :
- `auth:sanctum`
- `organization.context`
- abonnement
- entitlement
- permission RBAC

Ne jamais supprimer un middleware de sécurité uniquement pour faire passer un test.

## 6. RBAC

Le contrôle des permissions doit rester centralisé via `PermissionService` et `CheckPermission`.

Avant d'utiliser un slug `permission:*` dans `routes/api.php`, vérifier qu'il :
1. existe dans `RbacSeeder` ;
2. est attribué aux rôles appropriés ;
3. est couvert par un test Feature.

Surveiller notamment :
- `devices.view`
- `devices.manage`
- `cash.view`
- `cash.open`
- `cash.close`
- `cash.movements.view`
- `sync.push`

## 7. Owner legacy

Ne pas confondre :
- `organization_user.role = owner`
- les rôles RBAC de `organization_user_roles`

Toute évolution exige des tests explicites.

## 8. Tests Laravel

Après toute modification :
1. lancer les tests ciblés ;
2. corriger les échecs ;
3. lancer les tests du domaine ;
4. avant un checkpoint important, lancer la suite complète.

Baseline actuelle :

```text
269 tests
706 assertions
0 échec
```

## 9. Migrations

Ne pas modifier une migration historique partagée sans justification explicite.

Pour une évolution de schéma, créer une nouvelle migration.

## 10. Paiements

Pour Wave et les autres paiements :
- valider les montants côté serveur ;
- préserver l'idempotence ;
- préserver la réconciliation ;
- ne pas faire confiance au statut fourni par le client ;
- ne jamais exposer de secret.

## 11. Synchronisation

Toujours vérifier :
- tenant ;
- droits ;
- idempotence ;
- conflits ;
- répétition des requêtes.

## 12. Git

Ne jamais exécuter sans autorisation explicite :
- `git commit`
- `git push`
- `git merge`
- `git rebase`
- `git reset --hard`
- `git clean`

À la fin d'une tâche, proposer un message Conventional Commit en français.

## 13. Tâches d'audit

Si la mission est annoncée comme lecture seule :
- ne modifier aucun fichier ;
- ne créer aucun fichier ;
- ne modifier aucune dépendance ;
- ne lancer aucune migration destructive ;
- ne lancer aucun outil d'installation.

Présenter les constats avec fichier, emplacement, risque, recommandation et test à ajouter.
