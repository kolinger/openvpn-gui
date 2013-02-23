<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Account extends \Nette\Object
{

	const STATE_OK = 0;
	const STATE_ENDED = 1;
	const STATE_WAITING = 2;
	const STATE_UNPAID = 3;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var bool
	 */
	private $active;

	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var string
	 */
	private $note;

	/**
	 * @var \DateTime
	 */
	private $createDate;

	/**
	 * @var bool
	 */
	private $free;

	/**
	 * @var int
	 */
	private $state;



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
	 * @return bool
	 */
	public function getActive()
	{
		return $this->active;
	}



	/**
	 * @param bool $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
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
	 * @return string
	 */
	public function getUsername()
	{
		return $this->username;
	}



	/**
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}



	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}



	/**
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
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



	/**
	 * @return \DateTime
	 */
	public function getCreateDate()
	{
		return $this->createDate;
	}



	/**
	 * @param \DateTime $createDate
	 */
	public function setCreateDate($createDate)
	{
		$this->createDate = \Nette\DateTime::from($createDate);
	}



	/**
	 * @return bool
	 */
	public function getFree()
	{
		return $this->free;
	}



	/**
	 * @param bool $free
	 */
	public function setFree($free)
	{
		$this->free = $free;
	}

}