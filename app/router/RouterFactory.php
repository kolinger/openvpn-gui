<?php

use Nette\Application\Routers\Route;



/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new \Nette\Application\Routers\RouteList();

		$router[] = new Route('login', 'User:login');
		$router[] = new Route('logout', 'User:logout');
		$router[] = new Route('create', 'Account:create');
		$router[] = new Route('view/<id>', 'Account:view');
		$router[] = new Route('<presenter>[/<action>][/<id>]', 'Account:default');

		return $router;
	}

}