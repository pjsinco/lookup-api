<?php

namespace Elit;

/**
 * Create unique identifiers.
 */
class Hasher
{
  /**
   * Create an identifier for a physician.
   *
   * @param string $idBase The base for creating the hash
   * @param string fullName Full name
   *
   * @return string Format: <firstName>-<lastName>-DO-<hash>
   *
   */
  public static function createId($idBase, $fullName) {
    if (empty($idBase) || empty($fullName)) {
      return '';
    }

    $punctStripped = 
      preg_replace('/[.,\/#!$%\^&\*;:{}=\-_`~()]/', '', $fullName);

    return sprintf(
      '%s-%s',
      mb_strtolower(str_replace(' ', '-', $punctStripped)),
      mb_substr(hash('sha256', $idBase), 0, 18)
    );
  }
}
