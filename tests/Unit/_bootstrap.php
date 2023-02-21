<?php

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;

class MockLatteFactory implements LatteFactory
{

	public function create(): Engine
	{
		return new Engine();
	}

}

