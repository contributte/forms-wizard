# Form wizard for nette/forms
[![Build Status](https://travis-ci.org/WebChemistry/Forms-Wizard.svg?branch=master)](https://travis-ci.org/WebChemistry/Forms-Wizard)

## Installation

**Composer**
```
composer require webchemistry/forms-wizard
```

**Config**

```yaml
extensions:
    - WebChemistry\Forms\Controls\DI\WizardExtension ## Autoregistration of macros
```

# Usage

## Component

```php
class Wizard extends WebChemistry\Forms\Wizard\Component {

    protected function finish() {
        $values = $this->getValues();
    }

    protected function createStep1() {
        $form = $this->getForm();

        $form->addText('name', 'User name')
            ->setRequired();

        $form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

        return $form;
    }

    protected function createStep2() {
        $form = $this->getForm();

        $form->addText('email', 'Email')
            ->setRequired();

        $form->addSubmit(self::PREV_SUBMIT_NAME, 'Back');
        $form->addSubmit(self::FINISH_SUBMIT_NAME, 'Register');

        return $form;
    }
}
```

```yaml
services:
    - Wizard
```

## Presenter

```php

class HomepagePresenter extends Nette\Application\UI\Presenter {

    /** @var Wizard */
    private $wizard;

    public function __construct(Wizard $wizard) {
        $this->wizard = $wizard;
    }

    protected function createComponentWizard() {
        return $this->wizard;
    }

}

```

## Template

```html
<div n:wizard="wizard">
    <ul n:if="!$wizard->isSuccess()">
        <li n:foreach="$wizard->steps as $step" n:class="$wizard->isDisabled($step) ? disabled, $wizard->isActive($step) ? active">
            <a n:tag-if="$wizard->useLink($step)" n:href="changeStep! $step">{$step}</a>
        </li>
    </ul>

    {step 1}
        {control $form}
    {/step}

    {step 2}
        {control $form}
    {/step}

    {step success}
        Registration was successful
    {/step}
</div>
```
