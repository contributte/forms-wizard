![](https://heatbadger.now.sh/github/readme/contributte/forms-wizard/)

<p align=center>
  <a href="https://github.com/contributte/forms-wizard/actions"><img src="https://badgen.net/github/checks/contributte/forms-wizard/master"></a>
  <a href="https://coveralls.io/r/contributte/forms-wizard"><img src="https://badgen.net/coveralls/c/github/contributte/forms-wizard"></a>
  <a href="https://packagist.org/packages/contributte/forms-wizard"><img src="https://badgen.net/packagist/dm/contributte/forms-wizard"></a>
  <a href="https://packagist.org/packages/contributte/forms-wizard"><img src="https://badgen.net/packagist/v/contributte/forms-wizard"></a>
</p>
<p align=center>
  <a href="https://packagist.org/packages/contributte/forms-wizard"><img src="https://badgen.net/packagist/php/contributte/forms-wizard"></a>
  <a href="https://github.com/contributte/forms-wizard"><img src="https://badgen.net/github/license/contributte/forms-wizard"></a>
  <a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
  <a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
  <a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

Multi-step form wizard for Nette applications. Build form flows with separate steps, navigation, default values, and conditional skipping.

## Versions

| State  | Version | Branch   | PHP     |
|--------|---------|----------|---------|
| dev    | `^4.1`  | `master` | `>=8.1` |
| stable | `^4.0`  | `master` | `>=8.1` |

## Installation

To install latest version of `contributte/forms-wizard` use [Composer](https://getcomposer.org).

```bash
composer require contributte/forms-wizard
```

## Usage

### Register Extension

```neon
extensions:
	- Contributte\FormWizard\DI\WizardExtension
```

### Component

```php
use Contributte\FormWizard\Wizard as BaseWizard;
use Nette\Application\UI\Form;

class Wizard extends BaseWizard
{
	private array $stepNames = [
		1 => 'Skip username',
		2 => 'Username',
		3 => 'Email',
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

		$this->setDefaultValues(2, function (Form $form, array $values): void {
			$form->setDefaults([
				'username' => 'john_doe',
			]);
		});
	}

	public function getStepData(int $step): array
	{
		return [
			'name' => $this->stepNames[$step],
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

### Presenter

```php
final class HomepagePresenter extends Nette\Application\UI\Presenter
{
	/** @var Wizard @inject */
	public $wizard;

	public function handleChangeStep(int $step): void
	{
		$this['wizard']->setStep($step);

		$this->redirect('wizard'); // Optional, hides parameter from URL.
	}

	protected function createComponentWizard(): Wizard
	{
		return $this->wizard;
	}
}
```

### Template

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

## Development

See [how to contribute](https://contributte.org) to this package. This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
    <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>
<a href="https://github.com/MartkCz">
    <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/10145362?v=3&s=80">
</a>

-----

Consider to [support](https://contributte.org/partners) **contributte** development team.
Also thank you for using this package.
