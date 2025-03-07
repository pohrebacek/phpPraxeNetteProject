<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostsRepository;
use App\Module\Model\User\UsersRepository;
use Nette\Application\UI\Form;
use Nette;
use App\Module\Model\Settings\SettingsFacade;
use App\Module\Model\Settings\SettingsRepository;
use App\Module\Model\Base\BaseRepository;


final class AdminPresenter extends BasePresenter{
    public function __construct(
        private PostsRepository $postsRepository,
        private SettingsFacade $settingsFacade,
        private SettingsRepository $settingsRepository,
        protected Nette\Database\Explorer $database,
        private UsersRepository $usersRepository,
        private array $settingsParam = []
    ) {

    }

    public function beforeRender()
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

    public function startup(): void //tohle musí bejt ve startup, protože jinak ta pageSettingsFormSucceeded fce nebude mít přístup k tomu naplněnýmu poli settingsParam (idk proč)
    {
        parent::startup();
        $this->settingsParam = $this->settingsFacade->allSetingsToDTO();
        bdump($this->settingsParam);
        
    }

    public function renderShow(): void
    {

    }

    public function renderSearch($dbName): void    //zatim neni nikde využitá
    {
        $q = $this->getParameter("q");
        bdump($dbName);
        bdump("jou");
    }

    public function renderDatabase($dbName): void
    {
        $this->template->dbName = $dbName;
        $q = $this->getParameter("q");
        bdump($q);
        bdump($dbName);
        $data = [];
        $data = $this->getAllByTableName($dbName);
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
        $this->template->data = $this->filterColumns($data, $dbName); 



            
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


    public function actionDelete($recordId, $dbName): void
    {
        bdump($recordId, $dbName);
        $this->database->table($dbName)->get($recordId)->delete();
        $this->flashMessage("Záznam byl smazán");
        $this->redirect("Admin:database", $dbName);
    }

    public function getAllByTableName(string $tableName): array 
    {
        return $this->database->table($tableName)->fetchAll();
    }

    public function createComponentPageSettingsForm(): Form
    {
        $form = new Form;

        $form->addText('postsPerPage', 'Počet příspěvků na jedné stránce')
            ->setRequired('Toto pole je povinné.')
            ->setHtmlAttribute('type', 'number')
            ->setHtmlAttribute("class", "form-control")
            ->setDefaultValue($this->settingsParam["postsPerPage"]);

        $form->addSubmit('submit','Uložit nastavení stránky')
             ->setHtmlAttribute("class", "btn btn-outline-primary");

        $form->onSuccess[] = [$this, 'pageSettingsFormSucceeded'];

        return $form;
    }

    public function pageSettingsFormSucceeded(Form $form)
    {
        $data = $form->getValues();
        bdump($data);
        if ($data->postsPerPage < 1 || $data->postsPerPage > count($this->postsRepository->getAll())) {
            $form->addError("Zadejte platný počet příspěvků");
        } else {
            $this->settingsRepository->saveSettings($data);
        }
    }
}