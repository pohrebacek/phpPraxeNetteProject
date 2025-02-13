<?php
namespace App\Module\Front\Presenters;

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
	private string $templateIsShow = "false";
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
		$postTitle = $this->postsRepository->postMapper->map($post);
		$postTitle = $postTitle->title;
		bdump("". $postTitle ."");
		$this->template->post = $post;
		$this->template->comments = $post->related('comments')->order('created_at');
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
        $comment = $this->commentsRepository->getRowById($id);
        if (!$comment) {
            $this->error('Comment not found');
        }
        $this->getComponent('commentForm')->setDefaults($comment->toArray());
    }

	protected function createComponentCommentForm(): Form
	{
		$form = new Form;

		$form->addHidden('templateIsShow', $this->templateIsShow);
	
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
				unset($data->templateIsShow);
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