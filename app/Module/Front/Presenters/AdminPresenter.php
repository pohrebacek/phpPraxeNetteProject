<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostsRepository;
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
        private array $settingsParam = []
    ) {

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

    public function renderDatabase($dbName): void
    {
        $this->template->dbName = $dbName;
        bdump($dbName);
        $data = [];
        $data = $this->getAllByTableName($dbName);
        bdump($data);
        $this->template->data = $data;

        //DEBUG
        foreach($data as $line){
            $lineData = $line->toArray();
            bdump($lineData);
            foreach ($lineData as $column => $value) {
                bdump ("Column: $column, Value: $value");
            }
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
            ->setDefaultValue($this->settingsParam["postsPerPage"]);

        $form->addSubmit('submit','Uložit nastavení stránky');

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