<?php

namespace Nevadskiy\Nova\Translatable;

use Illuminate\Support\Arr;

class Localizer
{
    /**
     * The locale list.
     *
     * @var array
     */
    protected static $locales = [];

    /**
     * The field name localizer function.
     *
     * @var callable|null
     */
    protected static $nameLocalizer;

    /**
     * Specify locale list for the localized.
     */
    public static function locales(array $locales): void
    {
        static::$locales = $locales;
    }

    /**
     * Make a localized field set from the given field.
     */
    public static function make(callable $callback): Fields
    {
        return new Fields((new static)->localize($callback));
    }

    /**
     * Make localized fields.
     */
    public function localize($callback): array
    {
        $fields = [];

        foreach (static::$locales as $locale) {
            foreach (Arr::wrap($callback($locale)) as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Localize a name of the field using the given callback.
     */
    public static function localizeNameUsing(callable $callback): void
    {
        static::$nameLocalizer = $callback;
    }

    /**
     * Localize a name of the field according to the given locale.
     */
    public static function localizeName(string $name, string $locale): string
    {
        return call_user_func(static::$nameLocalizer ?: static function (string $name, string $locale) {
            return "{$name} ($locale)";
        }, $name, $locale);
    }

    /**
     * Localize an attribute of the field according to the given locale.
     */
    public static function localizeAttribute(string $attribute, string $locale): string
    {
        return "{$attribute}__$locale";
    }
}
