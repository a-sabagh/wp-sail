<?php

namespace WPSail\Utility;

use Aimeos\Macro\Macroable;

class Str
{
    use Macroable;

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        return static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
    }

}
