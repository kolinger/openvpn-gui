<?php

use Nette\Application\Routers\Route;



/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @var bool
	 */
	private $useHttps;


	/**
	 * @param bool $useHttps
	 */
	public function __construct($useHttps)
	{
		$this->useHttps = $useHttps;
	}

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new \Nette\Application\Routers\RouteList();

		$flags = 0;
		if ($this->useHttps) {
			$flags = Route::SECURED;
		}
		
		$router[] = new Route('login', 'User:login', $flags);
		$router[] = new Route('logout', 'User:logout', $flags);
		$router[] = new Route('create', 'Account:create', $flags);
		$router[] = new Route('view/<id>', 'Account:view', $flags);
		$router[] = new Route('<presenter>[/<action>][/<id>]', 'Account:default', $flags);

		return $router;
	}

}