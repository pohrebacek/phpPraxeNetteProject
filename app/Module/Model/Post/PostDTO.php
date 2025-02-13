<?php
namespace App\Module\Model\Post;

use Nette;

class PostDTO {
    function __construct(
        public mixed $id, public mixed $title, public mixed $content    //phpstan měl problém že constructor očekává např string a dostane mixed
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}