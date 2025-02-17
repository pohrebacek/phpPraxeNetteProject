<?php

declare(strict_types=1);


namespace App\Module\Front\Presenters;


use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Security\MyAuthorizator;


final class HomepagePresenter extends BasePresenter
{
    public function __construct(
		private PostsRepository $postsRepository,
	) {
	}


    public function renderDefault(): void
    {
		$postPerPage = 5;
		
		$role = $this->getUserRole();
		bdump($role);

		$posts = $this->postsRepository->getAll()->fetchAll();
		$postsArray = [];
		foreach ($posts as $post) {
    		$postsArray[] = $post;
		}
		bdump($postsArray);
		bdump(array_chunk($postsArray, $postPerPage));

	    $this->template->posts = $posts;
    }

	

}
