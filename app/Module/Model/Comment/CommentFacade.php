<?php
namespace App\Module\Model\Comment;

use Nette;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Comment\CommentDTO;
use App\Module\Model\Comment\CommentMapper;

final class CommentFacade
{
    public function __construct(
        private CommentsRepository $commentsRepository,
        protected Nette\Database\Explorer $database,
        private CommentMapper $commentMapper

    )   {
    }

    public function getCommentDTO(int $id): CommentDTO
    {
        $commentRow = $this->commentsRepository->getRowById($id);
        bdump($commentRow);
        return $this->commentMapper->map($commentRow);
    }
}