<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\PostCommentFacade;
use Nette;
use Nette\Application\UI\Form;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;

/**
 * @method void postFormSucceeded(Form $form)
 */
final class EditPresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private PostsRepository $postsRepository,
        private PostCommentFacade $postCommentFacade,
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
        $post = $this->postsRepository
            ->getRowById($id);
    
        if (!$post) {
            $this->error('Post not found');
        }
    
        $this->getComponent('postForm')
            ->setDefaults($post->toArray());
        
    }

    

    public function actionDelete(int $id): void
    {
        $post = $this->postsRepository->getRowById($id);
        if (!$post) {
            $this->error('Post not found');
        }
        $this->postCommentFacade->deletePost($id);
        $this->redirect('Homepage:');
    }


    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->addText('title', 'Titulek:')
            ->setRequired();
        $form->addTextArea('content', 'Obsah:')
            ->setRequired();
    
        $form->addSubmit('send', 'Uložit a publikovat');
    
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }


    /**
    * @param Form $form  //specifikuje jaký pole ta funkce přijímá
    */
    public function postFormSucceeded(Form $form): void
    {
        $id = $this->getParameter('id');
        $data = (array) $form->getValues();
    
        if ($id) {
            $post = $this->postsRepository
                ->getRowById($id);
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
