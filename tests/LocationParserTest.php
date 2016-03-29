<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Elit\LocationParser;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * 
 */
class LocationParserTest extends PHPUnit_Framework_TestCase
{

  public function testFindsComma() 
  {

    $value = 'las vegas,';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals($results['state'], '');
    $this->assertEquals($results['rest'], 'las vegas');

    $value = 'las vegas  , ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals($results['state'], '');
    $this->assertEquals($results['rest'], 'las vegas');

    $value = ',';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals($results['state'], '');
    $this->assertEquals($results['rest'], '');

    $value = ' indianapolis  ,   indiana ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals($results['state'], 'indiana');
    $this->assertEquals($results['rest'], 'indianapolis');

  }

  public function testFindsNoComma()
  {
    $value = 'las vegas   ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals($results[0], 'las');
    $this->assertEquals($results[1], 'vegas');


  }
  
}
