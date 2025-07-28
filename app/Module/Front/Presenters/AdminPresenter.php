<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostsRepository;
use App\Module\Model\ExternalPost\ExternalPostsRepository;
use App\Module\Model\User\UsersRepository;
use Nette\Application\UI\Form;
use Nette;
use Nette\Caching\Cache;
use App\Module\Model\Settings\SettingsFacade;
use App\Module\Model\Settings\SettingsRepository;
use App\Module\Model\Base\BaseRepository;
use App\Module\Model\ExternalPost\ExternalPostDTO;
use App\Service\CurrentUserService;

final class AdminPresenter extends BasePresenter{
    public function __construct(
        private PostsRepository $postsRepository,
        private SettingsFacade $settingsFacade,
        private SettingsRepository $settingsRepository,
        protected Nette\Database\Explorer $database,
        private UsersRepository $usersRepository,
        private ExternalPostsRepository $externalPostsRepository,
        private Nette\Caching\Cache $blogFeedCache,
        private CurrentUserService $currentUser,
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
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }

    }

    public function downloadFeedData(string $url)
    {
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        $context = stream_context_create($contextOptions);
        return file_get_contents($url, false, $context);
    }

    public function handleGeneratePost(): void
    {
        if (!$this->currentUser->isAdmin()) {
            $this->flashMessage("Na tuto adresu nemáte přístup!", "danger");
            $this->redirect("Homepage:");
        }
        $url = "https://ancient-literature.com/category/blog/feed/";

        $xml = $this->blogFeedCache->load($url, function() use ($url){    //koukne jestli v cache je něco se zadanym klíčem, jestli ne, pustí se funkce co vrácenou hodnotu dop cahce uloží
            bdump("soubor stažen protože nebyl v cache");
            return $this->downloadFeedData($url);
        }, [Cache::Expire => "60 minutes"]);

        $xml = simplexml_load_string($xml);

        $items = [];

        foreach ($xml->channel->item as $item) {
            bdump($item->guid);
            $items[] = $item;
        }

        usort($items, function ($a, $b) {
	    	$timeA = strtotime((string)$a->pubDate);    //převede údaj na timestamp pro lepší práci s časem
	    	$timeB = strtotime((string)$b->pubDate);
	    	return $timeB <=> $timeA; //SESTUPNĚ
	    });

        $newPost = $items[0];
        foreach($items as $item) {
            if (!$this->externalPostsRepository->getExternalPostByGuid($item->guid)) {
                $newPost = $item;
                bdump($newPost);
                break;
            }
        }

        $postData = [];
        $postData["title"] = $newPost->title;

        $namespaces = $newPost->getNamespaces(true);
        $contentEncoded = $newPost->children($namespaces['content'])->encoded;
        $postData["content"] = (string) $contentEncoded;



        if ($newPost->image) {
            $postData["image"] = $newPost->image;
        }
        $postData["user_id"] = $this->currentUser->getId();

        $newPostRow = $this->postsRepository->saveRow($postData, null);
        $this->externalPostsRepository->saveRow([
            "guid" => (string)$newPost->guid,
            "post_id" => $newPostRow->id
        ], null);

        $this->flashMessage("Příspěvek byl úspěšně vygenerován", "success");

    }

    public function actionDelete($recordId, $dbName): void
    {
        bdump($recordId, $dbName);
        if ($dbName == "users" && $this->currentUser->getId() == $recordId) {
            $this->flashMessage("Nemůžete smazat svůj účet!", "danger");
            $this->redirect("AdminDb:".$dbName);
        }
        $this->database->table($dbName)->get($recordId)->delete();
        $this->flashMessage("Záznam byl smazán");
        $this->redirect("AdminDb:".$dbName);
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
        $form->addText('charsForNonPremium', 'Počet zobrazovaných znaků u premium příspěvků pro nepředplatitele')
            ->setRequired('Toto pole je povinné.')
            ->setHtmlAttribute('type', 'number')
            ->setHtmlAttribute("class", "form-control")
            ->setDefaultValue($this->settingsParam["charsForNonPremium"]);

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
            $this->flashMessage("Zadejte platný počet příspěvků", "danger");
        } elseif ($data->charsForNonPremium < 1) {
            $this->flashMessage("Zadejte platný počet znaků", "danger");
        }
        else {
            $this->settingsRepository->saveSettings($data);
            $this->flashMessage("Nastavení bylo úspěšně uloženo", "success");
        }
    }
}