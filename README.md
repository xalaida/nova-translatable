# Nova Translatable Fields

This package allows you to add translatable fields to Laravel Nova resources.

It is designed to work in tandem with the main [nevadskiy/laravel-translatable](https://github.com/nevadskiy/laravel-translatable.git) package, which manages how translations are stored in the database.

## 🔌 Installation

```bash
composer require nevadskiy/nova-translatable
```

Also make sure the main [nevadskiy/laravel-translatable](https://github.com/nevadskiy/laravel-translatable.git) package is installed.

## ⚙ Demo

The code below shows how to make a Nova resource translatable using the package.

```php
<?php

namespace App\Nova;

use App\Models\Book as BookModel;
use Nevadskiy\Nova\Translatable\PerformsTranslatableQueries;
use Nevadskiy\Nova\Translatable\Fields;

class Book extends Resource
{
    use PerformsTranslatableQueries;

    public static $model = BookModel::class;

    public static $title = 'name';

    public static $search = ['id', 'name'];

    public function fields(Request $request): array
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Fields::forLocale(fn (string $locale) => [
                Text::make('Name')->sortable(),

                Textarea::make('Description'),
            ])
                ->onlyCurrentLocaleOnIndex()
                ->make(),
 
            // ...
        ];
    }
}
```

## 📄 Documentation

### Fields factory

To define translatable fields, use a `Nevadskiy\Nova\Translatable\Fields` factory as following:

```
Fields::forLocale(function (string $locale) {
    return [
        Text::make('Name'),
        Textarea::make('Description'),
    ];
})
    ->make(),
```

The static method `forLocale` accepts a callable function,
that receives a single `$locale` argument for which translatable fields are going to be created 
and must return an array with simple Nova fields that should be translatable.

In the end, to create those fields, you need to call a `make` method.

So basically, if you define 2 fields for 4 locales, the factory will create 8 fields (4 x 2) which will behave like regular fields.

### Defining locales

To specify the global list of locales, you can add the following code to the `AppServiceProvider`.

```php
use Nevadskiy\Nova\Translatable\Fields;

...

public function boot(): void
{
    Fields::defaultLocales(['en', 'uk', 'pl', 'cz']);
}
```

If you want to use different locales for resources, you can specify them using the `locales` method.

```
Fields::forLocale(function (string $locale) {
    return [
        Text::make('Name'),
        Textarea::make('Description'),
    ];
})
    ->locales(['en', 'uk', 'pl', 'cz'])
    ->make(),
```

### Customizing label

By default, a label of the translatable field is shown with the locale suffix: ` [en]`.

To customize the label display logic for fields, you can globally specify the label display logic in the `AppServiceProvider`.

```php
use Nevadskiy\Nova\Translatable\Fields;

...

public function boot(): void
{
    Fields::labelUsing(fn (string $label, string $locale) => "{$label} ({$locale})");
}
```

You can also specify the label yourself when defining a field in a resource.

```php
Fields::forLocale(function (string $locale) {
    return [
        Text::make(__('Name (:locale)', ['locale' => $locale])),
    ];
})
    ->rawLabel()
    ->make(),
```

### Index View 

It is common case when you want to show only translation for single locale on the index view instead of translation for all locales.

To do this, call the `onlyCurrentLocaleOnIndex` method.

```php
Fields::forLocale(function (string $locale) {
    return [
        Text::make('Name'),
    ];
})
    ->onlyCurrentLocaleOnIndex()
    ->make(),
```

To do this, call the `onlyCurrentLocaleOnIndex` method. 
This method will call the `hideOnIndex` method on all fields whose locale differs from the current one.

The `onlyFallbackLocaleOnIndex` method is also available, or you can specify the locales yourself using the `onlyLocalesOnIndex` method.

### Ignoring untouched fields

By default, a resource form passes translations to all locales during form submission, even if you use the `required` validation rule for only one locale.

To ignore fields for which translations have not yet been filled in, you can use the `ignoreUntouched` method.

```php
Fields::forLocale(function (string $locale) {
    return [
        Text::make('Name'),
    ];
})
    ->onlyCurrentLocaleOnIndex()
    ->locales(['en', 'uk', 'pl', 'cz', 'es', 'de', 'zh'])
    ->ignoreUntouched()
    ->make(),
```

This allows you to create a model without filling in all translations if you have a lot of locales.

### Advanced usage

You can use your own construction logic for a field by the given locale. It is convenient to do this with the Laravel `tap` helper.

```php
Fields::forLocale(function (string $locale) {
    return [
        tap(Text::make(__('Title [:locale]', ['locale' => $locale]), 'title'), function (Field $field) use ($locale) {
            if ($locale === 'pl') {
                $field->required()
            }
        }),
    ];
})
    ->make(),
```

### Searching and sorting

If you want to search or sort models by translatable fields, add a `PerformsTranslatableQueries` trait to the resource.

```php
<?php

namespace App\Nova;

use App\Models\Book as BookModel;
use Nevadskiy\Nova\Translatable\PerformsTranslatableQueries;

class Book extends Resource
{
    use PerformsTranslatableQueries;
}
```

## 📑 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## ☕ Contributing

Contributions are welcome and will be fully credited.

We accept contributions via Pull Requests.

## 🔓 Security

If you discover any security related issues, please [e-mail me](mailto:nevadskiy@gmail.com) instead of using the issue tracker.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

## 🔨 To Do

- [ ] refactor `ignoreUntouched` behaviour using `has` translator method.
- [ ] add static `labelUsing` method and add possibility to configure global labelSuffix.
- [ ] add `rawLabel` method.
- [ ] add `filterable` support.
- [ ] add `onlyFallbackLocaleRequired`, `onlyCurrentLocaleRequired` methods.
- [ ] add `ignoreUntouchedOnUpdate`, `ignoreUntouchedOnCreate` methods.
- [ ] add `mergeLocales` method.
