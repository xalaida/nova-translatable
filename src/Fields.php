<?php

namespace Nevadskiy\Nova\Translatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Fields
{
    /**
     * The default locales list.
     *
     * @var array
     */
    protected static $defaultLocales = [];

    /**
     * The attribute locale separator.
     *
     * @var string
     */
    protected static $attributeLocaleSeparator = '__';

    /**
     * The default field name customizer function.
     *
     * @var callable
     */
    protected static $defaultNameCustomizer;

    /**
     * The fields' resolver function.
     *
     * @var callable
     */
    protected $fieldsResolver;

    /**
     * The field name customizer function.
     *
     * @var callable
     */
    protected $nameCustomizer;

    /**
     * The locales list.
     *
     * @var array
     */
    protected $locales = [];

    /**
     * The index locales list.
     *
     * @var array|null
     */
    protected $indexLocales;

    /**
     * Indicates that untouched fields should be ignored.
     *
     * @var bool
     */
    protected $ignoreUntouched = false;

    /**
     * Specify the locale list for the field.
     */
    public static function defaultLocales(array $locales): void
    {
        static::$defaultLocales = $locales;
    }

    /**
     * Specify the default customizer function for the field name.
     */
    public static function nameUsing(callable $customizer): void
    {
        static::$defaultNameCustomizer = $customizer;
    }

    /**
     * Specify the default customizer function for the field name.
     */
    public static function originalNames(): void
    {
        static::$defaultNameCustomizer = static::originalNameCustomizer();
    }

    /**
     * Specify the attribute locale separator for the field.
     */
    public static function attributeLocaleSeparator(string $separator): void
    {
        static::$attributeLocaleSeparator = $separator;
    }

    /**
     * Get the attribute locale separator for the field.
     */
    public static function getAttributeLocaleSeparator(): string
    {
        return static::$attributeLocaleSeparator;
    }

    /**
     * Make a new fields factory instance using the given fields' resolver function.
     *
     * @param callable(string $locale): array<Field> $fieldsResolver
     */
    public static function forLocale(callable $fieldsResolver): self
    {
        return new static($fieldsResolver);
    }

    /**
     * Make a new fields factory instance.
     *
     * @param callable(string $locale): array<Field> $fieldsResolver
     */
    public function __construct(callable $fieldsResolver)
    {
        $this->fieldsResolver = $fieldsResolver;
        $this->nameCustomizer = static::$defaultNameCustomizer;
        $this->locales = static::$defaultLocales;
    }

    /**
     * Use the original name of the field.
     */
    public function originalName(): self
    {
        $this->nameCustomizer = static::originalNameCustomizer();

        return $this;
    }

    /**
     * Use the given locales for the field.
     */
    public function locales(array $locales): self
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * Get the locale list.
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * Get the locale list for the index view.
     */
    public function getIndexLocales(): ?array
    {
        return $this->indexLocales;
    }

    /**
     * Show a field only for the current locale on the index view.
     */
    public function onlyCurrentLocaleOnIndex(): self
    {
        return $this->onlyLocalesOnIndex([config('app.locale')]);
    }

    /**
     * Show a field only for the fallback locale on the index view.
     */
    public function onlyFallbackLocaleOnIndex(): self
    {
        return $this->onlyLocalesOnIndex([config('app.fallback_locale')]);
    }

    /**
     * Show fields only for the given locales on the index view.
     */
    public function onlyLocalesOnIndex(array $locales = []): self
    {
        $this->indexLocales = $locales;

        return $this;
    }

    /**
     * Show fields for each locale in the index view except the given locales.
     */
    public function exceptLocalesOnIndex(array $locales = []): self
    {
        $this->indexLocales = collect($this->getLocales())
            ->diff($locales)
            ->all();

        return $this;
    }

    /**
     * Ignore the untouched field.
     */
    public function ignoreUntouched(bool $ignoreUntouched = true): self
    {
        $this->ignoreUntouched = $ignoreUntouched;

        return $this;
    }

    /**
     * Make translatable fields.
     */
    public function make(): MergeValue
    {
        return new MergeValue($this->makeFields());
    }

    /**
     * Make fields for all locales.
     */
    public function makeFields(): array
    {
        $fields = [];

        foreach ($this->getLocales() as $locale) {
            foreach ($this->makeFieldsForLocale($locale) as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Make fields for the given locale.
     */
    protected function makeFieldsForLocale(string $locale): array
    {
        $fields = [];

        foreach ($this->newFields($locale) as $field) {
            $fields[] = $this->configureField($field, $locale);
        }

        return $fields;
    }

    /**
     * Resolve fields for the given locale.
     */
    protected function newFields(string $locale): array
    {
        return call_user_func($this->fieldsResolver, $locale);
    }

    /**
     * Configure the field according to the given locale.
     */
    protected function configureField(Field $field, string $locale): Field
    {
        return tap($field, function (Field $field) use ($locale) {
            $this->modifyName($field, $locale);
            $this->modifyAttribute($field, $locale);
            $this->configureIndexView($field, $locale);
        })
            ->resolveUsing(function ($value, Model $model, string $attribute) use ($locale) {
                return $model->translator()->getOr($this->getOriginalAttribute($attribute, $locale), $locale);
            })
            ->fillUsing(function (NovaRequest $request, Model $model, string $attribute, string $requestAttribute) use ($locale) {
                $originalAttribute = $this->getOriginalAttribute($attribute, $locale);

                if ($this->shouldFill($request, $model, $originalAttribute, $requestAttribute, $locale)) {
                    $model->translator()->set($originalAttribute, $request->get($requestAttribute), $locale);
                }
            });
    }

    /**
     * Configure index field according to the given locale.
     */
    protected function configureIndexView(Field $field, string $locale): void
    {
        if (! is_null($this->getIndexLocales())) {
            $originalCondition = $field->showOnIndex;

            $field->showOnIndex(function () use ($originalCondition, $locale) {
                $isShown = is_callable($originalCondition)
                    ? $originalCondition(func_get_args())
                    : $originalCondition;

                return $isShown && collect($this->getIndexLocales())->contains($locale);
            });
        }
    }

    /**
     * Configure a filter of the field.
     *
     * @TODO: feature this.
     */
    protected function configureFieldFilter(Field $field, string $locale): void
    {
        $field->filterableCallback = function (NovaRequest $request, $query, $value) use ($field, $locale) {
            return $query->whereTranslatable($this->getOriginalAttribute($field->attribute, $locale), $value, $locale);
        };
    }

    /**
     * Determine if the field should be filled.
     */
    protected function shouldFill(NovaRequest $request, Model $model, string $attribute, string $requestAttribute, string $locale): bool
    {
        if (! $request->has($requestAttribute)) {
            return false;
        }

        if ($this->isTouched($request, $model, $attribute, $requestAttribute, $locale)) {
            return true;
        }

        if ($this->ignoreUntouched) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the field was touched.
     */
    protected function isTouched(NovaRequest $request, Model $model, string $attribute, string $requestAttribute, string $locale): bool
    {
        // TODO: refactor using ->has() method, method ->getOr() can return empty string.
        if ($model->translator()->getOr($attribute, $locale)) {
            return true;
        }

        if ($request->get($requestAttribute)) {
            return true;
        }

        return false;
    }

    /**
     * Modify the field name according to the locale.
     */
    protected function modifyName(Field $field, string $locale): void
    {
        $customizer = $this->nameCustomizer ?: static function (string $name, string $locale) {
            return "{$name} ($locale)";
        };

        $field->name = $customizer($field->name, $locale);
    }

    /**
     * The customizer function to keep original name.
     */
    protected static function originalNameCustomizer(): callable
    {
        return static function (string $name) {
            return $name;
        };
    }

    /**
     * Modify the field attribute according to the locale.
     */
    protected function modifyAttribute(Field $field, string $locale): void
    {
        $field->attribute .= $this->attributeSuffix($locale);
    }

    /**
     * Get the attribute suffix.
     */
    protected function attributeSuffix(string $locale): string
    {
        return static::getAttributeLocaleSeparator() . $locale;
    }

    /**
     * Get the original attribute name.
     */
    protected function getOriginalAttribute(string $attribute, string $locale): string
    {
        return Str::beforeLast($attribute, $this->attributeSuffix($locale));
    }
}
