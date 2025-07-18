<?php
namespace App\Module\Model\Comment;

use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\User\UserFacade;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Comment\CommentDTO;
use App\Module\Model\Comment\CommentMapper;
use App\Module\Model\Like\LikesRepository;
use App\Module\Model\LikeComment\LikesCommentsRepository;
use Nette\Utils\Strings;

final class CommentFacade
{
    public function __construct(
        private CommentsRepository $commentsRepository,
        protected Nette\Database\Explorer $database,
        private CommentMapper $commentMapper,
        private PostsRepository $postsRepository,
        private UsersRepository $usersRepository,
        private LikesRepository $likesRepository,
        private LikesCommentsRepository $likesCommentsRepository

    )   {
    }

    public function getCommentDTO(int $id): CommentDTO
    {
        $commentRow = $this->commentsRepository->getRowById($id);
        bdump($commentRow);
        return $this->commentMapper->map($commentRow);
    }

    public function deleteComment(int $id)
    {
        $this->database->transaction(function () use ($id) {
            $this->deleteRepliesRecursive($id);
            $this->commentsRepository->deleteRow($id);
        });
    }

    private function deleteRepliesRecursive(int $id): void
    {
        $replies = $this->database->table($this->commentsRepository->getTable())
            ->where('replyTo', $id)
            ->fetchAll();

        foreach ($replies as $reply) {
            $this->deleteRepliesRecursive($reply->id); // smaže odpovědi na odpověď
            $this->commentsRepository->deleteRow($reply->id);
        }
    }

    public function filterCommentsData($data)
    {
        foreach($data as $index => $line){
            $lineData = $line;
            if (!is_array($line)){
                $lineData = $line->toArray();
            }
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

    public function countByUserAndMonth($userId, $date)
    {
        $start = new \DateTimeImmutable("{$date}-01 00:00:00");
        $end = $start->modify("first day of next month")->modify("-1 second");
        $countedPosts = sizeof($this->database->table($this->commentsRepository->getTable())
            ->where("ownerUser_id = ?", $userId)
            ->where("created_at >= ?", $start)
            ->where("created_at <= ?", $end)
            ->fetchAll());
        return $countedPosts;
    }

    public function countByUserAndYear($userId, $year)
    {
        return sizeof($this->database->table($this->commentsRepository->getTable())
            ->where("YEAR(created_at)", strval($year))
            ->where("ownerUser_id", $userId)
            ->fetchAll());
    }

    public function getNumberOfLikes(int $id)
    {
        return sizeof($this->likesCommentsRepository->getRowsByCommentId($id));
    }

    public function getCommentsByFilter(string $column, string $parameter)
    {
        if ($column == "ownerUser_id" && $parameter) //parameter je jméno a ne id, uživateli se totiž bude líp hledat podle jména a ne podle id
        {
            $users = $this->database->table($this->usersRepository->getTable())->where("username LIKE ?", "%$parameter%")->fetchPairs("id", "username"); //vrátí pole ve tvaru id => username
            bdump($users);

            if (!$users) {
                return [];
            }
            
            return $this->database->table($this->commentsRepository->getTable())->where($column . " IN ?", array_keys($users))->fetchAll();    //není potřeba žádnej foreach protože to obstarává samotnej dotaz
        }

        if ($column == "post_id" && $parameter)
        {
            $posts = $this->database->table($this->postsRepository->getTable())->where("title LIKE ?", "%$parameter%")->fetchPairs("id", "title");
            bdump($posts);

            if (!$posts) {
                return [];
            } 

            return $this->database->table($this->commentsRepository->getTable())->where($column . " IN ?", array_keys($posts))->fetchAll();
        }
        return $this->database->table($this->commentsRepository->getTable())->where("{$column} LIKE ?", "%$parameter%")->fetchAll();
    }

    public function getOwnerUsername(CommentDTO $comment)
    {
        return $this->usersRepository->getUsernameById($comment->ownerUser_id);
    }

    public function getReplyToPreview(int $id)
    {
        $replyToComment = $this->getCommentDTO($id);
        $replyToUser = $this->usersRepository->getUsernameById($replyToComment->ownerUser_id);
        return $replyToUser . ": " . Strings::truncate($replyToComment->content, 30);
        
    }
}