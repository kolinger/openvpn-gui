<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class PaymentRepository extends Repository
{

	const TABLE_NAME = 'payment';


	/**
	 * @param int $account
	 * @param int $state
	 * @param string $note
	 * @param string $date
	 * @return int
	 */
	public function create($account, $state, $note, $date)
	{
		$data = array(
			'account_id' => $account,
			'state' => $state,
			'date' => $date,
			'note' => $note,
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
	 * @param int $id
	 * @return Payment
	 */
	public function findOneByIdAndAccount($account, $id)
	{
		$row = $this->connection->table(static::TABLE_NAME)
			->where('id = ?', $id)
			->where('account_id = ?', $account)
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

		return $this->fillEntities('Payment', $rows);
	}


	/**
	 * @param int $id
	 * @param int $state
	 * @param \DateTime $date
	 * @param string $note
	 */
	public function save($id, $state, $date, $note)
	{
		$data = array(
			'state' => $state,
			'note' => $note,
			'date' => $date,
		);

		$this->connection->query('UPDATE ' . static::TABLE_NAME . ' SET ? WHERE id = ?', $data, $id);
	}


	/**
	 * @param int $id
	 */
	public function remove($id)
	{
		$this->connection->query('DELETE FROM  ' . static::TABLE_NAME . ' WHERE id = ?', $id);
	}
}