<?php

use Nette\Application\UI\Form;



/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class UserPresenter extends BasePresenter
{

	/**
	 * @param object $element
	 */
	public function checkRequirements($element)
	{
		if ($this->action == 'login' && $this->user->isLoggedIn()) {
			$this->redirect('Account:');
		}
	}



	public function renderLogin()
	{
		$this->template->title = 'Přihlášení';
	}



	/**
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentForm()
	{
		$form = new Form;
		$form->addProtection('Platnost stránky vypršela, aktualizujte stránku a opakujte akci');

		$form->addPassword('password', 'Heslo')
			->setRequired('Musíte vyplnit heslo');

		$form->addSubmit('login', 'Přihlásit');

		$presenter = $this;
		$form->onSuccess[] = function (Form $form) use ($presenter) {
			try {
				$presenter->user->login($form->values->password);
				$presenter->flashMessage('Přohlášení proběhlo úspěšně', 'success');
				$presenter->redirect('Account:');
			} catch (\Nette\Security\AuthenticationException $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}



	public function actionLogout()
	{
		$this->user->logout();
		$this->flashMessage('Odhlášení proběhlo úspěšně', 'success');
		$this->redirect('User:login');
	}

}