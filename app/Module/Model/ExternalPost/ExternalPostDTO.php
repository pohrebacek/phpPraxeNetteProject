<?php
namespace App\Module\Model\ExternalPost;

use Nette;

readonly class ExternalPostDTO {
    function __construct(
        public int $id, public string $guid, public int $postId
    ) {
        
    }
}