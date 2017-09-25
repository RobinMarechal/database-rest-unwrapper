<?php
/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 24/09/2017
 * Time: 23:45
 */

namespace RobinMarechal\DatabaseRestUnwrapper\Http;

use Illuminate\Http\Request;

class UrlHelper
{
	public static function userWantsAll (Request $request)
	{
		return $request->has("all") && $request->get("all") == "true";
	}
}