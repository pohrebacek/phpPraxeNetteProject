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
            foreach($lineData as $column => $value) {
                if ($column == "user_id") {
                    $lineData["Od uživatele: "] = ($this->usersRepository->getRowById($value))->username;
                } elseif ($column == "comment_id") {
                    $lineData["U komentáře: "] = ($this-> commentsRepository->getRowById($value))->title;
                }
            }
            $data[$index] = $lineData;
        }
        return $data;
    }

    public function getLikeDTO(int $id): LikeCommentDTO|null
    {
        $likeRow = $this->likesCommentsRepository->getRowById($id);
        if ($likeRow){
            return $this->likeCommentMapper->map($likeRow);
        }
        return null;
    }

    public function getLikesByFilter(string $column, string $parameter)
    {
        if ($column == "user_id" && $parameter) //parameter je jméno a ne id, uživateli se totiž bude líp hledat podle jména a ne podle id
        {
            $user = $this->usersRepository->getRowByUsername($parameter); //takže podle jména najdu usera
            if ($user) {
                return $this->database->table($this->likesCommentsRepository->getTable())->where($column, $user->id)->fetchAll(); //a podle jeho id vyhledam record postu v db
            }
            return $this->database->table($this->likesCommentsRepository->getTable())->where($column, "")->fetchAll();  //vyhodí 0 záznamů pokud v se db nic nenašlo podle parametru
        }

        if ($column == "comment_id" && $parameter)
        {
            $comments = $this->database->table($this->commentsRepository->getTable())->where("title LIKE ?", "%$parameter%")->fetchAll();
            bdump($comments);
            if ($comments) {
                $commentsToRender = [];
                foreach ($comments as $post) {
                    $foundCommentRecordsByPostId = $this->database->table($this->likesCommentsRepository->getTable())->where($column, $post->id)->fetchAll();
                    if ($foundCommentRecordsByPostId) {
                        $commentsToRender = $foundCommentRecordsByPostId;   //pokud v db table comments najdu comment co má post_id jako id jednoho z postů co jsem našel podle jména, tak ho vyrenderuju, jinak to znamená že ten post nemá commenty, takže ho nerederuju
                    }
                }
                bdump($commentsToRender);
                return $commentsToRender;
            }
            return $this->database->table($this->likesCommentsRepository->getTable())->where($column, "")->fetchAll();  //vyhodí 0 záznamů pokud v se db nic nenašlo podle parametru

        }
        return $this->database->table($this->likesCommentsRepository->getTable())->where("{$column} LIKE ?", "%$parameter%")->fetchAll();
    }
}