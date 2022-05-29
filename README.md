# Nova Translatable Fields

## Demo

Configure field according to locale 

Simple usage:

```php
public function fields(): array
{
    return [
        Translatable::fields(fn (string $locale) => [
            Text::make('Name'),
            Textarea::make('Description'),
        ])
            ->labelSuffix(fn (string $locale) => " [{$locale}]")
            ->onlyCurrentLocaleOnIndex()
            ->make(),
    ];
}
```

```php
/**
 * @inheritdoc
 */
public function fields(NovaRequest $request): array
{
    return [
        ID::make()->sortable(),

        Translatable::fields(function (string $locale) {
            return [
                tap(Text::make(__('Title [:locale]', ['locale' => $locale]), 'title'), function (Field $field) use ($locale) {
                    if ($locale === app()->getLocale()) {
                        $field->required()
                    }
                }),

                tap(Text::make(__('Description [:locale]', ['locale' => $locale]), 'description'), function (Field $field) use ($locale) {
                    if ($locale === app()->getLocale()) {
                        $field->required()
                    }
                }),
            ];
        })
            ->make(),
    ];
}
```

## To Do

- [ ] doc `ignoreUntouched` method.
- [ ] add `labelSuffix` method.
