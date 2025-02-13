<?php
namespace App\Module\Model\Security;

use Nette\Security;
use Nette\Security\Permission;

class MyAuthorizator implements Security\Authorizator{
    public function __construct(
        private Permission $acl,
    ) {
        //Roles
        $this->acl->addRole("guest");
        $this->acl->addRole("user", "guest");
        $this->acl->addRole("admin", "user");

        //Resources
        $this->acl->addResource("post");
        $this->acl->addResource("comment");

        //Operations
        $this->acl->allow("guest", ["post", "comment"], "view");
        $this->acl->allow("user", ["post", "comment"], "add");
        $this->acl->allow("admin");

        $assertion = function (Permission $acl, string $role, string $resource, string $privilege): bool {
            $role = $acl->getQueriedRole(); // objekt Registered
            $resource = $acl->getQueriedResource(); // objekt Article
            return $role->id == $resource->authorId;
        };

        $this->acl->allow('registered', 'article', 'edit', $assertion);
    }

    public function isAllowed($role, $resource, $privilege): bool   //díky týhle metodě ji nemusíš volat přes to acl
    {
        return $this->acl->isAllowed($role, $resource, $privilege);
    }   
}