<?php
namespace App\Module\Model\User;

use App\Module\Model\User\UserMapper;
use Nette;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\User\UserDTO;

final class UserFacade  //facade je komplexnější práci s nějakym repository, prostě složitější akce, plus může pracovat s víc repos najednou
{
	public function __construct(
		private UsersRepository $usersRepository,
        protected Nette\Database\Explorer $database,
        private UserMapper $userMapper
	) {
	}

    public function getUserDTO(int|string $id): UserDTO    //jedna funkce co za tebe převede row na DTO aniž bys to v kodu musel vypisovat jak kokot 
    {
        if (is_numeric($id)) {
            $postRow = $this->usersRepository->getRowById($id);
        } else {
            $postRow = $this->usersRepository->getRowByUsername($id);
        }     
        return $this->userMapper->map($postRow);
    }


}