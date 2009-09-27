<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Výsledok databázových operácií.
 */

namespace Shakal;

/**
 * \class SQLResult
 * \brief Výsledok spustenia dotazu pre databázu.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class SQLResult implements \Countable, \IteratorAggregate
{
	/**
	 * \var SQLConnection
	 */
	private $_connection;
	private $_result;
	private $_driver;
	private $_rowNum = 0;

	/**
	 * Vytvorenie nového výsledku SQL dotazu.
	 * \param resource result
	 */
	public function __construct($result, SQLConnection &$connection)
	{
		$this->_result     = &$result;
		$this->_connection = &$connection;
		$this->_driver     = &$connection->driver();
	}

	/**
	 * Získanie riadku výsledku vo forme poľa. Viac informácii o type výsledku v
	 * sekcii \ref SQL_resultType "Typ výsledku". V prípade neúspechu (napr. už
	 * boli prečítané všetky záznamy) vráti \e false.
	 *
	 * Príklad použitia:
	 * \code
	 * while (($riadok = $result->fetchArray()) !== false) {
	 *   // Spracovanie
	 * }
	 * \endcode
	 *
	 * \sa SQLDriver::fetchArray()
	 * \return array|bool
	 */
	public function fetchArray($type = SQL::Both)
	{
		$data = $this->_driver->fetchArray($this->_result, $type);
		if ($data !== false) {
			$this->_rowNum++;
		}
		return $data;
	}

	/**
	 * Vytvorenie objektu z riadku tabuľky.
	 *
	 * \param string className
	 *   Názov triedy, ktorej inštancia sa má vytvoriť. V prípade, že nie je
	 *   zadaná žiadna trieda vytvorí sa inštancia stdClass.
	 * \param array params
	 *   Pole voliteľných parametrov poslaných konštruktoru triedy.
	 *
	 * \return object
	 *   Objekt obsahujúci dáta riadku. V prípade neúspechu vráti \e false.
	 */
	public function fetchObject($className = 'stdClass', $params = null)
	{
		$data = $this->_driver->fetchObject($this->_result, $className, $params);
		if ($data !== false) {
			$this->_rowNum++;
		}
		return $data;
	}

	/**
	 * Získanie riadku tabuľky ako asociatívne pole.
	 *
	 * \sa fetchArray()
	 * \return array|bool
	 */
	public function fetchAssoc()
	{
		return $this->_driver->fetchArray($this->_result, SQL::Assoc);
	}

	/**
	 * Získanie riadku tabuľky ako homogénne pole.
	 *
	 * \sa fetchArray()
	 * \return array|bool
	 */
	public function fetchRow()
	{
		return $this->_driver->fetchArray($this->_result, SQL::Num);
	}

	/**
	 * Získanie všetkych riadkov tabuľky do homogénneho poľa pričom každá
	 * položka zodpovedá riadku vrátenému funkciou fetchArray.
	 *
	 * \sa fetchArray()
	 * \return array
	 */
	public function fetchAllArray($type = SQL::Both)
	{
		$rows = array();
		while (($row = $this->fetchArray($type)) !== false) {
			array_push($rows, $row);
		}
		return $rows;
	}

	/**
	 * Získanie všetkých riadkov vo forme homogénneho poľa, ktorého každá
	 * položka je asociatívnym poľom.
	 *
	 * \sa fetchAllArray(), fetchAssoc()
	 * \return array
	 */
	public function fetchAllAssoc()
	{
		return $this->fetchAllArray(SQL::Assoc);
	}

	/**
	 * Získanie všetkých riadkov vo forme homogénneho poľa, ktorého každá
	 * položka je homogénnym poľom.
	 *
	 * \sa fetchAllArray(), fetchRow()
	 * \return array
	 */
	public function fetchAllRows()
	{
		return $this->fetchAllArray(SQL::Num);
	}

	/**
	 * Získanie nízkoúrovňového výsledku %SQL dotazu.
	 * \return resource
	 */
	public function &result()
	{
		return $this->_result;
	}

	/**
	 * Získanie všetkych položiek vo forme homogénneho poľa obsahujúceho
	 * objekty.
	 *
	 * \sa fetchObject()
	 * \return array
	 */
	public function fetchAllObject($className = 'stdClass', $params = null)
	{
		$objects = array();
		while (($object = $this->fetchObject($className, $params)) !== false) {
			array_push($objects, $object);
		}
		return $objects;
	}

	/**
	 * Funkcia vráti výsledok ako asociatívne pole. Kľúčom poľa je stĺpec
	 * s číslom \a keyField a jeho hodnotou bude hodnota v stlpci, ktorého
	 * poradové číslo je \a valField.
	 * \throw SQLException
	 * \return array
	 */
	public function fetchPairs($keyField = 0, $valField = 1)
	{
		$assocArr = array();
		$rowNum = 1;
		while (($row = $this->fetchArray()) !== false) {
			if ($rowNum === 1) {
				if (!isset($row[$keyField]))
					throw new SQLException('Field "'.$keyField.'" does not exists.');
				if (!isset($row[$valField]))
					throw new SQLException('Field "'.$valField.'" does not exists.');
			}
			$assocArr[$row[$keyField]] = $row[$valField];
			$rowNum++;
		}
		return $assocArr;
	}

	/**
	 * Získanie jedinej hodnoty prvého riadku výsledku. Číslo stĺpca určuje
	 * argument \a field.
	 * \return string
	 */
	public function fetchOne($field = 0)
	{
		$result = array();
		$rowNum = 1;
		while (($row = $this->fetchArray()) !== false) {
			if ($rowNum === 1) {
				if (!isset($row[$field]))
					throw new SQLException('Field "'.$field.'" does not exists.');
			}
			array_push($result, $row[$field]);
		}
		return $result;
	}

	/**
	 * Získanie počtu riadkov vo výsledku.
	 * \return int
	 */
	public function count()
	{
		return $this->_driver->numRows($this->_result);
	}

	/**
	 * Získanie počtu aktualizovaných riadkov v poslednom dotaze.
	 * \return int
	 */
	public function affectedRows()
	{
		return $this->_driver->affectedRows($this->_result);
	}

	/**
	 * Presun na záznam \a rowNum. Ak databáza nepodporuje presun v záznamoch
	 * vyvolá funkcia výnimku SQLException.
	 * \throw SQLException
	 */
	public function seek($rowNum)
	{
		$this->_rowNum = $rowNum;
		return $this->_driver->seek($this->_result, $rowNum);
	}

	/**
	 * Získanie aktuálneho čísla záznamu.
	 *
	 * \return int
	 */
	public function rowNum()
	{
		return $this->_rowNum;
	}

	/**
	 * Získanie iterátoru pre prechádzanie záznamami. Volanie je ekvivalentné
	 * kódu:
	 * \code
	 * $result->getArrayIterator()
	 * \endcode
	 *
	 * Príklad použitia:
	 * \code
	 * foreach ($result as $row) {
	 *   // ...
	 * }
	 * \endcode
	 *
	 * \sa getArrayIterator()
	 * \return SQLArrayResultIterator
	 */
	public function getIterator()
	{
		return new SQLArrayResultIterator($this, SQL::Both);
	}

	/**
	 * Získanie iterátora na prechádzanie záznamami vo forme poľa. Typ poľa je
	 * určený agrumentom \a type.
	 *
	 * Príklad použitia:
	 * \code
	 * foreach ($result->getArrayIterator() as $row) {
	 *   // ...
	 * }
	 * \endcode
	 *
	 * \return SQLArrayResultIterator
	 */
	public function getArrayIterator($type = SQL::Both)
	{
		return new SQLArrayResultIterator($this, $type);
	}

	/**
	 * Získanie iterátora na prechádzanie záznamami vo forme objektu.
	 *
	 * Príklad použitia:
	 * \code
	 * foreach ($result->getObjectIterator() as $object) {
	 *   // ...
	 * }
	 * \endcode
	 *
	 * \return SQLObjectResultIterator
	 */
	public function getObjectIterator($className = 'stdClass', $args = null)
	{
		return new SQLObjectResultIterator($this, $className, $args);
	}
}

?>
