<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Spracovanie %SQL výrazov.
 */

namespace Shakal;

/**
 * \brief Rozhranie pre %SQL výrazy.
 * \licenses \gpl
 */
interface ISQLExpr
{
	/**
	 * Prevod do natívneho %SQL výrazu.
	 * \return string
	 */
	public function toNativeExpr(SQLConnection $connection);
}

/**
 * \brief %SQL výraz.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 */
class SQLExpr implements ISQLExpr
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
class SQLRawExpr implements ISQLExpr
{
	private $_expr;

	/**
	 * Vytvorenie nového jednoduchého %SQL výrazu. Tento výraz sa nebude nijako
	 * modifikovať.
	 * \param string expr
	 */
	public function __construct($expr)
	{
		$this->_expr = $expr;
	}

	public function toNativeExpr(SQLConnection $connection)
	{
		return (string)$this->_expr;
	}
}


?>
