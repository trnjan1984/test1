<?php
/**
 * Class for manipulating database connections
 */
class Database
{
	const LINK_TYPE_MYSQL = 1;
	const LINK_TYPE_MSSQL = 2;
	const DATE_FORMAT     = 'Y-m-d H:i:s';
	
/**
 * Holds the name of the selected database
 * @var string
 */
	static public $databaseName;
/**
 * Holds all created database links
 * @var array 
 */
	static private $links = array();
/**
 * Id of the current link used 
 * @var id
 */
	static public $currentLinkId = NULL;
	
/**
 * Constructor
 *
 * @access private
 */
	private function __construct() 
	{
	}
	
/**
 * Static. Processes database connection parameters.
 *
 * Checks for default paramaters if custom not provided
 * @param array Connection parameters: server, user, password, database.
 * @return array The processed parameters.
 */
 	private static function processParameters($parameters = NULL)
	{
		$returnParameters	= array('server'   => NULL,
						'user'     => NULL, 
						'password' => NULL, 
						'database' => NULL);
		// get parameters, if not provided, use default parameters
		$returnParameters['server']   = (!$parameters['server'])   ? Config::DB_DEFAULT_SERVER   : $parameters['server'];
		$returnParameters['user']     = (!$parameters['user'])     ? Config::DB_DEFAULT_USER     : $parameters['user'];
		$returnParameters['password'] = (!$parameters['password']) ? Config::DB_DEFAULT_PASSWORD : $parameters['password'];
		$returnParameters['database'] = (!$parameters['database']) ? Config::DB_DEFAULT_DATABASE : $parameters['database'];
		
		return $returnParameters;
	}
	
/**
 * Check if the link is MySQL type.
 *
 * @access public
 * @param $idLink
 * @return bool TRUE if MySQL, FALSE otherwise.
 */
 	public static function isMysql($idLink = NULL)
	{
		//Dump::liveDump('------------------');
		//Dump::liveDump($idLink);	
		if (!$idLink)
			$idLink = self::$currentLinkId;
		//Dump::liveDump($idLink);	
		//Dump::liveDump(self::$links);
		//Dump::liveDump(self::$links);	
		if (!array_key_exists($idLink, self::$links))
		//if(isset(self::$links['identifier']))
		//if (!array_key_exists($idLink, User::$sessionData['sql'])
			exit('Wrong link id passed for Database::isMysql()');
		elseif (self::$links[$idLink]['type'] === self::LINK_TYPE_MYSQL)
			return TRUE;
		else
			return FALSE;
	}
	
/**
 * Check if the link is MSSQL type.
 *
 * @access public
 * @param $idLink
 * @return bool TRUE if MSSQL, FALSE otherwise.
 */
 	public static function isMssql($idLink = NULL)
	{
		/*if (!$idLink)
			$idLink = self::$currentLinkId;
			
		if (!array_key_exists($idLink, self::$links))
			exit('Wrong link id passed for Database::isMssql()');
		elseif (self::$links[$idLink]['type'] === self::LINK_TYPE_MSSQL)
			return TRUE;
		else*/
			return FALSE;
	}
 
/**
 * Static. Sets the link identifier and current database type from $links array 
 * by index key as current link.
 *
 * @access public
 * @param int $idLinkLink index key as defined in $links array
 * @return void
 */
	public static function selectLinkId($idLink)
	{//Dump::liveDump($idLink);
		if (array_key_exists($idLink, self::$links))
			self::$currentLinkId = $idLink;
		else
			exit('Wrong link id passed for Database::selectLinkId()');
	}
	
/**
 * Static. Gets the current link identifier.
 *
 * @access public
 * @return void
 */
	public static function getCurrentLink()
	{
		if (isset(self::$currentLinkId))
			return self::getLink(self::$currentLinkId);
		else
			exit('No current link set for Database::getCurrentLink()');
	}
	
/**
 * Static. Gets the link identifier by its id.
 *
 * @access public
 * @param int $idLink Link index key as defined in $links array
 * @return void
 */
	private static function getLink($idLink)
	{
		if (array_key_exists($idLink, self::$links))
			return self::$links[$idLink]['identifier'];
		else
			exit('No link to get for Database::getCurrentLink()');
	}
	
/**
 * Static. Selects a database to use
 *
 * Also runs a "SET NAMES 'utf8' query to force using of UTF-8 encoding.
 * @access public
 * @param string $databaseName Name of the database to use by databse connection, as defined by self::$currentLinkId
 * @return void
 */
	public static function selectDatabase($databaseName = NULL)
	{
		self::$databaseName = $databaseName ? $databaseName : Config::DB_DEFAULT_DATABASE;

		/*if (self::isMssql(self::$currentLinkId))
		{
			if ( !mssql_select_db(self::$databaseName, self::getCurrentLink()) )
				exit ('Unable to select database.');
		
				// This is a possible solution to overcome character field length problems
				self::query('SET TEXTSIZE 2147483647');
				//ini_set('odbc.defaultlrl', '2147483647');
		}
		else*/
		if (self::isMysql(self::$currentLinkId))
		{
			if ( !mysqli_select_db(self::getCurrentLink(), self::$databaseName) )
				exit ('Unable to select database.');
			
			//self::query("SET NAMES 'utf8';");
			//self::query('SET DATEFORMAT ymd;');
		} 
		else
			exit('No link available for Database::selectDatabase()');
	}
/**
 * Static. Opens link to the MySQL database
 *
 * Connects to the database by given parameters, selects the newly created link as the current link
 * If no parameters passed, the default parameters wil be used for connection. 
 * @access public
 * @param array $parameters Default NULL, keys are 'server', 'user', 'password', 'database'
 * @return void
 */
	public static function connectMysql($parameters = NULL)
	{
		$parameters = self::processParameters($parameters);
		//Dump::liveDump($parameters);
		// connect with given parameters, TRUE makes sure that new link will be created every time mysql_connect is called
		$link = 0;
		try {
			$link = mysqli_connect($parameters['server'], $parameters['user'], $parameters['password'], $parameters['database']);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			//$link = 0;
			
		}
		
		//$link = mysql_connect($parameters['server'], $parameters['user'], $parameters['password'], TRUE, MYSQL_CLIENT_COMPRESS);
		
		/*$tryCount = 1;
		while(!$link && $tryCount < 20)
		{
			try {
				$link = mysql_connect($parameters['server'], $parameters['user'], $parameters['password'], TRUE, MYSQL_CLIENT_COMPRESS);
			} catch (Exception $e) {
				//echo 'Caught exception: ',  $e->getMessage(), "\n";
				$link = 0;
				echo 'Ne mogu se spojiti na bazu.';
			}
			$tryCount++;
		}
		*/
		
		mysqli_set_charset($link, 'utf8');
mysqli_query($link, "SET NAMES 'utf8'");
mysqli_query($link, "SET NAMES utf8");
mysqli_query($link, "SET CHARACTER SET utf8");
mysqli_query($link, "SET COLLATION_CONNECTION='utf8_unicode_ci'");		

//$db->set_charset("utf8"); // MySQL


		
		
		// save the database link to $links array
		//Dump::liveDump($link);
		if ($link)
			self::$links[] = array('identifier' => $link, 'type' => self::LINK_TYPE_MYSQL);
			//User::updateSession(array('sql' => array('identifier' => $link, 'type' => self::LINK_TYPE_MYSQL)));
		else
			exit ('Unable to connect to database server.');
		
		// selects the new link (last key in the array $links) as current
		$keys = array_keys(self::$links);
		//$keys = array_keys(User::$sessionData['sql']);
		self::selectLinkId(array_pop($keys));
		//self::selectLinkId($link);
		//Dump::liveDump(array_pop(array_keys(self::$links)));
		self::selectDatabase($parameters['database']);
		
	}
	
/**
 * Static. Opens link to the MsSQL database
 *
 * Connects to the database by given parameters, selects the newly created link as the current link
 * If no parameters passed, the default parameters wil be used for connection. 
 * @access public
 * @param array $parameters Default NULL, keys are 'server', 'user', 'password', 'database'
 * @return void
 */
	public static function connectMssql($parameters = NULL)
	{
		// set these values to overcome text fields truncating problems
		ini_set('mssql.textlimit', '2147483647');
		ini_set('mssql.textsize',  '2147483647');
		ini_set('mssql.connect_timeout',  5);
		ini_set('mssql.timeout',  0);
		ini_set('mssql.datetime_convert',  'On');
		ini_set('mssql.charset',  'UTF-8');

		$parameters = self::processParameters($parameters);

		// connect with given parameters, TRUE makes sure that new link will be created every time mysql_connect is called
		$link = mssql_connect($parameters['server'], $parameters['user'], $parameters['password'], TRUE);

		// save the database link to $links array
		if ($link)
			self::$links[] = array('identifier' => $link, 'type' => self::LINK_TYPE_MSSQL);
		else
			exit ('Unable to connect to database server.');
			
		// selects the new link (last key in the array $links) as current
		self::selectLinkId(array_pop(array_keys(self::$links)));
		self::selectDatabase($parameters['database']);
	}

/**
 * Static. Closes the link or all links to database(s)
 *
 * If no link index key passed, all created links will be closed. Else closes the passed link. 
 * @access public
 * @param int $idLink Default NULL, link index key as defined in $links array
 * @return void
 */
	public static function closeLink($idLink = NULL) 
	{
		if ($idLink)
		{
			$linkToClose = self::getLink($idLink);
			
			if (self::isMssql($idLink))
				mssql_close($linkToClose);
			if (self::isMysql($idLink))
				mysqli_close($linkToClose);
				
			unset(self::$links[$idLink]);
		}
		else
		{
			foreach (self::$links as $idLink => $link)
			{
				$linkToClose = self::getLink($idLink);
				
				if (self::isMssql($idLink))
					mssql_close($linkToClose);
				if (self::isMysql($idLink))
					mysqli_close($linkToClose);
			}
			
			self::$links = array();
		}
	}

/**
 * Queries the database using the current link.
 *
 * @access public
 * @param $query The query to run
 * @return resource Result returned by query.
 */
	public static function query($query)
	{
		//if(isset(User::$sessionData['username']))
			//$username = User::$sessionData['username'];
		//else
			$username = 'No User';
		$query.= ' /*Username ' . $username . ' Username*/';
		if (self::isMssql())
			$result = mssql_query($query, Database::getCurrentLink());
		elseif (self::isMysql())
			$result = mysqli_query(Database::getCurrentLink(), $query);

		if ($result)
			return $result;
		else
		{
			//echo $query;
			Dump::liveDump($query);
			return FALSE;
		}
	}
	
/**
 * Fetches the result by assoc.
 *
 * @access public
 * @param $result Result to fetch
 * @return array Row fetched from the result.
 */
	public static function fetch_assoc(&$result)
	{
		if (self::isMssql())
			return mssql_fetch_assoc($result);
		elseif (self::isMysql())
			return mysqli_fetch_assoc($result);
	}
	
/**
 * Fetches the result by row.
 *
 * @access public
 * @param $result Result to fetch
 * @return array Row fetched from the result.
 */
	public static function fetch_row(&$result)
	{
		if (self::isMssql())
			return mssql_fetch_row($result);
		elseif (self::isMysql())
			return mysqli_fetch_row($result);
	}
	
/**
 * Returns the number of rows in result.
 *
 * @access public
 * @param $result Result to check
 * @return array Row fetched from the result.
 */
	public static function num_rows($result)
	{
		if (self::isMssql())
			return mssql_num_rows($result);
		elseif (self::isMysql())
			return mysqli_num_rows($result);
	}
	
/**
 * Returns the number of affected rows from database on a current link.
 *
 * @access public
 * @return array Rows affected.
 */
	public static function affected_rows()
	{
		if (self::isMssql())
			return mssql_rows_affected(self::getCurrentLink());
		elseif (self::isMysql())
			return mysqli_affected_rows(self::getCurrentLink());
	}
	
/**
 * Returns table names from database.
 *
 * @access public
 * @return array Table names. 
 */
	public function getTablesNames()
	{
		if (!isset(self::$currentLinkId))
			exit("No database selected.");
			
		$allTables = array();
		
		if (self::isMssql())
		{
			$result = self::query('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = \'BASE TABLE\'');
		
			while ($table = self::fetch_assoc($result))
				$allTables[] = $table['TABLE_NAME'];
		}	
		elseif (self::isMysql())
		{
			$result = self::query('SHOW TABLES');
			
			while ($table = self::fetch_row($result))
				$allTables[] = $table[0];
		}
				
		self::free_result($result);
		
		return $allTables;
	}
	
/**
 * Frees the result.
 *
 * @access public
 * @param resource $result
 * @return array Table names. 
 */
	public static function free_result($result)
	{
		if (self::isMssql())
			mssql_free_result($result);
		elseif (self::isMysql())
			mysqli_free_result($result);
	}
	
/**
 * Gets the result.
 *
 * @access public
 * @param resource $result,
 * @param int $columnId The id of the column to retrieve. 
 * @param int $field The id of the field to retrieve, 0 if not defined. 
 * @return array Table names. 
 */
	public function result($result, $rowId, $field = NULL)
	{
		if (self::isMssql())
		{
			$field = ($field) ? $field : 0;
			return mssql_result($result, $rowId, $field);
		}
		elseif (self::isMysql())
			return mysqli_result($result, $rowId, $field);
	}
	
/**
 * Returns the last id inserted in MSSQL database.
 *
 * @access public
 * @param resource $result
 * @return array Table names. 
 */
	private function custom_mssql_insert_id() {
		$id     = FALSE;
		$result = self::query('SELECT @@identity AS id');
		
		if ($row = self::fetch_row($result))
			$id = trim($row[0]);
		
		self::free_result($result);
		
		return $id;
	}

/**
 * Returns the last id inserted.
 *
 * @access public
 * @return int Last inserted id. 
 */
	public static function insert_id()
	{
		if (self::isMssql())
			return self::custom_mssql_insert_id();
		elseif (self::isMysql())
			return mysqli_insert_id(self::getCurrentLink());
	}
	
/**
 * Escapes the string for database input.
 *
 * @access public
 * @param string $value
 * @return string Escaped value. 
 */
	public static function escape_string($value)
	{
		if (self::isMssql())
		{                  
			//$value = addslashes($value);
			$value = str_replace("'",  "''",     $value);
			$value = str_replace("\0", "[NULL]", $value);
			//$value = str_replace('"', '\"', $value);
			//$value = str_replace("&", "\&", $value);
			 
			return $value;
		}
		elseif (self::isMysql())
			return mysqli_real_escape_string(self::getCurrentLink(), $value);
	}
	
/**
 * Strtotime function for Mssql dates, since there are issues with the standard one.
 *
 * @access public
 * @param string $mssqlDateTime An mssql datetime
 * @return string Timestamp
 */
	public static function mssqlStrtotime($mssqlDateTime)
	{
		$newDatetime = preg_replace('/:[0-9][0-9][0-9]/','',$mssqlDateTime);
		$time	     = strtotime($newDatetime);
		
		return $time;
	}
	
	public static function datetimeToSqlDatetime($datetime)
	{
		if ($datetime == "")
			return $datetime;
		else		
			return date("Y-m-d H:i:s", strtotime($datetime));
	}
	
	public static function sqlDatetimeToSlashedDatetime($datetime)
	{		
		if ($datetime == "")
			return $datetime;
		else	
			return date("d/m/Y H:i:s", strtotime($datetime));
	}		
	
/**
 * Static. Converts slashed date, dd/mm/yyyy into an SQL date, yyyy-mm-dd
 *
 * @access public
 * @param string $date
 * @return string SQL date.
 */
	public static function convertSlashedDateToSQLDate($date)
	{
		list($day, $month, $year) = explode("/", $date);
		
		return $year . "-" . $month . "-" . $day;
	}

/**
 * Static. Converts SQL date, yyyy-mm-dd into a slashed date, dd/mm/yyyy
 *
 * If the date is not provided, the empty string is returned. (This function is expected to be used mainly in forms)
 * @access public
 * @param string SQL formatted $date
 * @return string slashed date.
 */
	public static function convertSQLDateToSlashedDate($date)
	{
		if (empty($date))
			return "";
			
		list($year, $month, $day) = explode("-", $date);
		
		return $day . "/" . $month . "/" . $year;
	}
}
?>