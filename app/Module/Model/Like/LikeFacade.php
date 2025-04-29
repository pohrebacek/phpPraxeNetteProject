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

    public function getLikesByFilter(string $column, string $parameter)
    {
        if ($column == "user_id" && $parameter) //parameter je jméno a ne id, uživateli se totiž bude líp hledat podle jména a ne podle id
        {
            $user = $this->usersRepository->getRowByUsername($parameter); //takže podle jména najdu usera
            if ($user) {
                return $this->database->table($this->likesRepository->getTable())->where($column, $user->id)->fetchAll(); //a podle jeho id vyhledam record postu v db
            }
            return $this->database->table($this->likesRepository->getTable())->where($column, "")->fetchAll();  //vyhodí 0 záznamů pokud v se db nic nenašlo podle parametru
        }

        if ($column == "post_id" && $parameter)
        {
            $posts = $this->database->table($this->postsRepository->getTable())->where("title LIKE ?", "%$parameter%")->fetchAll();
            bdump($posts);
            if ($posts) {
                $commentsToRender = [];
                foreach ($posts as $post) {
                    $foundCommentRecordsByPostId = $this->database->table($this->likesRepository->getTable())->where($column, $post->id)->fetchAll();
                    if ($foundCommentRecordsByPostId) {
                        $commentsToRender = $foundCommentRecordsByPostId;   //pokud v db table comments najdu comment co má post_id jako id jednoho z postů co jsem našel podle jména, tak ho vyrenderuju, jinak to znamená že ten post nemá commenty, takže ho nerederuju
                    }
                }
                bdump($commentsToRender);
                return $commentsToRender;
            }
            return $this->database->table($this->likesRepository->getTable())->where($column, "")->fetchAll();  //vyhodí 0 záznamů pokud v se db nic nenašlo podle parametru

        }
        return $this->database->table($this->likesRepository->getTable())->where("{$column} LIKE ?", "%$parameter%")->fetchAll();
    }
}