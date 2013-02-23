<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Authenticator extends \Nette\Object implements \Nette\Security\IAuthenticator
{

	/**
	 * @var string
	 */
	private $password;



	/**
	 * @param Nette\DI\Container $container
	 */
	public function __construct(\Nette\DI\Container $container)
	{
		$this->password = $container->parameters['password'];
	}



	/**
	 * @param array $credentials
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		$password = $credentials[0];

		if ($password !== $this->password) {
			throw new \Nette\Security\AuthenticationException('Špatné heslo', self::INVALID_CREDENTIAL);
		}

		return new \Nette\Security\Identity(0);
	}

}