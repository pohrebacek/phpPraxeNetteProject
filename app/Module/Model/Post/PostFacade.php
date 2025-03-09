<?php
namespace App\Module\Model\Post;

use App\Module\Model\Post\PostMapper;
use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Post\PostDTO;

final class PostFacade  //facade je komplexnější práci s nějakym repository, prostě složitější akce, plus může pracovat s víc repos najednou
{
	public function __construct(
		private PostsRepository $postsRepository,
		private CommentsRepository $commentsRepository,
        protected Nette\Database\Explorer $database,
        private PostMapper $postMapper
	) {
	}

    public function filterPostColumns($data): void
    {
        //funkce na filtraci dat z posts db na něco uživatelsky přívětívého
    }

    public function deletePost(int $id): void
    {
        $this->database->transaction(function () use ($id) {
            $this->commentsRepository->deleteCommentByPostId($id);
            $this->postsRepository->deleteRow($id);
        });
    }

    public function getPostDTO(int $id): PostDTO    //jedna funkce co za tebe převede row na DTO aniž bys to v kodu musel vypisovat jak kokot 
    {
        $postRow = $this->postsRepository->getRowById($id);
        return $this->postMapper->map($postRow);
    }


}
