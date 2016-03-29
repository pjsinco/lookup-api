<?php

namespace Elit;
use Elit\USStates;

/**
 * 
 */
class LocationParser
{

  /**
   * Remove all n > 1 white spaces.
   *
   */
  public static function spaceStrip($value)
  {
    return trim(preg_replace('/\s\s+/', ' ', $value));
  }

  /**
   * Replace a comma with a space.
   *
   */
  public static function commaStrip($value)
  {
    return trim(self::spaceStrip(preg_replace('/,/', ' ', $value)));
  }

  /**
   * Replace common punctuation characters with a space
   *
   */
  public static function punctStrip($value)
  {
    return self::spaceStrip(
      preg_replace('/[.,\/#!$%\^&\*;:{}=\-_`~()]/', ' ', $value)
    );
  }

  
  /**
   * Split a value on punctuation, white space.
   *
   */
  public static function tokenize($value)
  {
    $punctStripped = self::punctStrip($value);
    //$commaStripped = self::commaStrip($punctStripped);
    $spaceStripped = self::spaceStrip($punctStripped);
    

    $tokenized = null;

    $tokenized = ((mb_strpos($spaceStripped, ' ') > 0)) ? 
      explode(' ', $spaceStripped) : array($spaceStripped);

    $last = self::lastToken($tokenized);
    $lastTwo = self::lastTwoTokens($tokenized);
    $tokenCount = count($tokenized);

    if ($tokenCount === 1) {
      return [
        'all' => $tokenized,
      ];
    } elseif ($tokenCount === 2) {
      return [
        'all' => $tokenized,
        'last' => [
          'lastToken' => $last['last'],
          'rest' => $last['rest'],
        ],
      ];
    } else {
      return [
        'all' => $tokenized,
        'last' => [
          'lastToken' => $last['last'],
          'rest' => $last['rest'],
        ],
        'lastTwo' => [
          'lastTwoTokens' => $lastTwo['lastTwo'],
          'rest' => $lastTwo['rest'],
        ],
      ];
    }
  }

  public static function hasComma($value)
  {
    return preg_match('/,/', $value) === 1;
  }

  public static function getZip($value) 
  {
    $re = "/^(.*[^\\d])?(\\d{5}$)/";
    preg_match($re, trim($value), $out);

    if (!empty($out)) {
      return $out[count($out) - 1];
    }

    return $out;
  }

  public static function getDigits($value)
  {

    $re = "/\\d+/";
    if (preg_match($re, trim($value), $out) === 1) {
      return $out[0];
    }

    return null;
  }

  private static function lastToken($tokens)
  {
    return [
      'last' => array_pop($tokens),
      'rest' => $tokens,
    ];
  }

  private static function lastTwoTokens($tokens)
  {
    if (count($tokens) < 2) {
      return null;
    }

    $last = array_pop($tokens);
    $nextToLast = array_pop($tokens);

    return [
      'lastTwo' => implode(' ', [$nextToLast, $last]),
      'rest' => $tokens,
    ];
  }

  private static function normalizeAbbrev($abbrev)
  {
    return strtoupper($abbrev);
  }

  private static function normalizeFullState($fullStateName)
  {
    return ucwords(strtolower($fullStateName));
  }

  /**
   * Close up two letters when separated by a space
   *
   */
  public static function closeUp($value)
  {
    return preg_replace('/^(\\w)\\s(\\w)/i', '$1$2', $value);
  }

  public static function stateAbbrev($abbrev) {
    $stripped = self::punctStrip($abbrev);
    $closedUp = self::closeUp($stripped);

    if (isset(USStates::$uspsStates[self::normalizeAbbrev($closedUp)])) {
      return self::normalizeAbbrev($closedUp);
    } else if (
        $stateAbbrev = array_search(
          self::normalizeFullState($closedUp), 
          USStates::$uspsStates
        )
      ) {
      return $stateAbbrev;
    } 

    return null;
  }

  /**
   * Convert forms of of St., Ft. and Mt. to 
   * Saint, Fort and Mount.
   *
   */
  public static function transformCity($value)
  {
    $stripped = self::spaceStrip($value);

    $saintRe = "/^(st\\.?)\\s|^st\\.?$/i";
    //$mountRe = "/^(mt\\.?)\\s/i";
    $mountRe = "/^(mt\\.?)\\s|^mt\\.?$/i";
    //$fortRe = "/^(ft\\.?)\\s/i";
    $fortRe = "/^(ft\\.?)\\s|^ft\\.?$/i";

    if (preg_match($saintRe, $stripped) > 0) {
      return preg_replace($saintRe, 'saint ', $stripped);
    } else if (preg_match($mountRe, $stripped) > 0) {
      return preg_replace($mountRe, 'mount ', $stripped);
    } else if (preg_match($fortRe, $stripped) > 0) {
      return preg_replace($fortRe, 'fort ', $stripped);
    }

    return $value;
  }
  
  /**
   * Split string on comma.
   *
   * @param string
   * @return array
   * @author PJ
   */
  private static function splitOnOneComma($value)
  {
    $split = explode(',', $value);

    // There should only be 1 comma
    if (count($split) !== 2) {
      return null;
    }

    $splits = [];
    foreach ($split as $v) {
      array_push($splits, trim($v));
    }
    return $splits;
  }

  public static function parseLocation($location)
  {

    if (self::hasComma($location)) {
      $split = self::splitOnOneComma($location);
      if ($split) {
        // Uppercase if we have an abbrev
        $state = (preg_match('/^\w\w$/', $split[1]) > 0) ? 
          self::normalizeAbbrev($split[1]) : $split[1];
        return [
          'state' => $state,
          'rest' => trim(self::transformCity($split[0])),
        ];
      }
    }

    $tokens = self::tokenize($location);
    $state = null;
    if (isset($tokens['last'])) {
      $state = self::stateAbbrev($tokens['last']['lastToken']);
      $rest = trim(self::transformCity(implode(' ', $tokens['last']['rest'])));
    } 

    if (empty($state)) {
      if (isset($tokens['lastTwo'])) {
        $state = self::stateAbbrev($tokens['lastTwo']['lastTwoTokens']);
        $rest = trim(self::transformCity(implode(' ', $tokens['lastTwo']['rest'])));
      }
    }
  
    if (!empty($state)) {
      return [
        'state' => $state,
        'rest' => trim(self::transformCity($rest)),
      ];
    }

    return array_map(function($token) {
      return trim(self::transformCity($token));
    }, $tokens['all']);


    //if (empty($state)) {
    //$state = self::stateAbbrev(self::lastTwoTokens($tokens));
    //}

  }
}
