<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Like
{
    /**
     * Applica un filtro LIKE sul titolo trattando % e _ come letterali.
     */
    public static function where(EloquentBuilder|QueryBuilder $query, string $column, string $value): void
    {
        $query->whereRaw($column.' LIKE ? ESCAPE \'\\\'', [static::pattern($value)]);
    }

    /**
     * Escapa i caratteri speciali di LIKE e avvolge il valore con wildcard di substring.
     */
    public static function pattern(string $value): string
    {
        return '%'.addcslashes($value, '\\%_').'%';
    }
}
