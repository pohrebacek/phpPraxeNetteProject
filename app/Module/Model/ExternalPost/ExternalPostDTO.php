<?php
namespace App\Module\Model\ExternalPost;

use Nette;

readonly class ExternalPostDTO {
    function __construct(
        public mixed $id, public mixed $guid, public mixed $postId
    ) {
        
    }
}