<?php

declare(strict_types=1);


namespace App\Module\Front\Presenters;


use Nette;
use App\Module\Model\Post\PostsRepository;


final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
		private PostsRepository $postsRepository,
	) {
	}

    public function renderDefault(): void
    {
	    $this->template->posts = $this->postsRepository
		    ->getAll();
    }

}
