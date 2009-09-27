<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Prístup k databáze a základné konštanty používané %SQL modulom.
 */

namespace Shakal;

/**
 * \brief Iterátor pre prechádzanie výsledkom %SQL dotazu.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
abstract class SQLResultIterator implements \Countable, \Iterator
{
	/**
	 * \var SQLResult
	 */
	private $_result;
	private $_data = false;
	private $_ptr  = 0;

	/**
	 * Získanie nasleudjúceho riadku tabuľky. Po volaní tejto funkcia sa musia
	 * nastaviť dáta metódou setData(). Po 
	 * \return array|object
	 * \sa setData()
	 */
	abstract protected function newData();

	/**
	 * Vytvorenie nového iterátoru pre výsledok \a result.
	 */
	protected function __construct(SQLResult &$result)
	{
		$this->_result = &$result;
		$this->_ptr    = $result->rowNum();
	}

	/**
	 * Získanie dát aktuálneho záznamu.
	 * \return array|object
	 */
	public function current()
	{
		return $this->_data;
	}

	/**
	 * Získanie aktuálneho čísla riadku.
	 * \return int
	 */
	public function key()
	{
		return $this->_ptr;
	}

	/**
	 * Presun interného kurzoru na prvý záznam. Táto funkcia na databázach,
	 * ktoré nepodporujú presun v zázname vyvolá výnimku SQLException.
	 * \throw SQLException
	 */
	public function rewind()
	{
		if ($this->_ptr === 0) {
			return;
		}
		$this->_ptr = 0;
		$this->_result->seek(0);
		$this->newData();
	}

	/**
	 * Presun na nasledujúci záznam.
	 */
	public function next()
	{
		$this->newData();
		if ($this->_data !== false)
			$this->_ptr++;
	}

	/**
	 * Ak je záznam platný (je možné čítať záznam, kurzor sa nenachádza na konci)
	 * vráti \e true.
	 * \return bool
	 */
	public function valid()
	{
		// Ak su dáta false - neplatný záznam
		return $this->_data !== false;
	}

	/**
	 * Získanie počtu riadkov vo výsledku.
	 * \return int
	 */
	public function count()
	{
		return count($this->_result);
	}

	/**
	 * Získanie výsledku SQLResult, s ktorým iterátor pracuje.
	 * \return SQLResult
	 */
	protected function result()
	{
		return $this->_result;
	}

	/**
	 * Funkcia nastavuje interné dáta, ktoré budú vrátené volaním funkcie
	 * current().
	 * \sa newData()
	 */
	protected function setData($data)
	{
		$this->_data = $data;
	}
}

/**
 * \brief Iterátor pre prechádzanie riadkami výsledku vrátenými ako tabuľka.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class SQLArrayResultIterator extends SQLResultIterator
{
	private $_type;

	/**
	 * Vytvorenie poľového nového iterátoru pre výsledok \a result typu \a type.
	 * Podrobnosti o typoch sú v sekcii \ref SQL_resultType "Typ výsledku".
	 * \sa SQLResult::fetchArray()
	 */
	public function __construct(SQLResult &$result, $type)
	{
		parent::__construct($result);
		$this->_type = $type;
		$this->newData();
	}

	protected function newData()
	{
		$data = $this->result()->fetchArray($this->_type);
		$this->setData($data);
	}
}

/**
 * \brief Iterátor pre prechádzanie riadkami výsledku vo forme objektov
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class SQLObjectResultIterator extends SQLResultIterator
{
	private $_className;
	private $_args;

	/**
	 * Vytvorenie nového objektového iterátoru pre výsledok \a result. Pre každý
	 * riadok sa vytvorí objekt \a className, ktorého konštruktor bude volaný s
	 * agrumentmi \a args.
	 * \sa SQLResult::fetchObject()
	 */
	public function __construct(SQLResult &$result, $className, $args)
	{
		parent::__construct($result);
		$this->_className = $className;
		$this->_args = $args;
		$this->newData();
	}

	protected function newData()
	{
		$data = $this->result()->fetchObject($this->_className, $this->_args);
		$this->setData($data);
	}
}




?>
