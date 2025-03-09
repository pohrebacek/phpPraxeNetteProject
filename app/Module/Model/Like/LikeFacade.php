<?php

namespace App\Module\Model\Like;

use Nette;
use App\Module\Model\Like\LikesRepository;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Like\LikeDTO;
use App\Module\Model\Like\LikeMapper;

final class LikeFacade
{
    public function __construct(
        private LikesRepository $likesRepository,
        protected Nette\Database\Explorer $database,
        private LikeMapper $likeMapper,
        private UsersRepository $usersRepository,
        private PostsRepository $postsRepository
    ) {

    }

    public function filterLikesData($data)
    {
        foreach($data as $index => $line){
            $lineData = $line->toArray();
            foreach($lineData as $column => $value) {
                if ($column == "user_id") {
                    $lineData["Od uživatele: "] = ($this->usersRepository->getRowById($value))->username;
                } elseif ($column == "post_id") {
                    $lineData["U postu: "] = ($this->postsRepository->getRowById($value))->title;
                }
            }
            $data[$index] = $lineData;
        }
        return $data;
    }

    public function getLikeDTO(int $id): LikeDTO|null
    {
        $likeRow = $this->likesRepository->getRowById($id);
        if ($likeRow){
            return $this->likeMapper->map($likeRow);
        }
        return null;
    }
}