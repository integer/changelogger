<?php

use Nette\Application\UI\Presenter;

/**
 * Sign presenter.
 */
class SignPresenter extends Presenter
{

	public function actionIn($backlink)
	{
	}

	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage("Byl jste odhlášen");
		$this->redirect("Changelog:default");
	}

	public function createComponentSignForm()
	{
		$form = new Nette\Application\UI\Form;

		$form->addText("username", "Login: ")
			->setRequired();
		$form->addPassword("password", "Heslo: ")
			->setRequired();
		$form->addPassword("password2", "heslo pro kontrolu")
			->setRequired()
			->addRule($form::EQUAL, "Hesla se neshoduji", $form["password"]);
		$form->addSubmit("send", "Registrace");
		$form->onSuccess[] = callback($this, "signFormSubmitted");
		return $form;
	}

	public function signFormSubmitted($form)
	{
		if($form->isSuccess())
		{
			$values = $form->getValues();
			//$this->context->userModel->add($values->username, $values->password); /** @todo */
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Changelog:default');
		}
	}

	public function createComponentLoginForm()
	{
		$form = new Nette\Application\UI\Form;

		$form->addText("username", "Login: ")
			->setRequired();
		$form->addPassword("password", "Heslo: ")
			->setRequired();

		$form->addSubmit("send", "Přihlas mě!");
		$form->onSuccess[] = callback($this, "loginFormSubmitted");
		return $form;
	}

	public function loginFormSubmitted(Nette\Application\UI\Form $form)
	{
		if($form->isSuccess())
		{
			$values = $form->getValues();
			try
			{
				$this->getUser()->login($values->username, $values->password);
				$this->flashMessage("Přihlásil jsi se jako " . $values->username);
				//$this->restore($this->getParamemetr['backlink']);  // in this tool is backlink useless
				$this->redirect('Changelog:default');
			}
			catch(\Nette\Security\AuthenticationException $e)
			{
				$this->flashMessage($e->getMessage(), "error");
			}
		}
	}

}

