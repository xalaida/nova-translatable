<?php

namespace Nevadskiy\Nova\Translatable\Fields;

use Laravel\Nova\Fields\Text as Field;
use Nevadskiy\Nova\Translatable\Translatable;

class Text extends Field
{
    use Translatable;
}
