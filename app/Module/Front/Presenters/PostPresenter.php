<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\Post\PostDTO;
use Nette;
use Nette\Application\UI\Form;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Post\PostFacade;
use App\Module\Model\Comment\CommentFacade;
use App\Module\Model\Security\MyAuthorizator;
use App\Module\Model\User\UserFacade;
use App\Module\Model\Like\LikesRepository;
use App\Module\Model\Like\LikeFacade;
use App\Module\Model\Settings\SettingsRepository;
use App\Service\CurrentUserService;
use App\Module\Model\LikeComment\LikesCommentsRepository;
use App\Module\Model\LikeComment\LikeCommentFacade;

/**
 * @method void commentFormSucceeded(\stdClass $data)
 */
final class PostPresenter extends BasePresenter
{


	/** @var string */
	private string $templateIsShow = "false";	//mam to jako vlastnost protože jinak to jako k samotný variable nemá v ostatních funkcích přístup
	private string $showReplyCommentForm = "false";
	private ?int $replyCommentId = null;	//ten ? říká že to je nullable int (int co může být int nebo null a začíná jako null)
	public function __construct(
		private CommentsRepository $commentsRepository,
		private PostsRepository $postsRepository,
		private PostFacade $postFacade,
		private CommentFacade $commentFacade,
		private UserFacade $userFacade,
		private LikesRepository $likesRepository,
		private CurrentUserService $currentUser,
		private LikesCommentsRepository $likesCommentsRepository,
		private SettingsRepository $settingsRepository,
		private UsersRepository $usersRepository,
		private LikeFacade $likeFacade,
		private LikeCommentFacade $likeCommentFacade,
        private int $charsForNonPremium = 300
	) {
	}

	public function startup(): void
	{
		parent::startup();
		$this->charsForNonPremium = (int) ($this->settingsRepository->getRowByKey("charsForNonPremium"))->value;
		bdump($this->charsForNonPremium);
	}
	

	public function renderShow(int $id): void
	{
		bdump($this->currentUser->hasPremiumAccess());
		bdump($this->replyCommentId);
		//NA DEBUG
		$user = $this->getUser();
		if ($user->isLoggedIn()){
			$user = $this->userFacade->getUserDTO($user->id);

		}
		bdump($user);

		$this->templateIsShow = "true";
		$this->template->showReplyCommentForm = $this->showReplyCommentForm;
		$this->template->replyCommentId = $this->replyCommentId;

		$post = $this->postsRepository
			->getRowById($id);
		if (!$post) {
			$this->error("Stránka nebyla nalezena");
		}
		$postDTO = $this->postFacade->getPostDTO($post->id);
		bdump($postDTO);	//bdump sám vypíše atributy a co je co
		$this->template->premium = false;

		bdump($postDTO->premium && !$this->currentUser->hasPremiumAccess());
		//if (($postDTO->premium && !$this->currentUser->hasPremiumAccess()) || !$this->userFacade->isOwnerOfPost($postDTO, $user->id) && !$this->currentUser->isAdmin()) {	//post je buď premium a user nemá premium, nebo uživatel neni owner postu a zároveň neni amdin
		if ($postDTO->premium && (!$this->currentUser->hasPremiumAccess() || !$this->userFacade->isOwnerOfPost($postDTO, $user->id) && !$this->currentUser->isAdmin())) {	//post je premium a zároveň user nemá premium, nebo uživatel neni owner postu a zároveň neni amdin
			bdump("preview");
			$this->template->premium = true;	//pak se nastaví že post je premium pro template
			$this->template->postContent = $this->postFacade->getPreview($postDTO, $this->charsForNonPremium);	//a nastaví se pouze preview verze postu
		}

		$this->template->post = $postDTO;
		$this->template->postAuthor = $this->postFacade->getOwnerUserName($postDTO);
		$this->template->imagePath = $this->postFacade->getImagePath($postDTO);


		$this->template->comments = $post->related('comments')->where('replyTo IS NULL')->order('created_at');	//related prostě zjistí jaký záznamy z uvedený tabulky jsou vázaný na záznam co funcki volá 
		
		$repliesGrouped = [];
		foreach ($post->related('comments')->where('replyTo IS NOT NULL')->order('created_at') as $reply) {	//projde odpovědi a do pole přiřadí každýmu id komentu co má odpověď tu danou odpověď
			$repliesGrouped[$reply->replyTo][] = $reply;	//přidej na pozici "id komentu" jeho odpověď, ta druhá [] říká "přidej to na konec pole"
		}
		$this->template->replies = $repliesGrouped;
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
        $this->commentFacade->deleteComment($id);
		$this->flashMessage("Komentář byl úspěšně smazán", "success");
        $this->redirect('Post:show', $comment->post_id);
    }

	public function handleLike(): void
	{
		$data = [];
		$data["post_id"] = is_numeric($this->getParameter("id")) ? intval($this->getParameter("id")) : 0;
		$data["user_id"] = is_numeric(($this->getUser())->id) ? intval(($this->getUser())->id) : 0;
		
		$this->likeFacade->toggleLike($data);

		$this->redirect("Post:show", $data["post_id"]);
		
	}

	public function handleLikeComment(int $commentId): void
	{
		$data = [];
		$data["comment_id"] = $commentId;
		$data["user_id"] = is_numeric(($this->getUser())->id) ? intval(($this->getUser())->id) : 0;

		$this->likeCommentFacade->toggleLikeComment($data);

		$this->redirect("Post:show", $this->getParameter("id"));


	}

	public function handleReply(int $commentId): void
	{
		bdump($commentId);
		$this->showReplyCommentForm = "true";
		$this->replyCommentId = $commentId;

		if ($this->isAjax()) {
			$this->redrawControl("commentFormSnippet-$commentId");
		}
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
		$form->addHidden("replyCommentId", $this->replyCommentId);
		
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
		bdump($data);
		if ($data->replyCommentId == '') {
			$data->replyCommentId = null;
		}

		$user = $this->getUser();
		$user = $this->userFacade->getUserDTO($user->id);
		$templateIsShow = $data->templateIsShow;
		

		
		if ($id) {
			if($data->templateIsShow == "false") {
				$data->replyTo = $data->replyCommentId;
				unset($data->replyCommentId);
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
					"ownerUser_id" => $user->id,
					"replyTo" => $data->replyCommentId
				], null);
			bdump($comment);
        	}

			if ($comment){
				if ($templateIsShow == "false") {
					$this->flashMessage("Komentář byl úspěšně upraven", "success");
				} else {
					$this->flashMessage("Komentář byl úspěšně přidán", "success");
				}
				if ($edit){
					$this->redirect("Post:show", $comment->post_id);
				}
        		$this->redirect("Post:show", $id);
			}
        
    	}
	}
}