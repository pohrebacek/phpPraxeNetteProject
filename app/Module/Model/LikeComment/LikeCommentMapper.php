<?php
namespace App\Module\Model\LikeComment;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\LikeComment\LikeCommentDTO;

class LikeCommentMapper
{

    public static function map(ActiveRow $row): LikeCommentDTO {      
        $id = $row->id;
        $comment_id = is_numeric($row->comment_id) ? intval($row->comment_id) : 0;
        $user_id = is_numeric($row->user_id) ? intval($row->user_id) : 0;

        /**
         * @var int $id
         * @var int $comment_id
         * @var int $user_id 
         */
        return new LikeCommentDTO($id, $comment_id, $user_id);
    }
}