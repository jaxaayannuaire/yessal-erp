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
- `sync_metadata` et `bootstrap_metadata`.

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

La synchronisation des ventes offline et le journal de changements complet sont
hors périmètre de cette fondation. Une implémentation Drift pourra remplacer
`LocalCacheStore` sans modifier les repositories.

## Limites connues

- le bootstrap initial utilise les endpoints REST ; `sync/pull` n'est pas un
  bootstrap complet ;
- les variantes sont chargées par `GET /products/{product}/variants` : le
  bootstrap MVP effectue donc une requête par produit ;
- stock et variantes ne sont pas encore actualisés par le pull incrémental ;
- aucune vente offline, mutation de stock locale ou réservation locale n’est
  encore stockée ;
- l'application liste les appareils de l'organisation puis compare le
  `device_uuid` local persistant ; un filtre backend dédié serait une
  optimisation ultérieure.
