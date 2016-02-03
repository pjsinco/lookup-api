<?php

namespace Elit;
//use Elit\DoctorHandler;

class SearchHelper
{
    

    public static function hasTwoWords($string)
    {
        $normalized = self::normalize($string);

        return count(explode(' ', $normalized)) == 2;
    }

    public static function normalize($string)
    {
        $replaced = preg_replace(
            "/[^a-z]+/i", 
            " ", 
            trim(strtolower($string))
        );

        return $replaced;
    }

    public static function getAsTwoWordArray($string)
    {
        if (! self::hasTwoWords($string)) {
            return false;
        }

        return explode(' ', self::normalize($string));
    }
}

