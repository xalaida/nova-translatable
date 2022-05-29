<?php

namespace Nevadskiy\Nova\Translatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * @mixin Resource
 * TODO: refactor eager loading by resolving locales from fields
 */
trait PerformsTranslatableQueries
{
    /**
     * @inheritdoc
     */
    protected static function initializeQuery(NovaRequest $request, $query, $search, $withTrashed)
    {
        return parent::initializeQuery($request, $query, $search, $withTrashed)
            ->withoutTranslationsScope();
    }

    /**
     * @inheritdoc
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return parent::indexQuery($request, $query)
            ->with('translations');
    }

    /**
     * @inheritdoc
     */
    public static function detailQuery(NovaRequest $request, $query): Builder
    {
        return parent::detailQuery($request, $query)
            ->with('translations');
    }

    /**
     * @inheritdoc
     */
    public static function editQuery(NovaRequest $request, $query)
    {
        return parent::editQuery($request, $query)
            ->with('translations');
    }

    /**
     * @inheritdoc
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return parent::relatableQuery($request, $query)
            ->with('translations');
    }

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
            [$guessColumn, $locale] = static::guessAttributeAndLocale($column);

            if ($query->getModel()->isTranslatable($guessColumn)) {
                $query->orderByTranslatable($guessColumn, $direction, $locale);
            } else {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Guess the original attribute name and locale from the given column.
     */
    protected static function guessAttributeAndLocale(string $column): array
    {
        return [
            Str::beforeLast($column, Translatable::getAttributeLocaleSeparator()),
            Str::afterLast($column, Translatable::getAttributeLocaleSeparator())
        ];
    }
}
