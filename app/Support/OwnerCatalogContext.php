<?php

namespace App\Support;

use Illuminate\Http\Request;

final class OwnerCatalogContext
{
    public static function from(Request $request): array
    {
        $query = $request->query();
        $name = is_string($query['q'] ?? null) ? mb_substr(trim($query['q']), 0, 150) : '';
        $state = $query['tampil'] ?? null;
        $page = filter_var($query['page'] ?? null, FILTER_VALIDATE_INT);

        return array_filter([
            'q' => $name !== '' ? $name : null,
            'tampil' => in_array($state, ['publik', 'nonaktif', 'kategori'], true) ? $state : null,
            'page' => $page > 1 ? $page : null,
        ], static fn ($value) => $value !== null);
    }
}
