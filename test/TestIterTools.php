<?php

require_once(implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), "..", "src", "IterTools.php")));

class IterToolsTest extends PHPUnit_Framework_TestCase {
    public function testMapTypeChecks() {
        try {
            // wrong input
            $itertest = IterTools\map("ucfirst", null);
            $this->assertTrue(false, 'The input is neither array nor Traversable but the error was not raised.');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $itertest = IterTools\map(null, null);
            $this->assertTrue(false, 'The callable is not callable but the error was not raised. #1');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $itertest = IterTools\map('thisissurenotafunction', null);
            $this->assertTrue(false, 'The callable is not callable but the error was not raised. #2');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $itertest = IterTools\map("ucfirst", array('A', 'B', 'C'));
            $this->assertTrue(true, 'Cannot happen.');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(false, 'map chokes on valid input.');
        }


        try {
            $itertest = IterTools\map(function($x) { return $x;}, array('A', 'B', 'C'));
            $this->assertTrue(true, 'Cannot happen.');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(false, 'map chokes on valid input.');
        }

    }

    public function testFilterTypeChecks() {
        try {
            // wrong input
            $itertest = IterTools\filter("is_null", null);
            $this->assertTrue(false, 'The input is neither array nor Traversable but the error was not raised.');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $itertest = IterTools\filter(null, null);
            $this->assertTrue(false, 'The callable is not callable but the error was not raised. #1');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $itertest = IterTools\filter('thisissurenotafunction', null);
            $this->assertTrue(false, 'The callable is not callable but the error was not raised. #2');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

    public function testReduceTypeChecks() {
        try {
            // wrong input
            $itertest = IterTools\reduce("is_null", null);
            $this->assertTrue(false, 'The input is neither array nor Traversable but the error was not raised.');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $itertest = IterTools\reduce(null, null);
            $this->assertTrue(false, 'The callable is not callable but the error was not raised. #1');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $itertest = IterTools\reduce('thisissurenotafunction', null);
            $this->assertTrue(false, 'The callable is not callable but the error was not raised. #2');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

    public function testMapReturnsIterWrapper() {
        $iter = IterTools\map(function($x) { return strrev($x); }, array('abcde', 'fghij'));
        $this->assertInstanceOf('IterTools\\IterWrapper', $iter);
    }

    public function testMapWorks() {
        $iter = IterTools\map(function($x) { return strrev($x); }, array('abcde', 'fghij'));
        $result = array();
        foreach($iter as $elem) {
            $result[] = $elem;
        }
        $this->assertEquals(2, count($result));
        $this->assertEquals('edcba', $result[0]);
        $this->assertEquals('jihgf', $result[1]);
        $iter = IterTools\map(function($x) { return strtoupper($x); }, IterTools\map(function($x) { return strrev($x); }, array('abcde', 'fghij')));
        $result = array();
        foreach($iter as $elem) {
            $result[] = $elem;
        }
        $this->assertEquals(2, count($result));
        $this->assertEquals('EDCBA', $result[0]);
        $this->assertEquals('JIHGF', $result[1]);
    }

    public function testFilterWorks() {
        $iter = IterTools\filter(function ($x) {return preg_match('/a/i', $x);}, array('Omaha', 'Arkansas', 'New York', 'Wyoming', 'Mexico', 'Ancoridge') );
        $ia = array_values($iter->toArray());
        $this->assertEquals(3, count($ia));
        $this->assertEquals('Omaha', $ia[0]);
        $this->assertEquals('Arkansas', $ia[1]);
        $this->assertEquals('Ancoridge', $ia[2]);
    }

    public function testKeysArePreserved() {
        $iter = IterTools\filter(function ($x) {return preg_match('/a/i', $x);}, array('Omaha', 'Arkansas', 'New York', 'Wyoming', 'Mexico', 'Ancoridge') );
        $ia = array_keys($iter->toArray());
        $this->assertEquals(3, count($ia));
        $this->assertEquals(0, $ia[0]);
        $this->assertEquals(1, $ia[1]);
        $this->assertEquals(5, $ia[2]);
        $iter = IterTools\map(function($x) { return strtoupper($x); }, IterTools\map(function($x) { return strrev($x); }, array('input' => 'abcde', 'output' => 'fghij')));
        $ia = array_keys($iter->toArray());
        $this->assertEquals(2, count($ia));
        $this->assertEquals('input', $ia[0]);
        $this->assertEquals('output', $ia[1]);
    }

    public function testReduce() {
        $result = IterTools\reduce(function($x, $y) {return $x * $y;}, IterTools\map(function ($x) {return $x + 1; }, array(0, 1, 2, 3)), 1);
        $this->assertEquals(24, $result);
        $result = IterTools\reduce(function($x, $y) {return $x * $y;}, array(1, 2, 3, 4), 1);
        $this->assertEquals(24, $result);
    }

    public function testMerge() {
        $result = IterTools\merge(
            array(1, 2, 3, 4),
            IterTools\map('strtolower', array('A', 'C', 'I', 'D')),
            new \ArrayIterator(array('foo', 'bar'))
        )->toArray();
        $this->assertEquals(array('1', '2', '3', '4', 'a', 'c', 'i', 'd', 'foo', 'bar'), $result);
    }

    public function testFlip() {
        $a = array('key1' => 'value1', 'key2' => 'value2');
        $i = IterTools\flip($a)->toArray();
        $this->assertEquals(array('value1' => 'key1', 'value2' => 'key2'), $i);
    }

    public function testKeys() {
        $a = array('key1' => 'value1', 'key2' => 'value2');
        $i = IterTools\keys($a)->toArray();
        $this->assertEquals(array_keys($a), $i);
    }

    public function testValues() {
        $a = array('key1' => 'value1', 'key2' => 'value2');
        $i = IterTools\values($a)->toArray();
        $this->assertEquals(array_values($a), $i);
    }
}

