# Patterns probables par composant

| Composant                       | Pattern probable                       | Intérêt                                                                                                                           | Risque d'abus                                                                                          |
|---------------------------------|----------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|
| DocumentExtractionPromptBuilder | Builder                                | Construire proprement des prompts complexes selon le type de document, les consignes et le format de sortie attendu               | Le transformer en usine à gaz configurable dans tous les sens pour trois pauvres chaînes de caractères |
| AiRawExtractionService          | Strategy, Adapter, Facade              | Changer de stratégie selon le type de source, encapsuler l'appel au provider IA, masquer la mécanique technique au reste de l'app | Lui faire porter trop de logique métier ou trop d'intelligence de validation                           |
| RawExtractionStore              | Repository, Gateway                    | Centraliser l'écriture et la lecture des artefacts bruts, homogénéiser le stockage                                                | Le gonfler avec de la logique de transformation ou de validation qui n'a rien à faire là               |
| DatasetTransformer              | Pipeline, Mapper, Strategy             | Appliquer une transformation déterministe étape par étape, convertir du brut vers du canonique, spécialiser selon les sources     | Finir avec un énorme transformeur monolithique qui connaît tout le système                             |
| DatasetValidator                | Chain of Responsibility, Specification | Empiler des règles de validation lisibles, séparables et composables                                                              | Multiplier des validateurs minuscules au point de perdre toute lisibilité d'ensemble                   |
| DatasetExporter                 | Builder, Factory                       | Produire des artefacts finaux cohérents et complets, contrôler la forme de sortie                                                 | Y injecter des décisions métier qui devraient déjà avoir été prises avant                              |
| Import command                  | Command                                | Représenter explicitement une action métier et technique pilotable, traçable et testable                                          | Mettre toute l'implémentation dedans et refaire un gros script procédural déguisé                      |
| FrameworkImportService          | Strategy, Mapper                       | Gérer les règles spécifiques à l'import du référentiel et le mapping vers SQL                                                     | Dupliquer les mêmes règles que d'autres importeurs au lieu d'extraire ce qui est commun                |
| PromotionImportService          | Strategy, Mapper                       | Importer les promotions en respectant leurs contraintes propres                                                                   | Le mélanger avec les règles de création métier manuelle d'une promotion                                |
| ModuleImportService             | Strategy, Mapper                       | Importer des modules avec leurs champs et leurs relations                                                                         | Le faire dériver vers un éditeur ou un gestionnaire de fallback applicatif                             |
| ProjectImportService            | Strategy, Mapper                       | Même logique que pour les modules, mais pour les projets                                                                          | Dupliquer 90 pour cent du code de ModuleImportService sans vraie abstraction                           |
| EvaluationImportService         | Strategy, Mapper                       | Traduire les données normatives et contextuelles des évaluations en entités SQL                                                   | Y cacher des corrections silencieuses sur des données qui devraient être rejetées                      |
| SkillImportService              | Strategy, Mapper                       | Même logique sur les compétences                                                                                                  | Même dérive que pour les évaluations                                                                   |
| RelationImportService           | Mapper, Specification                  | Projeter proprement les pivots et vérifier les références attendues                                                               | Y recréer à la main toutes les règles déjà validées en amont                                           |
| ImportReportBuilder             | Builder                                | Générer un rapport lisible et complet à partir de multiples événements d'import                                                   | Le transformer en pseudo-logger métier ou en objet fourre-tout                                         |
| PromotionCreatorFromFramework   | Factory, Command                       | Instancier proprement une nouvelle promotion à partir d'un framework existant, avec initialisation contrôlée                      | Le faire dépendre directement de l'UX, des formulaires ou de détails HTTP                              |
| PromotionEditor                 | Command                                | Encapsuler clairement la modification d'une promotion et ses règles                                                               | Mettre la validation de formulaire, le chargement SQL et la logique métier dans un seul service        |
| ModuleEditor                    | Command                                | Même idée pour les matières                                                                                                       | Devenir un service géant de gestion de module avec cinquante méthodes                                  |
| ProjectEditor                   | Command                                | Même idée pour les projets                                                                                                        | Même dérive que ModuleEditor                                                                           |
| Referential query services      | Query Object, Repository               | Fournir des lectures adaptées à l'UX sans polluer les repositories                                                                | Réimplémenter toute la logique d'assemblage dans les controllers malgré leur présence                  |
| Pedagogical query services      | Query Object                           | Sortir des vues de lecture métier ciblées pour les promotions, modules et projets                                                 | En faire des mini services métier de modification déguisés                                             |
| View models builders            | Builder, Mapper                        | Construire des objets d'affichage propres à partir du domaine applicatif                                                          | Les laisser devenir des transformeurs métier cachés                                                    |
| Controllers                     | Facade légère                          | Entrée simple vers les cas d'usage, adaptation HTTP, rien de plus                                                                 | Y remettre de la logique métier "juste pour aller plus vite", la grande tradition du drame             |
| Forms                           | Specification, Validator               | Encapsuler les contraintes d'entrée et certaines règles applicatives simples                                                      | Vouloir y faire de la vraie logique métier transverse                                                  |
| Application validators          | Specification                          | Exprimer des règles métiers lisibles et réutilisables                                                                             | En faire une forêt de specs incompréhensible pour des règles triviales                                 |
| Dataset conformity validators   | Chain of Responsibility, Specification | Contrôler la conformité du dataset final avant import                                                                             | Les faire se recouper ou se contredire faute de frontière claire                                       |
| Repositories Doctrine           | Repository                             | Encapsuler l'accès aux entités persistées et quelques requêtes métier utiles                                                      | Les transformer en mini services métier tentaculaires                                                  |
| Logs et audit                   | Decorator, Observer                    | Enrichir certains flux avec de la traçabilité sans salir le cœur métier                                                           | Mettre du logging partout au point de noyer le signal dans le bruit                                    |

---

# Les patterns les plus structurants

## 1. Pipeline

C'est le squelette du traitement documentaire :

- extraction
- stockage brut
- transformation
- validation
- export
- import

Sans lui, tu retombes vite dans un gros bloc spaghetti.

## 2. Strategy

Très utile dès qu'un comportement dépend du type de document ou d'entité :

- PDF RNCP
- PDF règlement
- Excel matrice
- Word matière
- Word projet

Pareil côté import si certaines entités ont des règles spécifiques.

## 3. Mapper

C'est un pattern discret, mais chez toi il est central.
Tu vas mapper partout :

- document vers brut
- brut vers final
- final vers SQL
- SQL vers vue

## 4. Command

Très adapté pour les cas d'usage écriture :

- importer
- créer une promotion
- éditer une matière
- éditer un projet

Ça aide à garder des actions explicites et testables.

## 5. Query Object

Très utile côté lecture UX.
Ça évite d'avoir :

- des controllers trop bavards
- des repositories trop lourds
- des assemblages bricolés partout

## 6. Chain of Responsibility

Parfait pour la validation dataset.
Tu peux chaîner proprement :

- structure
- références
- unicité
- cohérence métier

## 7. Builder

Très naturel pour :

- prompts
- rapports
- view models
- exports finaux

---

# Ceux à utiliser avec prudence

## Factory

Très utile, mais seulement quand la création est réellement complexe.
Sinon on finit avec une factory pour construire un tableau de trois champs, et là bon, on fait semblant.

## Specification

Super pour les règles métier importantes, combinables ou réutilisables.
Moins utile pour des règles simples qu'un bon if bien lisible ferait mieux.

## Decorator

Intéressant pour logs, audit, enrichissements techniques.
Pas un besoin central au début.

## Template Method

Peut être pratique pour mutualiser une structure de traitement commune.
Mais attention à ne pas faire une hiérarchie abstraite trop rigide trop tôt.
