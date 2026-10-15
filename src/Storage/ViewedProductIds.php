<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Storage;

/**
 * The rules of the list both storages keep: the last viewed product first, no repeats, at most $limit ids.
 */
final class ViewedProductIds
{
    /**
     * @param array<array-key, mixed> $ids
     *
     * @return int[]
     */
    public static function prepend(array $ids, int $id, int $limit): array
    {
        array_unshift($ids, $id);

        return self::normalize($ids, $limit);
    }

    /**
     * Keeps positive integer ids only. The list comes from the visitor (a cookie can hold anything),
     * so whatever is not an id is dropped.
     *
     * @param array<array-key, mixed> $ids
     *
     * @return int[]
     */
    public static function normalize(array $ids, int $limit): array
    {
        $result = [];
        foreach ($ids as $id) {
            $id = filter_var($id, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (false === $id || in_array($id, $result, true)) {
                continue;
            }

            $result[] = $id;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }
}
