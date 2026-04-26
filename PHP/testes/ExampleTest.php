<?php
use PHPUnit\Framework\TestCase;


class ExampleTest extends TestCase {
    public function test_Example1() {
        $this->assertTrue(2 === 2);
    }
    
    public function test_Example2() {
        $this->assertEquals(["name" => "John", "age" => 30], ["name" => "John", "age" => 30]);
    }

}