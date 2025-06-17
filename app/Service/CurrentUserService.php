<?php
namespace App\Service;

use App\Module\Model\User\UserFacade;
use App\Module\Model\User\UserDTO;
use Nette\Security\User;

final class CurrentUserService
{
	public function __construct(
		private User $netteUser,
		private UserFacade $userFacade
	) {}

	public function isLoggedIn(): bool
	{
		return $this->netteUser->isLoggedIn();
	}

	public function getId(): ?int
	{
		return $this->netteUser->getId();
	}

	public function getIdentity(): ?\Nette\Security\SimpleIdentity
	{
		return $this->netteUser->getIdentity();
	}

	public function getDTO(): ?UserDTO
	{
		if (!$this->isLoggedIn()) {
			return null;
		}

		return $this->userFacade->getUserDTO($this->getId());
	}

	//kvůli této metodě vlastně vznikla tato třída, v basePresenteru ta metoda být nemohla protože presenter neviděl na userFacade, v userFacade to zas nemělo přístup k přihlášenýmu uživateli, a tak vznikla tato třída
	public function hasPremium(): bool
	{
		$dto = $this->getDTO();
		if (!$dto) {
			return false;
		}

		return $this->userFacade->hasPremium($dto);
	}
}
