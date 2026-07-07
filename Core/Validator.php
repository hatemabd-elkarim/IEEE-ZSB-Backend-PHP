<?php

namespace Core;

class Validator
{
    public static function string($value, $min = 4, $max = INF)
    {
        $value = trim($value);

        return strlen($value) >= $min && strlen($value) <= $max;
    }


    public static function email($value)
    {
        $value = trim($value);

        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function greaterThan(int $value, int $greaterThan): bool
    {
        return $value > $greaterThan;
    }
}
