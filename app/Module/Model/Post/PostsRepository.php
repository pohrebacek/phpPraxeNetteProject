<?php
namespace App\Module\Model\Post;

use Nette;
use App\Module\Model\Base\BaseRepository;
use App\Module\Model\Post\PostMapper;

final class PostsRepository extends BaseRepository
{
	public function __construct(
		protected Nette\Database\Explorer $database,
		public PostMapper $postMapper
	) {
		$this->table = "posts";
	}

	

	
}
