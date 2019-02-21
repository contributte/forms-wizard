<?php

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\ILatteFactory;

class MockLatteFactory implements ILatteFactory
{

	public function create(): Engine
	{
		return new Engine();
	}

}

