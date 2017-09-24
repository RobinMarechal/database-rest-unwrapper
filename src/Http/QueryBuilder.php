<?php

namespace RobinMarechal\DatabaseRestUnwrapper\Http;
use Carbon\Carbon;
use \Illuminate\Support\Facades\Request;

/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 22/09/2017
 * Time: 19:35
 */
class QueryBuilder
{
	private $class;
	private $query;
	private $request;


	function __construct (&$query, $class)
	{
		$this->query = $query;
		$this->class = $class;
		$this->request = Request::instance();
	}


	public static function getPreparedQuery ($class)
	{
		$query = $class::query();

		return self::buildQuery($query, $class);
	}


	public static function buildQuery(&$query, $class)
	{
		$instance = new QueryBuilder($query, $class);
		$instance->build();

		return $instance->getBuiltQuery();
	}


	protected function build ()
	{
		$this->applyUrlParams();
	}

	// ?....&limit=..&offset=...
	public function applyLimitingParameters ()
	{
		if ($this->has("limit")) {
			$this->query->take($this->request->get("limit"));
		}

		if ($this->has("offset")) {
			$this->query->skip($this->request->get("offset"));
		}
	}


	// ?....&orderby=..&order=...
	public function applyOrderingParameters ()
	{
		if ($this->has("orderby")) {
			$orderBy = $this->getRawArrayFromString($this->request->get('orderby'))[0];
			if ($this->has("order")) {
				$this->query->orderBy($orderBy, $this->request->get("order"));
			}
			else {
				$this->query->orderBy($orderBy);
			}
		}
	}


	// ?....&from=..&to=...
	public function applyTemporalParameters ()
	{
		$modelClassName = '\\' . $this->class;
		$temporalField = (new $modelClassName())->temporalField;

		if (isset($temporalField) && $temporalField != null) {
			$from = $this->has("from") ? Carbon::parse($this->request->get("from")) : null;
			$to = $this->has("to") ? Carbon::parse($this->request->get("to")) : null;

			if (isset($from) && isset($to)) {
				$this->query->whereBetween($temporalField, [$from, $to]);
			}
			else if ($this->has("from")) {
				$this->query->where($temporalField, '>=', $from);
			}
			else if ($this->has("to")) {
				$this->query->where($temporalField, '<=', $to);
			}
		}
	}


	// ?....&with=rel1,rel2,rel3.rel3rel...
	public function applyRelationsParameters ()
	{
		if ($this->has("with")) {
			$with = $this->request->get("with");
			if ($with == "all" || $with == '*') {
				$this->query->withAll();
			}
			else {
				$withArr = explode(",", $this->request->get('with'));
				$this->query->with($withArr);
			}
		}
	}


	public function applyFieldSelectingParameters ()
	{
		if ($this->has('select')) {
			$fields = $this->request->get('select');
			$arr = $this->getRawArrayFromString($fields);
			$this->query->select($arr);
		}
	}


	public function applyWhereParameter ()
	{
		if ($this->has('where')) {
			$wheres = explode(';', $this->request->get('where'));
			foreach ($wheres as $where) {
				$this->query->whereRaw($where);
			}
		}
	}


	protected function getRawArrayFromString ($str)
	{
		$sep = '+';
		$str = preg_replace('/,\s+,/', ',', $str);
		$len = strlen($str);
		$p = 0;

		for ($i = 0; $i < $len; $i++) {
			$c = $str[ $i ];

			if ($c == ',' && $p == 0) {
				$str[ $i ] = $sep;
				continue;
			}
			else if ($c == '(') {
				$p++;
				continue;
			}
			else if ($c == ')') {
				$p--;
			}

			if ($p < 0) {
				throw Exception("Error in URL query parameter");
			}
		}

		$arr = explode($sep, $str);

		for ($i = 0; $i < count($arr); $i++) {
			$v = $arr[ $i ];
			if (preg_match('/[a-z\d_]+\([a-z\d_]+(,(([a-z\d_]+)|("\s*")))*\)/i', $v)) {
				$arr[ $i ] = DB::raw($v);
			}
		}

		return $arr;
	}


	protected function applyUrlParams ()
	{
		$this->applyRelationsParameters();
		$this->applyLimitingParameters();
		$this->applyOrderingParameters();
		$this->applyTemporalParameters();
		$this->applyFieldSelectingParameters();
		$this->applyWhereParameter();
	}
}