<?php

use Elit\DoctorHandler;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class TestDoctorHandle extends PHPUnit_Framework_TestCase
{

    public function testFindsMoreThanOneWord()
    {
        $strings = [
            'one two',
            ' one two',
            ' one two ',
            '3 two foo',
            '\3 two',
            '.3 two',
        ];
        foreach ($strings as $string) {
            $this->assertTrue(DoctorHandler::hasMoreThanOneWord($string));
        }
    }

    public function testDoesNotFindMoreThanOneWord()
    {
        $strings = [
            'onetwo',
            ' onetwo',
            ' ONETWO ',
            '3twofoo',
            '\3two',
            '       .3two   ',
        ];
        foreach ($strings as $string) {
            $this->assertFalse(DoctorHandler::hasMoreThanOneWord($string));
        }
    }

    public function testFindsDoctorInString()
    {
        $strings = [
            'dr smith',
            'dr. smith',
            'doctor smith',
            ' doctor smith',
            'Dr smith',
            'DR smith',
            'Dr. smith',
            'DR. smith',
            'Doctor smith',
            'DOCTOR smith',
        ];

        foreach ($strings as $string) {
            $this->assertTrue(DoctorHandler::hasDoctor($string));
        }
    }

    public function testDoenstFindDoctorInString()
    {
        $strings = [
            'smith',
            'drsmith',
            'doctorsmith',
            'DrSmith DrBanks',
            'droctor smith'
        ];

        foreach ($strings as $string) {
            $this->assertFalse(DoctorHandler::hasDoctor($string));
        }
    }


    public function testReturnsStringWithoutDoctor()
    {
        $strings = [
            'dr smith',
            'dr. smith',
            'doctor smith',
            ' doctor smith',
            'Dr smith',
            'DR smith',
            'Dr. smith',
            'DR. smith',
            'Doctor smith',
            'DOCTOR smith',
        ];

        foreach ($strings as $string) {
            $this->assertEquals(DoctorHandler::normalize($string), 'smith');
        }
    }
}
