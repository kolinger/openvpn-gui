<?php

use Nette\Application\UI\Form;



/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class AccountPresenter extends BasePresenter
{

	/**
	 * @var array
	 * @persistent
	 */
	public $filters = array(
		'active' => 1
	);

	/**
	 * @var AccountRepository
	 */
	private $accountRepository;

	/**
	 * @var PaymentRepository
	 */
	private $paymentRepository;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var \Nette\Http\IRequest
	 */
	private $httpRequest;

	/**
	 * @var \Nette\Http\IResponse
	 */
	private $httpResponse;



	/**
	 * @param AccountRepository $accountRepository
	 */
	public function injectAccountRepository(AccountRepository $accountRepository)
	{
		$this->accountRepository = $accountRepository;
	}



	/**
	 * @param Configuration $configuration
	 */
	public function injectConfiguration(Configuration $configuration)
	{
		$this->configuration = $configuration;
	}



	/**
	 * @param PaymentRepository $paymentRepository
	 */
	public function injectPaymentRepository(PaymentRepository $paymentRepository)
	{
		$this->paymentRepository = $paymentRepository;
	}



	/**
	 * @param Nette\Http\IRequest $httpRequest
	 */
	public function injectHttpRequest(Nette\Http\IRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}



	/**
	 * @param Nette\Http\IResponse $httpResponse
	 */
	public function injectHttpResponse(Nette\Http\IResponse $httpResponse)
	{
		$this->httpResponse = $httpResponse;
	}



	/**
	 * @param object $element
	 */
	public function checkRequirements($element)
	{
		if (!$this->user->isLoggedIn()) {
			$this->flashMessage('Pro zobrazení stránky se musíte přihlásit', 'error');
			$this->redirect('User:login');
		}
	}



	/************************ list ************************/



	public function renderDefault()
	{
		$this->template->title = 'Přehled';
		$this->template->accounts = $this->accountRepository->findAll($this->filters);
		$active = 0;
		$nonFree = 0;
		foreach ($this->template->accounts as $account) {
			if ($account->getActive()) {
				$active++;
				if (!$account->getFree()) {
					$nonFree++;
				}
			}
		}
		$this->template->active = $active;
		$this->template->nonFree = $nonFree;

		$this['filterForm']->setDefaults($this->filters);
	}



	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentFilterForm()
	{
		$form = new Form;

		$form->addText('username');

		$form->addText('name');

		$form->addText('email');

		$form->addSelect('active')
			->setItems(array(
				-1 => 'vše',
				1 => 'aktivní',
				0 => 'deaktivováno',
			));

		$form->addSelect('state')
			->setItems(array(
				-1 => 'vše',
				Account::STATE_OK => 'v pořádku',
				Account::STATE_ENDED => 'ukončeno',
				Account::STATE_WAITING => 'dohodnuto',
				Account::STATE_UNPAID => 'nezaplaceno',
			));

		$form->addSubmit('filter', 'Filtrovat');

		$form->addSubmit('reset', 'Resetovat');

		$presenter = $this;
		$form->onSuccess[] = function (Form $form) use ($presenter) {
			if ($form['reset']->isSubmittedBy()) {
				$presenter->filters = array();
				$presenter->redirect('this');
				return;
			}

			$values = $form->values;
			$filters = array();

			if (trim($values->username) != '') {
				$filters['username'] = trim($values->username);
			}

			if (trim($values->name) != '') {
				$filters['name'] = trim($values->name);
			}

			if (trim($values->email) != '') {
				$filters['email'] = trim($values->email);
			}

			if (in_array($values->state, array(0, 1, 2, 3))) { // TODO: refactor
				$filters['state'] = $values->state;
			}

			if (in_array($values->active, array(0, 1))) {
				$filters['active'] = $values->active;
			}

			$presenter->filters = $filters;
			$presenter->redirect('this');
		};

		return $form;
	}



	/************************ create ************************/



	public function renderCreate()
	{
		$this->template->title = 'Vytvoření klienta';
	}



	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentForm()
	{
		$form = new Form;

		$username = $form->addText('username', 'Uživ. jméno');
		if (!$this->getParameter('id')) {
			$username->setRequired('Musíte vyplnit uživ. jméno')
				->addRule(Form::PATTERN, 'Uživ. jméno smí obsahovat jen malé znaky anglické abecedy, čísla a podrtržítko', '[a-z0-9\_]*');
		} else {
			$username->setDisabled();
		}

		$form->addText('name', 'Jméno')
			->setRequired('Musíte vyplnit jméno');

		$form->addText('email', 'E-mail')
			->setRequired('Musíte vyplnit e-mail');

		$form->addCheckbox('free', 'Zdarma');

		$form->addTextArea('note', 'Poznámka');

		$form->addSubmit('save', 'Uložit');

		$presenter = $this;
		$accountRepository = $this->accountRepository;
		$form->onSuccess[] = function (Form $form) use ($presenter, $accountRepository) {
			$values = $form->values;
			try {
				$id = (int) $presenter->getParameter('id');
				if ($id) {
					$accountRepository->save($id, $values->free, $values->email, $values->name, $values->note); // TODO state!
					$presenter->flashMessage('Účet byl upraven', 'success');
					$presenter->redirect('this');
				} else {
					$accountRepository->create($values->free, $values->username, $values->email, $values->name, $values->note);
					$presenter->flashMessage('Účet byl vytvořen', 'success');
					$presenter->redirect('default');
				}
			} catch (\Nette\InvalidArgumentException $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}



	/************************ edit ************************/



	/**
	 * @param int $id
	 */
	public function renderEdit($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$this->template->title = 'Úprava účtu ' . $account->getUsername();
		$this['form']->setDefaults(array(
			'name' => $account->getName(),
			'email' => $account->getEmail(),
			'note' => $account->getNote(),
			'free' => $account->getFree(),
		));
	}



	/************************ payments ************************/



	/**
	 * @param int $id
	 */
	public function renderPayments($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$this->template->title = 'Platby účtu ' . $account->getUsername();
		$this->template->account = $account;
		$this->template->payments = $this->paymentRepository->findByAccount($id);
		$this->template->breaks = $this->paymentRepository->findBreaksByAccount($id);
		$this->template->lastBreak = end($this->template->breaks);

		if ($this->isAjax()) {
			$this->invalidateControl('flashes');
		}

		$this['form']['username']->setValue($account->getUsername());
		$this['breakStartForm']['account']->setValue($account->getId());
		$this['breakEndForm']['account']->setValue($account->getId());
	}



	/**
	 * @param array $data
	 * @param int $account
	 */
	public function handlePaymentInfo(array $data, $account)
	{
		if (count($data) == 2) { // payment not exists - create it
			$account = $this->accountRepository->findOneById($account);
			if (strtotime($data[0] . '-' . $data[1] . '-01') > $account->getCreateDate()->getTimestamp()) {
				$data[0] = $this->paymentRepository->create($account->getId(), $data[0], $data[1]);
			} else {
				$data[0] = FALSE;
			}
		}

		if (!$data[0]) {
			$this->flashMessage('Vybraný měsíc je před vytvořením samotného certifikátu - nelze platit dobu, kdy certifikát neexistoval', 'warning');
			if (!$this->isAjax()) {
				$this->redirect('this');
			}
			return;
		}

		$payment = $this->paymentRepository->findOneById($data[0]);

		$this->template->payment = $payment;
		$this->template->dialog = TRUE;

		$this['paymentForm']->setDefaults(array(
			'payment' => $payment->getId(),
			'note' => $payment->getNote(),
			'state' => $payment->getState(),
		));

		if ($this->isAjax()) {
			$this->invalidateControl('dialog');
		}
	}



	public function handleClosePaymentInfo()
	{
		if ($this->isAjax()) {
			$this->invalidateControl('dialog');
		} else {
			$this->redirect('this');
		}
	}



	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentPaymentForm()
	{
		$form = new Form;

		$form->addSelect('state', 'Stav')
			->setItems(array(
				0 => 'nezaplaceno',
				1 => 'čeká se',
				2 => 'zaplaceno',
			));

		$form->addTextArea('note', 'Poznámka');

		$form->addSubmit('save', 'Uložit');

		$form->addHidden('payment');

		$presenter = $this;
		$paymentRepository = $this->paymentRepository;
		$form->onSuccess[] = function (Form $form) use ($presenter, $paymentRepository) {
			$values = $form->values;

			$paymentRepository->save($values->payment, $values->state, $values->note);

			$presenter->flashMessage('Platba byla uložena');
			if ($presenter->isAjax()) {
				$presenter->invalidateControl('table');
				$presenter->invalidateControl('dialog');
			} else {
				$presenter->redirect('this');
			}
		};

		return $form;
	}



	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentBreakStartForm()
	{
		$form = new Form;

		$form->addText('date');

		$form->addSubmit('send', 'Pozastavit platby');

		$form->addHidden('account');

		$presenter = $this;
		$paymentRepository = $this->paymentRepository;
		$form->onSuccess[] = function (Form $form) use ($presenter, $paymentRepository) {
			$values = $form->values;
			$values['date'] = $presenter->convertCzechDateToDateTime($values->date);
			try {
				$paymentRepository->createBreak($values->account, $values->date);
				$presenter->flashMessage('Platba byla pozastavena', 'success');
				$presenter->redirect('this');
			} catch (\Nette\InvalidStateException $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}



	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentBreakEndForm()
	{
		$form = new Form;

		$form->addText('date');

		$form->addSubmit('send', 'Obnovit platby');

		$form->addHidden('account');

		$presenter = $this;
		$paymentRepository = $this->paymentRepository;
		$form->onSuccess[] = function (Form $form) use ($presenter, $paymentRepository) {
			$values = $form->values;
			$values['date'] = $presenter->convertCzechDateToDateTime($values->date);
			try {
				$paymentRepository->cancelBreak($values->account, $values->date);
				$presenter->flashMessage('Platba byla pozastavena', 'success');
				$presenter->redirect('this');
			} catch (\Nette\InvalidStateException $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}



	/************************ download ************************/



	/**
	 * @param int $id
	 */
	public function actionDownload($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$zip = $this->configuration->downloadAndZipFiles($account);

		$response = new \Nette\Application\Responses\FileResponse($zip, $account->getUsername() . '.zip');
		$response->send($this->httpRequest, $this->httpResponse);
		unlink($zip);
		
		$this->terminate();
	}



	/************************ email ************************/


	/**
	 * @param int $id
	 */
	public function actionEmail($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$zip = $this->configuration->downloadAndZipFiles($account);
		$config = $this->context->parameters['email'];

		$template = $this->createTemplate();
		$template->setFile(__DIR__ . '/../templates/email.latte');

		$message = new \Nette\Mail\Message();
		$message->setFrom($config['from']);
		$message->setSubject($config['subject']);
		$message->addTo($account->getEmail());
		$message->setBody($template);
		$message->addAttachment($account->getUsername() . '.zip', file_get_contents($zip));
		$message->send();

		unlink($zip);

		$this->flashMessage('E-mail byl odeslán', 'success');
		$this->redirect('default');
	}



	/************************ deactivate ************************/



	/**
	 * @param int $id
	 */
	public function actionDeactivate($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$this->accountRepository->deactivate($account);
		$this->flashMessage('Účel byl deaktivován', 'success');
		$this->redirect('default');
	}



	/************************ activate ************************/



	/**
	 * @param int $id
	 */
	public function actionActivate($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$this->accountRepository->activate($account);
		$this->flashMessage('Účel byl aktivován', 'success');
		$this->redirect('default');
	}



	/************************ helpers ************************/



	/**
	 * @param Account $account
	 * @param Payment|NULL $payment
	 * @param $year
	 * @param $month
	 * @param PaymentBreak|NULL $break
	 * @return string
	 */
	public function formatMonthClass($account, $payment, $year, $month, $break)
	{
		if ($break !== NULL) {
			return 'normal';
		}
		if ($payment === NULL) {
			if (strtotime($year . '-' . $month . '-01') < $account->getCreateDate()->getTimestamp()) { // before
				return 'normal';
			} else { // payment missing - unpaid
				if (strtotime($year . '-' . $month . '-01') > time()) { // future
					return 'normal';
				}
				return 'bad';
			}
		} else {
			if ($payment->getState() == Payment::STATE_OK) { // already payed
				return 'ok';
			} else if ($payment->getState() == Payment::STATE_WAITING) { // on way?
				return 'warning';
			} else { // unpaid
				if (strtotime($year . '-' . $month . '-01') > time()) { // future
					return 'normal';
				}
				return 'bad';
			}
		}
	}



	/**
	 * @param int $month
	 */
	public function getMonthName($month)
	{
		$months = array(
			1=> 'leden',
			'únor',
			'březen',
			'duben',
			'květen',
			'červen',
			'červenec',
			'srpen',
			'září',
			'říjen',
			'listopad',
			'prosinec'
		);
		return $months[$month];
	}



	/**
	 * @param string $string
	 * @return Nette\DateTime
	 */
	public function convertCzechDateToDateTime($string)
	{
		$parts = explode('. ', $string);
		return \Nette\DateTime::from($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
	}

}