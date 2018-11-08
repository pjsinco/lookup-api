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
}
