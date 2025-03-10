<?php
namespace App\Module\Model\Comment;

use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Comment\CommentDTO;
use App\Module\Model\Comment\CommentMapper;

final class CommentFacade
{
    public function __construct(
        private CommentsRepository $commentsRepository,
        protected Nette\Database\Explorer $database,
        private CommentMapper $commentMapper,
        private PostsRepository $postsRepository,
        private UsersRepository $usersRepository

    )   {
    }

    public function getCommentDTO(int $id): CommentDTO
    {
        $commentRow = $this->commentsRepository->getRowById($id);
        bdump($commentRow);
        return $this->commentMapper->map($commentRow);
    }

    public function filterCommentsData($data)
    {
        foreach($data as $index => $line){
            $lineData = $line->toArray();
            foreach($lineData as $column => $value) {
                if ($column == "name") {
                    $lineData["Od uživatele: "] = ($this->usersRepository->getRowByUsername($value))->username;
                } elseif ($column == "post_id") {
                    $lineData["U postu: "] = ($this->postsRepository->getRowById($value))->title;
                }
            }
            $data[$index] = $lineData;
        }
        return $data;
    }
}