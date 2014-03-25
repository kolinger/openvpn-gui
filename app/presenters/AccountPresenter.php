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

		$form->addText('email');

		$form->addSelect('active')
			->setItems(array(
				-1 => 'vše',
				1 => 'aktivní',
				0 => 'deaktivováno',
			));

		/*$form->addSelect('state')
			->setItems(array(
				-1 => 'vše',
				Account::STATE_OK => 'v pořádku',
				Account::STATE_UNPAID => 'nezaplaceno',
				Account::STATE_FREE => 'zdaramo',
			));*/

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

			if (trim($values->email) != '') {
				$filters['email'] = trim($values->email);
			}

			/*if (in_array($values->state, array(-1, Account::STATE_OK, Account::STATE_UNPAID, Account::STATE_FREE))) {
				$filters['state'] = $values->state;
			}*/

			if (in_array($values->active, array(-1, FALSE, TRUE))) {
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

		$form->addText('email', 'E-mail');

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
					$accountRepository->save($id, $values->free, $values->email, $values->note);
					$presenter->flashMessage('Účet byl upraven', 'success');
					$presenter->redirect('default');
				} else {
					$accountRepository->create($values->free, $values->username, $values->email, $values->note);
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
			'username' => $account->getUsername(),
			'email' => $account->getEmail(),
			'note' => $account->getNote(),
			'free' => $account->getFree(),
		));
	}


	/************************ configurations ************************/


	/**
	 * @param int $id
	 */
	public function renderConfigurations($id)
	{
		$account = $this->accountRepository->findOneById($id);
		$this->template->title = 'Konfigurace účtu ' . $account->getUsername();
		$configurations = array();
		foreach ($this->context->parameters['configurations'] as $name => $file) {
			$configurations[] = $name;
		}
		$this->template->configurations = $configurations;
		$this->template->account = $account;
	}


	/************************ download ************************/


	/**
	 * @param int $id
	 * @param string $type
	 */
	public function actionDownload($id, $type)
	{
		$account = $this->accountRepository->findOneById($id);
		$zip = $this->configuration->downloadAndZipFiles($account, $type);

		$response = new \Nette\Application\Responses\FileResponse($zip, $account->getUsername() . '.zip');
		$response->send($this->httpRequest, $this->httpResponse);
		unlink($zip);

		$this->terminate();
	}


	/************************ email ************************/


	/**
	 * @param int $id
	 * @param string $type
	 */
	public function actionEmail($id, $type)
	{
		$account = $this->accountRepository->findOneById($id);
		$zip = $this->configuration->downloadAndZipFiles($account, $type);
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
		$this->redirect('configurations', array($id));
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
}