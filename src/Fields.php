<?php

namespace Nevadskiy\Nova\Translatable;

use Illuminate\Http\Resources\MergeValue;

class Fields extends MergeValue
{
    /**
     * Perform a callback only for a field with the given locale.
     */
    public function onLocale(string $locale, callable $callback): self
    {
        foreach ($this->all() as $field) {
            if ($field->getLocale() === $locale) {
                $callback($field);
            }
        }

        return $this;
    }

    /**
     * Perform a callback only for a field with a fallback locale.
     */
    public function onFallbackLocale(callable $callback): self
    {
        return $this->onLocale($this->getFallbackLocale(), $callback);
    }

    /**
     * Show only a field with a fallback locale on the index view.
     */
    public function showOnIndexOnlyFallbackLocale(): self
    {
        foreach ($this->all() as $field) {
            if ($field->getLocale() === $this->getFallbackLocale()) {
                $field->showOnIndex();
            } else {
                $field->hideFromIndex();
            }
        }

        return $this;
    }

    /**
     * Show only a field with a fallback locale on the index view.
     */
    public function requiredOnlyFallbackLocale(): self
    {
        foreach ($this->all() as $field) {
            if ($field->getLocale() === $this->getFallbackLocale()) {
                $field->mergeRules(['required']);
            } else {
                $field->mergeRules(['nullable']);
            }
        }

        return $this;
    }

    /**
     * Proxy calls on each field.
     */
    public function __call(string $name, array $arguments)
    {
        foreach ($this->all() as $field) {
            call_user_func([$field, $name], ...$arguments);
        }

        return $this;
    }

    /**
     * Get all localized fields.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get the fallback locale.
     */
    protected function getFallbackLocale(): string
    {
        return app()->getFallbackLocale();
    }
}
