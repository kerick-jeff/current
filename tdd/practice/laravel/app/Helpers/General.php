<?php

namespace App\Helpers;

class General {
    public static function sum(int|float $a, int|float $b): int|float
    {
        return $a + $b;
    }
}
