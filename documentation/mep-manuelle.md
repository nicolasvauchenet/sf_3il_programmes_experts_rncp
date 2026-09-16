# Mise en production manuelle

Cette procédure décrit une mise en production manuelle depuis le serveur, sans passer par la CI/CD.

Le site tourne dans Docker Compose. Le code applicatif est copié dans l'image au moment du build avec `COPY . /app`. Un simple `git pull` sur le serveur met donc le dépôt à jour, mais ne met pas à jour le code exécuté par le container déjà lancé.

## Pré-requis serveur

- être connecté en SSH sur le serveur de production ;
- être placé dans le dossier du projet, par exemple `~/refrncp` ;
- avoir Docker et Docker Compose disponibles ;
- avoir un fichier `.env.docker.prod` présent sur le serveur ;
- ne pas dépendre de `php` ou `symfony` sur l'hôte : les commandes Symfony doivent être lancées dans le container.

## Vérifications avant MEP

```bash
cd ~/refrncp
git status
docker compose ps
test -f .env.docker.prod
```

Le `git status` doit être propre avant le déploiement. Si des fichiers sont modifiés sur le serveur, il faut comprendre pourquoi avant de faire le `git pull`.

## Déploiement

```bash
cd ~/refrncp
git pull
mkdir -p data/referentiels certs var/backups/sqlite
if test -f data/sf_3il_programmes_experts_rncp.db; then cp data/sf_3il_programmes_experts_rncp.db var/backups/sqlite/pre-deploy-$(date +%Y%m%d%H%M%S).db; fi
docker compose build app
docker compose stop app || true
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker compose up -d app
docker compose exec -T app php bin/console cache:clear --env=prod
docker compose ps
```

L'ordre important est :

1. récupérer le code avec `git pull` ;
2. créer les dossiers persistants attendus par Docker Compose ;
3. sauvegarder le fichier SQLite avant migration ;
4. reconstruire l'image avec `docker compose build app` ;
5. arrêter l'ancien container applicatif ;
6. lancer les migrations depuis la nouvelle image ;
7. recréer/redémarrer le container applicatif ;
8. vider le cache prod depuis le container.

## Pourquoi ne pas lancer `php bin/console` directement ?

Sur le serveur, PHP CLI peut être absent de l'hôte. C'est normal si l'application tourne uniquement dans Docker.

Ces commandes peuvent donc échouer :

```bash
php bin/console cache:clear
symfony console cache:clear
bin/console cache:clear
```

La bonne forme est :

```bash
docker compose exec app php bin/console cache:clear --env=prod
```

ou, si le container `app` n'est pas encore lancé :

```bash
docker compose run --rm app php bin/console cache:clear --env=prod
```

## Import des référentiels

L'import des référentiels se fait depuis l'administration du site, pas en SSH.

Les archives JSON envoyées par l'administration sont extraites dans `data/referentiels`. En production Docker, ce dossier correspond à `/app/data/referentiels` dans le container et à `./data/referentiels` sur le serveur.

Ce dossier est persistant grâce au volume Docker Compose `./data:/app/data`. Il ne faut donc pas le supprimer lors d'un déploiement.

## Vérifications après MEP

```bash
docker compose exec app php bin/console debug:router app_register --env=prod
docker compose exec app php bin/console doctrine:migrations:status --env=prod
curl --retry 5 --retry-delay 2 --retry-connrefused -f http://127.0.0.1
```

À vérifier dans le navigateur :

- `/connexion` ;
- `/inscription` ;
- `/administration/utilisateurs` avec un compte administrateur ;
- une page de promotion, par exemple `/promotion?promotion=cdwfs&year=2026-2027`.

## Données persistantes

Les données de production à conserver sont :

- la base SQLite : `data/sf_3il_programmes_experts_rncp.db` ;
- les référentiels déposés depuis l'administration : `data/referentiels/` ;
- les certificats : `certs/`.

Le build Docker peut être relancé sans écraser ces éléments, car ils sont montés depuis le serveur dans le container.

## Point d'attention

Le cache Symfony est reconstruit pendant le build de l'image, mais il est quand même utile de lancer un `cache:clear --env=prod` après `docker compose up -d` si des variables d'environnement ont changé sur le serveur.

Si les modifications n'apparaissent pas après un `git pull`, le premier réflexe doit être :

```bash
docker compose build app
docker compose up -d app
```

Sinon, le container continue probablement à servir l'ancienne image. Le dépôt est à jour, mais l'application qui tourne vit encore dans le passé. Très vintage, mais pas souhaité.
