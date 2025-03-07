<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostDTO;
use Nette;
use Nette\Application\UI\Form;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Post\PostFacade;
use App\Module\Model\Comment\CommentFacade;
use App\Module\Model\Security\MyAuthorizator;
use App\Module\Model\User\UserFacade;
use App\Module\Model\Like\LikesRepository;

/**
 * @method void commentFormSucceeded(\stdClass $data)
 */
final class PostPresenter extends BasePresenter
{


	/** @var string */
	private string $templateIsShow = "false";	//mam to jako vlastnost protože jinak to jako k samotný variable nemá v ostatních funkcích přístup
	public function __construct(
		private CommentsRepository $commentsRepository,
		private PostsRepository $postsRepository,
		private PostFacade $postFacade,
		private CommentFacade $commentFacade,
		private UserFacade $userFacade,
		private LikesRepository $likesRepository
	) {
	}

	

	public function renderShow(int $id): void
	{
		//NA DEBUG
		$user = $this->getUser();
		if ($user->isLoggedIn()){
			$user = $this->userFacade->getUserDTO($user->id);

		}
		bdump($user);

		$this->templateIsShow = "true";
		$post = $this->postsRepository
			->getRowById($id);
		if (!$post) {
			$this->error("Stránka nebyla nalezena");
		}
		$postDTO = $this->postFacade->getPostDTO($post->id);
		bdump($postDTO);	//bdump sám vypíše atributy a co je co
		$this->template->post = $post;
		if ($postDTO->image){
			$this->template->imagePath = $postDTO->image;
		} else {
			$this->template->imagePath = null;
		}
		$this->template->comments = $post->related('comments')->order('created_at');	//related prostě zjistí jaký záznamy z uvedený tabulky jsou vázaný na záznam co funcki volá 
		$this->template->likesCount = $post->related('likes')->count();
		
		//NA DEBUG
		foreach ($this->template->comments as $comment) {
			bdump($this->commentFacade->getCommentDTO($comment->id));
		}
	}

	public function actionDeleteComment(int $id): void
    {
        $comment = $this->commentFacade->getCommentDTO($id);
        if (!$comment) {
            $this->error('Comment not found');
        }
        $this->commentsRepository->deleteRow($id);
        $this->redirect('Post:show', $comment->post_id);
    }

	public function handleLike(): void
	{
		$data = [];
		$data["post_id"] = is_numeric($this->getParameter("id")) ? intval($this->getParameter("id")) : 0;
		$data["user_id"] = is_numeric(($this->getUser())->id) ? intval(($this->getUser())->id) : 0;

		if (!$this->likesRepository->getRowByPostIdAndUserId($data["post_id"], $data["user_id"]))
		{
			$this->likesRepository->saveRow($data, null);
		} else {
			$this->likesRepository->deleteLikeByPostIdAndUserId($data["post_id"], $data["user_id"]);
		}
		$this->redirect("Post:show", $data["post_id"]);
		
	}


	
	public function renderEditComment(int $id): void
    {
		bdump($this->templateIsShow);
        $comment = $this->commentFacade->getCommentDTO($id);
        if (!$comment) {
            $this->error('Comment not found');
        }
        $this->getComponent('commentForm')->setDefaults($comment);
    }

	protected function createComponentCommentForm(): Form
	{
		$form = new Form;

		$form->addHidden('templateIsShow', $this->templateIsShow);	//přidá do formu skrytou vlastnost, to protože to jinak nešlo předat to info
	
		$form->addTextArea('content')
			->setRequired()
			->setHtmlAttribute("class", "form-control");
	
		$form->addSubmit('send', 'Publikovat komentář')
			 ->setHtmlAttribute("class", "btn btn-outline-primary");
	
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

		$user = $this->getUser();
		$user = $this->userFacade->getUserDTO($user->id);
		

		
		if ($id) {
			if($data->templateIsShow == "false") {
				unset($data->templateIsShow);	//tady se smaže to hidden vlastnost aby později nedělala bordel
				$this->commentsRepository->saveRow((array)$data, $id);
				$edit = true;
				$comment = $this->commentFacade->getCommentDTO($id);
			}
        	else {
            $comment = $this->commentsRepository
                ->saveRow([
					"post_id" => $id,
					"name" => $user->username,
					"email" =>$user->email,
					"content" => $data->content,
					"ownerUser_id" => $user->id
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