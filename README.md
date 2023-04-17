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
use Nevadskiy\Nova\Translatable\Fields\Text;
use Nevadskiy\Nova\Translatable\Fields\Textarea;

class Book extends Resource
{
    use PerformsTranslatableQueries;

    public static $model = BookModel::class;

    public static $title = 'name';

    public static $search = ['id', 'name'];

    public function fields(Request $request): array
    {
        return [
            ID::make()
                ->sortable(),

            Text::make('Name')
                ->sortable()
                ->translatable()
                ->requiredOnlyFallbackLocale()
                ->showOnIndexOnlyFallbackLocale()

            Textarea::make('Description')
                ->translatable()
                ->requiredOnlyFallbackLocale(),
        ];
    }
}
```

## 📄 Documentation

### Defining locales

To specify the global list of locales, you can add the following code to the `AppServiceProvider`.

```php
use Nevadskiy\Nova\Translatable\Localizer;

public function boot(): void
{
    Localizer::locales(['en', 'uk', 'pl', 'cz']);
}
```

### Customizing field labels (names)

By default, a name of the translatable field has the following format: `Title (en)`.

To customize the name display logic for fields, you can specify the customizer function globally in the `AppServiceProvider`.

```php
use Nevadskiy\Nova\Translatable\Fields;

public function boot(): void
{
    Localizer::localizeNameUsing(fn (string $name, string $locale) => "{$name} ({$locale})");
}
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
