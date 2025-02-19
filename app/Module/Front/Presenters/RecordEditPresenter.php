<?php
namespace App\Module\Front\Presenters;


use Nette;
use Exception;
use Nette\Application\AbortException;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Settings\SettingsRepository;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Like\LikesRepository;
use Nette\Application\UI\Form;
use App\Module\Model\User\UserFacade;
use App\Module\Model\Post\PostFacade;
use App\Module\Model\Comment\CommentFacade;
use App\Module\Model\Settings\SettingsFacade;
use App\Module\Model\Like\LikeFacade;

final class RecordEditPresenter extends BasePresenter   //jednotlivé formy jsou nadepsaná komentářem "POST/COMMENT... FORM"
{
    /** @var string */
	private string $templateIsAdd = "false";
    public function __construct(
        private UsersRepository $usersRepository,
        private PostsRepository $postsRepository,
        private CommentsRepository $commentsRepository,
        private LikesRepository $likeRepository,
        private SettingsRepository $settingsRepository,
        private UserFacade $userFacade,
        protected Nette\Security\Passwords $passwords,
        private PostFacade $postFacade,
        private CommentFacade $commentFacade,
        private LikeFacade $likeFacade,
        private SettingsFacade $settingsFacade
    ) {}

    public function renderAdd($dbName): void
    {
        $this->template->dbNames = [
            'posts' => "postForm",
            'comments' => "commentForm",
            'likes' => "likeForm",
            'users' => 'userForm',
            'settings' => 'settingsForm'
        ];
        $this->templateIsAdd = "true";
        // Simulace statusu (může být z databáze nebo jiného zdroje)
        $this->template->dbName = $dbName;

    }

    public function renderEdit($recordId, $dbName): void
    {
        $this->template->dbNames = [
            'posts' => "postForm",
            'comments' => "commentForm",
            'likes' => "likeForm",
            'users' => 'userForm',
            'settings' => 'settingsForm'
        ];
        $this->template->dbName = $dbName;
        

        switch ($dbName) {
            case "posts":
                $post = $this->postFacade->getPostDTO($recordId);
                if (!$post) {
                   $this->error('Post not found');
                }
                $this->getComponent('postForm')
                    ->setDefaults($post);
                break;

            case "comments":
                $comment = $this->commentFacade->getCommentDTO($recordId);
                if (!$comment) {
                   $this->error('Comment not found');
                }
                $this->getComponent('commentForm')
                    ->setDefaults($comment);
                break;

            case "likes":
                $like = $this->likeFacade->getLikeDTO($recordId);
                if (!$like) {
                   $this->error('Like not found');
                }
                $this->getComponent('likeForm')
                    ->setDefaults($like);
                break;

            case "settings":
                $settings = $this->settingsFacade->getSettingsDTO($recordId);
                if (!$settings) {
                   $this->error('Settings not found');
                }
                $this->getComponent('settingsForm')
                    ->setDefaults($settings);
                break;

            case "users":
                $user = $this->userFacade->getUserDTO($recordId);
                if (!$user) {
                   $this->error('User not found');
                }
                $this->getComponent('userForm')
                    ->setDefaults($user);
                break;
          }
    }



    //POST FORM
    protected function createComponentPostForm(): Form
    {
        $form = new Form;

        $form->addHidden('templateIsAdd', $this->templateIsAdd);

        $form->addText("user_id", "Id usera za kterého přidat post")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("type", "number");
        $form->addText('title', 'Titulek:')
            ->setRequired();
        $form->addTextArea('content', 'Obsah:')
            ->setRequired();
        $form->addUpload('image', 'Vyberte úvodní fotografii:')
            // Používáme vlastní validaci pro kontrolu MIME typu souboru
            ->addRule(function ($item) {
                // Získáme MIME typ souboru
                $mimeType = mime_content_type($item->getValue()->getTemporaryFile());
                // Zkontrolujeme, zda je to obrázek
                return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
            }, 'Soubor musí být platný obrázek (JPG, PNG nebo GIF).');
    
        $form->addSubmit('send', 'Uložit a publikovat');
    
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }

    public function getImageFromForm ()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Zkontroluj, zda byl soubor nahrán
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                
                // Cesta k dočasnému souboru
                $tempPath = $_FILES['image']['tmp_name'];
                
                // Cesta k cílovému umístění
                // Nastavíme název souboru (např. původní název nebo nové unikátní jméno)
                $uploadDir = './images';  // Cílová složka pro nahrané obrázky
                $targetFile = $uploadDir . "/" . basename($_FILES['image']['name']);  // Původní název souboru
        
                // Zkontroluj, zda soubor neexistuje (volitelně, pokud nechceme přepsat soubor)
                if (!file_exists($targetFile)) {
                    move_uploaded_file($tempPath, $targetFile);
                    bdump($tempPath);
                }
                return "http://www.localhost:9000/images/" . basename($_FILES['image']['name']);
            } else {
                return null;
            }
        }
    }

    /**
    * @param Form $form  //specifikuje jaký pole ta funkce přijímá
    */
    public function postFormSucceeded(Form $form): void
    {
        try 
        {
            $data = (array) $form->getValues();
            $data["image"] = $this->getImageFromForm();
        
            if ($data["templateIsAdd"] == "false") {
                $recordId = $_GET['recordId'];
                unset($data["templateIsAdd"]);
                $this->postsRepository->saveRow($data, $recordId);
        
            } else {
                unset($data["templateIsAdd"]);
                $post = $this->postsRepository
                    ->saveRow($data, null);
            }
              
            $this->redirect("Admin:database", $this->postsRepository->getTable());
        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("Admin:database", $this->postsRepository->getTable());
        } catch (Exception $e) {
            $form->addError("Zadejte platné údaje");
        }

        
        
    }



    //COMMENT FORM
    protected function createComponentCommentForm(): Form
	{
		$form = new Form;

		$form->addHidden('templateIsAdd', $this->templateIsAdd);	//přidá do formu skrytou vlastnost, to protože to jinak nešlo předat to info
	
		$form->addText("post_id", "Id postu kterému přidat comment")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("type", "number");
        $form->addText("ownerUser_id", "Id usera za kterého napsat comment")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("type", "number");
        $form->addTextArea('content', 'Komentář:')
			->setRequired();
	
		$form->addSubmit('send', 'Publikovat komentář');
	
		$form->onSuccess[] = function (Form $form) {
			$data = $form->getValues();
			$this->commentFormSucceeded($data, $form);
		};
		return $form;
	}


	/**
	 * @param \stdClass $data
	 */
	public function commentFormSucceeded(\stdClass $data, Form $form): void    //stdClass je vlastně že metodě říkáš že pracuješ s objektem ale nechceš pro něj definovat třídu
    {
        try 
        {
            bdump($data->templateIsAdd);

		    $user = $this->usersRepository->getRowById($data->ownerUser_id);
            if ($user == null){
                throw new Exception;
            }
            bdump($user);
		

		
		
		    if($data->templateIsAdd == "false") {
                $recordId = $_GET['recordId'];
		    	unset($data->templateIsAdd);	//tady se smaže to hidden vlastnost aby později nedělala bordel
		    	$this->commentsRepository->saveRow((array)$data, $recordId);
		    	$comment = $this->commentFacade->getCommentDTO($recordId);
		    }
            else {
                $comment = $this->commentsRepository
                    ->saveRow([
		    		    "post_id" => $data->post_id,
		    		    "name" => $user->username,
		    		    "email" => $user->email,
		    		    "content" => $data->content,
		    		    "ownerUser_id" => $data->ownerUser_id
		    	    ], null);
		        bdump($comment);
            }

		    if ($comment){
		    	$this->flashMessage("Děkuji za komentář", "success");
            	$this->redirect("Admin:database", $this->commentsRepository->getTable());
		    }
        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("Admin:database", $this->commentsRepository->getTable());
        } catch (Exception $e) {
            $form->addError("Zadejte platné údaje");
        }
        
        
    	
	}



    //LIKE FORM
    protected function createComponentLikeForm(): Form
    {
        $form = new Form;
        $form->addHidden('templateIsAdd', $this->templateIsAdd);
        $form->addText("post_id", "Id postu kterému dát like")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("type", "number");
        $form->addText("user_id", "Id usera za kterého chcete dát like")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("type", "number");
        $form->addSubmit('submit','Přidat záznam');

        $form->onSuccess[] = [$this, 'likeFormSucceeded'];
        bdump("S");

        return $form;
    }

    public function likeFormSucceeded(Form $form): void
    {
        try {
            $data = $form->getValues();
            if ($data->templateIsAdd == "false") {
                $recordId = $_GET['recordId'];
                unset($data->templateIsAdd);
                $this->likeRepository->saveRow((array) $data, $recordId);
            } else {
                unset($data->templateIsAdd);
                bdump($data);
                $this->likeRepository->saveRow((array) $data, null);
            }
            $this->redirect("Admin:database", $this->likeRepository->getTable());
        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("Admin:database", $this->likeRepository->getTable());
        } catch (Exception $e) {
            $form->addError("Zadejte platné údaje");
        }
        
    }

    //USER FORM
    protected function createComponentUserForm(): Form
    {
        $form = new Form;
        $form->addHidden('templateIsAdd', $this->templateIsAdd);
        $form->addText('username', "Uživatelské jméno:")
            ->setRequired('Prosím vyplňte své uživatelské jméno.');
        
        $form->addEmail('email', 'Emailová adresa:')
            ->setRequired('Prosím, vyplňte svou emailovou adresu.');

        $form->addPassword('password','Heslo:')
            ->setRequired('Prosím, zvolte si své heslo.');
        
        $form->addPassword('passwordCheck', "Heslo znovu: ")
            ->setRequired('Prosím, vyplňte své heslo znovu.');

        $form->addText('role', 'Role nového uživatele');

        $form->addSubmit('send', 'Zaregistrovat se');

        $form->onSuccess[] = [$this, 'userFormSucceeded'];
        return $form;
    }

    public function userFormSucceeded(Form $form): void
    {
        try {
            $data = $form->getValues();
            $roles = ['user', 'admin'];
            if (in_array($data->role, $roles)) {
                bdump($data);
                $data->password = $this->passwords->hash($data->password);
                $foundUserByName = $this->usersRepository->getRowByUsername($data->username);
                $foundUserByEmail = $this->usersRepository->getRowByEmail($data->email);

                if ($data->templateIsAdd == "false") {
                    $recordId = $_GET['recordId'];  //NIKDE JINDE NEPOUŽÍVAT TADY V TOM FORMU UŽ
                    if (($foundUserByName && $foundUserByName->id != $recordId) || ($foundUserByEmail && $foundUserByEmail->id != $recordId)) {
                        $form->addError('Tento účet již existuje');
                    }
                    elseif (!$this->passwords->verify($data->passwordCheck, $data->password)) { //funkce verify zkontroluje hash a zadaný heslo, samotná funkce hash totiž udělá jinej hash i ze stejných slov
                        bdump($data);
                        $form->addError('Vámi zadaná hesla musí být stejná');
                    } else {
                        unset($data->passwordCheck);
                        unset($data->templateIsAdd);
                        $this->usersRepository->saveRow((array) $data, $recordId);
                    }
                } else {
                    if ($foundUserByName || $foundUserByEmail) {
                        $form->addError('Tento účet již existuje');
                    }
                    elseif (!$this->passwords->verify($data->passwordCheck, $data->password)) { //funkce verify zkontroluje hash a zadaný heslo, samotná funkce hash totiž udělá jinej hash i ze stejných slov
                        bdump($data);
                        $form->addError('Vámi zadaná hesla musí být stejná');
                    } else {
                        unset($data->passwordCheck);
                        unset($data->templateIsAdd);
                        bdump($data);
                        $this->usersRepository->saveRow((array) $data, null);
                        $this->redirect("Admin:database", $this->usersRepository->getTable());
                        
                    }
                }

            
            } else {
                $form->addError('Zadejte platnou roli');
            }
        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("Admin:database", $this->usersRepository->getTable());
        } catch (Exception $e) {
            $form->addError("Zadejte platné údaje");
        }

    }

    //SETTINGS FORM
    protected function createComponentSettingsForm(): Form
    {
        echo ("Slouží pouze pro přidání do databáze, samotné nastavení se spravuje v kódu");
        $form = new Form;
        $form->addHidden('templateIsAdd', $this->templateIsAdd);
        $form->addText("param", "Parametr")
             ->setRequired("Toto pole je povinné");
        $form->addText("value", "Hodnota parametru")
             ->setRequired("Toto pole je povinné");
        $form->addSubmit('submit','Přidat záznam');

        $form->onSuccess[] = [$this, 'settingsFormSucceeded'];

        return $form;
    }

    public function settingsFormSucceeded(Form $form): void
    {
        try {
            $data = $form->getValues();
            if ($data->templateIsAdd == "false") {
                $recordId = $_GET['recordId'];
                unset($data->templateIsAdd);
                $this->settingsRepository->saveRow((array) $data, $recordId);
            } else {
                bdump($data);
                unset($data->templateIsAdd);
                $this->settingsRepository->saveRow((array) $data, null);
            }
            $this->redirect("Admin:database", $this->settingsRepository->getTable());

        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("Admin:database", $this->settingsRepository->getTable());
        } catch (Exception $e) {
            $form->addError("Zadejte platné údaje");
        }
        
    }
}