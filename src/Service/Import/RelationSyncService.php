<?php

namespace App\Service\Import;

use Doctrine\Common\Collections\Collection;

final class RelationSyncService
{
    /**
     * @template T of object
     *
     * @param Collection<int, T> $currentCollection
     * @param array<int, T> $targetEntities
     * @param callable(T): void $add
     * @param callable(T): void $remove
     */
    public function sync(
        Collection $currentCollection,
        array      $targetEntities,
        callable   $add,
        callable   $remove,
    ): void
    {
        $currentEntities = $currentCollection->toArray();

        foreach ($currentEntities as $entity) {
            if (!in_array($entity, $targetEntities, true)) {
                $remove($entity);
            }
        }

        foreach ($targetEntities as $entity) {
            if (!in_array($entity, $currentEntities, true)) {
                $add($entity);
            }
        }
    }
}
