<?php
namespace App\Module\Model\Post;

use DateTime;
use Nette;

readonly class PostDTO {
    function __construct(
        public int $id, public string $title, public string $content, public ?string $image, public int $user_id, public DateTime $created_at, public bool $premium    //phpstan měl problém že constructor očekává např string a dostane mixed
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}