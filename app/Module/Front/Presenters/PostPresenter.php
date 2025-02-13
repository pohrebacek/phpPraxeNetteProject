<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostDTO;
use Nette;
use Nette\Application\UI\Form;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;

/**
 * @method void commentFormSucceeded(\stdClass $data)
 */
final class PostPresenter extends Nette\Application\UI\Presenter
{
	/** @var string */
	private string $templateIsShow = "false";	//mam to jako vlastnost protože jinak to jako k samotný variable nemá v ostatních funkcích přístup
	public function __construct(
		private CommentsRepository $commentsRepository,
		private PostsRepository $postsRepository,
	) {
	}

	public function renderShow(int $id): void
	{
		$this->templateIsShow = "true";
		$post = $this->postsRepository
			->getRowById($id);
		if (!$post) {
			$this->error("Stránka nebyla nalezena");
		}
		$postDTO = $this->postsRepository->postMapper->map($post);
		bdump($postDTO);	//bdump sám vypíše atributy a co je co
		$this->template->post = $post;
		$this->template->comments = $post->related('comments')->order('created_at');	//related prostě zjistí jaký záznamy z uvedený tabulky jsou vázaný na záznam co funcki volá 
		foreach ($this->template->comments as $comment) {
			bdump($this->commentsRepository->commentMapper->map($comment));
		}
	}

	public function actionDeleteComment(int $id): void
    {
        $comment = $this->commentsRepository->getRowById($id);
        if (!$comment) {
            $this->error('Comment not found');
        }
		$postID = $comment->post_id;
        $this->commentsRepository->deleteRow($id);
        $this->redirect('Post:show', $postID);
    }


	
	public function renderEditComment(int $id): void
    {
		bdump($this->templateIsShow);
        $comment = $this->commentsRepository->getRowById($id);
        if (!$comment) {
            $this->error('Comment not found');
        }
        $this->getComponent('commentForm')->setDefaults($comment->toArray());
    }

	protected function createComponentCommentForm(): Form
	{
		$form = new Form;

		$form->addHidden('templateIsShow', $this->templateIsShow);	//přidá do formu skrytou vlastnost, to protože to jinak nešlo předat to info
	
		$form->addText('name', 'Jméno:')
			->setRequired();
	
		$form->addEmail('email', 'E-mail:');
	
		$form->addTextArea('content', 'Komentář:')
			->setRequired();
	
		$form->addSubmit('send', 'Publikovat komentář');
	
		$form->onSuccess[] = function (Form $form) {
			$data = $form->getValues();
			$this->commentFormSucceeded($data);
		};
		return $form;
	}


	/**
	 * @param \stdClass $data
	 */
	public function commentFormSucceeded(\stdClass $data): void    //stdClass je vlastně že metodě říkáš že pracuješ s objektem ale nechceš pro něj definovat třídu
    {
        $id = $this->getParameter("id");	//id commentu, vyřešený problém: když má post a comment stejný id a na tom postu dat add comment, tak se přidá na post co má id jako post_id commentu co má stejný id
        $edit = false;
		bdump($data->templateIsShow);
		

		
		if ($id) {
			if($data->templateIsShow == "false") {
				unset($data->templateIsShow);	//tady se smaže to hidden vlastnost aby později nedělala bordel
				$this->commentsRepository->saveRow((array)$data, $id);
				$edit = true;
				$comment = $this->commentsRepository->getRowById($id);
			}
        	else {
            $comment = $this->commentsRepository
                ->saveRow([
					"post_id" => $id,
					"name" => $data->name,
					"email" =>$data->email,
					"content" => $data->content,
				], null);
			bdump($comment);
        	}

			if ($comment){
				$this->flashMessage("Děkuji za komentář", "success");
				if ($edit){
					$this->redirect("Post:show", $comment->post_id);
				}
        		$this->redirect("Post:show", $id);
			}
        
    	}
	}
}