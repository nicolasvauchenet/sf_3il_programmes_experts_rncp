<?php

declare(strict_types=1);

namespace App\Service\Framework;

use Symfony\Contracts\Cache\CacheInterface;

final readonly class CachedJsonResourceLoader
{
    public function __construct(
        private InRequestMemoryCache $memoryCache,
        private CacheInterface $cache,
        private JsonFileLoader $jsonFileLoader,
    ) {
    }

    public function load(string $path): JsonLoadResult
    {
        if (!is_file($path)) {
            return JsonLoadResult::missing($path);
        }

        $mtime = filemtime($path);
        $mtime = ($mtime === false) ? null : (int)$mtime;

        $cacheKey = $this->buildCacheKey($path, $mtime);

        if ($this->memoryCache->has($cacheKey)) {
            /** @var JsonLoadResult $cached */
            $cached = $this->memoryCache->get($cacheKey);

            return $cached;
        }

        $payload = $this->cache->get($cacheKey, function () use ($path): array {
            $json = $this->jsonFileLoader->readFile($path);
            $decoded = $this->jsonFileLoader->decode($json);

            if ($decoded['ok'] === true) {
                return [
                    'ok' => true,
                    'data' => $decoded['data'],
                    'error' => null,
                ];
            }

            return [
                'ok' => false,
                'data' => null,
                'error' => $decoded['error'],
            ];
        });

        $result = $payload['ok'] === true
            ? JsonLoadResult::success($path, $mtime, $payload['data'])
            : JsonLoadResult::invalid($path, $mtime, (string)$payload['error']);

        $this->memoryCache->set($cacheKey, $result);

        return $result;
    }

    private function buildCacheKey(string $path, ?int $mtime): string
    {
        $mtimePart = $mtime === null ? 'nomtime' : (string)$mtime;

        return 'framework_json.'.sha1($path.'|'.$mtimePart);
    }
}
