<?php

namespace Elit;
use Elit\USStates;

/**
 * 
 */
class LocationParser
{
  
  /**
   * Split a value on punctuation, white space
   *
   */
  public static function tokenize($value)
  {
    $punctStripped = 
      preg_replace('/[.,\/#!$%\^&\*;:{}=\-_`~()]/', ' ', $value);

    $spaceStripped = trim(preg_replace('/\s\s+|,/', ' ', $punctStripped));

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

  public static function stateAbbrev($abbrev) {
    if (isset(USStates::$uspsStates[self::normalizeAbbrev($abbrev)])) {
      return self::normalizeAbbrev($abbrev);
    } else if (
        $stateAbbrev = array_search(
          self::normalizeFullState($abbrev), 
          USStates::$uspsStates
        )
      ) {
      return $stateAbbrev;
    } 

    return null;
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
        return [
          'state' => $split[1],
          'rest' => $split[0],
        ];
      }
    }

    $tokens = self::tokenize($location);
    $state = null;
    if (isset($tokens['last'])) {
      $state = self::stateAbbrev($tokens['last']['lastToken']);
      $rest = implode(' ', $tokens['last']['rest']);
    } 

    if (empty($state)) {
      if (isset($tokens['lastTwo'])) {
        $state = self::stateAbbrev($tokens['lastTwo']['lastTwoTokens']);
        $rest = implode(' ', $tokens['lastTwo']['rest']);
      }
    }
  
    if (!empty($state)) {
      return [
        'state' => $state,
        'rest' => $rest,
      ];
    }

    return $tokens['all'];


    //if (empty($state)) {
    //$state = self::stateAbbrev(self::lastTwoTokens($tokens));
    //}




  }
}
