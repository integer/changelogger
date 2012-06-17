<?php

use Nette\Application\UI\Presenter;

/**
 * Changelog presenter.
 */
class ChangelogPresenter extends BasePresenter
{
	/** @var Changelog */
	private $changelogModel;
	
	public function startup()
	{
		parent::startup();
		
		// for short name and IDE hinting
		$this->changelogModel = $this->models->changelog;  
	}
	
	public function renderDefault()
	{
		$this->template->projects = $this->changelogModel->getProjects();
	}

	public function renderDetail($projectId)
	{
		$this->template->project = $this->changelogModel->getProject($projectId);
		$this->template->assignUsers = $this->changelogModel->getChangelogRecepients($projectId);
	}
	
	public function renderHistory($projectId)
	{
		$this->template->project = $this->changelogModel->getProject($projectId);
		$this->template->projectHistory = $this->changelogModel->getChangelogHistory($projectId);
	}
	
	public function createComponentNewUserProject()
	{
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Sign:in', $this->storeRequest());
		}
		
		$projectId = $this->getParameter("projectId");
		if(is_null($projectId))
		{
			throw new Exception("No projectId param");
		}

		$form = new \Nette\Application\UI\Form;
		$form->addSelect("userId", "Přidej uživatele", $this->changelogModel->getAvailableUsers($projectId), 1)
			->setPrompt("-== vyberte ze seznamu ==-")
			->setRequired("Musíte vybrat koho přiřadit");
		
		$form->addHidden("projectId", $projectId);
		$form->addSubmit("sent", "odesli");
		$form->onSuccess[] = callback($this, "newUserProjectSubmitted");
		return $form;
	}
	
	public function newUserProjectSubmitted(Nette\Application\UI\Form $form)
	{
		if($form->isSuccess())
		{
			$values = $form->getValues();
			$this->changelogModel->newUserProject($values->userId, $values->projectId);
			$this->redirect("Changelog:detail", array($values->projectId));
		}
	}
	
	public function createComponentUpdateChangelog($name)
	{
		if(!$this->getUser()->isLoggedIn())
		{
			$this->redirect('Sign:in', $this->storeRequest());  // ulozi pozadavek do session
		}
		
		$projectId = $this->getParameter("projectId");
		if(is_null($projectId))
		{
			throw new Exception("No projectId param");
		}
		
		
		$form = new \Nette\Application\UI\Form;
		$form->addTextArea("newRows", "Nové řádky: ", 80, 5);
		$form->addTextArea("changelog", "Aktualní verze: ", 80, 15);
		
		$form->setDefaults(array(
			"changelog" => $this->changelogModel->getActualChangelog($projectId),
		));
			
		$form->addHidden("projectId", $projectId);
		$form->addHidden("odeslano", 0);
		
		$form->addSubmit("savesent", "ulož a odesli");
		$form->addSubmit("save", "Jen ulož");
		$form['savesent']->onClick[] = callback($this, "saveAndSpamChangelog");
		$form['save']->onClick[] = callback($this, "saveChangelog");
		return $form;

	}
	
	public function saveChangelog(Nette\Forms\Controls\SubmitButton $button)
	{
		if($button->getForm()->isSuccess())
		{
			$values = $button->getForm()->getValues();
			$values = $this->changelogModel->prepareChangelog($values);
			$this->changelogModel->saveChangelogToDb($values, $this->user->id);
			$this->flashMessage("Changelog uložen");
			$this->redirect('this');
		}
	}
	
	public function saveAndSpamChangelog(Nette\Forms\Controls\SubmitButton $button)
	{
		if($button->getForm()->isSuccess())
		{
			$values = $button->getForm()->getValues();
			$values->sent = 1;
			$values = $this->changelogModel->prepareChangelog($values);
			$this->changelogModel->saveChangelogToDb($values, $this->user->id);
			
			$values = $this->changelogModel->sendMail($values, $this->user->id);
			$this->flashMessage("Changelog byl úspěšně odeslán");
			$this->redirect('this');
		}
	}
	
	
	
}

