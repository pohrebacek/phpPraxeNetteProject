<?php
namespace App\Module\Model\Like;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\Like\LikeDTO;

class LikeMapper
{

    public static function map(ActiveRow $row): LikeDTO {      
        $id = $row->id;
        $post_id = is_numeric($row->post_id) ? intval($row->post_id) : 0;
        $user_id = is_numeric($row->user_id) ? intval($row->user_id) : 0;

        /**
         * @var int $id
         * @var int $post_id
         * @var int $user_id 
         */
        return new LikeDTO($id, $post_id, $user_id);
    }
}