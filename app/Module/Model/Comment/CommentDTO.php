<?php
namespace App\Module\Model\Post;

use Nette;

class CommentDTO {
    function __construct(
        public mixed $id, public mixed $post_id, public mixed $name, public mixed $email, public mixed $content
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}