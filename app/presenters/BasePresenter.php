<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	public function beforeRender()
	{
		$this->template->title = 'OpenVPN GUI';
	}


	/**
	 * @return bool
	 */
	public function isPaymentsEnabled()
	{
		return $this->context->parameters['payments'];
	}

}