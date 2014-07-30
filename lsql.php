#!/usr/bin/php
<?

class lsql_result
{
	var $path;
	var $name;

	function __construct($name)
	{
		$this->name = $name;
		$this->path = realpath($name);
	}

	private $content;
	function content()
	{
		if ( isSet($this->content) )
		{
			return $this->content;
		}
		$this->content = file_get_contents($this->path);

		return $this->content;
	}
}

class lsql_query
{
	public $query;

	public $error = false;
	function __construct($query)
	{
		$this->query = $this->sanitizeQuery($query);
	}

	public $directories = [ "." ];

	private $findQueries = [];
	private $findFilters = [];

	private function queryOperators()
	{
		return [
			">", ">=", "=", "<", "<=",
			"contains"
		];
	}

	private function expressionPattern()
	{
		$keyPattern = "[a-z_]+";
		$operatorPattern = '(' . implode('|', $this->queryOperators() ) . ')';
		$valuePattern = "([0-9]+|['\"].*?['\"])+";
		return "(($keyPattern) *$operatorPattern *($valuePattern))";
	}
	function queryPattern()
	{
		$tablePattern = "[a-z_\*]+";

		return '/^ *' .

			// expression ( AND expression ... )
			"+({$this->expressionPattern()}( +AND +{$this->expressionPattern()})*)" .

			' *$/i';
	}

	function getResults()
	{
		if (! $this->query)
		{
			return false;
		}

		preg_match( $this->queryPattern(), $this->query, $matches);

		$this->performWhere( $matches[1] );

		$exec = exec( $this->findQuery(), $fileNames );

		$results = [];

		foreach($fileNames as $filename)
		{
			$results[] = new lsql_result($filename);
		}

		if ($this->error)
		{
			return false;
		}

		foreach($this->findFilters as $filter)
		{
			$results = array_filter($results, $filter);
		}

		return $results;
	}


	private function performSelect($parameterList)
	{
		$this->select = [];

		$parameters = explode(',', $parameterList);
		foreach ($parameters as $parameter)
		{
			$this->select[] = trim($parameter);
		}
	}

	private function performWhere($parameterList)
	{
		preg_match_all('/' . $this->expressionPattern() . '/i', $parameterList, $results);

		foreach($results[0] as $i => $expression)
		{
			$key = trim(strtolower($results[2][$i]));

			if (! $this->validCompareKey($key))
			{
				$this->error = "Invalid column: $key";
				return;
			}

			$operator = strtolower($results[3][$i]);
			if (! $this->validCompareOperator($operator, $key))
			{
				$this->error = "Column >$key< does not support operator: $operator";
				return;
			}

			$sanitizeFunction = $this->sanitizeFunctionForKey($key);

			$value = $sanitizeFunction( $results[4][$i] );

			$this->addQuery($key, $operator, $value);
		}
	}

	function addFindQuery($function, $value)
	{
		$this->findQueries[] = $function . ' ' . escapeshellarg($value);
	}

	function addQuery($key, $operator, $value)
	{
		if ($key == "extension")
		{
			if ($operator == "=")
			{
				$this->addFindQuery("-iname ", "*.$value");
				return;
			}
			if ($operator == "contains")
			{
				$this->addFindQuery("-iname ", "*.*$value*");
				return;
			}
		}
		if ($key == "name" || $key == "path")
		{
			$function = "-i$key";
			if ($operator == "=")
			{
				$this->addFindQuery($function, $value);
				return;
			}
			if ($operator == "contains")
			{
				$this->addFindQuery($function, "*$value*");
				return;
			}
		}
		if ($key == "content")
		{
			$this->findFilters[] = function($result) use ($operator, $value)
			{
				if ($operator == "=")
				{
					$aVal = trim(strtolower($result->content()));
					$bVal = trim(strtolower($value));

					return $aVal == $bVal;
				}
				if ($operator == "contains")
				{
					return stripos($result->content(), $value) !== false;
				}
				return true;
			};
		}
	}

	private function validCompareKey($key)
	{
		$type = $this->typeForKey($key);

		return (bool) $type;
	}

	private function typeForKey($key)
	{
		$types = [
			'name' => 'string',
			'path' => 'string',
			'extension' => 'string',
			'content' => 'string',
		];

		if (! isSet($types[$key]))
		{
			return NULL;
		}

		return $types[$key];
	}

	private function sanitizeFunctionForKey($key)
	{
		$type = $this->typeForKey($key);
		if (! $type) return false;

		$functions = [
			'string' => function($value)
			{
				$indexA = 0;
				$indexB = count($value) - 1;
				if ( $value[$indexA] == "'" && $value[$indexB] == "'")
				{

					$value = '"' . substr(	$value, 1, strlen($value) - 2 ) . '"';
				}
				return json_decode($value);
			},
			'number' => 'floatval',
		];

		if (! isSet($functions[$type]))
		{
			return false;
		}

		return $functions[$type];
	}

	private function validCompareOperator($operator, $key)
	{
		$operatorsPerType = [
			'string' => [ "=", "contains" ],
			'number' => [ ">", ">=", "=", "<", "<=" ],
		];
		$type = $this->typeForKey($key);

		if (! isSet($operatorsPerType[$type]))
		{
			return false;
		}

		$operator = strtolower($operator);

		return in_array( $operator, $operatorsPerType[$type] );

	}

	private function findQuery()
	{
		return "find " . implode(' ', $this->directories) . ' ' . implode(' ', $this->findQueries);
	}

	/**
	 * Returns a sanitized query (or false on failure)
	 * @param  string $query
	 * @return string?
	 */
	private function sanitizeQuery($query)
	{
		$success = preg_match( $this->queryPattern(), $query);
		if (! $success)
		{
			$this->error = "Couldn't ready $query";
			return false;
		}
		return $query;
	}
}









if ($argc == 1)
{
	echo <<<USAGE_HELP
# lsql:  Search files using SQL-style syntax

===========
== USAGE ==
===========
> $argv[0] [ directoryA, ... ] "expression"

=============
== COLUMNS ==
=============
name
path
extension
content

==============
== EXAMPLES ==
==============

# Find php files
> $argv[0] "extension = 'php'"

# Find files containing lorem ipsum
> $argv[0] "content contains 'lorem ipsum'"
USAGE_HELP;
	exit;
}

$input = array_slice($argv, 1);

$query = new lsql_query( array_pop($input) );
if (count($argv) > 2)
{
	$query->directories = $input;
}
$results = $query->getResults();
if ($results == false)
{
	echo $query->error . PHP_EOL;
	exit;
}

foreach($results as $result)
{
	echo $result->name . PHP_EOL;
}

?>