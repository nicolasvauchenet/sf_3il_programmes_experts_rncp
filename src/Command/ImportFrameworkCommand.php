<?php

namespace App\Command;

use App\Enum\ImportMode;
use App\Service\Import\FrameworkImportService;
use App\Service\Import\JsonDatasetLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:framework:import',
    description: 'Importe un référentiel JSON dans la base de données.',
)]
final class ImportFrameworkCommand extends Command
{
    public function __construct(
        private readonly JsonDatasetLoader      $datasetLoader,
        private readonly FrameworkImportService $importService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Chemin du dossier contenant structure.json et les fichiers JSON associés'
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Mode d’import : %s ou %s',
                    ImportMode::FULL->value,
                    ImportMode::MODULES_PROJECTS->value
                ),
                ImportMode::FULL->value
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $directory = (string)$input->getArgument('directory');
        $modeValue = (string)$input->getOption('mode');

        try {
            $mode = ImportMode::from($modeValue);
        } catch (\ValueError) {
            $io->error(sprintf(
                'Mode invalide : "%s". Valeurs autorisées : %s, %s.',
                $modeValue,
                ImportMode::FULL->value,
                ImportMode::MODULES_PROJECTS->value,
            ));

            return Command::FAILURE;
        }

        try {
            $io->title('Import du référentiel JSON');
            $io->text(sprintf('Dossier source : %s', $directory));
            $io->text(sprintf('Mode : %s', $mode->label()));

            $dataset = $this->datasetLoader->loadFromDirectory($directory, $mode);

            /** @var array<string, mixed> $structure */
            $structure = $dataset['structure'];
            /** @var array<string, mixed> $meta */
            $meta = $structure['meta'] ?? [];

            $io->section('Jeu de données détecté');
            $io->definitionList(
                ['Dataset' => (string)($meta['datasetCode'] ?? 'n/a')],
                ['RNCP' => (string)($meta['rncpCode'] ?? 'n/a')],
                ['Programme' => (string)($meta['programCode'] ?? 'n/a')],
                ['Titre' => (string)($meta['programTitle'] ?? 'n/a')],
                ['Année universitaire' => (string)($meta['academicYear'] ?? 'n/a')],
            );

            $io->section('Fichiers chargés');

            if ($mode === ImportMode::FULL) {
                $io->listing([
                    sprintf('Modules : %d', count($dataset['modules'])),
                    sprintf('Projets : %d', count($dataset['projects'])),
                    sprintf('Compétences : %d', count($dataset['skills'])),
                    sprintf('Évaluations : %d', count($dataset['evaluations'])),
                ]);
            } else {
                $io->listing([
                    sprintf('Modules : %d', count($dataset['modules'])),
                    sprintf('Projets : %d', count($dataset['projects'])),
                    'Compétences détaillées : non chargées en mode partiel',
                    'Évaluations détaillées : non chargées en mode partiel',
                ]);
            }

            $report = $this->importService->import($dataset, $mode);

            $io->section('Résumé de l’import');

            $rows = [];

            foreach ($report->all() as $section => $stats) {
                $rows[] = [
                    $section,
                    (string)$stats['created'],
                    (string)$stats['updated'],
                ];
            }

            if ($rows !== []) {
                $io->table(
                    ['Section', 'Créés', 'Mis à jour'],
                    $rows
                );
            }

            $io->success('Import terminé.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error([
                'Échec de l’import.',
                sprintf('Message : %s', $e->getMessage()),
            ]);

            return Command::FAILURE;
        }
    }
}
