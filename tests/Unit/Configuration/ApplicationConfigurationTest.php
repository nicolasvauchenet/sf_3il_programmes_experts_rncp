<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ApplicationConfigurationTest extends TestCase
{
    public function testApplicationDataDirectoryUsesPersistentDockerVolume(): void
    {
        $configuration = Yaml::parseFile($this->projectPath('config/services.yaml'));

        self::assertSame(
            '%kernel.project_dir%/data/referentiels',
            $configuration['parameters']['app.data_dir'] ?? null,
        );
    }

    public function testDockerComposePersistsApplicationDataDirectory(): void
    {
        $configuration = Yaml::parseFile($this->projectPath('compose.yml'));

        self::assertContains(
            './data:/app/data',
            $configuration['services']['app']['volumes'] ?? [],
        );
    }

    public function testTestEnvironmentUsesDedicatedSqliteDatabase(): void
    {
        $testEnv = (string) file_get_contents($this->projectPath('.env.test'));

        self::assertStringContainsString(
            'DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"',
            $testEnv,
        );
    }

    private function projectPath(string $path): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
