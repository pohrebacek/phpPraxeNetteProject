<?php

namespace App\Module\Model\Security;

use Nette\Security\Permission;
use App\Module\Model\User\UserDTO;
use App\Module\Model\Post\PostDTO;

class MyAuthorizator
{
    public static function create(): Permission
    {
        $acl = new Permission;

        //Roles
        $acl->addRole("guest");
        $acl->addRole("user", "guest");
        $acl->addRole("premium", "guest");
        $acl->addRole("admin", ["user", "premium"]);

        //Resources
        $acl->addResource("post");
        $acl->addResource("comment");
        $acl->addResource("premiumPost");

        //Operations
        $acl->allow("guest", ["post", "comment"], "view");
        $acl->allow("user", ["post", "comment"], "add");
        $acl->allow("premium", ["premiumPost"], ["add", "view"]);
        $acl->allow("admin");

        return $acl;
    }
}
