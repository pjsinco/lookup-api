<?php

use Elit\SearchHelper;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

error_reporting(E_ALL);

class SearchHelperTest extends PHPUnit_Framework_TestCase
{
    public function testTrueIsTrue( )
    {
        $this->assertTrue(true);
    }

    public function testNormalize()
    {
        $testStrings = [
            'Hello There',
            '   hello   there   ',
            '   HELLO  ,  THERE   ',
            'HELLO,THERE',
        ];

        foreach ($testStrings as $testString) {
            $this->assertEquals('hello there', SearchHelper::normalize($testString));
        }
    }

//    public function testNormalizeWithDoctor()
//    {
//        
//        $testStrings = [
//            'DR. Hello There',
//            '   dr hello   there   ',
//            '   doctor HELLO  ,  THERE   ',
//            'dr. HELLO,THERE',
//            'dr. HELLO,THERE',
//        ];
//
//        foreach ($testStrings as $testString) {
//            $this->assertEquals('hello there', SearchHelper::normalize($testString));
//        }
//    }

    public function testStringHasTwoWords()
    {
        $testStrings = [
            'Hello There',
            '   hello   there   ',
            '   HELLO  ,  THERE   ',
            'HELLO,THERE',
        ];

        foreach ($testStrings as $testString) {
            $this->assertTrue(SearchHelper::hasTwoWords($testString));
        }
    }

//    public function testStringHasTwoWordsEvenWithDoctor()
//    {
//        $testStrings = [
//            'DR. Hello There',
//            '   dr hello   there   ',
//            '   doctor HELLO  ,  THERE   ',
//            'dr. HELLO,THERE',
//            'dr. HELLO,THERE',
//        ];
//
//        foreach ($testStrings as $testString) {
//            $this->assertTrue(SearchHelper::hasTwoWords($testString));
//        }
//    }

    public function testStringDoesNotHaveTwoWords()
    {
        $testStrings = [
            'Hello There how are you',
            '   x hello   there   ',
            '   HELLO  ,x  THERE   ',
            '   HELLO  x,  THERE   ',
            'HELLO,THERE,yo',
        ];

        foreach ($testStrings as $testString) {
            $this->assertFalse(SearchHelper::hasTwoWords($testString));
        }
    }

    public function testGetAsTwoWords()
    {
        $testStrings = [
            'Hello There',
            '   hello   there   ',
            '   HELLO  ,  THERE   ',
            'HELLO,THERE',
        ];
        

        foreach ($testStrings as $testString) {
            $array = SearchHelper::getAsTwoWordArray($testString);
            $this->assertEquals(count($array), 2);
            $this->assertEquals($array[0], 'hello');
            $this->assertEquals($array[1], 'there');
        }
    }

//    public function testGetAsTwoWordsWithDoctor()
//    {
//        $testStrings = [
//            'DR. Hello There',
//            '   dr hello   there   ',
//            '   doctor HELLO  ,  THERE   ',
//            'dr. HELLO,THERE',
//            '       Dr. HELLO,THERE',
//        ];
//
//        foreach ($testStrings as $testString) {
//            $array = SearchHelper::getAsTwoWordArray($testString);
//            $this->assertEquals(count($array), 2);
//            $this->assertEquals($array[0], 'hello');
//            $this->assertEquals($array[1], 'there');
//        }
//    }

    public function testReturnsFalseWhenGivingThreeWords()
    {
        $testStrings = [
            'Hello There how are you',
            '   x hello   there   ',
            '   HELLO  ,x  THERE   ',
            '   HELLO  x,  THERE   ',
            'HELLO,THERE,yo',
        ];

        foreach ($testStrings as $testString) {
            $this->assertFalse(SearchHelper::getAsTwoWordArray($testString));
        }
    }

}
