<?php
namespace App\Module\Model\LikeComment;

use App\Module\Model\LikeComment\LikeCommentMapper;
use Nette;
use App\Module\Model\Base\BaseRepository;
use Nette\Database\Table\ActiveRow;

final class LikesCommentsRepository extends BaseRepository
{
    public function __construct(
        public LikeCommentMapper $likeCommentMapper,
        protected Nette\Database\Explorer $database,
    ) {
        $this->table = "likes_comments";
    }


    public function deleteLikeByCommentIdAndUserId(int $comment_id, int $user_id): int{
        return $this->database->table($this->table)->where([
            "comment_id" => $comment_id,
            "user_id" => $user_id
        ])->delete();
    }

    public function getRowByCommentId(int $comment_id): ActiveRow|null
    {
        $row = $this->database->table($this->table)->where("comment_id",$comment_id)->fetch();
        if ($row instanceof ActiveRow)
        {
            return $row;
        }
        return null;
    }

    public function getRowsByCommentId(int $comment_id)
    {
        $row = $this->database->table($this->table)->where("comment_id",$comment_id)->fetchAll();
        return $row;
    }

    public function getRowByCommentIdAndUserId(int $comment_id, int $user_id): ActiveRow|null
    {
        $row = $this->database->table($this->table)->where([
            "comment_id" => $comment_id,
            "user_id" => $user_id
        ])->fetch();
        if ($row instanceof ActiveRow)
        {
            return $row;
        }
        return null;
    }
}