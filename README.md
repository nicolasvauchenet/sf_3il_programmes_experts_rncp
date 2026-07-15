# Programmes Experts RNCP

Application Symfony de consultation et d'import des referentiels de formation RNCP.

## Prerequis

- PHP et Composer installes
- Les variables `.env` configurees, notamment `DATABASE_URL`

Par defaut, le projet pointe vers :

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/data/sf_3il_programmes_experts_rncp.db"
```

## Initialiser la base de donnees

Avec SQLite, la base est un fichier. Creer le dossier de stockage si besoin :

```powershell
New-Item -ItemType Directory -Force data
```

Appliquer les migrations :

```powershell
php bin/console doctrine:migrations:migrate
```

## Importer les referentiels JSON

La commande custom d'import est :

```powershell
php bin/console app:framework:import <dossier>
```

Elle prend un seul argument : le dossier du dataset JSON.

Exemple de structure attendue :

```text
public/data/cdwfs_2026-2027/
  structure.json
  skills/
  evaluations/
  modules/
  projects/
```

Le loader parcourt le dossier de maniere recursive. Il cherche un `structure.json` valide, puis importe les fichiers JSON des sous-dossiers.

### Importer l'annee 2026-2027

```powershell
php bin/console app:framework:import public/data/cdwfs_2026-2027
php bin/console app:framework:import public/data/asrc_2026-2027
php bin/console app:framework:import public/data/eadl_2026-2027
php bin/console app:framework:import public/data/eris_2026-2027
```

### Strategie detectee automatiquement

La commande choisit automatiquement la strategie selon l'etat de la base :

- `Creation complete` : le framework RNCP n'existe pas encore et le dataset contient `skills/` et `evaluations/`
- `Creation de promotion` : le framework existe deja, mais pas la promotion de l'annee importee
- `Remplacement complet` : le framework et la promotion existent deja, avec un dataset complet
- `Remplacement de promotion` : le framework et la promotion existent deja, avec un dataset partiel

Si le framework n'existe pas et que le dataset est partiel, l'import est refuse.

La commande actuelle n'accepte pas d'option `--mode`. Elle fonctionne uniquement avec le dossier :

```powershell
php bin/console app:framework:import public/data/asrc_2026-2027
```

## Verifier l'import

Une fois l'import termine, tester une page de promotion :

```text
/promotion?promotion=cdwfs&year=2026-2027
```

Autres exemples :

```text
/promotion?promotion=asrc&year=2026-2027
/promotion?promotion=eadl&year=2026-2027
/promotion?promotion=eris&year=2026-2027
```

## Pourquoi la base est necessaire

L'application lit les promotions et les referentiels depuis Doctrine pour afficher les pages. La page d'accueil utilise notamment `PromotionRepository`, et les pages metier reconstruisent les vues depuis les entites `Promotion`, `Framework`, `Block`, `Skill`, `Module`, `Project` et `Evaluation`.

Sans base creee, migrations appliquees et donnees importees, l'application ne peut pas afficher les referentiels.
