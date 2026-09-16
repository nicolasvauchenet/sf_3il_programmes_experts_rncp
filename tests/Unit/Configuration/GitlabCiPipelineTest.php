<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class GitlabCiPipelineTest extends TestCase
{
    public function testPhpunitJobIsEnabledOnDevelopAndMain(): void
    {
        $configuration = Yaml::parseFile($this->projectPath('.gitlab-ci.yml'));
        $rules = $configuration['ci:phpunit']['rules'] ?? [];

        self::assertContains(
            ['if' => '$CI_COMMIT_BRANCH == "develop" || $CI_COMMIT_BRANCH == "main"'],
            $rules,
        );
        self::assertNotContains(['when' => 'never'], $rules);
    }

    public function testPhpunitJobInstallsRuntimeExtensionsAndRunsChecks(): void
    {
        $configuration = Yaml::parseFile($this->projectPath('.gitlab-ci.yml'));
        $beforeScript = $configuration['ci:phpunit']['before_script'] ?? [];
        $script = $configuration['ci:phpunit']['script'] ?? [];

        self::assertContains('docker-php-ext-install pdo_sqlite zip', $beforeScript);
        self::assertContains('php bin/console lint:container --env=test', $script);
        self::assertContains('php bin/console lint:twig templates', $script);
        self::assertContains('php bin/phpunit', $script);
    }

    public function testProductionDeploymentKeepsPersistentDataAndMigratesBeforeRestart(): void
    {
        $configuration = Yaml::parseFile($this->projectPath('.gitlab-ci.yml'));
        $remoteScript = implode("\n", $configuration['deploy:prod']['script'] ?? []);

        self::assertStringContainsString('mkdir -p data/referentiels certs var/backups/sqlite', $remoteScript);
        self::assertStringContainsString('docker compose build app', $remoteScript);
        self::assertStringContainsString('(docker compose stop app || true)', $remoteScript);
        self::assertStringContainsString('docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction --env=prod', $remoteScript);
        self::assertStringContainsString('docker compose up -d app', $remoteScript);
        self::assertStringContainsString('docker compose exec -T app php bin/console cache:clear --env=prod', $remoteScript);

        self::assertLessThan(
            strpos($remoteScript, 'docker compose up -d app'),
            strpos($remoteScript, 'docker compose run --rm app php bin/console doctrine:migrations:migrate'),
        );
    }

    private function projectPath(string $path): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
