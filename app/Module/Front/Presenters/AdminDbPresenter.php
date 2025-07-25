<?php
namespace App\Module\Front\Presenters;

use Nette;
use App\Module\Model\Post\PostFacade;
use App\Module\Model\Like\LikeFacade;
use App\Module\Model\LikeComment\LikeCommentFacade;
use App\Module\Model\User\UserFacade;
use App\Module\Model\Comment\CommentFacade;
use App\Module\Model\User\UsersRepository;
use App\Service\CurrentUserService;
use App\Module\Model\Post\PostsRepository;

final class AdminDbPresenter extends BasePresenter {

    public function __construct(
        protected Nette\Database\Explorer $database,
        public PostFacade $postFacade,
        private LikeFacade $likeFacade,
        private CommentFacade $commentFacade,
        private UsersRepository $usersRepository,
        private PostsRepository $postsRepository,
        private UserFacade $userFacade,
        private LikeCommentFacade $likeCommentFacade,
        private CurrentUserService $currentUser
    ) {

    }

    public function beforeRender()  //kdyžtak uplně smazat
	{
		parent::beforeRender();
        $this->template->addFilter('shouldDisplay', function ($column, $dbName) {
            $hiddenColumns = ['ownerUser_id', 'password', 'last_logged_in'];
            if (in_array($column, $hiddenColumns)) {
                return false;
            }
            if ($column == 'content' && $dbName == 'posts') {
                return false;
            }
            return true;
        });
		
	}

    public function renderUserProfile(): void
    {
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }

        $recordId = $this->getParameter("recordId");
        $range = $this->getHttpRequest()->getQuery('range') ?? '6'; //pokud to vrátí null tak to přiřadí 6

        bdump($recordId);
        $user = $this->userFacade->getUserDTO($recordId);
        bdump($user);
        $this->template->userData = $user;
        bdump($this->userFacade->hasPremium($user));
        $this->template->hasPremium = $this->userFacade->hasPremium($user);

        [$labels, $posts, $comments] = $this->userFacade->getActivityData($recordId, $range);

        $this->template->labels = $labels;
        $this->template->posts = $posts;
        $this->template->comments = $comments;
        bdump($posts);
        bdump($labels);

        $this->template->likesOfPosts = $this->userFacade->getPostsLikes($user->id);
        $this->template->likesOfComments = $this->userFacade->getCommentsLikes($user->id);
    }

    public function renderPosts(): void 
    {
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }

        $q = $this->getParameter("q");
        $sort = $this->getHttpRequest()->getQuery('sort') ?? 'ASC';

        bdump($sort);
        bdump($q);
        $data = [];
        if (isset($_GET["filter"])) {
            $filter = $_GET['filter'];
            bdump($filter);
            $data = $this->postFacade->getPostsByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("posts");
        }
        
        $data = $this->postFacade->sortPosts($data, $sort);



        //DEBUG
        foreach($data as $line){
            $lineData = $line->toArray();
            //bdump($lineData);
            foreach ($lineData as $column => $value) {
                bdump ("Column: $column, Value: $value");
            }
        }

        if ($q) 
        {
            $this->template->filterInput = $q;
        }
        $this->template->data = $this->postFacade->filterPostColumns($data);   
    }

    public function renderComments(): void
    {
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }

        $data = [];
        $q = $this->getParameter("q");
        $sort = $this->getHttpRequest()->getQuery('sort') ?? 'ASC';

        if (isset($_GET["filter"])) {
            $filter = $_GET["filter"];
            bdump($filter);
            $data = $this->commentFacade->getCommentsByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("comments");
        }

        $data = $this->commentFacade->sortComments($data, $sort);
        
        if ($q) 
        {
            $this->template->filterInput = $q;
        }
        bdump($data);


        $data = $this->commentFacade->updateReplyToCommentData($data);

        bdump($data);
        $this->template->data = $this->commentFacade->filterCommentsData($data);
    }

    public function renderLikes(): void
    {
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }

        $data = [];
        $q = $this->getParameter("q");
        if (isset($_GET["filter"]))
        {
            $filter = $_GET["filter"];
            bdump($filter);
            $data = $this->likeFacade->getLikesByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("likes");
        }

        if ($q)
        {
            $this->template->filterInput = $q;
        }
        $this->template->data = $this->likeFacade->filterLikesData($data);
    }



    public function renderUsers(): void
    {
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }

        $data = [];
        $q = $this->getParameter("q");
        $sort = $this->getHttpRequest()->getQuery('sort') ?? 'ASC';

        if (isset($_GET["filter"]))
        {
            $filter = $_GET["filter"];
            bdump($filter);
            $data = $this->userFacade->getUsersByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("users");
        }

        $data = $this->userFacade->sortUsers($data, $sort);

        if ($q)
        {
            $this->template->filterInput = $q;
        }
        $this->template->data = $data;

    }

    public function getAllByTableName(string $tableName): array 
    {
        return $this->database->table($tableName)->fetchAll();
    }






}