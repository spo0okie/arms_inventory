<?php

namespace app\tests\unit;

use Codeception\Test\Unit;

class EchoTest extends Unit
{
	public function testEcho()
	{
		codecept_debug("Этот текст должен появиться\n");
		$this->assertTrue(true);
	}
}