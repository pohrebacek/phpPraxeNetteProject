<?php

namespace App\Module\Model\Like;

use Nette;
use App\Module\Model\Like\LikesRepository;
use App\Module\Model\Like\LikeDTO;
use App\Module\Model\Like\LikeMapper;

final class LikeFacade
{
    public function __construct(
        private LikesRepository $likesRepository,
        protected Nette\Database\Explorer $database,
        private LikeMapper $likeMapper
    ) {

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