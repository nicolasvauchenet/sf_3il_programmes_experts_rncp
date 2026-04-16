<?php

namespace App\Command;

use App\Service\Import\FrameworkImportService;
use App\Service\Import\JsonDatasetLoader;
use App\Service\Import\ImportStrategyResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:framework:import',
    description: 'Import intelligent d’un référentiel JSON',
)]
final class ImportFrameworkCommand extends Command
{
    public function __construct(
        private readonly JsonDatasetLoader      $loader,
        private readonly FrameworkImportService $importService,
        private readonly ImportStrategyResolver $resolver,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('directory', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $directory = (string)$input->getArgument('directory');

        try {
            $dataset = $this->loader->loadFromDirectory($directory);

            $structure = $dataset['structure'];

            $hasSkills = count($dataset['skills']) > 0;
            $hasEvaluations = count($dataset['evaluations']) > 0;

            $strategy = $this->resolver->resolve($structure, $hasSkills, $hasEvaluations);

            $io->section('Stratégie détectée');
            $io->text($strategy->label());

            $report = $this->importService->importWithStrategy($dataset, $strategy);

            $io->section('Résumé');

            foreach ($report->all() as $section => $stats) {
                $io->text(sprintf(
                    '%s → %d créés / %d mis à jour',
                    $section,
                    $stats['created'],
                    $stats['updated']
                ));
            }

            $io->success('Import terminé.');

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
