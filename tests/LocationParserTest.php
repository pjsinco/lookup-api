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

  public function testPunctStrip()
  {
    $value = 'il.';
    $results = LocationParser::punctStrip($value);
    $this->assertEquals('il', $results);

    $value = '    il.  ';
    $results = LocationParser::punctStrip($value);
    $this->assertEquals('il', $results);

    $value = 'n.m.';
    $results = LocationParser::punctStrip($value);
    $this->assertEquals('n m', $results);

    $value = '   h!llo! ..!';
    $results = LocationParser::punctStrip($value);
    $this->assertEquals('h llo', $results);
  }

  public function testStateAbbrev()
  {

    $value = 'il';
    $results = LocationParser::stateAbbrev($value);
    $this->assertEquals('IL', $results);

    $value = 'illinois';
    $results = LocationParser::stateAbbrev($value);
    $this->assertEquals('IL', $results);

    $value = 'il.';
    $results = LocationParser::stateAbbrev($value);
    $this->assertEquals('IL', $results);

    $value = 'nm';
    $results = LocationParser::stateAbbrev($value);
    $this->assertEquals('NM', $results);

    $value = 'new mexico';
    $results = LocationParser::stateAbbrev($value);
    $this->assertEquals('NM', $results);

    $value = 'n.m.';
    $results = LocationParser::stateAbbrev($value);
    $this->assertEquals('NM', $results);

  }

  public function testSpaceStrip()
  {
    $value = '   las   vegas  ';
    $results = LocationParser::spaceStrip($value);
    $this->assertEquals('las vegas', $results);
    
    $value = 'las vegas';
    $results = LocationParser::spaceStrip($value);
    $this->assertEquals('las vegas', $results);
  }


  public function testCommaStrip()
  {
    $value = 'las vegas, nm';
    $results = LocationParser::commaStrip($value);
    $this->assertEquals('las vegas nm', $results);

    $value = '   las vegas  ,   nm  ';
    $results = LocationParser::commaStrip($value);
    $this->assertEquals('las vegas nm', $results);
  }

  public function testFindsComma() 
  {
    $value = 'las vegas,';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals('', $results['state']);
    $this->assertEquals('las vegas', $results['rest']);

    $value = 'las vegas  , ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals('', $results['state']);
    $this->assertEquals('las vegas', $results['rest']);

    $value = ',';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals('', $results['state']);
    $this->assertEquals('', $results['rest']);

    $value = ' indianapolis  ,   indiana ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals('indiana', $results['state']);
    $this->assertEquals('indianapolis', $results['rest']);
  }

  public function testFindsNoComma()
  {
    $value = 'las vegas   ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals('las', $results[0]);
    $this->assertEquals('vegas', $results[1]);
  }

  public function testTransformCity()
  {
    $value = 'st louis';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('saint louis', $results);

    $value = 'st';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('saint ', $results);

    $value = '   St. louis  ';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('saint louis', $results);

    $value = 'mt union';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('mount union', $results);

    $value = '  Mt.   union  ';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('mount union', $results);

    $value = 'ft worth';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('fort worth', $results);

    $value = '   Ft.     worth   ';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('fort worth', $results);

    $value = 'worth ft';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('worth ft', $results);

    $value = 'louis st louis';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('louis st louis', $results);

    $value = '  union   mt   union  ';
    $results = LocationParser::transformCity($value);
    $this->assertEquals('  union   mt   union  ', $results);
  }
  
  public function testParseLocationWithoutAState()
  {
    $value = 'chicago';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(1, count($results));
    $this->assertEquals('chicago', $results[0]);
 
    $value = 'las vegas';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('las', $results[0]);
    $this->assertEquals('vegas', $results[1]);
  }

  public function testParseLocationWithComma()
  {
    $value = 'chicago,';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('chicago', $results['rest']);
    $this->assertEquals('', $results['state']);
 
    $value = 'las vegas,';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('las vegas', $results['rest']);
    $this->assertEquals('', $results['state']);
  }

  public function testParseLocationWithCommaAndPartialState()
  {
    $value = 'chicago, i';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('chicago', $results['rest']);
    $this->assertEquals('i', $results['state']);
 
    $value = 'las vegas,n';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('las vegas', $results['rest']);
    $this->assertEquals('n', $results['state']);
  }

  public function testParseLocationWithNoCommaAndPartialState()
  {
    $value = 'chicago i';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('chicago', $results[0]);
    $this->assertEquals('i', $results[1]);
 
    $value = 'las vegas n';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(3, count($results));
    $this->assertEquals('las', $results[0]);
    $this->assertEquals('vegas', $results[1]);
    $this->assertEquals('n', $results[2]);
  }

  public function testParseLocationWithCommaAndState()
  {
    $value = 'chicago, il';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals($results['rest'], 'chicago');
    $this->assertEquals($results['state'], 'IL');
  }

  public function testParseLocationWithNoCommaAndState()
  {

    $value = 'chicago il';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('chicago', $results['rest']);
    $this->assertEquals('IL', $results['state']);

    $value = 'chicago illinois';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('chicago', $results['rest']);
    $this->assertEquals('IL', $results['state']);
 
    $value = 'chicago il.';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('chicago', $results['rest']);
    $this->assertEquals('IL', $results['state']);
 
    $value = 'las vegas nm';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('las vegas', $results['rest']);
    $this->assertEquals('NM', $results['state']);

    $value = 'las vegas n.m.';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('las vegas', $results['rest']);
    $this->assertEquals('NM', $results['state']);

    $value = 'las vegas new mexico';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('las vegas', $results['rest']);
    $this->assertEquals('NM', $results['state']);

    $value = 'lees summit missouri';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('lees summit', $results['rest']);
    $this->assertEquals('MO', $results['state']);
  }

  public function testParseLocationWithTransformCity()
  {
    $value = 'st. louis';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('saint', $results[0]);
    $this->assertEquals('louis', $results[1]);

    $value = 'st.';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(1, count($results));
    $this->assertEquals('saint', $results[0]);

    $value = 'st. louis mo';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('saint louis', $results['rest']);
    $this->assertEquals('MO', $results['state']);

    $value = '  st  louis      mo  ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('saint louis', $results['rest']);
    $this->assertEquals('MO', $results['state']);

    $value = 'mt. union ohio';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('mount union', $results['rest']);
    $this->assertEquals('OH', $results['state']);

    $value = 'union mt. ohio';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('union mt', $results['rest']);
    $this->assertEquals('OH', $results['state']);

    $value = 'union mt ohio';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('union mt', $results['rest']);
    $this->assertEquals('OH', $results['state']);

    $value = 'streeter wv';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('streeter', $results['rest']);
    $this->assertEquals('WV', $results['state']);

    $value = 'str';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(1, count($results));
    $this->assertEquals('str', $results[0]);

    $value = 'st, wv';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('saint', $results['rest']);
    $this->assertEquals('WV', $results['state']);

    $value = '  indianapolis   in   ';
    $results = LocationParser::parseLocation($value);
    $this->assertEquals(2, count($results));
    $this->assertEquals('indianapolis', $results['rest']);
    $this->assertEquals('IN', $results['state']);

  }
}
