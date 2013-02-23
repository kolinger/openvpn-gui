<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class PaymentRepository extends Repository
{

	const TABLE_NAME = 'payment';
	const BREAKS_TABLE_NAME = 'payment_breaks';



	/**
	 * @param int $account
	 * @param int $year
	 * @param int $month
	 * @return int
	 */
	public function create($account, $year, $month)
	{
		$data = array(
			'account_id' => $account,
			'state' => 0,
			'date' => strtotime($year . '-' . $month . '-01'),
		);
		$this->connection->query('INSERT INTO ' . static::TABLE_NAME, $data);
		return $this->connection->lastInsertId();
	}



	/**
	 * @param int $id
	 * @return Payment
	 */
	public function findOneById($id)
	{
		$row = $this->connection->table(static::TABLE_NAME)
			->where('id = ?', $id)
			->fetch();

		return $this->fillEntity('Payment', $row);
	}



	/**
	 * @param int $account
	 * @return array
	 */
	public function findByAccount($account)
	{
		$rows = $this->connection->table(static::TABLE_NAME)
			->where('account_id = ?', $account)
			->order('date');

		$entities = $this->fillEntities('Payment', $rows);
		$payments = array();
		foreach ($entities as $entity) {
			$index = $entity->getDate()->format('Y-n');
			$payments[$index] = $entity;
		}
		return $payments;
	}



	/**
	 * @param string $account
	 * @return array
	 */
	public function findBreaksByAccount($account)
	{
		$rows = $this->connection->table(static::BREAKS_TABLE_NAME)
			->where('account_id = ?', $account);

		$entities = $this->fillEntities('PaymentBreak', $rows);
		$breaks = array();
		foreach ($entities as $entity) {
			$index = $entity->getStart()->format('Y-n');
			$breaks[$index] = $entity;
		}
		return $breaks;
	}



	/**
	 * @param int $id
	 * @param int $state
	 * @param string $note
	 */
	public function save($id, $state, $note)
	{
		$data = array(
			'state' => $state,
			'note' => $note,
		);

		$this->connection->query('UPDATE ' . static::TABLE_NAME . ' SET ? WHERE id = ?', $data, $id);
	}



	/**
	 * @param int $account
	 * @param \DateTime $date
	 * @throws Nette\InvalidStateException
	 */
	public function createBreak($account, \DateTime $date)
	{
		$lastBreak = $this->connection->table(static::BREAKS_TABLE_NAME)
			->order('end DESC')
			->limit(1)
			->fetch();

		if ($lastBreak) {
			$lastBreakDate = \Nette\DateTime::from($lastBreak->end);
			if ($lastBreakDate->getTimestamp() > $date->getTimestamp()) {
				throw new \Nette\InvalidStateException('Zvolené datum koliduje s jinou pauzou v platbách, zajdete datum po ' . $lastBreakDate->format('j. n. Y'));
			}
		}

		$this->connection->query('INSERT INTO ' . static::BREAKS_TABLE_NAME, array(
			'account_id' => $account,
			'start' => $date,
		));
	}



	/**
	 * @param int $account
	 * @param \DateTime $date
	 * @throws Nette\InvalidStateException
	 */
	public function cancelBreak($account, \DateTime $date)
	{
		$activeBreak = $this->connection->table(static::BREAKS_TABLE_NAME)
			->order('start DESC')
			->limit(1)
			->fetch();

		$startDate = \Nette\DateTime::from($activeBreak->start);
		if ($startDate->getTimestamp() > $date->getTimestamp()) {
			throw new \Nette\InvalidStateException('Nelze obnovit platbu dříve, než byla ukončena');
		}

		$this->connection->query('UPDATE ' . static::BREAKS_TABLE_NAME . ' SET ? WHERE account_id = ?', array('end' => $date), $account);
	}




}