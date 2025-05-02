<?php
namespace App\Module\Front\Presenters;

use Nette\Application\UI\Presenter;
use App\Module\Model\Security\MyAuthorizator;

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
        $user = $this->getUser()->getIdentity(); // Získání objektu uživatele
        bdump($user);
        bdump($this->getUser());

        if ($user && $user->roles[0] != 'guest') {
            return $user->roles[0];
        } else {
            return 'guest'; // Nepřihlášený uživatel
        }
    }
}
