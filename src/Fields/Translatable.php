<?php

namespace Nevadskiy\Nova\Translatable\Fields;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Nevadskiy\Nova\Translatable\Fields;
use Nevadskiy\Nova\Translatable\Localizer;

/**
 * @mixin Field
 *
 * @todo support `unique` validation rule.
 * @todo add possibility to use original resolver that was defined before the `translatable` call.
 * @todo add possibility to use original filler that was defined before the `translatable` call.
 */
trait Translatable
{
    /**
     * Indicates if the field is localized.
     *
     * @var bool
     */
    protected $localized = false;

    /**
     * The locale of the localized field.
     *
     * @var string|null
     */
    protected $locale;

    /**
     * The attribute name without localization.
     *
     * @var string
     */
    protected $originalAttribute;

    /**
     * Make a field translatable.
     */
    public function translatable(): Fields
    {
        return Localizer::make(function (string $locale) {
            return (clone $this)->localize($locale);
        });
    }

    /**
     * Localize the field according to the given locale.
     */
    public function localize(string $locale): self
    {
        $this->locale = $locale;
        $this->localized = true;

        $this->localizeName();
        $this->localizeAttribute();
        $this->localizeResolver();
        $this->localizeFiller();

        return $this;
    }

    /**
     * Get the field's locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Modify the field name according to the locale.
     */
    protected function localizeName(): void
    {
        $this->name = Localizer::localizeName($this->name, $this->locale);
    }

    /**
     * Modify the field attribute according to the locale.
     */
    protected function localizeAttribute(): void
    {
        $this->originalAttribute = $this->attribute;
        $this->attribute = Localizer::localizeAttribute($this->attribute, $this->locale);
    }

    /**
     * Modify the field resolver according to the locale.
     */
    protected function localizeResolver(): void
    {
        $this->resolveUsing(function () {
            return $this->resource->translator()->get($this->originalAttribute, $this->locale);
        });
    }

    /**
     * Modify the field filler according to the locale.
     */
    protected function localizeFiller(): void
    {
        $this->fillUsing(function (NovaRequest $request) {
            $this->resource->translator()->set($this->originalAttribute, $request->get($this->attribute), $this->locale);
        });
    }

    /**
     * @inheritdoc
     */
    protected function resolveAttribute($resource, $attribute)
    {
        if (! $this->localized) {
            return parent::resolveAttribute($resource, $attribute);
        }

        return $this->resource->translator()->get($this->originalAttribute, $this->locale);
    }

    /**
     * Merge the field rules.
     */
    public function mergeRules($rules): self
    {
        return $this->rules(array_merge($this->rules, $rules));
    }
}
