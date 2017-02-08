<?php
/**
 * The main framework class. Holds methods for porcessing data from database.
 */
class Data
{
/**
 * Name of the "created" column in tables.
 */
	const COLUMN_NAME_CREATED    = 'created';
/**
 * Name of the "modified" column in tables.
 */
  	const COLUMN_NAME_MODIFIED   = 'modified';

/**
 * Rules for validating user submitted data.
 * Example:
 * array( 'name'   => array('required' => 'You must provide a name!',
 *			    'unique' => 'Name provided already exists'))  
 * @var array
 */
 	public $columnRules;
/**
 * Columns to skip when running update or insert query.
 * Example:
 * array( 'modified', 'created' )
 * @var array
 */
	public $columnsToSkip;
/**
 * Name of the table to run query.
 * @var string
 */
	public $tableName;
/**
 * Name of the column holding id.
 * @var string
 */
	public $idColumnName;
/**
 * Query being processed.
 * @var string
 */
 	public $query;
/**
 * Data used to be displayed on query error. 
 * Should contain queries to run manually to get to the previous state.
 * @var string
 */
 	private $undoDump = array();
/**
 * Holds data for defined table, which is fetched trough a query or is going to be inserted/updated to database.
 * If multiple rows are set, data is saved as a 2-dimensional array, keys holding numbers of rows. 
 * If only one row of data, it is 1-dimensional array, keys holding names of the columns. 
 * @var array
 */
	protected $data;
/**
 * Counter for iterating data by Data::getData() method when $this->$data holding multiple rows.
 * @var int
 */
	public $dataIndexCounter = 0;
	
/**
 * Table skeleton made by running "DESCRIBE $this->tableName" query.
 * @var array
 */
	private $tableSkeleton = array();

/**
 * Prefix used for mssql queries with unicode data.
 * @var string
 */
 	private $stringValuePrefix = "";
/**
 * Constructor
 * Inherited constructor runs setTable and defines columnRules.
 * 
 * @access public
 */
	public function __construct() 
	{
	}

/**
 * Destructor. Also calls Data::clearData().
 *
 * @access public
 */
	public function __destruct()
	{
		$this->clearData();
	}
	
	public function getTableSkeleton()
	{
		return $this->tableSkeleton;
	}
	
	public static function str2HRconv($str)
	{
		$search_letters  = array("È", "Æ", "è", "æ", "ð", "#262", "#263", "#268", "#269", "#272", "#273","#352","#353","#381","#382", "Ä" ,"ÄŒ","Ä‡","Ä‡","Å¾","Å½","Å¡","Å","Ä‘","Ä");
		$replace_letters = array("Č", "Ć", "č", "ć", "đ", "Ć"   , "ć"   , "Č"   , "č"   ,  "Đ"  , "đ"   ,"Š"   ,"š"   ,"Ž"  , "ž"   , "č--" ,"Č---" ,"ć" ,"Ć" ,"ž" ,"Ž" ,"š" ,"Š","đ" ,"Đ");
		$str = str_replace($search_letters, $replace_letters, $str);
		return $str;
	}

	
	public static $key = 'ab123&%gg()GG';
	public static $iv = '167374356';

	public static function encryptString($string)
	{
		$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');

		mcrypt_generic_init($cipher, $key, $iv);
		$encrypted = mcrypt_generic($cipher,$string);
		mcrypt_generic_deinit($cipher);
		return $encrypted;
	}
	public static function decryptString($string)
	{
		$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
		mcrypt_generic_init($cipher, $key, $iv);
		$decrypted = mdecrypt_generic($cipher,$encrypted);
		mcrypt_generic_deinit($cipher);
		return  $decrypted;
	}

	
	
/**
 * Defines table to manipule data.
 *
 * @access public
 * @param string $tableName Name of the table to manipulate.
 * @return void
 */
	public function selectTable($tableName, $idColumn = NULL)
	{
		if (!Database::getCurrentLink())
			exit('Cannot select table before connected to database!');
		// set table name
		$this->tableName     = $tableName;
		// set name of the primary id column
		if($idColumn)
			$this->idColumnName  = $idColumn;
		else
			$this->idColumnName  = 'id_' . $tableName;
		// set column names to skip in insert/update queries
		$this->columnsToSkip = array($this->idColumnName);
		// clear data (select table can be called on an object which could already contain data)
		$this->data 	     = array();
		// create table skeleton
		$this->describeTable();
		// if database is mssql, set N string for appending (unicode)
		$this->stringValuePrefix = Database::isMssql() ? "N" : "";
	}

/**
 * Selects the data from the database by running a SELECT query.
 *
 * If no $queryData argument passed, "SELECT * FROM $this->tableName" query will be run.
 * If integer as $queryData is passed, "SELECT * FROM $this->tableName WHERE $self->idColumnName=$queryData" will be run.
 * If array with query parameters is passed, a custom query will be run.
 * If query string is passed, that query string will be run.
 * Possible parameters are ['columns' = '*'], ['from' = $this->tableName], ['where' = '1'], ['having' = ''], ['orderBy' = ''], ['limit' = '']
 * If the $queryData argument is int (selecting data by primary id), or the LIMIT was set to 1 row, result will be saved to $this->data as 1-dimensional array.
 * Else the result will be saved in $this->data as 2-dimensional array.
 * The method will clear all the previous data in $this->data before populating $this->data with the result, and reset $this->dataIndexCounter to initial state.
 * If no result, FALSE will be returned, else the number of rows is returned. The method will also free the result resource created by query.
 *
 * @access public
 * @param mixed $queryData Data for creating the SELECT query.
 * @return int Number of rows, error if query invalid.
 */
	public function selectData($queryData = NULL)
	{
		$limit = '';
		$top   = '';
		
		if (!$queryData)
			$this->query  = sprintf("SELECT * FROM %s", $this->tableName);
		elseif (is_numeric($queryData))
			$this->query  = sprintf("SELECT * FROM %s WHERE %s=%d", $this->tableName, $this->idColumnName, $queryData);
		elseif (is_string($queryData))
			$this->query  = $queryData;
		elseif (is_array($queryData))
		{
			$columns = (isset($queryData['columns'])) ? $queryData['columns'] : '*' ;
			$from    = (isset($queryData['from']))    ? $queryData['from']    : $this->tableName ;
			$where   = (isset($queryData['where']))   ? 'WHERE '    . $queryData['where']   : '' ;
			$having  = (isset($queryData['having']))  ? 'HAVING '   . $queryData['having']  : '' ;
			$orderBy = (isset($queryData['orderBy'])) ? 'ORDER BY ' . $queryData['orderBy'] : '' ;
			
			if (isset($queryData['limit']) || isset($queryData['top']))
			{
				if (!empty($queryData['limit']))
					$limiter = $queryData['limit'];
				elseif (!empty($queryData['top']))
					$limiter = $queryData['top'];
					
				if (Database::isMssql())
					$top   = 'TOP ' . $limiter;
				elseif (Database::isMysql())
					$limit = 'LIMIT ' . $limiter;
			}

			$this->query  = sprintf("SELECT %s %s FROM %s %s %s %s %s", $top, $columns, $from, $where, $having, $orderBy, $limit);
		}

		if ($result = Database::query($this->query))
		{
			$this->clearData();
			$this->resetDataIndexCounter();
			
			if ($numRows = Database::num_rows($result))
			{
				// if queried by prmary key or limit set to 1, save the data into one dimensional field, else in two dimensional filed
				if (is_numeric($queryData) || $limit == 'LIMIT 1' || $top == 'TOP 1')
					$this->data = Database::fetch_assoc($result);
				else
				{
					while ($row = Database::fetch_assoc($result))
						$this->data[] = $row;
				}
			}
	
			Database::free_result($result);
			
			return $numRows;
		}
		else
			exit('Error processing query: ' . $this->query);
	}

/**
 * Inserts values from $this->data into database using INSERT query.
 *
 * This method will generate and insert to database data from $this->data.
 * It will automatically generate values for created and modified columns.
 *
 * @access public
 * @return bool TRUE if query succesful, error otherwise.
 */
	public function insertData()
	{
		if (!$this->data)
			exit ('No data to insert for method Data::insertData()');

		if (Database::isMssql())
		{
			$this->resetDataIndexCounter();
			
			while ($data = $this->getData())
			{
				if ($this->checkIfColumnExists(self::COLUMN_NAME_CREATED))
					$data[self::COLUMN_NAME_CREATED] = date(Database::DATE_FORMAT);
				
				if ($this->checkIfColumnExists(self::COLUMN_NAME_MODIFIED))
					$data[self::COLUMN_NAME_MODIFIED] = date(Database::DATE_FORMAT);
				
				$columns = $this->getColumnsExpression($data);
				$values  = $this->getValuesExpression($data);
				$values = str_replace(array("N'NULL'"), 'NULL', $values);
				$this->query  = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->tableName, $columns, $values);
	
				if (!Database::query($this->query))
					exit('Error processing query: ' . $this->query . $this->getUndoDump());
					
				if (!$this->has2dData())
					break;
			}
			
			return TRUE;
		}
		elseif (Database::isMysql())
		{
			$this->query  = sprintf("INSERT INTO %s %s", $this->tableName, $this->createInsertColumnsAndValues());
	
			if (!Database::query($this->query))
				exit('Error processing query: ' . $this->query);
				
			return TRUE;
		}
	}
	
/**
 * Reinserts $this->data into database using INSERT query,
 * but first deletes all data with the same idColumn value, defined by the where parameter.
 * It will automatically generate values for created and modified columns.
 *
 * @access public
 * @param array $queryData query data
 * @return bool TRUE if query succesful, error otherwise.
 */
	public function reinsertData($queryData = NULL)
	{
		if (!$this->data)
			exit ('No data to reinsert for method Data::reinsertData()');
		if (empty($queryData['where']))
			exit ('No where argument defined for Data::reinsertData()');
			
		$where = 'WHERE ' . $queryData['where'];

		if ($this->checkIfColumnExists(self::COLUMN_NAME_CREATED))
			$this->data[self::COLUMN_NAME_CREATED] = date(Database::DATE_FORMAT);
		
		if ($this->checkIfColumnExists(self::COLUMN_NAME_MODIFIED))
			$this->data[self::COLUMN_NAME_MODIFIED] = date(Database::DATE_FORMAT);

		$this->createUndoDump($this->tableName, $queryData['where']);
		
		$this->query = sprintf("DELETE FROM %s %s", $this->tableName, $where);

		if (Database::query($this->query))
			$this->insertData();
		else
			exit('Error processing query: ' . $this->query);
	}

/**
 * Updates the database with values from $this->data.
 * It will automatically generate values for modified column.
 *
 * @access public
 * @return bool TRUE if query succesful, error otherwise.
 */
	public function updateData($columnsWhere = array())
	{
		if (!$this->data)
			exit ('No data to update for method Data::updateData()');

		$whereArray = array();
		if(!empty($columnsWhere))
		{
			foreach($columnsWhere as $column)
			{
				if(isset($this->data[$column]))
					$whereArray[] = "`" . $column . "` = '" . $this->data[$column] . "'" ;
			}
		}
		else if (isset($this->data[$this->idColumnName]))
		{
			$whereArray[] = "`" . $this->idColumnName . "` = '" . $this->data[$this->idColumnName] . "'" ;
		}
		else if (!$this->data[$this->idColumnName])
			exit ('No id key defined to update for method ::updateData()');
		
		
		if(empty($whereArray))
			exit ('No where column defined method ::updateData()');
		
		if ($this->checkIfColumnExists(self::COLUMN_NAME_MODIFIED))
			$this->data[self::COLUMN_NAME_MODIFIED] = date(Database::DATE_FORMAT);
				
		$query = sprintf("UPDATE %s SET %s WHERE %s ",
			          $this->tableName, str_replace(array("N'NULL'"), 'NULL', $this->createUpdateColumnsAndValues()), join(' AND ', $whereArray));
	
		if (Database::query($query))
			return TRUE;
		else
			exit('Error processing query: ' . $query);
	}
	
/** 
 * Creates undo dump
 *
 * @access private
 * @param string $tableName
 * @param string $where
 * @return void
 */
	private function createUndoDump($tableName, $where)
	{
		$dumpObject = new Data();
		$dumpObject->selectTable($tableName);
		$dumpObject->selectData(array('where' => $where));

		while ($data = $dumpObject->getData())
		{
			$columns = $this->getColumnsExpression($data);
			$values  = $this->getValuesExpression($data);
			
			$this->undoDump[] = sprintf("INSERT INTO %s (%s) VALUES (%s)", $tableName, $columns, $values);
		}
	}
	
/** 
 * Returns undo dump in string format
 *
 * @access public 
 * @return string Undo dump
 */
	private function getUndoDump()
	{
		return ' Use this undo dump to recover: ' . join(";\n", $this->undoDump);
	}

/**
 * Deletes the data defined by the key column from $this->data. 
 * 
 * If no $queryData defined, current table for operating is used, defined
 * in $this->tableName. Else it can accept the following parameters: ['where']
 * (required) - defining conditon on which to delete rows, ['tables'] - defining
 * tables to delete from (optional), ['from'] - to define tables to join for 
 * deletion (optional). 
 *
 * @access public
 * @return bool TRUE if query succesful, error otherwise.
 */
	public function deleteData($queryData = NULL)
	{
		if (!$queryData)
		{
			if (!$this->data)
				exit ('No data to delete for method ' . get_class($this) . '::deleteData()');

			if (!$this->data[$this->idColumnName])
				exit ('No id key defined to delete for method ' . get_class($this) . '::deleteData()');
		
				$this->query = sprintf("DELETE FROM %s WHERE %s=%s",
			        	       	        $this->tableName, $this->idColumnName, $this->getData($this->idColumnName));
		}
		else
		{
			if (empty($queryData['where']))
				exit ('No where argument defined for Data::reinsertData()');
			
			$where  = 'WHERE ' . $queryData['where'];
			$from   = (!empty($queryData['from']))   ? $queryData['from']   : $this->tableName;
			$tables = (!empty($queryData['tables'])) ? $queryData['tables'] : '';
			
			$this->query = sprintf("DELETE %s FROM %s %s", $tables, $from, $where);
		}
		
		if (Database::query($this->query))
			return TRUE;
		else
			exit('Error processing query: ' . $this->query);
	}

/**
 * Gets the data from $this->data.
 *
 * Depending on the type of the $this->data, if 2-dimensional, this method will 
 * iterate trough it, reading row by row of data each time called. If 
 * $this->data is 1-dimensional, all data in $this->data wil be read.
 * 
 * If no argument passed, method returns all data read.
 * If argumet passed is an array with keys representing names of the columns, 
 * method returns data defined by that names. If argument passed is a string 
 * representing name of the key, method returns the data from that key.
 * If the key index does not exist, NULL is returned.
 *
 * @access public
 * @param mixed $dataToGet Defines which data to return.
 * @return mixed Data requested. Specified column name data or array with 
 		 multiple columns. NULL if data doesn't exist.
 */
	public function getData($dataToGet = NULL)
	{
		$dataToReturn = NULL;
		
		// check if $this->data is 2-dimensional, iterate trough it
		if ($this->has2dData())
		{
			// if no data to read, return NULL
			if (!array_key_exists($this->dataIndexCounter, $this->data))
				return $dataToReturn;
			// else read $this->data from the current index defined by $this->dataIndexCounter and increase counter
			$dataToRead   = $this->data[$this->dataIndexCounter++];
		}
		else // if 1-dimensional, read data from the whole $this->data array
			$dataToRead   = $this->data;
		
		// if no argument passed, return all data read
		// else if argumet is array with keys, return data defined by keys
		// else if argument is string representing name of the key, return the data from that key
		if (!$dataToGet)
			$dataToReturn = $this->trimAndStrip($dataToRead);
		elseif (is_array($dataToGet))
		{
			foreach ($dataToGet as $dataIndex)
				$dataToReturn[] = $this->trimAndStrip($dataToRead[$dataIndex]);
		}
		elseif (is_string($dataToGet))
		{
			if (array_key_exists($dataToGet, $dataToRead))
				$dataToReturn = $this->trimAndStrip($dataToRead[$dataToGet]);
		}
		
		return $dataToReturn;
	}
	
/**
 * Trims and strips the data from the database. This is implemented mostly to trim possible 
 * empty strings returned by mssql as " ", and to strip the slashes.
 *
 * @access private
 * @param mixed $data
 * @return mixed Cleaned data
 */
	private function trimAndStrip($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_array($value))
					$data[$key] = $this->trimAndStrip($value);
				else
				{
					if (Database::isMssql())
						$data[$key] = trim($value);
						//$data[$key] = trim(stripslashes($value));
				}
			}
		}
		else
		{
			if (Database::isMssql())
				$data = trim($data);
				//$data = trim(stripslashes($data));
		}
		
		return $data;
	}
	public function getDataArray()
	{
		return $this->data;
	}
	
	
	public function getSumDataColumn($column)
	{
		$sum = 0;
		while ($catcher = $this->getData())
			$sum += $catcher[$column];
		$this->resetDataIndexCounter();
		return $sum;
	}
	
	public function getDataTable(array $atributes = array())
	{
		if($this->hasData())
		{
			$class = '';
			$style = '';
			$tdStyle = '';
			$titleStyle = '';
			if(!empty($atributes))
			{
				if(isset($atributes['class']))
					$class = $atributes['class'];
				if(isset($atributes['style']))
					$style = $atributes['style'];
				if(isset($atributes['tdstyle']))
					$tdStyle = $atributes['tdstyle'];
				if(isset($atributes['titlestyle']))
					$titleStyle = $atributes['titlestyle'];
			}
			
			$table = '
				<table style="' . $style . '" class="' . $class . '">';
			$columnCounter = 0;
			while( $dataCatcher = $this->getData())
			{
				if($columnCounter == 0)
				{
					if(isset($atributes['table-title']))
					$table .= '<th style="' . $titleStyle . '" colspan="' . count($dataCatcher) . '">' . $atributes['table-title'] . '</th>';
					$table .= '
					<tr>';
					$columnNames = array_keys($dataCatcher);
					foreach($columnNames as $columnName)
					{
	
						$table .= '
						<th style="padding: 7px; ' . $tdStyle . '">';		
								$table .= '
							' . $columnName . '';
						$table .= '
						</th>';			
					}	
					$table .= '
					</tr>';	
					
				}
				if($columnCounter % 2 == 0)
					$trClass = 'shade';
				else
					$trClass = '';
				
					$table .= '
					<tr class="' . $trClass . '">';
				foreach($dataCatcher as $td)
				{
						$table .= '
						<td style="padding: 7px; ' . $tdStyle . '">';
								$table .= trim(preg_replace ('/[^\p{L}\p{N}\p{S}\p{P}\s]/u', '', $td));
						$table .= '</td>';
				}
					$table .= '
					</tr>';
				$columnCounter++;
			}
			
			$table .= '
				</table>';
			$this->resetDataIndexCounter();
			return $table;
		}
		else
			return '';
	}
	
	public function createPagination($limit, $query, $page)
	{
		$to = $limit * $page;
		$from = $to - $limit;
		$this->selectData("
			" . $query . "
			LIMIT " . $from . ", " . $limit . "			
		");
		//Dump::liveDump($this->query);
		
		$tableHtml = '<table class="pagination">';
		$tableHtml .= '<tr>';
		$tableHtml .= '<th>' . '#' . '</th>';
		$headerCache = $this->getData();
		foreach($headerCache as $column => $value)
		{
			$tableHtml .= '<th>' . $column . '</th>';
		}
		$tableHtml .= '</tr>';
		$this->resetDataIndexCounter();
		$trCounter = $from + 1;
		while($dataCache = $this->getData())
		{
			$tableHtml .= '<tr>';
			$tableHtml .= '<td>' . $trCounter . '</td>';
			foreach($dataCache as $value)
			{
				$tableHtml .= '<td>' . $value . '</td>';
			}
			$tableHtml .= '</tr>';
			$trCounter++;
		}
		$tableHtml .= '</table>';
		
		$this->selectData("
			SELECT FOUND_ROWS() total;
		");
		
		$total = $this->getData('total');
		$numberOfPages = ceil($total / $limit);
		$paginationHtml = 'Pages: ';
		for($p = 1; $p <= $numberOfPages; $p++)
		{
			$classSelected = '';
			if($p == $page)
				$classSelected = 'selected-page';
			
			if($_SERVER['REQUEST_URI'] == '/')
				$uri = '?&';
			else
				$uri = preg_replace('/\&page\=[0-9]+/i', '', $_SERVER['REQUEST_URI']) . '&';
			
			$paginationHtml .= '<a href="' . $uri . 'page=' . $p . '" class="pagination-page-link ' . $classSelected . '">' . $p . '</a>';
		}
		echo 'Total: ' . $total . '<br />';
		echo $tableHtml;
		echo $paginationHtml;
		
	}
	
/**
 * Sets the passed data to $this->data.
 *
 * For 1-dimensional data array, this method will append the data if key 
 * doesn't exists. Also cleans up the given data - trims, strips tags, 
 * converts special characters and does strpislashes.
 *
 * @access public
 * @param  array $data Defines the data with keys and values to set 
 * 		 to $this->data.
 * @param  int $sanitize Defines to sanitaze the data or not. Defaults to TRUE.
 * @return void
 */
	public function setData($data, $sanitize = TRUE) 
	{
		if (is_array($data) && !isset($data[0]))
		{
			// clean up the given data - trim, strip tags, convert special characters and strpislashes
			foreach ($data as $columnName => $value)
			{		
				if (!is_array($value))
				{
					if ($sanitize)
						$value = $this->sanitizeData($columnName, $value);
	
					$this->data[$columnName] = $value;
				}
				else
				{
					$this->data[$columnName] = $value;
				}
			}
		}
		elseif (is_array($data) && isset($data[0]))
		{
			$this->clearData();
			
			foreach ($data as $key => $dataArray)
			{
				$this->appendData($dataArray);
			}	
		}
		else
			exit('Data argument must be an array in method ::setData()');
	}
	
/**
 * Appends the data to $this->data.
 *
 * Also cleans up the given data - trims, strips tags, converts special characters and does strpislashes.
 *
 * @access public
 * @param array $data Defines the data with keys and values to append to $this->data.
 * @return void
 */
	public function appendData($data) 
	{
		if (is_array($data))
		{
			// clean up the given data - trim, strip tags, convert special characters and strpislashes
			foreach ($data as $columnName => $value)
			{		
				if (!is_array($value))
				{
					$value = $this->sanitizeData($columnName, $value);	
					$data[$columnName] = $value;
				}
			
				$data[$columnName] = $value;
			}
			
			$this->data[] = $data;
		}
		else
			exit('Data argument must be an array in method ::setData()');
	}
	
/**
 * Creates an data array from $this->data.
 *
 * @access public
 * @param string $columnName Defines the column to index the array with. $this->idColumnName by default.
 * @return array A data array
 */
	public function toDataArray($columnName = NULL) 
	{
		if (!$this->has2dData())
			exit('Only 2d data supported for method ' . get_class($this) . '::toDataArray().');
			
		$columnName = (empty($columnName)) ? $this->idColumnName : $columnName;
		$dataArray  = array();
		
		while ($data = $this->getData())
		{
			// if the index is not set, create it and set the data
			// else create a subarray, if there are multiple rows on the same index
			if (!isset($dataArray[$data[$columnName]]))
				$dataArray[$data[$columnName]] = $data;
			else
			{
				// if the index already exists, and not array, copy data, delete the original,
				// and put it into subarray 
				if (!isset($dataArray[$data[$columnName]][0]))
				{
					$existingData 			 = $dataArray[$data[$columnName]];
					$dataArray[$data[$columnName]]   = array();
					$dataArray[$data[$columnName]][] = $existingData;
				}
				
				// then append the next data
				$dataArray[$data[$columnName]][] = $data;
			}
		}
		
		return $dataArray;
	}
	
/**
 * Sets the specified column in each $this->data subarray with the given value.
 *
 * @access public
 * @param string $column The column to set.
 * @param mixed  $data   The data to set column to.
 * @return void
 */
	public function setColumn($columnName, $dataValue) 
	{
		if ($this->has2dData())
		{
			if (!is_array($dataValue))
			{
				$value = $this->sanitizeData($columnName, $dataValue);
				
				foreach ($this->data as $key => $dataRow)
				{
					$this->data[$key][$columnName] = $dataValue;
				}
			}
			else
				exit('Data value cannot be array in method ' . get_class($this) . '::setColumn()');
		}
		else
			exit('Only 2d data supported in method ' . get_class($this) . '::setColumn()');
	}

/**
 * Checks the column rules to determine if the column is binary.
 *
 * @access protected
 * @param  string $columnName The column name to check
 * @return bool TRUE if binary, FALSE otherwise
 */
	protected function isColumnBinary($columnName)
	{
		if (!isset($this->columnRules[$columnName]['binary']))
			return FALSE;
		else
			return TRUE;
	}
	
/**
 * Checks the column rules to determine if the column is integer.
 *
 * @access protected
 * @param  string $columnName The column name to check
 * @return bool TRUE if integer, FALSE otherwise
 */
	protected function isColumnInteger($columnName)
	{
		if (!isset($this->columnRules[$columnName]['integer']))
			return FALSE;
		else
			return TRUE;
	}
	
/**
 * Checks the column rules to determine if the column is html.
 *
 * @access protected
 * @param  string $columnName The column name to check
 * @return bool TRUE if html, FALSE otherwise
 */
	protected function isColumnHtml($columnName)
	{
		if (!isset($this->columnRules[$columnName]['html']))
			return FALSE;
		else
			return TRUE;
	}

/**
 * Checks the column name to determine if the column is identifier.
 *
 * @access protected
 * @param  string $columnName The column name to check
 * @return bool TRUE if identifier, FALSE otherwise
 */
	protected function isColumnIdentifier($columnName)
	{
		if (strpos($columnName, 'id_') === 0)
			return TRUE;
		else
			return FALSE;
	}
	
/**
 * Sanities the given value for safer handling.
 *
 * @access public
 * @param  string $column The column which sanitazing rules will be used.
 * @param  mixed  $data   The data to sanitize.
 * @return mixed  $data   The sanitized data.
 */
	public function sanitizeData($columnName, $data)
	{
		$magicQuotesGpc = get_magic_quotes_gpc() ? TRUE : FALSE;
		
		$data = trim($data);
	
		if (!$this->isColumnBinary($columnName))
		{
			// strip tags by default, except if overriden by column rule
			if (!$this->isColumnHtml($columnName))
			{
				$data 	= strip_tags($data);
				$data	= htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
			}
			
			if ($magicQuotesGpc)
				$data = stripslashes($data);
		}
		
		return $data;
	}
	
/**
 * Clears (sets to NULL) the data from $this->data.
 *
 * Accepts columns to clear from $this->data (Works only for single object data (1-dimensional array $this->data)).
 * If no argument passed, clears all data from current object.
 * @access public
 * @param mixed Data columns to clear.  
 * @return void
 */
	public function clearData($columnsToClear = NULL) 
	{
		// if 1-dimensional data
		if (!$this->has2dData())
		{
			// if no columns defined to clear, clear all data.
			// if array passed, clear data defined by passed column names
			// if string passed (column name), clear data from that column name
			if (!$columnsToClear)
				$this->data = array();
			elseif (is_array($columnsToClear))
			{
				foreach ($columnsToClear as $columnName)
					$this->data[$columnName] = NULL;
			}
			elseif (is_string($columnsToClear))
				$this->data[$columnsToClear] = NULL;
		}
		else
		{
			if ($columnsToClear)
				exit('Cannot clear specified columns in 2-dimensional data in method ' . get_class($this) . '::clearData().');
			else
				$this->data = array();
		}
	}
	
/**
 * Unsets the data from $this->data.
 *
 * Accepts columns to unset from $this->data (Works only for single object data (1-dimensional array $this->data)).
 * @access public
 * @param mixed Data columns to clear.  
 * @return void
 */
	public function unsetData($columnsToClear) 
	{
		// if 1-dimensional data
		if (!$this->has2dData())
		{
			// if array passed, clear data defined by passed column names
			// if string passed (column name), clear data from that column name
			if (is_array($columnsToClear))
			{
				foreach ($columnsToClear as $columnName)
					unset($this->data[$columnName]);
			}
			elseif (is_string($columnsToClear))
				unset($this->data[$columnsToClear]);
		}
		else
			exit('Cannot clear specified columns in 2-dimensional data in method ' . get_class($this) . '::clearData().');
	}
	
/**
 * Counts the data from $this->data.
 *
 * Counts the data array and returns the count if $this->data is 2-dimensional.
 * Returns 1 if $this->data is 1-dimensional, 0 if no data.
 * @access public
 * @return int Count of rows in $this->data.  
 */
	public function countData() 
	{
		// check if $this->data is 2-dimensional
		// if it it, return the number of rows
		// else if data is 1-dimensional, return 1 if not empty, 0 if empty
		if ($this->has2dData())
			return count($this->data);
		elseif (!empty($this->data))
			return 1;
		else 
			return 0;
	}
	
/**
 * Creates columns and value pairs from $this->data for INSERT query.
 *
 * Returns the array containing strings used in INSERT queries.
 * The returned array has two keys. ['columns'] holding the column names in form of "(column1, column2, ..., columnN)"
 * and ['values'] holding corresponding values in form of "('string1', interger2, ..., 'stringN')".
 *
 * @access private
 * @return array $returnData Holds columns and values strings.
 */
	private function createInsertColumnsAndValues()
	{
		$columns 	  = array();
		$valueExpressions = array();
		
		// set data index counter to init state
		$this->resetDataIndexCounter();
		
		while ($data = $this->getData())
		{
			if ($this->checkIfColumnExists(self::COLUMN_NAME_CREATED))
				$data[self::COLUMN_NAME_CREATED] = date(Database::DATE_FORMAT);
				
			if ($this->checkIfColumnExists(self::COLUMN_NAME_MODIFIED))
				$data[self::COLUMN_NAME_MODIFIED] = date(Database::DATE_FORMAT);
					
			$values = array();
			
			foreach ($data as $columnName => $value)
			{
				// skip from passing if not an array, if not in list of columns to skip,
				// or not in table skeleton at all (for example $_POST['action']) 
				if (!is_array($value) && !in_array($columnName, $this->columnsToSkip) && array_key_exists($columnName, $this->tableSkeleton))
				{
					if (!in_array($columnName, $columns))
						$columns[] = $columnName;
					
					//$value        = mysql_real_escape_string($value);
					// if the column name begins with "id_", the value is integer, so don't put it into apostrophs
					//if (!$this->isColumnBinary($columnName))
						$value = Database::escape_string($value);
						
					$value 	  = $this->getQuotedValue($value, $columnName);
					$values[] = $value;
				}
			}
			
			// format the values with commas, put into parenthess
			$queryString = '(' . join(', ', $values) . ')';
			
			if ($this->has2dData())
			{
				$valueExpressions[] = $queryString;
			}
			else
			{
				$valueExpressions = $queryString;
				break;
			}
		}

		// set data index counter back to init state
		$this->resetDataIndexCounter();
		
		if (empty($valueExpressions))
			return FALSE;
			
		if (is_array($valueExpressions))
			$valueExpressions = join(', ', $valueExpressions);
	
		$columnExpression = '(' . join(', ', $columns) . ')';
		
		return $columnExpression . ' VALUES ' . $valueExpressions;
	}
	
/**
 * Creates columns and value pairs from $this->data for UPDATE query.
 *
 * Returns the array containing strings used in UPDATE queries.
 * The returned array has two keys. ['columns'] holding the column names in form of "(column1, column2, ..., columnN)"
 * and ['values'] holding corresponding values in form of "('string1', interger2, ..., 'stringN')".
 *
 * @access private
 * @return array $returnData Holds columns and values strings.
 */
	private function createUpdateColumnsAndValues()
	{
		$returnData = FALSE;

		// $this->data must be a 1-dimensional array
		if (!$this->has2dData())
		{
			foreach ($this->data as $columnName => $value)
			{
				// skip from passing if not an array, if not in list of columns to skip,
				// or not in table skeleton at all (for exapmple $_POST['action']) 
				if (!is_array($value) && !in_array($columnName, $this->columnsToSkip) && array_key_exists($columnName, $this->tableSkeleton))
				{
					//if (!$this->isColumnBinary($columnName))
						$value = Database::escape_string($value);
					
					$value 	  = $this->getQuotedValue($value, $columnName);
					$returnData[] = $columnName . '=' . $value;
				}
			}
			
			$returnData = join(', ', $returnData);
		}
		
		return $returnData;
	}
	
/**
 * Creates columns expression for the insert query.
 *
 * @access private
 * @param $array Data to create the expression from
 * @return string Expression.
 */
	private function getColumnsExpression($data)
	{
		$columns = array();
		
		foreach ($data as $columnName => $value)
		{
			// skip from passing if not an array, if not in list of columns to skip,
			// or not in table skeleton at all (for example $_POST['action']) 
			if (!is_array($value) && !in_array($columnName, $this->columnsToSkip) && array_key_exists($columnName, $this->tableSkeleton))
			{
				if (!in_array($columnName, $columns))
					$columns[] = $columnName;
			}
		}
		
		$columns = join(', ', $columns);

		return $columns;
	}
	
/**
 * Creates values expression for the insert query.
 *
 * @access private
 * @param $array Data to create the expression from
 * @return string Expression.
 */
	private function getValuesExpression($data)
	{
		$values 	   = array();
		
		foreach ($data as $columnName => $value)
		{
			// skip from passing if not an array, if not in list of columns to skip,
			// or not in table skeleton at all (for example $_POST['action']) 
			if (!is_array($value) && !in_array($columnName, $this->columnsToSkip) && array_key_exists($columnName, $this->tableSkeleton))
			{
				// if the column name begins with "id_", the value is integer, so don't put it into apostrophs
				//if (!$this->isColumnBinary($columnName))
					$value = Database::escape_string($value);
				
				$value 	  = $this->getQuotedValue($value, $columnName);
				$values[] = $value;
			}
		}
		
		// format the values with commas, put into parenthess
		$values = join(', ', $values);
		
		return $values;
	}
	
/**
 * Returns the value quoted for query, depending on the column rules.
 *
 * @access private
 * @param mixed $value A value to quote
 * @param string Column name to check rules for
 * @return string The quoted value
 */
	private function getQuotedValue($value, $columnName)
	{
		if ($this->isColumnIdentifier($columnName) || $this->isColumnInteger($columnName))
			return $value;
		elseif ($this->isColumnBinary($columnName))
		{
			if (Database::isMssql())
				return "CONVERT(VARBINARY(max), '" . $value . "')";
			else
				return "'" . $value . "'";
		}
		else
			return $this->stringValuePrefix . "'" . $value . "'";
	}
/**
 * Describe table $this->data and save column names into $this->tableSkeleton.
 *
 * @access public 
 * @return void
 */
	public function describeTable() 
	{
		if (Database::isMssql(Database::$currentLinkId))
		{
			$this->query     = "sp_columns " . $this->tableName;
			$columnNameField = 'COLUMN_NAME';
		}
		elseif (Database::isMysql(Database::$currentLinkId))
		{
			$this->query     = "DESCRIBE " . $this->tableName;
			$columnNameField = 'Field';
		}
		
		$result = Database::query($this->query);

		if (Database::num_rows($result))
		{
			while ($row = Database::fetch_assoc($result))
				$this->tableSkeleton[$row[$columnNameField]] = NULL;
		}
		else
			exit("Table " . $this->tableName . ' does not exists in method ' . get_class($this) . '::describeTable().');
	}
	
/**
 * Populates $this->data with columns containing NULL values from table skeleton
 * defined in $this->tableSkeleton.
 *
 * @access public 
 * @return void
 */
	public function initializeData() 
	{
		// first clear the data since there may be data already
		$this->data = $this->tableSkeleton;
	}

/**
 * Checks if there is data in $this->data.
 *
 * @access public 
 * @return mixed number of rows if there is data, FALSE otherwise
 */
 	public function hasData()
	{
		// check if $this->data is 2-dimensional
		// if it is, return the count of rows. If no rows, return FALSE
		if ($this->has2dData())
		{
			$count = count($this->data);
				
			if ($count > 0)
				return $count;
			else
				return FALSE;
		}
		elseif (!empty($this->data))
			return 1;
		else
			return FALSE;
	}

	
/**
 * Calls validation and sets the validated data to $this->data if validation succesful.
 *
 * @access public 
 * @return bool TRUE if validation succesful, FALSE otherwise
 */
	public function validateData()
	{
		// validate this data object. If valid, set the procesed data as new data
		$processedData = Validation::processData($this);
		
		if ($processedData === FALSE)
			return FALSE;
		else
		{
			$this->setData($processedData, FALSE);
			return TRUE;
		}
	}
	
/**
 * Checks if column exists in the given table in current database.
 *
 * @access private 
 * @param string $columnName Name of the column to check if exists.
 * @param string $tableName Name of the table to check in.
 * @return bool TRUE if column exists, FALSE otherwise.
 */
	private function checkIfColumnExists($columnName)
	{
		if (Database::isMssql(Database::$currentLinkId))
		{
			$this->query     = "sp_columns " . $this->tableName;
			$columnNameField = 'COLUMN_NAME';
		}
		elseif (Database::isMysql(Database::$currentLinkId))
		{
			$this->query     = "DESCRIBE " . $this->tableName;
			$columnNameField = 'Field';
		}
		
		$result = Database::query($this->query);
		
		while ($row = Database::fetch_assoc($result))
		{
			if ($row[$columnNameField] == $columnName)
				return TRUE;
		}
		
		return FALSE;
	}
	
/**
 * Returns id of the last inserted row.
 *
 * @access public 
 * @return int Id of the last inserted row
 */
	public function getInsertId()
	{
		return Database::insert_id();
	}
/**
 * Checks if $this->data is 2-dimensional
 *
 * @access public 
 * @return bool TRUE if 2-dimensional, FALSE otherwise.
 */
	public function has2dData()
	{
		if (isset($this->data[0]))
			return TRUE;
		else
			return FALSE;
	}
	
/**
 * Resets the data index counter
 *
 * @access public 
 * @return void
 */
	public function resetDataIndexCounter()
	{
		$this->dataIndexCounter = 0;
	}
	
/**
 * Holds the procedure for encripting the data
 * 
 * @access public static
 * @param string $password The password to encript.
 * @return string The encripted password.
 */
	public static function encriptData($password)
	{
		$salt = md5($password);
		return sha1($password.'Sp33dY'.$salt);
	}
	
/**
 * Returns timestamp of the difference between two dates.
 * 
 * @access public static
 * @param string $startDate
 * @param string $endDate
 * @return int Time difference timestamp
 */
	public static function timeDifference($startDate, $endDate) 
	{
		if (Database::isMssql())
		{
			$startTimestamp = Database::mssqlStrtotime($startDate);
			$endTimestamp   = Database::mssqlStrtotime($endDate);
		}
		else
		{
			$startTimestamp = strtotime($startDate);
			$endTimestamp   = strtotime($endDate);
		}
		
		$seconds = $endTimestamp - $startTimestamp;

		$days       = floor($seconds / 86400);
		$seconds -= $days * 86400;
		
		$hours      = floor($seconds / 3600);
		$seconds -= $hours * 3600;
		
		$minutes   = floor($seconds / 60);
		$seconds -= $minutes * 60;
		
		return array("d" => $days, "h" => $hours, "m" => $minutes, "s" => $seconds);
	}
	
	public function dump()
	{
		echo '<pre>';
		var_dump($this);
		echo '<pre>';
	}
	public static function reindexFlippedArray($array)
	{
		$newArray = array();
		foreach($array as $key => $value)
			if(is_numeric($key))
				$newArray[] =  preg_replace('/([0-9]{2}|[0-9]{1})\_\_\_/', '', $value);
			else
				$newArray[] = preg_replace('/([0-9]{2}|[0-9]{1})\_\_\_/', '', $key);
		return $newArray;
	}
	public static function sanitizeString4database($string)
	{
				
		$string = trim(
			preg_replace('%(?:
			\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
			)%xs', '', //remove 4byte utf8 characters
				(
					preg_replace ('/[^\p{L}\p{N}\p{S}\p{P}\s]/u', '', $string) //remove unknown characters
				)
			)
		);
		return $string;

	}
	public static function getCliArrayString($array, $mainIndex = 'ajax')
	{
	
		function createDataList($listArray , $data = '', $lastKey = '', $mainIndex)
		{
			$html = '';
			
			$stringg = array();
			foreach($listArray as $key => $valueArray)
			{
				if(is_array($valueArray))
				{
					$html =  $lastKey . '[' . $key . ']';
					$data = createDataList($valueArray, '[' . $key . ']', $html, $mainIndex);
					$html .= $data['work'];
					$stringg += $data['string'];
				}
				else if($key !== $valueArray)
				{
					$html = $lastKey . "[" . $key . "]" . "=" . $valueArray;
					$stringg[$html] = $mainIndex . str_replace(array("'", '"', ";", '(', ')', '&', ' '), array("\'", '\"', '\;', '\(', '\)', '\&', '---'), $html);
				}
			}
			return array('work' => $html, 'string' => $stringg);
		}
		$postStringArray = createDataList($array , $data = '', $lastKey = '', $mainIndex);
		return join(' ', $postStringArray['string']);
	}
}
?>