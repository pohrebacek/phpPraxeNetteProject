<?php
namespace App\Module\Model\Comment;

use Nette;

readonly class CommentDTO {
    function __construct(
        public mixed $id, public mixed $post_id, public mixed $name, public mixed $email, public mixed $content, public mixed $ownerUser_id, public mixed $replyTo
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}