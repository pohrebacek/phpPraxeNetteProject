<?php
namespace App\Module\Model\Like;

use Nette;

class LikeDTO {
    function __construct(
        public mixed $id, public mixed $post_id, public mixed $user_id
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}