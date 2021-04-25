<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use DateTime;
use Nette\Application\UI\Form;
use Nette\ComponentModel\Container;
use Nette\ComponentModel\IComponent;
use Nette\Forms;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\UnexpectedValueException;
use Nette\Utils\ArrayHash;
use ReflectionMethod;

class Wizard extends Container implements IWizard
{

	private const CURRENT_STEP = 'currentStep';

	private const VALUES = 'values';

	private const LAST_STEP = 'lastStep';

	/** @var Session */
	private $session;

	/** @var DateTime|string|int */
	protected $expiration = '+ 20 minutes';

	/** @var callable[] */
	public $onSuccess = [];

	/** @var IFormFactory */
	private $factory;

	/** @var bool */
	private $isSuccess = false;

	/** @var mixed[] */
	private $finalValues = [];

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

	protected function getSection(): SessionSection
	{
		return $this->session->getSection('wizard' . $this->getName())->setExpiration($this->expiration);
	}

	private function resetSection(): void
	{
		$this->finalValues = $this->getValues(true);

		$this->getSection()->remove();
	}

	public function getCurrentStep(): int
	{
		return $this->getSection()[self::CURRENT_STEP] ?: 1;
	}

	/**
	 * @return mixed[]|ArrayHash
	 */
	public function getValues(bool $asArray = false)
	{
		$values = $this->finalValues;
		if (!$this->finalValues) {
			$values = (array) $this->getSection()[self::VALUES];
		}

		return $asArray ? $values : ArrayHash::from($values);
	}

	public function getLastStep(): int
	{
		return $this->getSection()[self::LAST_STEP] ?: 1;
	}

	public function setStep(int $step): IWizard
	{
		if ($this->getLastStep() >= $step && $step > 0 && $this->getComponent('step' . $step, false)) {
			$this->getSection()[self::CURRENT_STEP] = $step;
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

		if ($submitName === self::PREV_SUBMIT_NAME) {
			$currentStep = $this->getCurrentStep();
			$this->getSection()[self::CURRENT_STEP] = $currentStep - 1;
		} else {
			if ($submitName === self::NEXT_SUBMIT_NAME && $form->isValid()) {
				$this->merge($form->getValues(true));
				$this->getSection()[self::LAST_STEP] = $this->getSection()[self::CURRENT_STEP] = $this->getCurrentStep() + 1;
			} else {
				if ($submitName === self::FINISH_SUBMIT_NAME && $form->isValid() && $this->getSection()[self::VALUES] !== null) {
					$this->merge($form->getValues(true));

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

	/**
	 * @param mixed[] $array
	 */
	private function merge(array $array): void
	{
		$this->getSection()[self::VALUES] = array_merge((array) $this->getSection()[self::VALUES], $array);
	}

	public function render(): void
	{
		$this->create()->render();
	}

	public function create(?string $step = null): Form
	{
		/** @var Form $form */
		$form = $this->getComponent('step' . ($step ?? $this->getCurrentStep()));
		$form->setValues($this->getValues(true));

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
