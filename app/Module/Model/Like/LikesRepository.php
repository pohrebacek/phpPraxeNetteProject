<?php
namespace App\Module\Model\Like;

use App\Module\Model\Like\LikeMapper;
use Nette;
use App\Module\Model\Base\BaseRepository;
use Nette\Database\Table\ActiveRow;

final class LikesRepository extends BaseRepository
{
    public function __construct(
        public LikeMapper $likeMapper,
        protected Nette\Database\Explorer $database,
    ) {
        $this->table = "likes";
    }


    public function deleteLikeByPostIdAndUserId(int $post_id, int $user_id): int{
        return $this->database->table($this->table)->where([
            "post_id" => $post_id,
            "user_id" => $user_id
        ])->delete();
    }

    public function getRowByPostId(int $post_id): ActiveRow|null
    {
        $row = $this->database->table($this->table)->where("post_id",$post_id)->fetch();
        if ($row instanceof ActiveRow)
        {
            return $row;
        }
        return null;
    }

    public function getRowsByPostId(int $post_id)   //vrátí rows z likes table s předanym post id
    {
        $row = $this->database->table($this->table)->where("post_id",$post_id)->fetchAll();
        return $row;
    }



    public function getRowByPostIdAndUserId(int $post_id, int $user_id): ActiveRow|null
    {
        $row = $this->database->table($this->table)->where([
            "post_id" => $post_id,
            "user_id" => $user_id
        ])->fetch();
        if ($row instanceof ActiveRow)
        {
            return $row;
        }
        return null;
    }
}