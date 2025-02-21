<?php
namespace App\Module\Model\User;

use Nette;

readonly class UserDTO {
    function __construct(
        public mixed $id, public mixed $username, public mixed $email, public mixed $password, public mixed $role    //phpstan měl problém že constructor očekává např string a dostane mixed
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}