<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Nette\Application\UI\Form;

interface IFormFactory
{

	public function create(): Form;

}
