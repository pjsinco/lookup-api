<?php

use Elit\Hasher;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class TestHasher extends PHPUnit_Framework_TestCase
{

  protected $hash;

  public function setUp()
  {
    $this->hash = Hasher::createId('157524', 'Patrick Sinco');
  }

  public function testReturnsAString()
  {
    $this->assertTrue(is_string($this->hash));
  }

  public function testReturnsSameValueOnMultipleCalls()
  {
    $hash1 = Hasher::createId('157524', 'Patrick Sinco');
    $hash2 = Hasher::createId('157524', 'Patrick Sinco');
    $hash3 = Hasher::createId('157524', 'Patrick Sinco');

    $this->assertTrue($hash1 == $hash2);
    $this->assertTrue($hash1 == $hash3);
    $this->assertTrue($hash2 == $hash3);
  }

  public function testZeroPadding()
  {
    $hash1 = Hasher::createId('1', 'Patrick Sinco');
    $hash2 = Hasher::createId('01', 'Patrick Sinco');
    $hash3 = Hasher::createId('001', 'Patrick Sinco');
    $hash4 = Hasher::createId('0001', 'Patrick Sinco');
    $hash5 = Hasher::createId('00001', 'Patrick Sinco');
    $hash6 = Hasher::createId('000001', 'Patrick Sinco');

    $this->assertTrue(
      ($hash1 == $hash6) &&
      ($hash2 == $hash6) &&
      ($hash3 == $hash6) &&
      ($hash4 == $hash6) &&
      ($hash5 == $hash6)
    );
  }
}
