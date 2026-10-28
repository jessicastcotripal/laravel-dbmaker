<?php

/**
 * Created by syscom.
 * User: syscom
 * Date: 17/06/2019
 * Time: 15:50
 */
namespace DBMaker\ODBC;

use PDOStatement;

class DBMakerODBCPdoStatement extends PDOStatement {
	protected $query;
	protected $options;
	protected $params = [];
	protected $statement;
	
	/**
	 *
	 * @param string $conn        	
	 * @param string $query        	
	 * @return Dbmaker\Odbc\DBMakerPdo
	 */
	public function __construct($conn,$query,$options = []) {
		$this->query = preg_replace('/(?<=\s|^):[^\s:]++/um','?',$query);
		$this->params = $this->getParamsFromQuery($query);
		$this->statement = odbc_prepare($conn,$this->query);
		$this->options = $options;
	}
	
	/**
	 * get Params From Query String
	 *
	 * @param string $qry        	
	 * @return array
	 */
	protected function getParamsFromQuery($query) {
		$params = [];
		$qryArray = explode(" ",$query);
		$i = 0;
		while(isset($qryArray[$i])) {
			if(preg_match ( "/^:/",$qryArray[$i]))
				$params[$qryArray[$i]] = null;
			$i++;
		}
		return $params;
	}
	
	/**
	 *
	 * @return int
	 */
	public function rowCount() {
		return odbc_num_rows($this->statement);
	}
	
	/**
	 *
	 * @param string $param        	
	 * @param string $val        	
	 * @param string $ignore        	
	 * @return void
	 */
	public function bindValue($param,$val,$ignore = null) {
		$this->params[$param] = $val;
	}
	
	/**
	 *
	 * @param array $ignore        	
	 * @return void
	 */
	public function execute($ignore = null) {
		odbc_execute($this->statement,$this->params);
		$this->params = [];
	}
	
	public function fetchAll($mode = \PDO::FETCH_DEFAULT, ...$args) {
		$records = [];
		while($record = $this->fetch()) {
			$records [] = $record;
		}
		if ($this->options['dbidcap'] == 1)
			$this->keysToLower($records);
		return $records;
	}
	
	/**
	 * Fetch an associative array from an ODBC query.
	 *
	 * @param array $option        	
	 * @param array $ignore        	
	 * @param array $ignore2        	
	 * @return array
	 */
	public function fetch($option = null, $ignore = null,$ignore2 = null) {
		$values = [];

		if (odbc_fetch_into($this->statement, $values) === false) {
			return false;
		}

		$row = [];
		foreach ($values as $index => $value) {
			$row[odbc_field_name($this->statement, $index + 1)] = $value;
		}

		return (object) $row;
	}
	function &keysToLower(&$obj) {
		if(is_object($obj)) {
			$newobj = (object)array();
			foreach($obj as $key => &$val)
				$newobj->{strtolower($key)} = $this->keysToLower($val);
			$obj = $newobj;
		}else if(is_array($obj))
			foreach($obj as &$value)
				$this->keysToLower($value);
		return $obj;
	}
}
