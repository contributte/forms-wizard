<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Contributte\FormWizard\Session\WizardSessionSection;
use Contributte\FormWizard\Steps\StepCounter;
use InvalidArgumentException;
use LogicException;
use Nette\Application\UI\Component;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\ComponentModel\IContainer;
use Nette\Forms;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Session;
use Nette\UnexpectedValueException;
use Nette\Utils\ArrayHash;
use ReflectionMethod;

class Wizard extends Component implements IWizard
{

	/** @var Session */
	private $session;

	/** @var WizardSessionSection|null */
	private $section;

	/** @var StepCounter|null */
	private $stepCounter;

	/** @var string|null */
	protected $expiration = '+ 20 minutes';

	/** @var callable[] */
	public $onSuccess = [];

	/** @var IFormFactory|null */
	private $factory;

	/** @var bool */
	private $isSuccess = false;

	/** @var Presenter|null */
	private $presenter;

	/** @var bool */
	private $startupCalled = false;

	/** @var mixed[] */
	private $stepsConditions = [];

	/** @var mixed[] */
	private $defaultValuesCallbacks = [];

	public function __construct(Session $session)
	{
		$this->session = $session;
	}

	public function setFactory(IFormFactory $factory): IWizard
	{
		$this->factory = $factory;

		return $this;
	}

	protected function skipStepIf(int $step, callable $callback): void
	{
		if ($step < 1) {
			throw new InvalidArgumentException(sprintf('Step must be greater than 0, %d given', $step));
		}

		if ($step === 1) {
			throw new InvalidArgumentException('Cannot skip first step');
		}

		if ($step === $this->getTotalSteps()) {
			throw new InvalidArgumentException('Cannot skip last step');
		}

		$this->stepsConditions[$step][] = $callback;
	}

	protected function setDefaultValues(int $step, callable $defaultValuesCallback): void
	{
		if ($step < 1) {
			throw new InvalidArgumentException(sprintf('Step must be greater than 0, %d given', $step));
		}

		$this->defaultValuesCallbacks[$step][] = $defaultValuesCallback;
	}

	protected function startup(): void
	{
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

	public function getStepCounter(): StepCounter
	{
		if (!$this->stepCounter) {
			for ($counter = 1; $counter < 1000; $counter++) {
				if (!method_exists($this, 'createStep' . $counter) && !$this->getComponent('step' . $counter, false)) {
					$counter--;
					break;
				}
			}

			if ($counter < 1) {
				throw new LogicException('Wizard must have at least 1 step');
			}

			$this->stepCounter = new StepCounter($this->getSection(), $counter);
		}

		return $this->stepCounter;
	}

	public function getCurrentStep(): int
	{
		return $this->getStepCounter()->getCurrentStep();
	}

	public function isStepSkipped(int $step): bool
	{
		$values = $this->getRawValues();
		foreach ($this->stepsConditions[$step] ?? [] as $callback) {
			if ($callback($values)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool[]
	 */
	public function getSteps(): array
	{
		$steps = [];

		for ($step = 1; $step <= $this->getStepCounter()->getTotalSteps(); $step++) {
			$steps[$step] = !$this->isStepSkipped($step);
		}

		return $steps;
	}

	/**
	 * @return mixed[]
	 */
	public function getStepData(int $step): array
	{
		return [];
	}

	public function getTotalSteps(): int
	{
		return $this->getStepCounter()->getTotalSteps();
	}

	/**
	 * @return mixed[]|ArrayHash<string|int,mixed>
	 */
	public function getValues(bool $asArray = false)
	{
		$values = [];
		foreach ($this->getRawValues() as $step => $value) {
			if ($this->isStepSkipped($step)) {
				continue;
			}

			$values = array_merge($values, $value);
		}

		return $asArray ? $values : ArrayHash::from($values);
	}

	/**
	 * @return mixed[]
	 */
	public function getRawValues(): array
	{
		return (array) $this->getSection()->getValues();
	}

	public function getLastStep(): int
	{
		return $this->getStepCounter()->getLastStep();
	}

	public function setStep(int $step): IWizard
	{
		if (!$this->isStepSkipped($step)) {
			$this->getStepCounter()->setCurrentStep($step);
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

		if ($form === null) {
			return;
		}

		$submitName = $button->getName();
		$step = $this->extractStepFromName($form->getName());

		if (!$step || $step !== $this->getCurrentStep()) {
			return;
		}

		if ($submitName === self::PREV_SUBMIT_NAME) {
			do {
				$this->getStepCounter()->previousStep();
			} while ($this->isStepSkipped($this->getCurrentStep()));
		} elseif ($form->isValid()) {
			/* @phpstan-ignore-next-line $form->getValues('array') always returns an array */
			$this->getSection()->setStepValues($this->getCurrentStep(), $form->getValues('array'));

			if ($submitName === self::NEXT_SUBMIT_NAME) {
				do {
					$this->getStepCounter()->nextStep();
				} while ($this->isStepSkipped($this->getCurrentStep()));
			} elseif ($submitName === self::FINISH_SUBMIT_NAME && $this->getStepCounter()->canFinish()) {
				$this->isSuccess = true;
				$this->finish();
				foreach ($this->onSuccess as $callback) {
					$callback($this);
				}

				$this->getSection()->reset();
			}
		}
	}

	public function render(): void
	{
		$this->create()->render();
	}

	public function reset(): void
	{
		$this->session->destroy();
		$this->section = null;
	}

	public function create(?string $step = null): Form
	{
		$step = (int) ($step ?? $this->getCurrentStep());
		/** @var Form $form */
		$form = $this->getComponent('step' . $step);

		// Set default values via callbacks
		$values = $this->getRawValues();
		foreach ($this->defaultValuesCallbacks[$step] ?? [] as $callback) {
			$callback($form, $values);
		}

		// Set submited values
		$form->setValues((array) $this->getSection()->getStepValues($step));

		return $form;
	}

	/**
	 * @param string|null $name
	 */
	protected function extractStepFromName($name): ?int
	{
		if ($name === null || !preg_match('#^step(\d+)$#', $name, $matches)) {
			return null;
		}

		return (int) $matches[1];
	}

	/**
	 * @return static
	 */
	public function addComponent(IComponent $component, ?string $name, ?string $insertBefore = null)
	{
		if ($this->extractStepFromName($name) !== null) {
			if (!$component instanceof Forms\Form) {
				throw new UnexpectedValueException(
					sprintf('Component %s must be instance of %s, %s given', (string) $name, Forms\Form::class, get_class($component))
				);
			}

			$this->applyCallbacksToButtons($component);
		}

		return parent::addComponent($component, $name, $insertBefore);
	}

	protected function createComponent(string $name): ?IComponent
	{
		if (preg_match('#^step\d+$#', $name)) {
			$ucname = ucfirst($name);
			$method = 'create' . $ucname;
			if ($ucname !== $name && method_exists($this, $method) && (new ReflectionMethod($this, $method))->getName() === $method) {
				$component = $this->$method($name);
				if (!$component instanceof IComponent && $this->getComponent($name) === null) {
					throw new UnexpectedValueException(
						sprintf('Method %s::%s() did not return or create the desired component.', static::class, $method)
					);
				}

				return $component;
			}

			return null;
		}

		return parent::createComponent($name);
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

	/**
	 * @return ?Presenter
	 */
	public function getPresenter(): ?Presenter
	{
		if (!$this->presenter) {
			$this->presenter = parent::getPresenter();
		}

		return $this->presenter;
	}

	protected function validateParent(IContainer $parent): void
	{
		if (!$this->startupCalled) {
			$this->startupCalled = true;

			$this->startup();
		}
	}

}
