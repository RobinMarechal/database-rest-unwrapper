<?php

namespace RobinMarechal\DatabaseRestUnwrapper\Http;

use Carbon\Carbon;
use function explode;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Request;
use function strpos;

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


	public static function buildQuery (&$query, $class)
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
		if ($this->request->filled("limit")) {
			$limit = $this->request->get("limit");

			if(strpos($limit, ','))
			{
				$arr = explode(',', $limit);
				$this->query->take($arr[0]);
				$this->query->skip($arr[1]);
				return;
			}

			$this->query->take($limit);
		}

		if ($this->request->filled("offset")) {
			$this->query->skip($this->request->get("offset"));
		}
	}


	// ?....&orderby=..&order=...
	public function applyOrderingParameters ()
	{
		if ($this->request->filled("orderby")) {
			$orderBy = $this->request->get('orderby');
			$order = $this->request->get('order') ?: 'ASC';

			if (strpos($orderBy, ',') != -1) {
				$arr = explode(',', $orderBy);
				$orderBy = $arr[0];
				$order = $arr[1];
			}

			$this->query->orderBy($orderBy, $order);
		}
	}


	// ?....&from=..&to=...
	public function applyTemporalParameters ()
	{
		$modelClassName = '\\' . $this->class;
		$temporalField = (new $modelClassName())->temporalField;

		if (isset($temporalField) && $temporalField != null) {
			$from = $this->request->filled("from") ? Carbon::parse($this->request->get("from")) : null;
			$to = $this->request->filled("to") ? Carbon::parse($this->request->get("to")) : null;

			if (isset($from) && isset($to)) {
				$this->query->whereBetween($temporalField, [$from, $to]);
			}
			else if ($this->request->filled("from")) {
				$this->query->where($temporalField, '>=', $from);
			}
			else if ($this->request->filled("to")) {
				$this->query->where($temporalField, '<=', $to);
			}
		}
	}


	// ?....&with=rel1,rel2,rel3.rel3rel...
	public function applyRelationsParameters ()
	{
		if ($this->request->filled("with")) {
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
		if ($this->request->filled('select')) {
			$fields = $this->request->get('select');
			$arr = $this->getRawArrayFromString($fields);
			$this->query->select($arr);
		}
	}


	public function applyWhereParameter ()
	{
		if ($this->request->filled('where')) {
			$wheres = explode(';', $this->request->get('where'));
			foreach ($wheres as $where) {
				$this->query->whereRaw($where);
			}
		}
	}


	protected function getRawArrayFromString ($str)
	{
		$sep = '+';
		$str = preg_replace('/\( \s+/', '', $str);
		$str = preg_replace('/\s+ \)/', '', $str);
		$str = preg_replace('/,\s+/', ',', $str);
		$str = preg_replace('/\s+,/', ',', $str);
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
			if (strpos($arr[ $i ], '=')) {
				$tmp = explode('=', $arr[ $i ]);
				$f = $tmp[1];
				$as = $tmp[0];
				$arr[ $i ] = "$f AS $as";
			}
			if (preg_match('/[a-z\d_]+\(((\*|\w+)|[a-z\d_]+(,(([a-z\d_]+)|("\s*")))*)\)(\s*as\s+\w+)?/i', $arr[ $i ])) {
				$arr[ $i ] = DB::raw($arr[ $i ]);
			}
		}

		return $arr;
	}


	public function applyDistinct ()
	{
		if ($this->request->filled('distinct') && $this->request->get('distinct') == true) {
			$this->query->distinct();
		}
	}


	protected function applyUrlParams ()
	{
		$this->applyRelationsParameters();
		$this->applyLimitingParameters();
		$this->applyOrderingParameters();
		$this->applyTemporalParameters();
		$this->applyFieldSelectingParameters();
		$this->applyWhereParameter();
		$this->applyDistinct();
	}

	protected function getBuiltQuery()
	{
		return $this->query;
	}
}