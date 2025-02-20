<?php
namespace App\Module\Model\Comment;

use App\Module\Model\Comment\CommentMapper;
use Nette;
use App\Module\Model\Base\BaseRepository;
use Nette\Database\Table\ActiveRow;

final class CommentsRepository extends BaseRepository
{
	

	public function __construct(
		protected Nette\Database\Explorer $database,
		public CommentMapper $commentMapper
	) {
		$this->table = "comments";
	}

	

	

	public function deleteCommentByPostId(int $id): int{
        return $this->database->table($this->table)->where('post_id', $id)->delete();
	}

	public function getRowByPostId(mixed $post_id): ActiveRow|null
    {
        $row = $this->database->table($this->table)->where('post_id', $post_id)->fetch();
        if ($row instanceof ActiveRow)
        {
            return $row;
        }
        return null;
    }
}
