<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Context;

use App\Service\Context\ModuleTextBlockFormatter;
use PHPUnit\Framework\TestCase;

final class ModuleTextBlockFormatterTest extends TestCase
{
    private ModuleTextBlockFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ModuleTextBlockFormatter();
    }

    public function testFormatsObjectivesAsIntroductionAndList(): void
    {
        $value = "À l'issue de ce module, l'apprenant sera capable de : "
            . "Installer des environnements virtualisés (Appliquer) "
            . "Administrer un annuaire sécurisé (Active Directory, LDAP) pour les utilisateurs (Appliquer) "
            . "Évaluer la qualité des services (Évaluer)";

        $result = $this->formatter->formatObjectives($value);

        self::assertSame("À l'issue de ce module, l'apprenant sera capable de :", $result['intro']);
        self::assertSame([
            'Installer des environnements virtualisés (Appliquer)',
            'Administrer un annuaire sécurisé (Active Directory, LDAP) pour les utilisateurs (Appliquer)',
            'Évaluer la qualité des services (Évaluer)',
        ], $result['items']);
    }

    public function testKeepsUnstructuredObjectivesAsText(): void
    {
        $result = $this->formatter->formatObjectives('Comprendre les architectures logicielles.');

        self::assertSame(['description' => 'Comprendre les architectures logicielles.'], $result);
    }

    public function testFormatsPrerequisitesAsParagraphs(): void
    {
        $result = $this->formatter->formatPrerequisites(
            'Connaissances de base en informatique et réseaux. Familiarité avec les principes de virtualisation et de sécurité informatique.'
        );

        self::assertSame([
            'Connaissances de base en informatique et réseaux.',
            'Familiarité avec les principes de virtualisation et de sécurité informatique.',
        ], $result['paragraphs']);
    }
}
