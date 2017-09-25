<?php
/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 25/09/2017
 * Time: 02:46
 */

namespace RobinMarechal\DatabaseRestUnwrapper\Http;


use function camel_case;
use Carbon\Carbon;
use RobinMarechal\DatabaseRestUnwrapper\Http\Helpers\Helper;
use RobinMarechal\DatabaseRestUnwrapper\Http\Response\ResponseData;
use function str_singular;
use Symfony\Component\Debug\Exception\UndefinedFunctionException;
use Symfony\Component\HttpFoundation\Response;

trait HandleRestRequest
{
	protected $traitRequest;
	protected $postValues;


	function __construct ($request)
	{
		$this->traitRequest = $request;
		$this->postValues = $request->json()
									->all();
	}


	/*
	 * ------------------------------------------------------------------
	 * ------------------------------------------------------------------
	 */

	public function defaultAll ($class)
	{
		$data = QueryBuilder::getPreparedQuery($class)
							->get();

		return new ResponseData($data, Response::HTTP_OK);
	}


	public function defaultGetById ($class, $id)
	{
		$data = QueryBuilder::getPreparedQuery($class)
							->find($id);

		return new ResponseData($data, Response::HTTP_OK);
	}


	public function defaultPut ($class, $id)
	{
		$data = $this->defaultGetById($class, $id)
					 ->getData();

		if ($data == null) {
			return new ResponseData(null, Response::HTTP_BAD_REQUEST);
		}

		$data->update($this->traitRequest->all());

		if ($this->traitRequest->userWantsAll()) {
			$data = $this->all();
		}

		return new ResponseData($data, Response::HTTP_OK);
	}


	public function defaultDelete ($class, $id)
	{
		$data = $class::find($id);

		if ($data == null) {
			return new ResponseData(null, Response::HTTP_BAD_REQUEST);
		}

		$data->delete();

		if ($this->traitRequest->userWantsAll()) {
			$data = $this->all();
		}

		return new ResponseData($data, Response::HTTP_OK);
	}


	public function defaultPost ($class)
	{
		$data = $class::create($this->postValues);
		$data;

		if ($this->traitRequest->userWantsAll()) {
			$data = $this->all();
		}

		return new ResponseData($data, Response::HTTP_CREATED);
	}


	public function defaultGetFromTo ($class, $from, $to, $field = "created_at")
	{
		$fromCarbon = Carbon::parse($from);
		$toCarbon = Carbon::parse($to);

		$array = $this->traitRequest->getPreparedQuery($class)
									->whereBetween($field, [$fromCarbon, $toCarbon])
									->get();

		return new ResponseData($array, Response::HTTP_OK);
	}


	/**
	 * @param $class        string the model (usually associated with the current controller) class name
	 * @param $id           int the id of the resource
	 * @param $relationName string the relation name. This can be chained relations, separated with '.' character.
	 *
	 * @warning if chained relations, all of these (but the last) have to be BelongsTo relations (singular relations),
	 *          otherwise this will fail
	 * @return ResponseData the couple (json, Http code)
	 */
	public function defaultGetRelationResult ($class, $id, $relationName)
	{
		$data = $class::with([$relationName => function ($query) use ($class) {
			$this->traitRequest->applyUrlParams($query, $class);
		}])
					  ->find($id);

		if (!isset($data)) {
			return new ResponseData(null, Response::HTTP_NOT_FOUND);
		}

		$rels = explode('.', $relationName);
		foreach ($rels as $r) {
			$data = $data->$r;
		}

		return new ResponseData($data, Response::HTTP_OK);
	}


	public function defaultGetRelationResultOfId ($class, $id, $relationClass, $relationName, $relationId = null)
	{
		if ($relationId == null) {
			return $this->defaultGetRelationResult($class, $id, $relationName);
		}


		$data = $class::with([
			$relationName => function ($query) use ($relationId, $relationClass) {
				$this->traitRequest->applyUrlParams($query, $relationClass);
			}])
					 ->where((new $class())->getTable() . '.id', $id)
					 ->first();

		if (!isset($data)) {
			return new ResponseData(null, Response::HTTP_NOT_FOUND);
		}

		$rels = explode('.', $relationName);
		foreach ($rels as $r) {
			$data = $data->$r;
		}

		$data = $data->where('id', "=", $relationId)
				   ->first();

		return new ResponseData($data, Response::HTTP_OK);
	}


	// ---

	public function all ()
	{
		$class = Helper::getRelatedModelClassName($this);
		$resp = $this->defaultAll($class);

		return \response()->json($resp->getData(), $resp->getCode());
	}


	public function getById ($id)
	{
		$class = Helper::getRelatedModelClassName($this);
		$resp = $this->defaultGetById($class, $id);

		return \response()->json($resp->getData(), $resp->getCode());
	}


	public function getFromTo ($from, $to)
	{
		$class = Helper::getRelatedModelClassName($this);
		$resp = $this->defaultGetFromTo($class, $from, $to);

		return \response()->json($resp->getData(), $resp->getCode());
	}


	public function put ($id)
	{
		$class = Helper::getRelatedModelClassName($this);
		$resp = $this->defaultPut($class, $id);

		return \response()->json($resp->getData(), $resp->getCode());
	}


	public function delete ($id)
	{
		$class = Helper::getRelatedModelClassName($this);
		$resp = $this->defaultDelete($class, $id);

		return \response()->json($resp->getData(), $resp->getCode());
	}


	public function post ()
	{
		$class = Helper::getRelatedModelClassName($this);
		$resp = $this->defaultPost($class);

		return \response()->json($resp->getData(), $resp->getCode());
	}


	//    public function relations ($id, $params, $relatedId = null)
	//    {
	//        $relations = explode('/', $params);
	//
	//        $modelClassName = Helper::getRelatedModelClassName($this);
	//        $relatedModel = str_singular(array_last($relations));
	//        $relatedModel = 'App\\'.strtoupper(substr($relatedModel, 0, 1)) . substr($relatedModel, 1);
	//        $relationStr = join('.', $relations);
	//
	//        $resp = $this->defaultGetRelationResultOfId($modelClassName, $id, $relatedModel, $relationStr, $relatedId);
	//
	//        return $resp->getData();
	//    }


	public function __call ($method, $parameters)
	{
		if (strpos($method, "get") == 0 && strlen($method) > 3 && is_array($parameters) && isset($parameters[0])) {
			$relation = camel_case(substr($method, 3));

			$relatedModelClassName = str_singular($relation);
			$relatedModelClassName = strtoupper(substr($relatedModelClassName, 0, 1)) . substr($relatedModelClassName, 1);

			$thisModelClassName = Helper::getRelatedModelClassName($this);

			$id = $parameters[0];
			$relatedId = null;
			if (isset($parameters[1])) {
				$relatedId = $parameters[1];
			}

			if (!is_numeric($id) || (isset($relatedId) && !is_numeric($relatedId))) {
				GOTO FUNCTION_NOT_FOUND;
			}

			// Ok
			$resp = $this->defaultGetRelationResultOfId($thisModelClassName, $id, $relatedModelClassName, $relation, $relatedId);

			return response()->json($resp->getData(), $resp->getCode());
		}

		FUNCTION_NOT_FOUND:
		throw new UndefinedFunctionException();
	}
}