<?php

namespace Nevadskiy\Nova\Translatable;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Query\Search\Column;

class TranslatableColumn extends Column
{
    /**
     * @inheritdoc
     */
    public function __invoke($query, $search, string $connectionType, string $whereOperator = 'orWhere'): Builder
    {
        return $query->whereTranslatable(
            $this->column,
            "%{$search}%",
            null,
            $this->getQueryOperator($connectionType),
            $this->getQueryBoolean($whereOperator)
        );
    }

    /**
     * Get the query operator according to the connection type.
     */
    protected function getQueryOperator(string $connectionType): string
    {
        if ($connectionType === 'pgsql') {
            return 'ilike';
        }

        return 'like';
    }

    /**
     * Get the query boolean according to the where operator.
     */
    protected function getQueryBoolean(string $whereOperator): string
    {
        if ($whereOperator === 'orWhere') {
            return 'or';
        }

        return 'and';
    }
}
