<?php

namespace Nevadskiy\Nova\Translatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * @mixin Resource
 */
trait PerformsTranslatableQueries
{
    /**
     * @inheritdoc
     */
    protected static function initializeSearch($query, $search, $searchColumns)
    {
        return parent::initializeSearch($query, $search, static::prepareSearchColumns($query, $searchColumns));
    }

    /**
     * Prepare the search columns.
     */
    protected static function prepareSearchColumns(Builder $query, array $searchColumns): array
    {
        return collect($searchColumns)
            ->transform(function ($column) use ($query) {
                if (is_string($column) && $query->getModel()->isTranslatable($column)) {
                    return new TranslatableColumn($column);
                }

                return $column;
            })->all();
    }

    /**
     * @inheritdoc
     */
    protected static function applyOrderings($query, array $orderings)
    {
        $orderings = array_filter($orderings);

        if (empty($orderings)) {
            return empty($query->getQuery()->orders) && ! static::usesScout()
                ? $query->latest($query->getModel()->getQualifiedKeyName())
                : $query;
        }

        foreach ($orderings as $column => $direction) {
            [$guessColumn, $locale] = static::guessTranslatableColumnAndLocale($column);

            if ($query->getModel()->isTranslatable($guessColumn)) {
                $query->orderByTranslatable($guessColumn, $direction, $locale);
            } else {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Guess a translatable column and locale from the given column.
     */
    protected static function guessTranslatableColumnAndLocale(string $column): array
    {
        return [
            Str::beforeLast($column, Localizer::SEPARATOR),
            Str::afterLast($column, Localizer::SEPARATOR)
        ];
    }
}
