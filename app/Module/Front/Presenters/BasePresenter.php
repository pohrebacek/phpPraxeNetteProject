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

}
