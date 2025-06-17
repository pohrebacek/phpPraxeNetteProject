<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostFacade;
use App\Module\Model\User\UserFacade;
use Nette;
use Nette\Application\UI\Form;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentFacade;
use App\Service\CurrentUserService;

/**
 * @method void postFormSucceeded(Form $form)
 */
final class EditPresenter extends BasePresenter
{
	public function __construct(
		private PostsRepository $postsRepository,
        private PostFacade $postFacade,
        private UserFacade $userFacade,
        private CommentFacade $commentFacade,
        private CurrentUserService $currentUser
	) {
	}

    public function startup(): void
    {
        parent::startup();
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }

 

    public function renderEdit(int $id): void   //stránka na upravení postu, id převezme ze šablony
    {
        $post = $this->postFacade->getPostDTO($id);
    
        if (!$post) {
            $this->error('Post not found');
        }
    
        $this->getComponent('postForm')
            ->setDefaults($post);
        
    }

    

    public function actionDelete(int $id): void
    {
        $post = $this->postFacade->getPostDTO($id);
        if (!$post) {
            $this->error('Post not found');
        }
        $this->postFacade->deletePost($id);
        $this->redirect('Homepage:');
    }


    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->addText('title', 'Titulek:')
            ->setRequired()
            ->setHtmlAttribute("class", "form-control");
        $form->addTextArea('content', 'Obsah:')
            ->setRequired()
            ->setHtmlAttribute("class", "form-control");
        $form->addUpload('image', 'Vyberte úvodní fotografii:')
            // Používáme vlastní validaci pro kontrolu MIME typu souboru
            ->setHtmlAttribute("class", "form-control")
            ->addRule(function ($item) {
                // Získáme MIME typ souboru
                $mimeType = mime_content_type($item->getValue()->getTemporaryFile());
                // Zkontrolujeme, zda je to obrázek
                return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
            }, 'Soubor musí být platný obrázek (JPG, PNG nebo GIF).');
    
            bdump($this->getUser()->getIdentity());
            if ($this->currentUser->hasPremiumAccess()) {
                bdump($this->getUser());
                $form->addCheckbox("premium", "Premium")
                    ->setHtmlAttribute("class", "form-check-input");
            }

        
        $form->addSubmit('send', 'Uložit a publikovat')
             ->setHtmlAttribute("class", "btn btn-outline-primary");
    
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
        $id = $this->getParameter('id');
        $data = (array) $form->getValues();
        $data["image"] = $this->getImageFromForm();
        $user = $this->getUser();
        $user = $this->userFacade->getUserDTO($user->id);
        $data["user_id"] = $user->id;
        bdump($data);
    
        if ($id) {
            $post = $this->postFacade->getPostDTO($id);
            $this->postsRepository->saveRow($data, $id);
    
        } else {
            $post = $this->postsRepository
                ->saveRow($data, $id);
        }
    
        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        if ($post){
            $this->redirect('Post:show', $post->id);
        }
        
    }
}
