<?php
namespace App\Module\Front\Presenters;

use Nette;
use App\Module\Model\Post\PostFacade;
use App\Module\Model\Like\LikeFacade;
use App\Module\Model\User\UserFacade;
use App\Module\Model\Comment\CommentFacade;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Post\PostsRepository;

final class AdminDbPresenter extends BasePresenter {

    public function __construct(
        protected Nette\Database\Explorer $database,
        public PostFacade $postFacade,
        private LikeFacade $likeFacade,
        private CommentFacade $commentFacade,
        private UsersRepository $usersRepository,
        private PostsRepository $postsRepository,
        private UserFacade $userFacade
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
        $recordId = $this->getParameter("recordId");
        $range = $this->getHttpRequest()->getQuery('range') ?? '6'; //pokud to vrátí null tak to přiřadí 6

        bdump($recordId);
        $user = $this->userFacade->getUserDTO($recordId);
        bdump($user);
        $this->template->userData = $user;

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
        $q = $this->getParameter("q");
        bdump($q);
        $data = [];
        if (isset($_GET["filter"])) {
            $filter = $_GET['filter'];
            bdump($filter);
            $data = $this->postFacade->getPostsByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("posts");
        }

        
        
        bdump($data);
        //$this->template->data = $data;

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
        $data = [];
        $q = $this->getParameter("q");
        if (isset($_GET["filter"])) {
            $filter = $_GET["filter"];
            bdump($filter);
            $data = $this->commentFacade->getCommentsByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("comments");
        }
        
        if ($q) 
        {
            $this->template->filterInput = $q;
        }
        $this->template->data = $this->commentFacade->filterCommentsData($data);
    }

    public function renderLikes(): void
    {
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
        $data = [];
        $q = $this->getParameter("q");
        if (isset($_GET["filter"]))
        {
            $filter = $_GET["filter"];
            bdump($filter);
            $data = $this->userFacade->getUsersByFilter($filter, $q);
        } else {
            $data = $this->getAllByTableName("users");
        }

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

    public function getRecordsByFilter(string $tableName, $column, $parameter) {    //rozdělit funkci do facades, tahle funkce nebude ale každá facade bude mít svoji verzi táhle fce
        bdump($tableName);
        bdump($column);
        bdump($parameter);
        if ($column == "id" && $parameter) {
            return $this->database->table($tableName)->where($column, $parameter)->fetchAll();
        }

        if ($column == "user_id" && $parameter)
        {
            $user = $this->usersRepository->getRowByUsername($parameter);
            if ($user) {
                return $this->database->table("posts")->where($column, $user->id)->fetchAll();
            }
            return $this->database->table("posts")->where($column, "")->fetchAll();
        }

        if($column == "post_id" && $parameter)
        {
            $user = $this->usersRepository->getRowByUsername($parameter);
            if ($user) {
                return $this->database->table("posts")->where($column, $user->id)->fetchAll();
            }
            return $this->database->table("posts")->where($column, "")->fetchAll();
        }
        return $this->database->table($tableName)->where("{$column} LIKE ?", "%$parameter%")->fetchAll();
    }



    public function filterColumns($data, $dbName)   //postupně přepisuju do fasád, pak smažu, už se nepoužívá
    {
        //funcke co podle jména db vyřadí nepotřebné parametry aby to vše bylo uživatelsky přívětivé
        switch($dbName){
            case "posts":
                foreach($data as $index => $line){
                    $lineData = $line->toArray();
                    foreach($lineData as $column => $value) {
                        if ($column == "user_id") {
                            //$data[$column] = "Napsáno uživatelem: ";
                            //$data[$value] = ($this->usersRepository->getRowById($value))->username;
                            $lineData["Od uživatele: "] = ($this->usersRepository->getRowById($value))->username;
                        }
                        //bdump("$column, $value");
                    }
                    $data[$index] = $lineData;
                }         
                //bdump($data);
                return $data;

            case "comments":
                foreach($data as $index => $line){
                    $lineData = $line->toArray();
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
            case "likes":
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
            case "users":
                return $data;
            case "settings":
                return $data;
        }
    }
}