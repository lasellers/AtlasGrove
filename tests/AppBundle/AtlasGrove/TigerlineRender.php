<?php
// tests/AppBundle/Util/CalculatorTest.php
namespace Tests\AppBundle;

use AppBundle\AtlasGrove;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
	$container=null;
	public function __construct()
	{

	}

    public function testGetFont()
    {
    	$tigerline=new TigerlineRender($container);
		$font=$tigerline->getFont();

        $this->assert(strlen($font)>0);
    }
   /* public function testFalse()
    {
        $this->assertEquals(1, 0);
    }*/
}