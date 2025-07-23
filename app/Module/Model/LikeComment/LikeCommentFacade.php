<?php

namespace App\Module\Model\LikeComment;

use Nette;
use App\Module\Model\LikeComment\LikesCommentsRepository;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\LikeComment\LikeCommentDTO;
use App\Module\Model\LikeComment\LikeCommentMapper;
use App\Module\Model\Comment\CommentsRepository;

final class LikeCommentFacade
{
    public function __construct(
        private LikesCommentsRepository $likesCommentsRepository,
        protected Nette\Database\Explorer $database,
        private LikeCommentMapper $likeCommentMapper,
        private UsersRepository $usersRepository,
        private PostsRepository $postsRepository,
        private CommentsRepository $commentsRepository
    ) {

    }

    public function filterLikesData($data)
    {
        foreach($data as $index => $line){
            $lineData = $line->toArray();
            bdump($lineData);
            foreach($lineData as $column => $value) {
                if ($column == "user_id") {
                    $lineData["Od uživatele: "] = ($this->usersRepository->getRowById($value))->username;
                } elseif ($column == "comment_id") {
                    $lineData["U komentáře: "] = ($this-> commentsRepository->getRowById($value))->content;
                    bdump(($this-> commentsRepository->getRowById($value)));
                }
            }
            $data[$index] = $lineData;
        }
        bdump($data);
        return $data;
    }

    public function getLikeCommentDTO(int $id): LikeCommentDTO|null
    {
        $likeRow = $this->likesCommentsRepository->getRowById($id);
        if ($likeRow){
            return $this->likeCommentMapper->map($likeRow);
        }
        return null;
    }

}