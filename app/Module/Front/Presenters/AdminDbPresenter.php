<?php
namespace App\Module\Front\Presenters;

use Nette;
use App\Module\Model\Post\PostFacade;

final class AdminDbPresenter extends BasePresenter {

    public function __construct(
        protected Nette\Database\Explorer $database,
        public PostFacade $postFacade
    ) {

    }

    public function beforeRender()  //kdyžtak uplně smazat
	{
		parent::beforeRender();
        $this->template->addFilter('shouldDisplay', function ($column, $dbName) {
            $hiddenColumns = ['ownerUser_id', 'password'];
            if (in_array($column, $hiddenColumns)) {
                return false;
            }
            if ($column == 'content' && $dbName == 'posts') {
                return false;
            }
            return true;
        });
		
	}

    public function renderPosts(): void 
    {
        $q = $this->getParameter("q");
        bdump($q);
        $data = [];
        $data = $this->getAllByTableName("posts");
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
        $this->template->data = $this->postFacade->filterPostColumns($data, "posts");   
    }

    public function getAllByTableName(string $tableName): array 
    {
        return $this->database->table($tableName)->fetchAll();
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