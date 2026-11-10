# EcoPulse

[![Pipeline GitLab](https://gitlab.com/hafsa-hounaoui/ecopulse/badges/main/pipeline.svg)](https://gitlab.com/hafsa-hounaoui/ecopulse/-/pipelines)

Projet GitLab : [gitlab.com/hafsa-hounaoui/ecopulse](https://gitlab.com/hafsa-hounaoui/ecopulse)

**Tableau de bord de mesure des performances d'une API REST.**

EcoPulse expose trois endpoints REST de démonstration, mesure côté serveur le temps de traitement et la taille
de chaque réponse JSON, enregistre ces mesures dans PostgreSQL et les affiche dans un dashboard Nuxt :
indicateurs clés, tableau par endpoint et graphique des temps de réponse.

> Les délais des endpoints de démonstration sont **simulés** (charge artificielle) ; les **mesures**, elles,
> sont réelles.

Projet réalisé comme MVP full-stack, avec une attention portée à la qualité (tests automatisés, CI) et à la
sobriété numérique (agrégats calculés en SQL, JSON compact, graphique sans bibliothèque).

![Dashboard EcoPulse : trois boutons pour déclencher les endpoints fast, medium et slow, trois indicateurs (6 requêtes mesurées, 191 ms de temps de réponse moyen, 16,2 Ko de données échangées), un graphique en barres des temps de réponse où l'appel à slow culmine vers 640 ms, et un tableau par endpoint qui désigne slow comme le plus lent et le plus volumineux.](docs/dashboard.png)

---

## Stack technique

| Couche          | Technologie                                          |
|-----------------|------------------------------------------------------|
| Backend         | PHP 8.5, Symfony 7.4 (LTS), API REST JSON            |
| Persistance     | PostgreSQL 16, Doctrine ORM 3, Doctrine Migrations   |
| Frontend        | Nuxt 4, Vue 3 (Composition API), TypeScript, CSS     |
| Tests           | PHPUnit 13 (tests fonctionnels `WebTestCase`)        |
| Infrastructure  | Docker Compose (base de données), GitLab CI          |

---

## Architecture

```text
ecopulse/
├── backend/            API Symfony
│   ├── src/Controller/       DemoController (endpoints mesurés), MetricsController (lecture)
│   ├── src/EventSubscriber/  PerformanceSubscriber (mesure), ApiExceptionSubscriber (erreurs JSON)
│   ├── src/Entity/           Measurement (entité Doctrine)
│   ├── src/Repository/       MeasurementRepository (requêtes et agrégats SQL)
│   ├── migrations/           schéma de la base
│   └── tests/                tests fonctionnels PHPUnit
├── frontend/           Dashboard Nuxt
│   └── app/                  app.vue, components/, composables/useEcoPulse.ts, utils/
├── compose.yaml        PostgreSQL 16
└── .gitlab-ci.yml      Pipeline : tests backend + build frontend
```

```text
Navigateur ──► Nuxt :3000 ──(proxy /api/**)──► Symfony :8000 ──Doctrine──► PostgreSQL :5434
```

- Le navigateur n'appelle que Nuxt ; Nuxt relaie `/api/**` vers Symfony (une seule origine : pas de CORS).
- Un *event subscriber* Symfony chronomètre chaque appel à `/api/demo/*` avec `hrtime()` (horloge monotone),
  entre `kernel.request` et `kernel.response`, mesure la taille de la réponse en octets et enregistre la mesure
  au `kernel.terminate`.
- Seule la route de démonstration est mesurée : les appels du dashboard ne faussent pas les statistiques.

### API

| Méthode | Route                                   | Rôle                                                          |
|---------|-----------------------------------------|---------------------------------------------------------------|
| GET     | `/api/demo/fast`                        | Démo : aucun délai simulé, 5 éléments (≈ 0,3 Ko)              |
| GET     | `/api/demo/medium`                      | Démo : 150 ms simulés, 50 éléments (≈ 2,6 Ko)                 |
| GET     | `/api/demo/slow`                        | Démo : 600 ms simulés, 200 éléments (≈ 10 Ko)                 |
| GET     | `/api/metrics?limit=50`                 | Dernières mesures (`limit` entre 1 et 200)                    |
| GET     | `/api/stats`                            | Résumé global + statistiques par endpoint (du plus lent au plus rapide) |

Les erreurs sous `/api` sont renvoyées en JSON : `{"error": {"status": 404, "message": "Not Found"}}`
(404 route inconnue, 405 mauvaise méthode, 400 paramètre invalide, 500 erreur interne sans détail exposé).

---

## Lancer le projet

### Prérequis

- PHP ≥ 8.2 avec l'extension `pdo_pgsql` (testé avec PHP 8.5) et [Composer](https://getcomposer.org/)
- Node.js ≥ 20 (testé avec Node 24)
- Docker avec Docker Compose
- Optionnel : [Symfony CLI](https://symfony.com/download) (serveur local)

### 1. Base de données

```bash
docker compose up -d --wait
```

PostgreSQL est exposé sur le port **5434** (pour ne pas entrer en conflit avec un PostgreSQL local sur 5432).
Les identifiants (`ecopulse` / `ecopulse`) sont des valeurs de développement.

### 2. Backend Symfony

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
symfony serve -d --no-tls --port=8000
```

Sans Symfony CLI : `php -S 127.0.0.1:8000 -t public` (serveur mono-processus : les appels s'exécutent un par un).

Vérification : `curl http://127.0.0.1:8000/api/demo/fast`

### 3. Frontend Nuxt

```bash
cd frontend
npm install
npm run dev
```

Ouvrir **http://localhost:3000**. L'URL du backend peut être changée via la variable `NUXT_BACKEND_URL`
(par défaut `http://127.0.0.1:8000`), lue au démarrage de Nuxt.

### Changer le port de la base

Si le port 5434 est déjà utilisé : modifier le port dans `compose.yaml` et redéfinir `DATABASE_URL` dans un
fichier `backend/.env.local` (non versionné).

---

## Fonctionnalités

- Trois endpoints de démonstration aux profils différents (temps de réponse et volume de données).
- Mesure côté serveur du temps de traitement et de la taille des réponses, enregistrée dans PostgreSQL.
- API de lecture des mesures brutes et des statistiques agrégées (`COUNT`, `AVG`, `MIN`, `MAX`, `SUM` en SQL).
- Dashboard sur une seule page :
  - indicateurs : nombre de requêtes, temps de réponse moyen, volume de données échangées ;
  - boutons pour déclencher chacun des trois endpoints ;
  - tableau par endpoint, avec repérage du plus lent et du plus volumineux ;
  - graphique SVG des temps de réponse des 50 dernières requêtes, avec infobulle au survol ;
  - bouton d'actualisation, états de chargement, message d'erreur si l'API est injoignable ;
  - thème clair / sombre selon les préférences du système.
- Gestion des erreurs de l'API en JSON homogène.

---

## Tests

Les tests fonctionnels utilisent une base dédiée, `ecopulse_test`, distincte de la base de développement.

```bash
cd backend
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/phpunit --testdox
```

13 tests couvrent : les réponses des endpoints de démonstration, l'enregistrement des mesures (taille exacte,
durée au moins égale au délai simulé), l'absence de mesure pour les routes de lecture et les erreurs, les
agrégats et le tri de `/api/stats`, la limite et l'ordre de `/api/metrics`, les erreurs 400 / 404 / 405 en JSON.

### Intégration continue

`.gitlab-ci.yml` définit deux jobs exécutés à chaque push sur GitLab :

- `backend:tests` : PHP 8.5 + service PostgreSQL 16, migrations puis PHPUnit ;
- `frontend:build` : Node 24, `npm ci` puis `npm run build`.

---

## Limites connues

- **Périmètre volontairement restreint** : seuls les trois endpoints internes de démonstration sont mesurés ;
  il n'est pas possible de surveiller une URL externe. Pas d'authentification.
- **Temps serveur, pas latence complète** : la durée mesurée couvre le traitement dans Symfony (routage,
  contrôleur, construction de la réponse). Elle n'inclut ni le démarrage du framework, ni le réseau, ni le
  proxy Nuxt, ni le rendu navigateur.
- **Écriture en base en fin de requête** : la mesure est enregistrée au `kernel.terminate`. Avec PHP-FPM, cela
  se produit après l'envoi de la réponse ; avec le serveur local (`php-cgi`), le client attend cet `INSERT`
  (quelques dizaines de ms). La durée enregistrée, elle, ne l'inclut pas.
- **Premier appel plus lent** : après un vidage du cache Symfony, la première requête inclut la compilation du
  cache, ce qui apparaît dans les mesures.
- **Horodatage à la seconde** (`created_at`) ; l'ordre chronologique fiable est donné par l'identifiant.
- **Pas de rétention ni de pagination** : les mesures s'accumulent ; le dashboard affiche les 50 dernières.
- **Pas de tests frontend automatisés** : le frontend est validé par son build de production (en CI) et par
  des tests manuels.
- **Docker limité à la base de données** : Symfony et Nuxt se lancent sur la machine hôte.
