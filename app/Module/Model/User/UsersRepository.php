<?php
namespace App\Module\Model\User;

use Nette;
use App\Module\Model\Base\BaseRepository;
use App\Module\Model\Post\PostMapper;
use Nette\Database\Table\ActiveRow;

final class UsersRepository extends BaseRepository
{
	public function __construct(
		protected Nette\Database\Explorer $database,
		//public PostMapper $postMapper
	) {
		$this->table = "users";
	}


    public function getRowByUsername(string $username): ActiveRow|null
    {
        return $this->database->table($this->table)->where("username", $username)->fetch();
    }

    public function getRowByEmail(string $email): ActiveRow|null
    {
        return $this->database->table($this->table)->where("email", $email)->fetch();
    }

	

	
}
