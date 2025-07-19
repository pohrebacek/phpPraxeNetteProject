<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		// Přidej specifické pravidlo pro likes_comments
   		$router->addRoute('admin-db/likes_comments', [
   		    'module' => 'Front',
   		    'presenter' => 'AdminDb',
   		    'action' => 'likesComments',
   		]);

		
		//$router->addRoute('<presenter>/<action>', 'Front:Homepage:default');
		$router->addRoute('<presenter>/<action>[/<id>]', [
			'module' => 'Front',
			'presenter' => 'Homepage',
			'action' => 'default',
		]);
		
		return $router;
	}
}
