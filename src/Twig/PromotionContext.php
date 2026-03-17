<?php

namespace App\Twig;

use App\Dto\Context\FrameworkStructure;
use App\Service\Context\Loader\FrameworkStructureLoader;
use Symfony\Component\HttpFoundation\RequestStack;

final class PromotionContext
{
    private bool $loaded = false;
    private ?\App\Dto\Context\FrameworkStructure $structure = null;

    public function __construct(
        private readonly RequestStack             $requestStack,
        private readonly FrameworkStructureLoader $structureLoader,
    )
    {
    }

    public function getPromotionTitle(): ?string
    {
        $s = $this->getStructure();
        if ($s === null) {
            return null;
        }

        $title = $s->meta['programTitle'] ?? null;

        return is_string($title) && trim($title) !== '' ? trim($title) : null;
    }

    public function getPromotionLabel(): ?string
    {
        $s = $this->getStructure();
        if ($s === null) {
            return null;
        }

        $code = $s->meta['programCode'] ?? null;
        $year = $s->meta['academicYear'] ?? null;

        $code = is_string($code) ? trim($code) : '';
        $year = is_string($year) ? trim($year) : '';

        $label = trim($code . ' ' . $year);

        return $label !== '' ? $label : null;
    }

    public function getStructure(): ?FrameworkStructure
    {
        if ($this->loaded) {
            return $this->structure;
        }
        $this->loaded = true;

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $this->structure = null;
        }

        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if (trim($promotion) === '' || trim($year) === '') {
            return $this->structure = null;
        }

        try {
            return $this->structure = $this->structureLoader->load($promotion, $year);
        } catch (\Throwable) {
            return $this->structure = null;
        }
    }
}
