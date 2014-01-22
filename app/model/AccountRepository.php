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
	const CREATE_COMMAND = 'cd %s && source ./vars && export KEY_CN="%s" && export KEY_NAME="%s" && ./pkitool %s';
	const ZIP_COMMAND = 'cd %s && zip %s.zip ca.crt %s.crt %s.key';
	const CHECK_COMMAND = 'cd %s && cd %s && ls %s*';
	const REVOKE_COMMAND = 'cd %s && source ./vars && ./revoke-full %s';
	const CRL_COMMAND = 'cd %s && source ./vars && cd %s && export KEY_CN="%s" && export KEY_OU="VPN" && export KEY_NAME="%s" && $OPENSSL ca -gencrl -out "crl.pem" -config "$KEY_CONFIG"';

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
		$sql = 'SELECT a.*, (CASE WHEN free = 1 THEN 2 WHEN p.id IS NOT NULL THEN 1 ELSE 0 END) AS state
			FROM account a LEFT JOIN payment p ON a.id = account_id AND p.state = 0';

		if (count($filters)) {
			$sql .= ' WHERE';
		}
		$parameters = array();
		if (isset($filters['username'])) {
			$sql .= ' username LIKE \'%?%\'';
			$parameters[] = $filters['username'];
		}
		if (isset($filters['email'])) {
			if (isset($filters['username'])) {
				$sql .= ' AND';
			}
			$sql .= ' email LIKE \'%?%\'';
			$parameters[] = $filters['email'];
		}
		if (isset($filters['state']) && $filters['state'] != -1) {
			if (isset($filters['email'])) {
				$sql .= ' AND';
			}
			$sql .= ' state = ?';
			$parameters[] = $filters['state'];
		}
		if (isset($filters['active']) && $filters['active'] != -1) {
			if (isset($filters['state']) && $filters['state'] != -1) {
				$sql .= ' AND';
			}
			$sql .= ' active = ?';
			$parameters[] = $filters['active'];
		}

		$sql .= ' ORDER BY active DESC, createDate';

		$rows = $this->connection->queryArgs($sql, $parameters)->fetchAll();
		return $this->fillEntities('Account', $rows);
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
	 * @param string $note
	 * @throws PDOException
	 * @throws Nette\InvalidArgumentException
	 */
	public function create($free, $username, $email, $note)
	{
		$createDate = new \DateTime();
		$data = array(
			'free' => $free,
			'username' => $username,
			'email' => $email,
			'createDate' => $createDate->format('Y-m-d H:i:s'),
		);

		$output = $this->ssh->execute(sprintf(self::CHECK_COMMAND, $this->getConfig('rsaDir'), $this->getConfig('keysDir'), $username));
		if (strlen($output) != 0) {
			throw new \Nette\InvalidArgumentException('Certifikát se zvoleným uživatelským jménem již existuje');
		}

		try {
			$this->connection->query('INSERT INTO ' . static::TABLE_NAME, $data);
			$this->ssh->execute(sprintf(self::CREATE_COMMAND, $this->getConfig('rsaDir'), $username, $username, $username));
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
	 * @param string $note
	 */
	public function save($id, $free, $email, $note)
	{
		$data = array(
			'free' => $free,
			'email' => $email,
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
		$tempDir .= '/certificates/' . \Nette\Utils\Strings::random();
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
		$this->ssh->execute(sprintf(static::CRL_COMMAND, $this->getConfig('rsaDir'), $this->getConfig('keysDir'), $account->getUsername(), $account->getUsername()));

		// update database
		$this->connection->query('UPDATE ' . static::TABLE_NAME . ' SET ? WHERE id = ?', array('active' => TRUE), $account->getId());
	}


	/************************ helpers ************************/


	/**
	 * @param string $name
	 * @return mixed
	 */
	private function getConfig($name)
	{
		return isset($this->container->parameters[$name]) ? $this->container->parameters[$name] : NULL;
	}
}