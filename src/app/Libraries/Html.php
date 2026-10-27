<?php

namespace App\Libraries;

use Spatie\Html\Html as SpatieHtml;

/**
 * spatie/laravel-html, with textarea contents escaped the way
 * laravelcollective/html did. Spatie writes the value out raw, which
 * breaks news articles and event descriptions holding HTML.
 */
class Html extends SpatieHtml
{
    public function textarea($name = null, $value = null)
    {
        return parent::textarea($name)->html(e($this->old($name, $value)));
    }
}
