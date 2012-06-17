<?php

use Nette\Security as NS;

class DatabaseAuthenticator extends Nette\Object implements NS\IAuthenticator
{
	const USER = "user";
	const ADMIN = "admin";
	
	public $connection;

    function __construct(\DibiConnection $connection)
    {
        $this->connection = $connection;
    }

    function authenticate(array $credentials)
    {
        list($username, $password) = $credentials;
        $row = $this->connection->query("SELECT * FROM [users] WHERE [login] = %s", $username)->fetch();

        if (!$row) {
            throw new NS\AuthenticationException('Uživatel nenalezen.');
        }

        if ($row["password"] !== md5($password)) {
            throw new NS\AuthenticationException('Špatné heslo.');
        }

        return new NS\Identity($row["id_user"], $row["admin"] == 1 ? self::ADMIN : self::USER );
    }
}
