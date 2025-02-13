<?php
namespace App\Module\Model\Post;

use Nette;

class PostDTO {
    function __construct(
        public int $id, public string $title, public string $content
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
    }

    
}