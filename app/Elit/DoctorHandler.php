<?php

namespace Elit;

/**
 * 
 */
class DoctorHandler
{
    private static $forms = [ 'dr', 'dr.', 'doctor', ];

    public static function hasMoreThanOneWord($string)
    {
        $trimmed = trim($string);
        return (preg_match('/\s/', $trimmed)) ? true : false;
    }

    public static function hasDoctor($string)
    {

        $trimmed = trim($string);

        foreach (self::$forms as $form) {
            if (preg_match("/^$form /i", $trimmed) === 1 ) {
                return true;
            }
        }

        return false;
    }

    public static function normalize($string)
    {
        $trimmed = trim($string);

        if (self::hasMoreThanOneWord($trimmed) &&
            self::hasDoctor($trimmed)) {

            foreach (self::$forms as $form) {
                $replaced = preg_replace("/^$form /i", '', $trimmed);
                if ($replaced !== $trimmed) {
                    return $replaced;
                }
            }
        }

        return $trimmed;
    }
    
}
