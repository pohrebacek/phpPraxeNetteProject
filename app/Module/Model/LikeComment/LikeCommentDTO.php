<?php
namespace App\Module\Model\LikeComment;

use Nette;

readonly class LikeCommentDTO {
    function __construct(
        public int $id, public int $comment_id, public int $user_id
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}