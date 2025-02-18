<?php
namespace App\Module\Model\Security;

use Nette;
use Nette\Security\SimpleIdentity;
use Nette\Security\Authenticator;

class MyAuthenticator implements Nette\Security\Authenticator
{
    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\Passwords $passwords
    ) {

    }

    public function authenticate(string $username, string $password): SimpleIdentity
    {
        $row = $this->database->table("users")->where("username", $username)->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException('User not found.');
        }

        
        if (!$this->passwords->verify($password, $row->password)) {
			throw new Nette\Security\AuthenticationException('Invalid password.');
		}

        return new SimpleIdentity(
            $row->id,
            "$row->role",
            ["username" => $row->username,
                   "password"=> $row->password,
                   "email" => $row->email
            ],
        );
    }
    
}