<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Spracovanie %SQL výrazov.
 */

namespace Shakal;

/**
 * \brief %SQL výraz.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class SQLExpr
{
	private $_args;

	/**
	 * Vytvorenie nového výrazu.
	 * \todo Dopísať a zdokumentovať.
	 */
	public function __construct($query)
	{
		$args = func_get_args();
		$this->_args = $args;
	}

	/**
	 * Prevod do natívneho %SQL výrazu.
	 */
	public function toNativeExpr(SQLConnection $connection)
	{
		return implode($this->_args);
	}
}

/**
 * \brief Jednoduchý neupravený %SQL výraz.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class SQLRawExpr
{
	private $_query;

	/**
	 * Vytvorenie nového jednoduchého %SQL výrazu. Tento výraz sa nebude nijako
	 * modifikovať.
	 */
	public function __construct($query)
	{
		$this->_query = $query;
	}

	/**
	 * Prevod do natívneho %SQL výrazu.
	 */
	public function toNativeExpr(SQLConnection $connection)
	{
		return $this->_query;
	}
}


?>
