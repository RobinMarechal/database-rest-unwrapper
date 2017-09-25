<?php
/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 25/09/2017
 * Time: 02:23
 */

namespace RobinMarechal\DatabaseRestUnwrapper\Http\Controllers;

use function camel_case;
use function class_exists;
use ErrorException;
use Exception;
use function strtoupper;
use function substr;
use Symfony\Component\Debug\Exception\ClassNotFoundException;

use App\Http\Controllers\Controller;

class RestController extends Controller
{

	public function dispatch ($resource, $id = null, $relation = null, $relatedId = null)
	{
		if ($this->traitRequest->path() == 'api/auth/user') {
			return $this->user();
		}

		try {

			$controllerClassName = "App\\Http\\Controllers\\" . strtoupper($resource[0]) . camel_case(substr($resource, 1)) . "Controller";

			if (!class_exists($controllerClassName)) {
				throw new ClassNotFoundException("Controller '$controllerClassName' doesn't exist.", new ErrorException());
			}


			$controller = new $controllerClassName($this->traitRequest);

			if (!isset($id)) {
				if ($this->traitRequest->isMethod("get")) {
					return $controller->all();
				}
				else if ($this->traitRequest->isMethod("post")) {
					return $controller->post();
				}
				else {
					goto EXCEPTION;
				}
			}

			if (!isset($relation)) // findById
			{
				if ($this->traitRequest->isMethod("get")) {
					return $controller->getById($id);
				}
				else if ($this->traitRequest->isMethod("put")) {
					return $controller->put($id);
				}
				else if ($this->traitRequest->isMethod("delete")) {
					return $controller->delete($id);
				}
				else {
					goto EXCEPTION;
				}
			}
			else {
				$function = camel_case("get_" . $relation);

				return $controller->$function($id, $relatedId);
			}
		} catch (Exception $e) {
			throw $e;
			throw new Exception("The given URL is not valid. It should look like one of these:
			\n - '.../api/[resource]/'
			\n - '.../api/[resource]/[id]/'
			\n - '.../api/[resource]/[id]/[relation]/'
			\n - '.../api/[resource]/[id]/[relation]/[relatedId]' \n 
			With: \n
			 - [resource] the wanted data in plural form (users, articles, news...) \n
			 - [id] the id of the wanted resource \n
			 - [relations] an existing relation of the wanted resource (e.g /users/1/courses; /articles/3/author) \n
			 - [relatedId] the id of the related resource (e.g /users/1/courses/2; /articles/2/medias/7)");
		}

		EXCEPTION:
		throw new Exception("The requested action is invalid. (" . $this->traitRequest->url() . " with method " . $this->traitRequest->method() . ")");
	}
}