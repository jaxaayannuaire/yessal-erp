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

Le token et le cache de bootstrap sont chiffrés via `flutter_secure_storage`,
avec des clés isolées par utilisateur et organisation. Le cache contient les
entitlements, catégories, produits, clients, niveaux de stock et sessions.
Il permet un démarrage hors ligne limité après un bootstrap réussi.

La synchronisation des ventes offline et le journal de changements complet sont
hors périmètre de cette fondation. Une implémentation Drift pourra remplacer
`LocalCacheStore` sans modifier les repositories.

## Limites connues

- le bootstrap initial utilise les endpoints REST ; `sync/pull` n'est pas un
  bootstrap complet ;
- les variantes sont récupérables via les produits mais ne sont pas encore
  mises en cache globalement ;
- stock et variantes ne sont pas encore actualisés par le pull incrémental ;
- l'application liste les appareils de l'organisation puis compare le
  `device_uuid` local persistant ; un filtre backend dédié serait une
  optimisation ultérieure.
