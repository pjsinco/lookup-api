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
   * @param string firstName First name
   * @param string middleName Middle name
   * @param string lastName Last name
   *
   * @return string Format: <firstName>-<lastName>-DO-<hash>
   *
   */
  public static function createId($id, $firstName, $middleName, $lastName, $designation = 'DO') {
    $fullName = sprintf('%s %s %s %s', $firstName, $middleName, $lastName, $designation);

    if (empty($id)) { return ''; }

    $id = str_pad($id, 6, '0', STR_PAD_LEFT);
    $idBase64 = base64_encode($id);


    $punctStripped = 
      preg_replace('/[.,\/#!$%\^&\*;:{}=\-_`~()]/', '', $fullName);

    return sprintf(
      '%s-%s',
      mb_strtolower(str_replace(' ', '-', $punctStripped)),
      mb_substr(hash('sha256', $idBase64), 0, 18)
    );
  }
}
