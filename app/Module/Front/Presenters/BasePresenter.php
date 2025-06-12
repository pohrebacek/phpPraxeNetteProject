<?php
namespace App\Module\Front\Presenters;

use Nette\Application\UI\Presenter;
use App\Module\Model\Security\MyAuthorizator;
use App\Module\Model\User\UserFacade;

class BasePresenter extends Presenter
{
    
    public function startup(): void
    {
        parent::startup();
        $acl = MyAuthorizator::create();
		$this->getUser()->setAuthorizator($acl);
    }

    // Tato metoda bude zajišťovat získání role uživatele
    protected function getUserRole(): string
    {
        #$user = $this->userFacade->getUserDTO(($this->getUser())->id); // Získání objektu uživatele
        $user = $this->getUser()->getIdentity();
        bdump($user);
        bdump($this->getUser());

        if ($user && $user->roles[0] != 'guest') {
            return $user->roles[0];
        } 
        return 'guest'; // Nepřihlášený uživatel
        
    }
}
