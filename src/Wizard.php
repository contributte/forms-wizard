<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Closure;
use Contributte\FormWizard\Session\WizardSessionSection;
use Contributte\FormWizard\Steps\StepCounter;
use InvalidArgumentException;
use LogicException;
use Nette\Application\UI\Component;
use Nette\Application\UI\Form as UIForm;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Http\Session;
use Nette\UnexpectedValueException;
use Nette\Utils\ArrayHash;
use ReflectionMethod;

class Wizard extends Component implements IWizard
{

	/** @var callable[] */
	public array $onSuccess = [];

	protected ?string $expiration = '+ 20 minutes';

	private Session $session;

	private ?WizardSessionSection $section = null;

	private ?StepCounter $stepCounter = null;

	private ?IFormFactory $factory = null;

	private bool $isSuccess = false;

	private ?Presenter $presenter = null;

	private bool $startupCalled = false;

	/** @var mixed[] */
	private array $stepsConditions = [];

	/** @var mixed[] */
	private array $defaultValuesCallbacks = [];

	public function __construct(Session $session)
	{
		$this->session = $session;
	}

	public function setFactory(IFormFactory $factory): IWizard
	{
		$this->factory = $factory;

		return $this;
	}

	public function isSuccess(): bool
	{
		return $this->isSuccess;
	}

	public function getStepCounter(): StepCounter
	{
		$counter = 1;
		if ($this->stepCounter === null) {
			/** @phpstan-ignore-next-line $counter */
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
	 * @return array<mixed>|ArrayHash<mixed>
	 */
	public function getValues(bool $asArray = false): array|ArrayHash
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

	public function submitStep(SubmitButton $button): void
	{
		$form = $button->getForm();

		if ($form === null) {
			return;
		}

		$submitName = $button->getName();
		$step = $this->extractStepFromName($form->getName());

		if ($step === null || $step !== $this->getCurrentStep()) {
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
		$this->stepCounter = null;
	}

	public function create(?string $step = null): UIForm
	{
		$step = (int) ($step ?? $this->getCurrentStep());
		/** @var UIForm $form */
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
	 * @return static
	 */
	public function addComponent(IComponent $component, ?string $name, ?string $insertBefore = null): static
	{
		if ($this->extractStepFromName($name) !== null) {
			if (!$component instanceof Form) {
				throw new UnexpectedValueException(
					sprintf('Component %s must be instance of %s, %s given', (string) $name, Form::class, $component::class)
				);
			}

			$this->applyCallbacksToButtons($component);
		}

		return parent::addComponent($component, $name, $insertBefore);
	}

	public function getPresenter(): ?Presenter
	{
		if ($this->presenter === null) {
			$this->presenter = parent::getPresenter();
		}

		return $this->presenter;
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
		// No-op
	}

	protected function finish(): void
	{
		// No-op
	}

	protected function getSection(): WizardSessionSection
	{
		if ($this->section === null) {
			$section = $this->session->getSection('wizard' . $this->getName())
				->setExpiration($this->expiration);

			$this->section = new WizardSessionSection($section);
		}

		return $this->section;
	}

	protected function createForm(): UIForm
	{
		return $this->factory !== null ? $this->factory->create() : new UIForm();
	}

	protected function extractStepFromName(?string $name): ?int
	{
		if ($name === null || preg_match('#^step(\d+)$#', $name, $matches) === false) {
			return null;
		}

		return (int) $matches[1];
	}

	protected function createComponent(string $name): ?IComponent
	{
		if (preg_match('#^step\d+$#', $name) > 0) {
			$ucname = ucfirst($name);
			$method = 'create' . $ucname;
			if ($ucname !== $name && method_exists($this, $method) && (new ReflectionMethod($this, $method))->getName() === $method) {
				$callable = [$this, $method];
				assert(is_callable($callable));
				$callableMethod = Closure::fromCallable($callable);
				$component = $callableMethod($name);

				if (!($component instanceof IComponent) && $this->getComponent($name, false) === null) {
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

	protected function validateParent(IContainer $parent): void
	{
		if (!$this->startupCalled) {
			$this->startupCalled = true;

			$this->startup();
		}
	}

	private function applyCallbacksToButtons(Form $form): void
	{
		/** @var SubmitButton $control */
		foreach ($form->getComponents(false, SubmitButton::class) as $control) {
			if (!in_array($control->getName(), [self::FINISH_SUBMIT_NAME, self::NEXT_SUBMIT_NAME, self::PREV_SUBMIT_NAME], true)) {
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
