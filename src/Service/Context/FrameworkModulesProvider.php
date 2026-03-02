<?php

namespace App\Service\Context;

use App\Dto\Context\ModuleSheet;

final readonly class FrameworkModulesProvider
{
    public function __construct(
        private FrameworkFolderScanner $scanner,
        private ModuleSheetLoader $loader,
    ) {
    }

    /**
     * @return ModuleSheet[]
     */
    public function listModules(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'modules');

        $modules = [];

        foreach ($refs as $ref) {
            try {
                $modules[] = $this->loader->load($ref->fileCode, $ref->path);
            } catch (\Throwable) {
                continue;
            }
        }

        usort($modules, static fn(ModuleSheet $a, ModuleSheet $b): int => $a->fileCode <=> $b->fileCode);

        return $modules;
    }
}
