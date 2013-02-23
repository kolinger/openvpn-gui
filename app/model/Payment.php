<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Payment extends \Nette\Object
{

	const STATE_UNPAID = 0;
	const STATE_WAITING = 1;
	const STATE_OK = 2;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var int
	 */
	private $state;

	/**
	 * @var \DateTime
	 */
	private $date;

	/**
	 * @var string
	 */
	private $note;



	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}



	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}



	/**
	 * @return int
	 */
	public function getState()
	{
		return $this->state;
	}



	/**
	 * @param int $state
	 */
	public function setState($state)
	{
		$this->state = $state;
	}



	/**
	 * @return \DateTime
	 */
	public function getDate()
	{
		return $this->date;
	}



	/**
	 * @param string $date
	 */
	public function setDate($date)
	{
		$this->date = \Nette\DateTime::from($date);
	}



	/**
	 * @return string
	 */
	public function getNote()
	{
		return $this->note;
	}



	/**
	 * @param string $note
	 */
	public function setNote($note)
	{
		$this->note = $note;
	}

}