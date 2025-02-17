<?php
namespace App\Module\Model\Like;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\Like\LikeDTO;

class LikeMapper
{
    public function __costruct(

    ) {}

    public static function map(ActiveRow $row): LikeDTO {
        return new LikeDTO($row->id, $row->post_id, $row->user_id);
    }
}