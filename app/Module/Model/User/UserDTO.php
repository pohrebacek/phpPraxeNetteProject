<?php
namespace App\Module\Model\User;
use Nette\Utils\DateTime;


use Nette;

readonly class UserDTO {
    function __construct(
        public int $id, public string $username, public string $email, public string $password, public string $role, public DateTime $lastLoggedIn, public DateTime $registeredAt, public ?DateTime $premiumUntil    //phpstan měl problém že constructor očekává např string a dostane mixed
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}