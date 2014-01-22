<?php
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;


/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class PaymentPresenter extends BasePresenter
{

	/**
	 * @var int
	 * @persistent
	 */
	public $id;

	/**
	 * @var AccountRepository
	 */
	private $accountRepository;

	/**
	 * @var PaymentRepository
	 */
	private $paymentRepository;


	/**
	 * @param AccountRepository $accountRepository
	 */
	public function injectAccountRepository(AccountRepository $accountRepository)
	{
		$this->accountRepository = $accountRepository;
	}


	/**
	 * @param PaymentRepository $paymentRepository
	 */
	public function injectPaymentRepository(PaymentRepository $paymentRepository)
	{
		$this->paymentRepository = $paymentRepository;
	}


	/************************ list ************************/


	/**
	 * @param int $id
	 * @throws Nette\Application\BadRequestException
	 */
	public function renderDefault($id)
	{
		$account = $this->accountRepository->findOneById($id);
		if (!$account) {
			throw new BadRequestException();
		}

		$this->template->title = 'Platby účtu ' . $account->getUsername();
		$this->template->account = $account;
		$this->template->payments = $this->paymentRepository->findByAccount($id);

		$payment = (int) $this->getParameter('payment');
		if ($payment) {
			$payment = $this->paymentRepository->findOneByIdAndAccount($id, $payment);
			if (!$payment) {
				throw new BadRequestException();
			}
			$this['form']->setDefaults(array(
				'date' => $payment->getDate(),
				'note' => $payment->getNote(),
				'state' => $payment->getState(),
				'payment' => $payment->getId(),
				'account' => $id,
			));
			$this->template->dialogTitle = 'Editace platby ze dne ' . $payment->getDate();
			$this->invalidateControl('dialog');
		} else {
			$this['form']->setDefaults(array(
				'account' => $id,
			));
			$this->template->dialogTitle = 'Přidání nové platby';
		}
	}


	/************************ form ************************/


	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentForm()
	{
		$form = new Form;

		$form->addText('date', 'Období')
			->setRequired();

		$form->addSelect('state', 'Stav')
			->setItems(array(
				0 => 'nezaplaceno',
				2 => 'zaplaceno',
			));

		$form->addTextArea('note', 'Poznámka');

		$form->addHidden('account');
		$form->addHidden('payment');

		$form->addSubmit('save', 'Uložit');

		$presenter = $this;
		$paymentRepository = $this->paymentRepository;
		$form->onSuccess[] = function (Form $form) use ($presenter, $paymentRepository) {
			$values = $form->values;
			$account = $values->account;
			$payment = (int) $values->payment;

			if ($payment) {
				$payment = $paymentRepository->findOneByIdAndAccount($account, $payment);
				if (!$payment) {
					throw new BadRequestException();
				}
				$paymentRepository->save($payment->getId(), $values->state, $values->date, $values->note);
			} else {
				$paymentRepository->create($account, $values->state, $values->note, $values->date);
			}

			$presenter->flashMessage('Platba byla uložena', 'success');
			$presenter->redirect('default');
		};

		return $form;
	}


	/************************ delete ************************/

	/**
	 * @param int $id
	 * @param int $payment
	 * @throws Nette\Application\BadRequestException
	 */
	public function actionDelete($id, $payment)
	{
		$payment = $this->paymentRepository->findOneByIdAndAccount($id, $payment);
		if (!$payment) {
			throw new BadRequestException();
		}

		$this->paymentRepository->remove($payment->getId());
		$this->flashMessage('Platba ze dne ' . $payment->getDate() . ' byla smzána', 'success');
		$this->redirect('default', $id);
	}
}