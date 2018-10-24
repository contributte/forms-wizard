<?php declare(strict_types=1);

namespace WebChemistry\Forms\Controls\Wizard;

use Nette\Application\UI\Form;

interface IFormFactory {

	/**
	 * @return Form
	 */
	public function create(): Form;

}
