<?php
namespace App\Module\Model;

use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;

final class PostCommentFacade
{
	public function __construct(
		private PostsRepository $postsRepository,
		private CommentsRepository $commentsRepository,
        protected Nette\Database\Explorer $database,
	) {
	}

    public function deletePost(int $id): void
    {
        $this->database->transaction(function () use ($id) {
            $this->commentsRepository->deleteCommentByPostId($id);
            $this->postsRepository->deleteRow($id);
        });
    }


}
