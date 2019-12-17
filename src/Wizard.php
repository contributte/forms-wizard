<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Contributte\FormWizard\Session\WizardSessionSection;
use DateTime;
use Nette\Application\UI\Form;
use Nette\ComponentModel\Container;
use Nette\ComponentModel\IComponent;
use Nette\Forms;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Session;
use Nette\UnexpectedValueException;
use Nette\Utils\ArrayHash;
use ReflectionMethod;

class Wizard extends Container implements IWizard
{

	/** @var Session */
	private $session;

	/** @var WizardSessionSection|null */
	private $section;

	/** @var DateTime|string|int */
	protected $expiration = '+ 20 minutes';

	/** @var callable[] */
	public $onSuccess = [];

	/** @var IFormFactory */
	private $factory;

	/** @var bool */
	private $isSuccess = false;

	public function __construct(Session $session)
	{
		$this->session = $session;
	}

	public function setFactory(IFormFactory $factory): IWizard
	{
		$this->factory = $factory;

		return $this;
	}

	protected function finish(): void
	{
	}

	public function isSuccess(): bool
	{
		return $this->isSuccess;
	}

	protected function getSection(): WizardSessionSection
	{
		if (!$this->section) {
			$section = $this->session->getSection('wizard' . $this->getName())
				->setExpiration($this->expiration);

			$this->section = new WizardSessionSection($section);
		}

		return $this->section;
	}

	private function resetSection(): void
	{
		$this->getSection()->reset();
	}

	public function getCurrentStep(): int
	{
		return $this->getSection()->getCurrentStep() ?: 1;
	}

	/**
	 * @return mixed[]|ArrayHash
	 */
	public function getValues(bool $asArray = false)
	{
		$values = $this->getSection()->getValues();

		return $asArray ? $values : ArrayHash::from($values);
	}

	public function getLastStep(): int
	{
		return $this->getSection()->getLastStep() ?: 1;
	}

	public function setStep(int $step): IWizard
	{
		if ($this->getLastStep() >= $step && $step > 0 && $this->getComponent('step' . $step, false)) {
			$this->getSection()->setCurrentStep($step);
		}

		return $this;
	}

	protected function createForm(): Form
	{
		return $this->factory ? $this->factory->create() : new Form();
	}

	public function submitStep(SubmitButton $button): void
	{
		$form = $button->getForm();
		$submitName = $button->getName();
		$currentStep = $this->getCurrentStep();

		if ($submitName === self::PREV_SUBMIT_NAME) {
			$this->getSection()->setCurrentStep($currentStep - 1);

		} else {
			$this->getSection()->setStepValues($currentStep, $form->getValues('array'));

			if ($submitName === self::NEXT_SUBMIT_NAME && $form->isValid()) {
				$step = $currentStep + 1;
				$this->getSection()->setCurrentStep($step);
				$this->getSection()->setLastStep($step);

			} else {
				if ($submitName === self::FINISH_SUBMIT_NAME && $form->isValid() && $this->getSection()->getValues() !== null) {
					$this->isSuccess = true;
					$this->finish();
					foreach ($this->onSuccess as $callback) {
						$callback($this);
					}

					$this->resetSection();
				}
			}
		}
	}

	public function render(): void
	{
		$this->create()->render();
	}

	public function create(?string $step = null): Form
	{
		$step = (int) ($step ?? $this->getCurrentStep());
		/** @var Form $form */
		$form = $this->getComponent('step' . $step);
		$form->setValues((array) $this->getSection()->getStepValues($step));

		return $form;
	}

	protected function createComponent(string $name): ?IComponent
	{
		$ucname = ucfirst($name);
		$method = 'create' . $ucname;
		if ($ucname !== $name && method_exists($this, $method) && (new ReflectionMethod($this, $method))->getName() === $method) {
			$component = $this->$method($name);
			if (!$component instanceof Forms\Form && !isset($this->components[$name])) {
				throw new UnexpectedValueException(
					sprintf('Method %s::%s() did not return or create %s.', static::class, $method, Form::class)
				);
			}

			$this->applyCallbacksToButtons($component);

			return $component;
		}

		return null;
	}

	private function applyCallbacksToButtons(Forms\Form $form): void
	{
		/** @var SubmitButton $control */
		foreach ($form->getComponents(false, SubmitButton::class) as $control) {
			if (!in_array($control->getName(), [self::FINISH_SUBMIT_NAME, self::NEXT_SUBMIT_NAME, self::PREV_SUBMIT_NAME])) {
				continue;
			}

			$control->onClick[] = [$this, 'submitStep'];
			$control->onInvalidClick[] = [$this, 'submitStep'];
			if ($control->getName() === self::PREV_SUBMIT_NAME) {
				$control->setValidationScope([]);
			}
		}
	}

}
