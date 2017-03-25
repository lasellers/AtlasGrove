<?php
// tests/AppBundle/Util/CalculatorTest.php
namespace Tests\AppBundle;

use AppBundle;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    public function testTrue()
    {
        $this->assertEquals(1, 1);
    }
   /* public function testFalse()
    {
        $this->assertEquals(1, 0);
    }*/
}