# Contributte\Form-wizzard

## Content

- [Usage - how use it](#usage)
	- [Register extension](#register-extension)
	- [Component](#component)
	- [Presenter](#presenter)
	- [Template](#template)


## Usage

### Register extension

```neon
extensions:
	- Contributte\FormWizard\DI\WizardExtension
```

## Component

```php

use Nette\Application\UI\Form;

class Wizard extends Contributte\FormWizard\Wizard {

	private array $stepNames = [
	    1 => "Skip username",
	    2 => "Username",
	    3 => "Email",
	];

	protected function finish(): void
	{
		$values = $this->getValues();
	}

	protected function startup(): void
	{
		$this->skipStepIf(2, function (array $values): bool {
			return isset($values[1]) && $values[1]['skip'] === true;
		});
	}

	public function getStepData(int $step): array
	{
		return [
		    'name' => $this->stepNames[$step]
		];
	}

	protected function createStep1(): Form
	{
		$form = $this->createForm();

		$form->addCheckbox('skip', 'Skip username');

		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep2(): Form
	{
		$form = $this->createForm();

		$form->addText('username', 'Username')
			->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Back');
		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep3(): Form
	{
		$form = $this->createForm();

		$form->addText('email', 'Email')
			->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Back');
		$form->addSubmit(self::FINISH_SUBMIT_NAME, 'Register');

		return $form;
	}
}
```

```neon
services:
	- Wizard
```

## Presenter

```php

final class HomepagePresenter extends Nette\Application\UI\Presenter {

	/** @var Wizard @inject */
	public $wizard;
	
	public function handleChangeStep(int $step): void 
	{
		$this['wizard']->setStep($step);
		
		$this->redirect('wizard'); // Optional, hides parameter from URL
	}

	protected function createComponentWizard(): Wizard 
	{
		return $this->wizard;
	}

}

```

## Template

```latte
<div n:wizard="wizard">
    <ul n:if="!$wizard->isSuccess()">
        <li n:foreach="$wizard->steps as $step" n:class="$wizard->isDisabled($step) ? disabled, $wizard->isActive($step) ? active">
            <a n:tag-if="$wizard->useLink($step)" n:href="changeStep! $step">{$step} - {$wizard->getStepData($step)['name']}</a>
        </li>
    </ul>

    {step 1}
        {control $form}
    {/step}

    {step 2}
        {control $form}
    {/step}

    {step 3}
        {control $form}
    {/step}

    {step success}
        Registration was successful
    {/step}
</div>
```
