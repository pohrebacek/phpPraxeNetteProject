<?php
namespace App\Module\Model\Comment;

use Nette;

readonly class CommentDTO {
    function __construct(
        public int $id, public int $post_id, public string $name, public string $email, public string $content, public int $ownerUser_id, public ?int $replyTo
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}