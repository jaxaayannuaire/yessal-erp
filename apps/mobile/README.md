# Yessal Caisse mobile

Fondation Flutter de Yessal Caisse, destinée à Android et Web.

## Configuration

La base API est fournie au lancement :

```bash
flutter run --dart-define=YESSAL_API_BASE_URL=http://10.0.2.2:8000/api/v1
```

Utiliser une URL HTTPS de staging ou production selon l'environnement. Aucune
URL de production n'est codée en dur.

## Parcours initial

`Splash → Login → Organisation → Boutique → Terminal → Device → Bootstrap → Home`.

Chaque appel métier envoie le Bearer token et `X-Organization-Id`. L'application
ne dépend jamais du fallback serveur vers la première organisation.

## Stockage local

`flutter_secure_storage` est réservé aux secrets et au contexte de faible
volume : token Sanctum, utilisateur et contexte actif minimal, ainsi que le
`device_uuid` isolé par utilisateur et organisation.

Les données métier sont stockées dans Drift/SQLite :

- `organizations_cache`, `entitlements_cache` ;
- `categories`, `products`, `product_variants`, `customers` ;
- `stock_levels`, `cash_sessions` ;
- `sync_metadata`, `bootstrap_metadata` et `sync_outbox`.

Les clés locales incluent au minimum `organization_id`, et `shop_id` lorsque
la ressource est propre à une boutique. Le bootstrap réseau est écrit dans une
transaction : les référentiels de la boutique sont remplacés, puis le metadata
de bootstrap est marqué valide seulement à la fin. Une erreur laisse donc le
dernier snapshot cohérent intact.

Après un bootstrap réussi, l’application peut restaurer le contexte et lire ce
snapshot Drift pour ouvrir Home en mode hors ligne limité. Les anciennes clés
JSON de `LocalCacheStore` ne sont plus lues pour le catalogue ; une application
en développement sans base Drift refait simplement un bootstrap réseau.

### Web

La base Web utilise `drift_flutter` avec le worker `web/drift_worker.js` et
`web/sqlite3.wasm`. Drift sélectionne OPFS lorsque le navigateur le permet et
retombe sur IndexedDB dans les navigateurs modernes compatibles. En production,
le serveur doit servir `sqlite3.wasm` avec `Content-Type: application/wasm`.
Les en-têtes COOP/COEP améliorent le support OPFS mais ne sont pas imposés par
cette fondation, afin de préserver les intégrations Web existantes.

Les ventes cash offline utilisent `sync_outbox` pour conserver un événement
`sale.create` immuable. Le replay est lancé explicitement par l'utilisateur,
une opération à la fois, puis l'état local est rafraîchi après une application
serveur confirmée. Drift remplace `LocalCacheStore` sans modifier les
repositories.

## Limites connues

## Vente cash en ligne

Le parcours Vente utilise le catalogue Drift pour composer un panier en mémoire,
puis crée une vente, enregistre un paiement cash et finalise la vente via l'API.
Le serveur reste la source de vérité pour les prix, le paiement, la session de
caisse et le stock. Le stock local est seulement indicatif. Une vente n'est
considérée finalisée qu'après confirmation du serveur. Après une finalisation
réussie, le bootstrap rafraîchit Drift.

- une coupure avant paiement conserve l'identifiant de vente en mémoire pour
  reprendre la même tentative ;
- la recherche et le panier restent utilisables localement, mais la validation
  finale exige l'API.

## Vente cash offline et Outbox

Lorsqu'une vente cash est enregistrée hors ligne, l'application conserve dans
Drift un snapshot `sale.create` immuable, avec son `event_uuid`, son
`local_uuid`, son numéro de reçu et son payload historique. Le stock local
n'est pas décrémenté artificiellement : le serveur reste autoritaire au replay.

La synchronisation est uniquement manuelle depuis l'écran Synchronisation. Les
événements `queued` sont envoyés séquentiellement, un événement par requête.
Les statuts `conflict`, `rejected` et `failed` restent persistés et peuvent être
consultés par tenant, avec leurs messages et identifiants techniques de support.
Le payload métier n'est pas affiché dans cette interface.

- le bootstrap initial utilise les endpoints REST ; `sync/pull` n'est pas un
  bootstrap complet ;
- les variantes sont chargées par `GET /products/{product}/variants` : le
  bootstrap MVP effectue donc une requête par produit ;
- stock et variantes ne sont pas encore actualisés par le pull incrémental ;
- aucun retry manuel ou automatique, aucune résolution de conflit, aucune
  correction de payload et aucune purge Outbox ne sont encore disponibles ;
- aucun background sync, timer ou polling réseau n'est mis en place ;
- l'application liste les appareils de l'organisation puis compare le
  `device_uuid` local persistant ; un filtre backend dédié serait une
  optimisation ultérieure.
