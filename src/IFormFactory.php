<?php

declare(strict_types=1);

namespace WebChemistry\Forms\Controls\Wizard;

use Nette\Application\UI\Form;

interface IFormFactory {

	public function create(): Form;

}
