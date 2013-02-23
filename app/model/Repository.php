<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Repository extends \Nette\Object
{

	/**
	 * @var \Nette\Database\Connection
	 */
	protected  $connection;



	/**
	 * @param Nette\Database\Connection $connection
	 */
	public function __construct(\Nette\Database\Connection $connection)
	{
		$this->connection = $connection;
	}



	/**
	 * @param string $entity
	 * @param Nette\Database\Table\ActiveRow $row
	 * @return object
	 */
	protected function fillEntity($entity, \Nette\Database\Table\ActiveRow $row)
	{
		$entity = new $entity;
		foreach ($row as $key => $value) {
			if (strpos($key, '_id') !== FALSE) {
				continue;
			}
			$entity->{'set' . ucfirst($key)}($value);
		}
		return $entity;
	}



	/**
	 * @param string $entity
	 * @param array $rows
	 * @return array
	 */
	protected function fillEntities($entity, $rows)
	{
		$array = array();
		foreach ($rows as $row) {
			$array[] = $this->fillEntity($entity, $row);
		}
		return $array;
	}

}