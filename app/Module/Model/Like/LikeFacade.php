<?php

namespace App\Module\Model\Like;

use Nette;
use App\Module\Model\Like\LikesRepository;
use App\Module\Model\Like\LikeDTO;

final class LikeFacade
{
    public function __construct(
        private LikesRepository $likesRepository,
        protected Nette\Database\Explorer $database
    ) {

    }

    public function getLikeDTO(int $id): LikeDTO
    {
        $likeRow = $this->likesRepository->getRowById($id);
        return $this->likesRepository->likeMapper->map($likeRow);
    }
}