# Rozšiření pro WebChemistry\Forms\Form

## Instalace

**Composer**
```
composer require webchemistry/forms-wizard
```

**Config**

```yaml
extensions:
    - WebChemistry\Forms\Controls\DI\WizardExtension
```

## Komponenta

```php
class Wizard extends WebChemistry\Forms\Wizard\Component {

    protected function finish() {
        $values = $this->getValues();
    }

    protected function createStep1() {
        $form = $this->getForm();

        $form->addText('name', 'Uživatelské jméno')
            ->setRequired();

        $form->addSubmit(self::NEXT_SUBMIT_NAME, 'Další');

        return $form;
    }

    protected function createStep2() {
        $form = $this->getForm();

        $form->addText('email', 'Email')
            ->setRequired();

        $form->addSubmit(self::PREV_SUBMIT_NAME, 'Zpět');
        $form->addSubmit(self::FINISH_SUBMIT_NAME, 'Registrovat');

        return $form;
    }
}
```

## Šablona

```html
<div n:wizard="wizard">
    <ul n:if="!$wizard->isSuccess()">
        <li n:foreach="$wizard->steps as $step" n:class="$wizard->isDisabled($step) ? disabled, $wizard->isActive($step) ? active"><a n:tag-if="$wizard->useLink($step)" n:href="changeStep! $step">{$step}</a></li>
    </ul>

    {step 1}
        {control $form}
    {/step}

    {step 2}
        {control $form}
    {/step}

    {step success}
        Úspěšně jste se registroval/a.
    {/step}
</div>
```