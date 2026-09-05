<?php
declare(strict_types=1);

namespace app\common\service;

final class ListPagination
{
    public static function normalize(array $input): array
    {
        return [
            'page' => max(1, (int) ($input['page'] ?? 1)),
            'page_size' => min(100, max(1, (int) ($input['page_size'] ?? 20))),
        ];
    }

    public static function response(array $items, int $page, int $pageSize, int $total): array
    {
        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'pages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ];
    }
}
