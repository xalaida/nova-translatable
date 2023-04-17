<?php

namespace Nevadskiy\Nova\Translatable\Fields;

use Laravel\Nova\Fields\Textarea as Field;
use Nevadskiy\Nova\Translatable\Translatable;

class Textarea extends Field
{
    use Translatable;
}
