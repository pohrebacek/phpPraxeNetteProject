<?php
namespace App\Module\Model\Comment;

use Nette;
use App\Module\Model\Base\BaseRepository;

final class CommentsRepository extends BaseRepository
{
	

	public function __construct(
		protected Nette\Database\Explorer $database,
	) {
		$this->table = "comments";
	}

	

	

	public function deleteCommentByPostId(int $id): int{
        return $this->database->table($this->table)->where('post_id', $id)->delete();
	}
}
