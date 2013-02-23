<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class AccountRepository extends Repository
{

	const TABLE_NAME = 'account';

	/**
	 * Commands
	 */
	const CREATE_COMMAND = 'cd %s && source ./vars && ./pkitool %s';
	const ZIP_COMMAND = 'cd %s && zip %s.zip ca.crt %s.crt %s.key';
	const CHECK_COMMAND = 'cd %s && cd %s && ls %s*';
	const REVOKE_COMMAND = 'cd %s && source ./vars && ./revoke-full %s';
	const CRL_COMMAND = 'cd %s && source ./vars && cd %s && export KEY_CN="" && export KEY_OU="" && export KEY_NAME="" && $OPENSSL ca -gencrl -out "crl.pem" -config "$KEY_CONFIG"';

	/**
	 * @var SSH
	 */
	private $ssh;

	/**
	 * @var Nette\DI\Container
	 */
	private $container;

	/**
	 * @var PaymentRepository
	 */
	private $paymentRepository;



	/**
	 * @param SSH $ssh
	 * @param Nette\Database\Connection $connection
	 * @param Nette\DI\Container $container
	 * @param PaymentRepository $paymentRepository
	 */
	public function __construct(SSH $ssh, \Nette\Database\Connection $connection, \Nette\DI\Container $container, PaymentRepository $paymentRepository)
	{
		parent::__construct($connection);
		$this->ssh = $ssh;
		$this->container = $container;
		$this->paymentRepository = $paymentRepository;
	}



	/**
	 * @param array $filters
	 * @return Account[]
	 */
	public function findAll(array $filters = array())
	{
		$rows = $this->connection->table(static::TABLE_NAME)
			->order('active DESC, createDate');

		foreach ($filters as $key => $value) {
			if ($key === 'state' || $key === 'active') {
				$rows->where($key . ' = ?', $value);
			} else {
				$rows->where($key . ' LIKE ?', '%' . $value . '%');
			}
		}

		$accounts = $this->fillEntities('Account', $rows);

		foreach ($accounts as $account) {
			if ($account->getFree()) {
				$account->setState(Account::STATE_OK);
			} else {
				$unpaid = FALSE;
				$waiting = FALSE;

				$payments = $this->paymentRepository->findByAccount($account->getId());
				$breaks = $this->paymentRepository->findBreaksByAccount($account->getId());
				for ($year = $account->getCreateDate()->format('Y'); $year <= date('Y'); $year++) {
					if ($year == date('Y')) {
						$end = date('n');
						$start = 1;
					} else if ($year == $account->getCreateDate()->format('Y')) {
						$start = $account->getCreateDate()->format('j') > 20 ? $account->getCreateDate()->format('n') + 1 : $account->getCreateDate()->format('n');
						$end = 12;
					} else {
						$start = 1;
						$end = 12;
					}
					for ($month = $start; $month <= $end; $month++) {
						$break = isset($break) && $break !== NULL && ($break->getEnd()->getTimestamp() == time() || $break->getEnd()->getTimestamp() > strtotime(date($year . '-' . $month . '-01'))) ? $break : (isset($breaks[$year . '-' . $month]) ? $breaks[$year . '-' . $month] : NULL);
						if (isset($payments[$year . '-' . $month])) {
							if ($payments[$year . '-' . $month]->getState() == Payment::STATE_WAITING) {
								$waiting = TRUE;
							} else if ($payments[$year . '-' . $month]->getState() == Payment::STATE_UNPAID) {
								if (!$break) {
									$unpaid = TRUE;
								}
							}
						} else {
							if (!$break) {
								$unpaid = TRUE;
							}
						}
					}
				}



				if ($unpaid) {
					$account->setState(Account::STATE_UNPAID);
				} else if ($waiting) {
					$account->setState(Account::STATE_WAITING);
				} else {
					$account->setState(Account::STATE_OK);
				}
			}
		}

		return $accounts;
	}



	/**
	 * @param int $id
	 * @return Account
	 */
	public function findOneById($id)
	{
		$row = $this->connection->table(static::TABLE_NAME)
			->where('id = ?', $id)->fetch();

		return $this->fillEntity('Account', $row);
	}



	/**
	 * @param bool $free
	 * @param string $username
	 * @param string $email
	 * @param string $name
	 * @param string $note
	 * @throws PDOException
	 * @throws Nette\InvalidArgumentException
	 */
	public function create($free, $username, $email, $name, $note)
	{
		$createDate = new \DateTime();
		$data = array(
			'active' => TRUE,
			'free' => $free,
			'username' => $username,
			'email' => $email,
			'name' => $name,
			'note' => $note,
			'createDate' => $createDate->format('Y-m-d H:i:s'),
		);

		$output = $this->ssh->execute(sprintf(self::CHECK_COMMAND, $this->getConfig('rsaDir'), $this->getConfig('keysDir'), $username));
		if (strlen($output) != 0) {
			throw new \Nette\InvalidArgumentException('Certifikát se zvoleným uživatelským jménem již existuje');
		}

		try {
			$this->connection->query('INSERT INTO ' . static::TABLE_NAME, $data);
			$this->ssh->execute(sprintf(self::CREATE_COMMAND, $this->getConfig('rsaDir'), $username));
		} catch (\PDOException $e) {
			if (strpos($e->getMessage(), 'column username is not unique')) {
				throw new \Nette\InvalidArgumentException('Uživatelské jméno je již obsazené');
			} else {
				throw $e;
			}
		}
	}



	/**
	 * @param int $id
	 * @param bool $free
	 * @param string $email
	 * @param string $name
	 * @param string $note
	 */
	public function save($id, $free, $email, $name, $note)
	{
		$data = array(
			'free' => $free,
			'email' => $email,
			'name' => $name,
			'note' => $note,
		);

		$this->connection->query('UPDATE ' . static::TABLE_NAME . ' SET ? WHERE id = ?', $data, $id);
	}



	/**
	 * @param Account $account
	 */
	public function deactivate(Account $account)
	{
		$this->ssh->execute(sprintf(static::REVOKE_COMMAND, $this->getConfig('rsaDir'), $account->getUsername()));
		$this->connection->query('UPDATE ' . static::TABLE_NAME . ' SET ? WHERE id = ?', array('active' => FALSE), $account->getId());
	}



	/**
	 * @param Account $account
	 */
	public function activate(Account $account)
	{
		$remoteDir = $this->getConfig('rsaDir') . '/' . $this->getConfig('keysDir');
		$tempDir = $this->getConfig('tempDir');
		if (!is_dir($tempDir . '/certificates')) {
			mkdir($tempDir . '/certificates');
		}
		$tempDir .=  '/certificates/' . \Nette\Utils\Strings::random();
		mkdir($tempDir);

		// index.txt
		$this->ssh->download($remoteDir . '/index.txt', $tempDir . '/index.txt');
		$index = file_get_contents($tempDir . '/index.txt');
		$rows = explode("\n", $index);
		foreach ($rows as $key => $row) {
			if (preg_match('/CN=' . $account->getUsername() . '/', $row)) {
				dump($row);
				$parts = explode("\t", $row);
				$parts[0] = 'V';
				$parts[2] = '';
				$rows[$key] = implode("\t", $parts);
			}
		}
		file_put_contents($tempDir . '/index.txt', trim(implode("\n", $rows)));
		$this->ssh->rm($remoteDir . '/index.txt');
		$this->ssh->upload($tempDir . '/index.txt', $remoteDir . '/index.txt', 0600);

		// cleanup
		unlink($tempDir . '/index.txt');
		rmdir($tempDir);

		// re-generate CRL
		$this->ssh->execute(sprintf(static::CRL_COMMAND, $this->getConfig('rsaDir'), $this->getConfig('keysDir')));

		// update database
		$this->connection->query('UPDATE ' . static::TABLE_NAME . ' SET ? WHERE id = ?', array('active' => TRUE), $account->getId());
	}



	/************************ helpers ************************/



	/**
	 * @param string $name
	 * @return mixed
	 */
	private function getConfig($name) {
		return isset($this->container->parameters[$name]) ? $this->container->parameters[$name] : NULL;
	}

}