<?php

namespace App\Command;

use App\Entity\{Promotion, PromotionDocument};
use App\Service\Admin\PromotionDocumentStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;

#[AsCommand(name: 'app:documents:import-sources', description: 'Reprend les documents historiques des promotions importées sans documents.')]
final class ImportSourceDocumentsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly PromotionDocumentStorage $storage, #[Autowire('%kernel.project_dir%/public/source')] private readonly string $source)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = 0;
        $labels = ['referentiel-rncp.pdf' => 'Référentiel RNCP', 'reglement-des-examens.pdf' => 'Règlement des examens', 'matrice-de-couverture.xlsx' => 'Matrice de couverture', 'projet-urban-hub.pdf' => 'Projet Urban Hub'];
        foreach ($this->em->getRepository(Promotion::class)->findAll() as $promotion) {
            if ($this->em->getRepository(PromotionDocument::class)->count(['promotion' => $promotion]) > 0) continue;
            $year = $promotion->getStartAt()?->format('Y') . '-' . $promotion->getEndAt()?->format('Y');
            $directory = $this->source . '/' . $promotion->getProgram()?->value . '_' . $year;
            $stored = [];
            try {
                foreach ($labels as $filename => $label) {
                    if (!is_file($directory . '/' . $filename)) continue;
                    $document = new PromotionDocument();
                    $document->promotion = $promotion;
                    $document->label = $label;
                    $document->originalName = $filename;
                    $document->filename = $this->storage->store(new File($directory . '/' . $filename));
                    $stored[] = $document->filename;
                    $document->position = count($stored);
                    $this->em->persist($document);
                }
                $this->em->flush();
            } catch (\Throwable $error) {
                foreach ($stored as $filename) $this->storage->remove($filename);
                throw $error;
            }
            $count += count($stored);
        }
        $io->success(sprintf('%d document(s) repris. Les fichiers originaux sont conservés.', $count));
        return Command::SUCCESS;
    }
}
