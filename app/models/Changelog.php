<?php

namespace Models;

class Changelog extends Base
{

	/** @var \DibiConnection */
	private $database;

	public function __construct(\Nette\DI\Container $context)
	{
		parent::__construct($context);
		$this->database = $this->getDatabase();
	}

	public function getProjects()
	{
		return $this->database->query("SELECT * FROM [projects] 
			ORDER BY [project_name]")->fetchAll();
	}

	public function getProject($id)
	{
		return $this->database->query("SELECT * FROM [projects] 
			WHERE [id_project] = %i", $id)->fetch();
	}

	public function getChangelogRecepients($id)
	{
		return $this->database->query("SELECT * FROM [users_projects]
			LEFT JOIN [users] ON [users].[id_user] = [users_projects].[id_user]
			WHERE [users_projects].[id_project] = %i", $id)->fetchAll();
	}

	public function getAvailableUsers($projectId)
	{
		return $this->database->query("SELECT [id_user], [name] 
			FROM [users] 
			WHERE [id_user] NOT IN (
				SELECT [id_user] FROM [users_projects] WHERE [id_project] = %i 
			)", $projectId)
				->fetchPairs();
	}

	public function newUserProject($userId, $projectId)
	{
		$data = array(
			"id_user" => (int) $userId,
			"id_project" => (int) $projectId,
		);
		$this->database->query("INSERT INTO [users_projects]", $data);
	}

	public function getActualChangelog($projectId)
	{
		return $this->database->query("SELECT [changelog_text] 
			FROM [changelogs] 
			WHERE [id_project] = %i 
			ORDER BY [id_changelog] DESC 
			LIMIT 1", $projectId)->fetchSingle();
	}

	public function getUserInfo($idUser)
	{
		return $this->database->query("SELECT * FROM [users] 
			WHERE [id_user] = %i", $idUser)->fetch();
	}

	public function getChangelogHistory($projectId)
	{
		return $this->database->query("SELECT [changelogs].*, [users].[name], [users].[email] 
			FROM [changelogs] 
			LEFT JOIN [users] ON [users].[id_user] = [changelogs].[id_user]
			WHERE [id_project] = %i 
			ORDER BY [date] DESC", $projectId)->fetchAll();
	}

	public function prepareChangelog($values)
	{
		$log = "";
		if(trim($values->newRows) != "")
		{
			foreach(explode("\n", $values->newRows) as $zmena)
			{
				if(trim($zmena) != "")
				{
					$log .= date("Y-m-d") . " " . $zmena . "\n";
				}
			}
		}

		$values->log = $log . $values->changelog;
		return $values;
	}

	public function saveChangelogToDb($values, $userId)
	{
		$changelogData = array(
			"id_project" => $values->projectId,
			"id_user" => $userId,
			"date" => date("c"),
			"sent" => ((isset($values->sent) && $values->sent == "1") ? "1" : "0"),
			"changelog_text" => $values->log,
		);

		$this->database->query("INSERT INTO [changelogs] ", $changelogData);
	}

	public function sendMail($values, $userId)
	{
		$values = $this->prepareMail($values, $userId);
		$subject = "Changelog " . $values->projectInfo["project_name"] . " " . $values->projectInfo["project_version"] . " (" . date("Y-m-d") . ") - " . $values->projectInfo["project_shortname"];
		$body = "Dobrý den,\n\nzasílám aktuální changelog pro " . $values->projectInfo["project_name"] . " - " . $values->projectInfo["project_shortname"] . "\n\n" . $values->sender["name"];
		
		$mail = new \Nette\Mail\Message;
		foreach($values->recepients as $r)
		{
			if(trim($r["email"]) != "")
			{
				$mail->addTo($r["email"], $r["name"]);
			}
		}

		$mail->setFrom($values->sender["email"], $values->sender["name"]);
		$mail->setSubject($subject);
		$mail->setBody($body);
		$mail->addAttachment('changelog.txt', $values->log);
		$mail->send();
	}

	private function prepareMail($values, $userId)
	{
		$values->projectInfo = $this->getProject($values->projectId);
		$values->recepients = $this->getChangelogRecepients($values->projectId);
		$values->sender = $this->getUserInfo($userId);
		return $values;
	}

}
