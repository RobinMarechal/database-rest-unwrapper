<?php
/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 24/09/2017
 * Time: 23:45
 */

namespace RobinMarechal\DatabaseRestUnwrapper\Http\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Helper
{
	public static function userWantsAll (Request $request)
	{
		return $request->has("all") && $request->get("all") == "true";
	}


	public static function getRelatedModelClassName (Controller $controller)
	{
		$fullName = get_class($controller);
		$reducedName = str_replace('Controller', '', array_last(explode('\\', $fullName)));

		return 'App\\' . str_singular($reducedName);
	}
}