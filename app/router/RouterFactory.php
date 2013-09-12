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

		$router[] = new Route('login', 'User:login', Route::SECURED);
		$router[] = new Route('logout', 'User:logout', Route::SECURED);
		$router[] = new Route('create', 'Account:create', Route::SECURED);
		$router[] = new Route('view/<id>', 'Account:view', Route::SECURED);
		$router[] = new Route('<presenter>[/<action>][/<id>]', 'Account:default', Route::SECURED);

		return $router;
	}

}