<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;

class DummyLatteFactory implements LatteFactory
{

	public function create(): Engine
	{
		return new Engine();
	}

}
