<?php

namespace Shakal;

/**
 * \brief Nízkoúrovňové rozhranie k databáze MySQL.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class mysql_SQLDriver implements ISQLDriver
{
	private $_link = null;

	/**
	 * \param array settings
	 */
	public function connect($settings)
	{
		// Pripojenie k databáze
		$this->_link = mysql_connect($settings['server'],
		                             $settings['username'],
		                             $settings['password']);
		if ($this->_link === false)
			throw new SQLException('Could not connect to MySQL server');

		// Výber databázy
		if (!mysql_select_db($settings['database']))
			throw new SQLException('Could not select database '.$settings['database']);

		// Nastavenie kódoavania
		$this->nativeQuery('SET NAMES \'utf8\'');
	}

	public function disconnect()
	{
		// Kontrola pripojenia k databáze
		if (is_null($this->_link))
			throw new SQLException('Not connected to database');

		// Odpojenie od databázy
		if (!mysql_close($this->_link))
			throw new SQLException('Could not disconnect from MySQL server');

		// Obnovenie odkazu na databázu
		$this->_link = null;
	}

	/**
	 * \param mixed value
	 * \param string type
	 * \return string
	 */
	public function escape($value, $type = null)
	{
		// Ak nie je zadany typ - detekcia typu
		if (is_null($type)) {
			if (is_integer($value))
				$type = SQL::Int;
			elseif (is_float($value))
				$type = SQL::Float;
			else
				$type = SQL::Text;
		}

		switch ($type) {
			case SQL::Int:
				return (int)$value;
			case SQL::Float:
				return (float)$value;
			case SQL::Date:
				return ($value instanceof DateTime) ? $value->format("'Y-m-d'") : date("'Y-m-d'");
			case SQL::Time:
				return ($value instanceof DateTime) ? $value->format("'H:i:s'") : date("'H:i:s'");
			case SQL::DateTime:
				return ($value instanceof DateTime) ? $value->format("'Y-m-d H:i:s'") : date("'Y-m-d H:i:s'");
			case SQL::UnixTime:
				return 'FROM_UNIXTIME('.((int)$value).')';
			// Rovnaké ošetrenie pre stĺpec aj tabuľku
			case SQL::Field:
			case SQL::Table:
				return '`'.$value.'`';
			// Rovnaké ošetrenie pre binárne aj textové dáata
			case SQL::Text:
			case SQL::Blob:
				return mysql_real_escape_string($value);
			default:
				trigger_error("Bad variable format", E_USER_ERROR);
				return null;
		}
	}

	/**
	 * \param resource result
	 * \param int type
	 * \return array
	 */
	public function fetchArray($result, $type)
	{
		// Prevod typu výsledku v Shakal\SQL na typ pre mysql_fetch_array
		switch ($type) {
			case SQL::Assoc:
				$resultType = MYSQL_ASSOC; break;
			case SQL::Num:
				$resultType = MYSQL_NUM;   break;
			case SQL::Both:
				$resultType = MYSQL_BOTH;  break;
			default:
				trigger_error("Bad result type", E_USER_ERROR);
				return;
		}

		return mysql_fetch_array($result, $resultType);
	}

	/**
	 * \param resource result
	 * \param string className
	 * \param array params
	 * \return mixed
	 */
	public function fetchObject($result, $className = 'stdClass', $params = null)
	{
		if (is_null($params)) {
			return mysql_fetch_object($result, $className);
		}
		else {
			return mysql_fetch_object($result, $className, $params);
		}
	}

	/**
	 * \param string query
	 * \return resource
	 */
	public function nativeQuery($query)
	{
		$result = mysql_query($query, $this->_link);
		if ($result === false) {
			$errNo = mysql_errno($this->_link);
			$error = mysql_error($this->_link);
			throw new SQLException($error, $errNo, $query);
		}
		return $result;
	}

	/**
	 * \param string savePoint
	 */
	public function begin($savePoint = null)
	{
		if (mysql_query('START TRANSACTION', $this->_link) === false)
			throw new SQLException('Could not start transaction: '.mysql_error($this->_link), mysql_errno($this->_link));
	}

	/**
	 * \param string savePoint
	 */
	public function commit($savePoint = null)
	{
		if (mysql_query('COMMIT', $this->_link) === false)
			throw new SQLException('Could not commit transaction: '.mysql_error($this->_link), mysql_errno($this->_link));
	}

	/**
	 * \param string savePoint
	 */
	public function rollback($savePoint = null)
	{
		if (mysql_query('ROLLBACK', $this->_link) === false)
			throw new SQLException('Could not rollback transaction: '.mysql_error($this->_link), mysql_errno($this->_link));
	}

	/**
	 * \param resource result
	 * \return int|bool
	 */
	public function numRows($result)
	{
		return mysql_num_rows($result);
	}

	/**
	 * \return int
	 */
	public function affectedRows()
	{
		return mysql_affected_rows($this->_link);
	}

	/**
	 * \param string seqName
	 * \return int
	 */
	public function lastInsertId($seqName = null)
	{
		$id = mysql_insert_id($this->_link);
		if ($id === false)
			throw new SQLException('Not connected to database');
		return $id;
	}

	/**
	 * \param resource result
	 */
	public function free($result)
	{
		return mysql_free_result($result);
	}

	/**
	 * \param resource result
	 * \param int row
	 * \return bool
	 */
	public function seek($result, $row)
	{
		return mysql_data_seek($result, $row);
	}
}


?>
