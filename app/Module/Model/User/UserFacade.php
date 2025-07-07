<?php
namespace App\Module\Model\User;

use App\Module\Model\User\UserMapper;
use Nette;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\User\UserDTO;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Post\PostFacade;
use App\Module\Model\Comment\CommentFacade;
use App\Module\Model\Post\PostDTO;
use Latte\Compiler\Nodes\Html\CommentNode;
use App\Module\Model\Comment\CommentsRepository;

final class UserFacade  //facade je komplexnější práci s nějakym repository, prostě složitější akce, plus může pracovat s víc repos najednou
{
	public function __construct(
		private UsersRepository $usersRepository,
        protected Nette\Database\Explorer $database,
        private UserMapper $userMapper,
        private PostsRepository $postsRepository,
        private PostFacade $postFacade,
        private CommentFacade $commentFacade,
        private CommentsRepository $commentsRepository
	) {
	}

    public function getUserDTO(int|string $id): UserDTO    //jedna funkce co za tebe převede row na DTO aniž bys to v kodu musel vypisovat jak kokot 
    {
        if (is_numeric($id)) {
            $postRow = $this->usersRepository->getRowById($id);
        } else {
            $postRow = $this->usersRepository->getRowByUsername($id);
        }     
        return $this->userMapper->map($postRow);
    }
    


    public function hasPremium(UserDTO $user): bool
    {
        return $user->premiumUntil > new \DateTimeImmutable();
    }

    public function getPostsLikes(int $userId)
    {
        $posts = $this->getPostsByUserId($userId);
        $likes = 0;
        foreach ($posts as $post) {
            $likes += $this->postFacade->getNumberOfLikes($post->id);
        }
        return $likes;
    }

    public function getCommentsLikes(int $userId)
    {
        $comments = $this->getCommentsByUserId($userId);
        $likes = 0;
        foreach ($comments as $comment) {
            $likes += $this->commentFacade->getNumberOfLikes($comment->id);
        }
        return $likes;
    }

    public function getPostsByUserId(int $userId)
    {
        return $this->database->table($this->postsRepository->getTable())->where("user_id", $userId)->fetchAll();
    }

    public function getCommentsByUserId(int $userId)
    {
        return $this->database->table($this->commentsRepository->getTable())->where("ownerUser_id", $userId)->fetchAll();
    }

    public function filterUsersData($data)
    {
        return $data;
    }

    public function getActivityData(int $userId, string $range): array
{
    // příklad: posledních 6 měsíců
    $now = new \DateTimeImmutable();
    $labels = [];
    $posts = [];
    $comments = [];
    if ($range == "years") {
        $registeredYear = intval($this->getUserDTO($userId)->registeredAt->format("Y"));
        bdump($registeredYear);
        $currentYear = intval($now->format("Y"));
        for ($i = $currentYear - $registeredYear; $i >= 0; $i--) {
            $date = $now->modify("-{$i} years")->format("Y");
            $labels[] = $date;

            $posts[] = $this->postFacade->countByUserAndYear($userId, $date); 

            $comments[] = $this->commentFacade->countByUserAndYear($userId, $date);
        }
        return [$labels, $posts, $comments];
    } else {
        $range = intval($range);
        for ($i = $range-1; $i >= 0; $i--) {
            $date = $now->modify("-{$i} months")->format('Y-m');   //posune momentální datum o $i měsíců zpět
            $labels[] = $date;
    
            $posts[] = $this->postFacade->countByUserAndMonth($userId, $date);  //zjistí kolik příspěvků ma uživatel v daném měsíci

            $comments[] = $this->commentFacade->countByUserAndMonth($userId, $date);
        }
    
        return [$labels, $posts, $comments]; // třetí bude třeba pro komentáře
    }


}


    public function getUsersByFilter(string $column, string $parameter)
    {
        return $this->database->table($this->usersRepository->getTable())->where("{$column} LIKE ?", "%$parameter%")->fetchAll();
    }

    public function isOwnerOfPost(PostDTO $post, int $userId): bool
    {
        return $userId == $post->user_id;
    }

    




}