<?php

class MockLatteFactory implements \Nette\Bridges\ApplicationLatte\ILatteFactory {

	public function create() {
		return new \Latte\Engine();
	}

}

