<?php
namespace App\Module\Model\Like;

use Nette;

readonly class LikeDTO {
    function __construct(
        public int $id, public int $post_id, public int $user_id
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}