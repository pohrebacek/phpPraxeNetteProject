<?php
namespace App\Module\Front\Presenters;

final class AdminDbPresenter extends BasePresenter {
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
        $this->template->data = $this->filterColumns($data, "posts");   
    }


    public function filterColumns($data, $dbName)
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