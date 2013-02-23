<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class PaymentBreak extends \Nette\Object
{

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var \DateTime
	 */
	private $start;

	/**
	 * @var \DateTime
	 */
	private $end;



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
	 * @return \DateTime
	 */
	public function getStart()
	{
		return $this->start;
	}



	/**
	 * @param string $date
	 */
	public function setStart($date)
	{
		$this->start = \Nette\DateTime::from($date);
	}



	/**
	 * @return \DateTime
	 */
	public function getEnd()
	{
		return $this->end;
	}



	/**
	 * @param string $date
	 */
	public function setEnd($date)
	{
		$this->end = \Nette\DateTime::from($date);
	}

}