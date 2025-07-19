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
            bdump($line);
            $lineData = $line;
            if (!is_array($line)) {
                $lineData = $line->toArray();
            }
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
        bdump($column);
        bdump($parameter);
        if ($column == "user_id" && $parameter) //parameter je jméno a ne id, uživateli se totiž bude líp hledat podle jména a ne podle id
        {
            $users = $this->database->table($this->usersRepository->getTable())->where("username LIKE ?", "%$parameter%")->fetchPairs("id", "username"); //vrátí pole ve tvaru id => username
            bdump($users);

            if (!$users) {
                return [];
            }
            
            return $this->database->table($this->likesRepository->getTable())->where($column . " IN ?", array_keys($users))->fetchAll();    //není potřeba žádnej foreach protože to obstarává samotnej dotaz
            
        }

        if ($column == "post_id" && $parameter)
        {
            $posts = $this->database->table($this->postsRepository->getTable())->where("title LIKE ?", "%$parameter%")->fetchPairs("id", "title");  //najdu posty co jsou podobný $parameter, a dam je do pole ve tvaru id => title
            bdump($posts);

            if (!$posts) {
                return [];
            } 

            return $this->database->table($this->likesRepository->getTable())->where($column . " IN ?", array_keys($posts))->fetchAll();    //vrátí post_likes, kterých post_id je v keys těch postů co sem našel podle title
        }
        return $this->database->table($this->likesRepository->getTable())->where("{$column} LIKE ?", "%$parameter%")->fetchAll();
    }
}