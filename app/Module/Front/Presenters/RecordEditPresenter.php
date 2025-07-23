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
use App\Module\Model\LikeComment\LikeCommentFacade;

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
        private SettingsFacade $settingsFacade,
        private LikeCommentFacade $likeCommentFacade
    ) {}

    public function renderAdd($dbName): void
    {
        $this->template->dbNames = [
            'posts' => "postForm",
            'comments' => "commentForm",
            'likes' => "likeForm",
            'users' => 'userForm'
        ];
        $this->templateIsAdd = "true";
        $this->template->dbName = $dbName;

    }

    public function renderChangePassword($userId, $dbName){
        $this->template->userId = $userId;
        $this->template->dbName = $dbName;

    }


    public function createComponentChangePassword(): Form 
    {
        $form = new Form;
        $form->addPassword("password", "Nové Heslo:")
             ->setRequired("Prosím zvolte nové heslo.")
             ->setHtmlAttribute("class", "form-control");
        $form->addPassword("passwordCheck", "Zadejte nové heslo znovu: ")
             ->setRequired("Zadejte nové heslo znovu")
             ->setHtmlAttribute("class", "form-control");
        $form->addSubmit("send", "Změnit heslo")
             ->setHtmlAttribute("class","btn btn-outline-primary");
        
        $form->onSuccess[] = [$this, "changePasswordFormSucceeded"];
        return $form;
    } 


    public function changePasswordFormSucceeded(Form $form)
    {
        $data = $form->getValues();
        $data->password = $this->passwords->hash($data->password);
        if (!$this->passwords->verify($data->passwordCheck, $data->password)) {
            $this->flashMessage("Hesla nejsou stejná", "danger");
        } else {
            $this->flashMessage("Hesla úspěšně změněna", "success");
            unset($data->passwordCheck);
            $this->usersRepository->saveRow((array) $data, $_GET["userId"]);
            $this->redirect("AdminDb:users");
        }
    }

    public function renderEdit($recordId, $dbName): void
    {
        $this->template->dbNames = [
            'posts' => "postForm",
            'comments' => "commentForm",
            'users' => 'userForm'
        ];
        $this->template->dbName = $dbName;
        $this->template->isCommentReply = null;
        

        switch ($dbName) {
            case "posts":
                $post = $this->postFacade->getPostDTO($recordId);
                $postArray = get_object_vars($post);
                $postArray["username"] = $this->postFacade->getOwnerUserName($post);
                bdump($postArray);
                if (!$post) {
                   $this->error('Post not found');
                }
                $this->getComponent('postForm')
                    ->setDefaults($postArray);
                break;

            case "comments":
                $comment = $this->commentFacade->getCommentDTO($recordId);
                if (!$comment) {
                   $this->error('Comment not found');
                } 
                $this->template->replyToId = $comment->replyTo ?? null; //Pokud $comment->replyTo existuje, uloží ho. Pokud neexistuje (je null), uloží null
                $this->template->replyToPreview = $comment->replyTo //Pokud $comment->replyTo není null, zavolá getReplyToPreview() a uloží výsledek. Pokud je null, uloží jen null.
                    ? $this->commentFacade->getReplyToPreview($comment->replyTo)
                    : null;
                $this->template->isCommentReply = $comment->replyTo; 
                bdump($comment->replyTo);  
                $this->getComponent('commentForm')
                    ->setDefaults($comment);
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

        $form->addText("username", "Jméno uživatele za kterého přidat post: ")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("class", "form-control");
        $form->addText('title', 'Titulek: ')
            ->setRequired()
            ->setHtmlAttribute("class", "form-control");
        $form->addTextArea('content', 'Obsah: ')
            ->setRequired()
            ->setHtmlAttribute("class", "form-control");
        $form->addUpload('image', 'Vyberte úvodní fotografii: ')
            // Používáme vlastní validaci pro kontrolu MIME typu souboru
            ->setHtmlAttribute("class", "form-control")
            ->addRule(function ($item) {
                // Získáme MIME typ souboru
                $mimeType = mime_content_type($item->getValue()->getTemporaryFile());
                // Zkontrolujeme, zda je to obrázek
                return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
            }, 'Soubor musí být platný obrázek (JPG, PNG nebo GIF).');

        $form->onAnchor[] = function (Form $form) {
            $values = $form->getValues('array');
            if ($values['templateIsAdd'] == 'true') {  //nešla podmínka $this->templateIsAdd == "true" protože to bralo ten form a ne vlastnost ig    
                $form->addSubmit('send', 'Přidat záznam')
                     ->setHtmlAttribute("class", "btn btn-outline-primary");
            } else {
                $form->addSubmit('send', 'Uložit záznam')
                     ->setHtmlAttribute("class", "btn btn-outline-primary");
            }
        };
    
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
            bdump($data);
        
            if ($data["templateIsAdd"] == "false") {    //záznam se mění
                $recordId = $_GET['recordId'];
                
                unset($data["templateIsAdd"]);
                $data["user_id"] = ($this->usersRepository->getRowByUsername($data["username"]))->id;
                unset($data["username"]);
                $this->postsRepository->saveRow($data, $recordId);
        
            } else {
                $data["user_id"] = ($this->usersRepository->getRowByUsername($data["username"]))->id;
                
                unset($data["username"]);
                unset($data["templateIsAdd"]);
                $post = $this->postsRepository
                    ->saveRow($data, null);
            }
              
            $this->redirect("AdminDb:posts");
        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("AdminDb:posts");
        } catch (Exception $e) {
            bdump($e);
            $this->flashMessage("Zadejte platné údaje", "danger");
        }

        
        
    }



    //COMMENT FORM
    protected function createComponentCommentForm(): Form
	{
		$form = new Form;

		$form->addHidden('templateIsAdd', $this->templateIsAdd);	//přidá do formu skrytou vlastnost, to protože to jinak nešlo předat to info
	
		$form->addText("post_id", "Id postu kterému přidat comment")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("type", "number")
             ->setHtmlAttribute("class", "form-control");
        $form->addText("name", "Jméno uživatele za kterého napsat comment")
             ->setRequired("Toto pole je povinné")
             ->setHtmlAttribute("class", "form-control");
        $form->addTextArea('content', 'Komentář:')
			->setRequired()
            ->setHtmlAttribute("class", "form-control");

        $form->onAnchor[] = function (Form $form) {
            $values = $form->getValues('array');
            if ($values['templateIsAdd'] == 'true') {  //nešla podmínka $this->templateIsAdd == "true" protože to bralo ten form a ne vlastnost ig    
                $form->addSubmit('send', 'Přidat záznam')
                     ->setHtmlAttribute("class", "btn btn-outline-primary");
            } else {
                $form->addSubmit('send', 'Uložit záznam')
                     ->setHtmlAttribute("class", "btn btn-outline-primary");
             }
            };
	
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

		    $user = $this->usersRepository->getRowByUsername($data->name);
            $user = $this->userFacade->getUserDTO($user->id);
            if ($user == null){
                throw new Exception;
            }
            bdump($user);
		

		
		
		    if($data->templateIsAdd == "false") {
                $recordId = $_GET['recordId'];
                
		    	unset($data->templateIsAdd);	//tady se smaže to hidden vlastnost aby později nedělala bordel
		    	$data->ownerUser_id = $user->id;
                $data->email = $user->email;
                $this->commentsRepository->saveRow((array)$data, $recordId);
		    	$comment = $this->commentFacade->getCommentDTO($recordId);
		    }
            else {
                $data->ownerUser_id = ($this->usersRepository->getRowByUsername($data->ownerUser_id))->id;
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
            	$this->redirect("AdminDb:comments");
		    }
        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("AdminDb:comments");
        } catch (Exception $e) {
            bdump($e);
            $this->flashMessage("Zadejte platné údaje", "danger");
        }
        
        
    	
	}





    //USER FORM
    protected function createComponentUserForm(): Form
    {
        $form = new Form;
        $form->addHidden('templateIsAdd', $this->templateIsAdd);
        $form->addText('username', "Uživatelské jméno:")
            ->setRequired('Prosím vyplňte své uživatelské jméno.')
            ->setHtmlAttribute("class", "form-control");
        
        $form->addEmail('email', 'Emailová adresa:')
            ->setRequired('Prosím, vyplňte svou emailovou adresu.')
            ->setHtmlAttribute("class", "form-control");

        $form->addSelect('role', 'Role nového uživatele', [
                'admin' => 'Administrátor',
                'user' => 'Uživatel'
            ])
            ->setDefaultValue("user")
            ->setHtmlAttribute("class", "form-control");
        

        bdump($this->templateIsAdd);
        $form->onAnchor[] = function (Form $form) {
            $values = $form->getValues('array');
            if ($values['templateIsAdd'] === 'true') {  //nešla podmínka $this->templateIsAdd == "true" protože to bralo ten form a ne vlastnost ig
                $form->addPassword('password', 'Heslo:')
                    ->setRequired('Prosím, zvolte si své heslo.')
                    ->setHtmlAttribute("class", "form-control");
    
                $form->addPassword('passwordCheck', "Heslo znovu:")
                    ->setRequired('Prosím, vyplňte své heslo znovu.')
                    ->setHtmlAttribute("class", "form-control");

                $form->addSubmit('send', 'Přidat záznam')
                    ->setHtmlAttribute("class", "btn btn-outline-primary");
            } else {
                $form->addSubmit('send', 'Uložit záznam')
                     ->setHtmlAttribute("class", "btn btn-outline-primary");
                $form->addButton('changePassword', 'Změnit heslo uživatele')
                     ->setHtmlAttribute('class', 'btn btn-outline-primary')
                     ->setHtmlAttribute('onclick', "window.location.href='" . $this->link('RecordEdit:changePassword', $_GET['recordId'], $_GET['dbName']) . "'");
            }
        };




        $form->onSuccess[] = [$this, 'userFormSucceeded'];
        return $form;
    }

    public function userFormSucceeded(Form $form): void
    {
        try {
            $data = $form->getValues();
            unset($data->changePassword);
            bdump($data);            
            $foundUserByName = $this->usersRepository->getRowByUsername($data->username);
            $foundUserByEmail = $this->usersRepository->getRowByEmail($data->email);

            if ($data->templateIsAdd == "false") {
                $recordId = $_GET['recordId'];  //NIKDE JINDE NEPOUŽÍVAT TADY V TOM FORMU UŽ
                if (($foundUserByName && $foundUserByName->id != $recordId) || ($foundUserByEmail && $foundUserByEmail->id != $recordId)) {
                    $this->flashMessage('Tento účet již existuje', "danger");
                } else {
                    
                    unset($data->templateIsAdd);
                    $this->usersRepository->saveRow((array) $data, $recordId);
                    $this->redirect("AdminDb:users");
                }
            } else {
                $data->password = $this->passwords->hash($data->password);
                if ($foundUserByName || $foundUserByEmail) {
                    $this->flashMessage('Tento účet již existuje', "danger");
                }
                elseif (!$this->passwords->verify($data->passwordCheck, $data->password)) { //funkce verify zkontroluje hash a zadaný heslo, samotná funkce hash totiž udělá jinej hash i ze stejných slov
                    bdump($data);
                    $this->flashMessage('Vámi zadaná hesla musí být stejná', "danger");
                } else {
                    
                    unset($data->passwordCheck);
                    unset($data->templateIsAdd);
                    bdump($data);
                    $this->usersRepository->saveRow((array) $data, null);
                    $this->redirect("AdminDb:users");        
                }
            }

        } catch (AbortException $e) {   //bez tohohle to bralo exception i když vše bylo ok
            $this->redirect("AdminDb:users");
        } catch (Exception $e) {
            $this->flashMessage("Zadejte platné údaje", "danger");
        }

    }



}