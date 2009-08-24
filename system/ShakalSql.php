<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Rozhranie pre prístup k databáze.
 */

/**
 * \brief SQL výraz.
 * \ingroup Shakal_Sql
 * \licenses \gpl
 *
 * Táto trieda sa používa na bezpečné skladanie SQL príkazov bez rizika SQL
 * injection. Pri skladaní výrazu sa premenné zapisujú do výrazu ako %(poradie
 * premennej). Samotné premenné sa zapisujú ako ďalšie voliteľné argumenty
 * konštruktoru.
 *
 * @code
 * new ShakalSqlExpr('INSERT INTO #__tabulka SET x = %1, y = %2 WHERE z = %1',
 *                    $prvyArgument,
 *                    $druhyArgument);
 * @endcode
 */
class ShakalSqlExpr
{
	/// Neupravený SQL výraz.
	public $query;
	/// Zoznam premenných vyskytujúcich sa vo výraze.
	public $args;

	/**
	 * Výtvorenie SQL výrazu.
	 *
	 * @param query Výraz, ktorý vytvárame.
	 * @param ...   Zoznam premenných vyskytujúcich sa v SQL výraze.
	 */
	public function  __construct($query)
	{
		$this->query = $query;
		$this->args = func_get_args();
		array_shift($this->args);
		if (isset($this->args[0]) && is_array($this->args[0])) {
			$this->args = $this->args[0];
		}
	}
}

/**
 * \brief SQL dotaz, ktorý nebude modifikovaný.
 * \ingroup Shakal_Sql
 * \licenses \gpl
 *
 * Táto trieda sa používa vo výnimočných prípadoch, keď je výraz nesmie byť
 * modifikovaný.
 */
class ShakalRawSqlExpr
{
	/// SQL výraz.
	public $query;

	/// Vytvorenie nového SQL výrazu.
	public function __construct($query)
	{
		$this->query = $query;
	}
}

/**
 * \brief Komunikácia s SQL databázou.
 * \ingroup Shakal_Sql
 * \licenses \gpl
 *
 * Každý druh SQL databázy používa iný druh prístupu k nej. Táto trieda slúži
 * na zjednotenie prístupu k SQL databázam.
 */
class ShakalSql
{
	/// Typ databázy \e MySQL.
	const MySQL = 1;

	private $_Link     = null;
	private $_DbPrefix = '';
	private $_DbType   = 0;

	/**
	 * Vytvorenie nového spojenia s databázou.
	 *
	 * Novú inštanciu ShakalSql je možné vytvoriť buď bez pripojenia k databáze
	 * (konštruktor sa volá bez parametrov), alebo so zavoalním konštruktora
	 * s identickými parametrami, ako má metóda connect.
	 */
	public function __construct()
	{
		$args = func_get_args();
		if (count($args) == 5) {
			$db_type     = $args[0];
			$db_server   = $args[1];
			$db_username = $args[2];
			$db_password = $args[3];
			$db_name     = $args[4];
			$this->connect($db_type, $db_server, $db_username, $db_password, $db_name);
		}
		elseif (count($args) != 0) {
			throw new ShakalSystemException('Wrong number of parameters');
		}
	}

	/**
	 * Metóda vráti prefix tabuliek v databáze.
	 *
	 * @sa setDbPrefix
	 */
	public function dbPrefix()
	{
		return $this->_DbPrefix;
	}

	/// Metóda vráti typ databázy.
	public function dbType()
	{
		return $this->_DbType;
	}

	/**
	 * Nastavenie prefixu tabuliek.
	 *
	 * Je bežné, že na jednom databázovom serveri je niekoľko webových aplikácií.
	 * Názvy niektorých ich tabuliek môžu navzájom kolidovať. V takom prípade
	 * sa používa prefix tabuliek (každá tabuľke aplikácie začína určitým
	 * reťazcom). Prefix je možné vo výraze zapísať ako reťazec '\#__'. Tento
	 * reťazec expanduje na hodnotu nastavenú touto funkciou. Ak sme teda
	 * prefix nastavili na 'shakal_' výraz '\#__table' sa zmení na 'shakal_table'.
	 */
	public function setDbPrefix($prefix)
	{
		$this->_DbPrefix = $prefix;
	}

	/**
	 * Pripojenie k SQL databáze.
	 *
	 * @param db_type Typ databázy, napr ShakalSql::MySQL.
	 * @param db_server Databázový server, ku ktorému sa chceme pripojiť.
	 * @param db_username Meno užívateľa pripájajúceho sa k databáze.
	 * @param db_password Heslo pre pripojenie k databázovému serveru.
	 * @param db_name Názov databázy.
	 */
	public function connect($db_type, $db_server, $db_username, $db_password, $db_name)
	{
		if (!is_null($this->_Link))
			$this->disconnect();

		if ($this->_Link == null) {
			$this->_Link = mysql_pconnect($db_server, $db_username, $db_password)
				or die('Mysql connection error: '.mysql_error());
			mysql_select_db($db_name, $this->_Link)
				or die('Select DB failed: '.mysql_error());
			mysql_query("SET NAMES 'utf8'", $this->_Link)
				or die('Could not set names');
		}
	}

	/// Odpojenie sa od databázového serveru.
	public function disconnect()
	{
		if (is_null($this->_Link))
			return;

		mysql_close($this->_Link);
		$this->_Link = null;
	}

	/**
	 * Upravenie premennej tak, aby bola bezpečne použiteľná v SQL príkaze.
	 * Ak je premenná textový reťazec automaticky sa vloží do úvodzoviek.
	 */
	public function quote($var)
	{
		return self::quoteStatic($var, $this->_DbPrefix, $this->_DbType);
	}

	/**
	 * Statická metóda pracujúca ako quote bez nutnosti vytvorenia inštancie
	 * ShakalSql.
	 */
	public static function quoteStatic($var, $dbType)
	{
		if (is_integer($var) || is_double($var) || is_float($var)) {
			return (string)$var;
		}
		elseif (is_null($var)) {
			return 'NULL';
		}
		else {
			return "'".mysql_real_escape_string($var)."'";
		}
	}

	/// Nahradenie podreťazca '\#__' za prefix.
	public function replacePrefix($data)
	{
		return self::replacePreifxStatic($data, $this->_DbPrefix, $this->_DbType);
	}

	/// Statická metóda fungujúca ako replacePrefix.
	public static function replacePreifxStatic($query, $prefix, $dbType)
	{
		return str_replace('#__', $prefix, $query);
	}

	/**
	 * Úprava dotazu do podoby vhodnej pre odoslanie do databázy.
	 *
	 * @param query Neupravený dotaz pre databázu.
	 * @param ...   Ďalšie argumenty, ktoré sa doplnia za reťazce %(číslo argumentu).
	 *
	 * @return Dotaz vhodný pre databázu.
	 */
	public function prepareQuery($query)
	{
		$args = func_get_args();
		array_shift($args);
		return self::prepareQueryStatic($query, $this->_DbPrefix, $this->_DbType, $args);
	}

	/// Funkcia funguje rovnako, ako prepareQuery, ale bez vytvorenia inštancie ShakalSql.
	public static function prepareQueryStatic($query, $dbPrefix, $dbType, array $args = array())
	{
		$query = self::replacePreifxStatic($query, $dbPrefix, $dbType);

		if (count($args) > 0) {
			$search  = array();
			$replace = array();
			$argNum  = 0;
			foreach ($args as $arg) {
				$argNum++;
				array_push($search, '%' . $argNum);
				array_push($replace, self::quoteStatic($arg, $dbType));
			}
			$query = str_replace($search, $replace, $query);
		}
		return $query;
	}


	/**
	 * Funkcia prevedie  string začínajúci aj končiaci znakom '#' na ShakalSqlExpr.
	 *
	 * \internal
	 */
	public static function toSqlExpression($str)
	{
		if (strlen($str) > 2
		    && $str[0] === '#'
		    && $str[strlen($str) - 1] === '#') {
			return new ShakalSqlExpr(substr($str, 1, strlen($str) - 2));
		}
		else
			return $str;
	}

	/**
	 * Prevod výrazu typu SakalSqlExpr, ShakalRawSqlExpr, alebo reťazca na
	 * reťazec vhodný pre databázu.
	 *
	 * \internal
	 */
	public static function renderExpressionStatic($expr, $dbPrefix, $dbType)
	{
		if ($expr instanceof ShakalSqlExpr)
			return self::prepareQueryStatic($expr->query, $dbPrefix, $dbType, $expr->args);
		elseif ($expr instanceof ShakalRawSqlExpr)
			return $expr->query;
		else
			return self::replacePreifxStatic($expr, $dbPrefix, $dbType);
	}

	/**
	 * Spracovanie a spustenie SQL dotazu.
	 *
	 * Táto metóda umožňuje bezpečne poskladať a spustiť SQL dotaz. Dotaz vykonáva
	 * metóda queryRaw.
	 *
	 * @param query Dotaz, ktorý sa má vykonať
	 * @param ...   Parametre SQL dotazu.
	 * @return Výsledok volania SQL dotazu.
	 *
	 * @code
	 * $db->query('INSERT INTO #__tabulka (x, y) VALUES (%1, %2)', $text, $num);
	 * @endcode
	 */
	public function query($query)
	{
		if (is_string($query)) {
			$args = func_get_args();
			array_shift($args);
			$expr = new ShakalSqlExpr($query, $args);
			$query = $expr;
		}
		return $this->queryRaw($this->renderExpressionStatic($query, $dbPrefix, $dbType));
	}

	/**
	 * @throw ShakalSqlException
	 *
	 * Spustenie dotazu a vrátenie výsledku z databázového serveru.
	 */
	public function queryRaw($query)
	{
		$result = mysql_query($query, $this->_Link);
		if ($result === false) {
			$error = mysql_error($this->_Link);
			$errno = mysql_errno($this->_Link);
			$this->rollback();
			throw new ShakalSqlException($errno, $error, $query);
		}
		return $result;
	}

	/// Začiatok transakcie.
	public function begin()
	{
		mysql_query('START TRANSACTION');
	}

	/// Potvrdenie (\e commit) transakcie.
	public function commit()
	{
		mysql_query('COMMIT');
	}

	/// Návrat na začiatok transakcie.
	public function rollback()
	{
		mysql_query('ROLLBACK');
	}

	/**
	 * Vytvorenie a vrátenie objektu ShakalSqlSelect.
	 */
	public function select()
	{
		return new ShakalSqlSelect($this);
	}
}

/**
 * \brief SQL dotaz pre výber dát.
 * \ingroup Shakal_Sql
 *
 * \par
 * Trieda ShakalSqlSelect umožňuje zostrojenie SQL výrazu nezávislého na duhu
 * databázy.
 */
class ShakalSqlSelect
{

	private $_distinct = '';
	private $_from     = array();
	private $_fields   = array();
	private $_join     = array();
	private $_where    = array();
	private $_group    = array();
	private $_having   = array();
	private $_order    = array();
	private $_limit    = false;

	private $_DbPrefix = '';
	private $_DbType   = 0;
	private $_sql      = null;

	/**
	 * Vytvorenie nového SQL select-u.
	 *
	 * @param sql Spojenie, ku ktorému sa select vzťahuje.
	 *
	 * Inštanciu ShakalSqlSelect je možné vytvoriť priamo zo spojenia s databázou
	 * volaním ShakalSql::select().
	 *
	 * @code
	 * $result = $db
	 *           ->select()
	 *           ->from('tabulka', array('stlpec'))
	 *           ->exec();
	 * @endcode
	 */
	public function __construct(ShakalSql $sql = null)
	{
		$this->_sql = $sql;
		if (!is_null($sql))
		{
			$this->_DbPrefix = $sql->dbPrefix();
			$this->_DbType   = $sql->dbType();
		}
	}

	/// Potlačenie duplicít vybraných riadkov.
	public function &distinct()
	{
		$this->_distinct = 'DISTINCT ';
		return $this;
	}

	/**
	 * Výber stĺpcov z tabuľky.
	 *
	 * @param table   Názov tabuľky, z ktorej sa vyberajú stĺpce.
	 * @param columns Stĺpce, ktoré sa majú vybrať z tabuľky.
	 * @param ...     Ďalšie stĺpce (dá sa použiť len v prípade, že columns nie je pole).
	 *
	 * Jeden select môže mať výber z niekoľkých tabuliek bez join-u. Je možné
	 * zapísať aj niekoľko výberov z jednej tabuľky. V takom prípade sa tieto
	 * výbere spoja do jediného rovnako ako keby stĺpce z oboch výberov boli
	 * použité v jednom.
	 *
	 * Výsledkom nasledujúceho kódu:
	 * @code
	 * $select = new ShakalSqlSelect;
	 * $select->from('#__tabulka', array('a'));
	 * $select->from('#__tabulka', 'b', 'c');
	 * @endcode
	 * je dotaz <tt>SELECT a, b, c FROM tabulka</tt>.
	 *
	 * Pre tabuľku a jej stĺpce je možné využiť aliasy. Alias sa zapisuje ako pole,
	 * ktorého kľúčom bude názov aliasu a hodnotou samotny názov stĺpca / tabuľky.
	 *
	 * Nasledujúci kód:
	 * @code
	 * $select = new ShakalSqlSelect;
	 * $select->from(array('tabulka_alias' => '#__tabulka'), 'a');
	 * $select->from('#__tabulka', array('stlpec_alias' => 'b'));
	 * echo $select;
	 * @endcode
	 * bude mať výstup <tt>SELECT tabulka_alias.a, tabulka.b AS stlpec_alias FROM tabulka AS tabulka_alias, tabulka</tt>.
	 */
	public function &from($table, $columns = '*')
	{
		$name = $alias = null;
		// Tu môže byť teoreticky výnimka pri shnahe o redefiníciu aliasu
		// Tabuľka definovaná s aliasom
		if (is_array($table)) {
			if (count($table) != 1)
				return $this;

			list($alias, $name) = each($table);
			$name = ShakalSql::renderExpressionStatic($name, $this->_DbPrefix, $this->_DbType);
			if (is_numeric($alias))
				$alias = $name;
		}
		// Tabuľka bez aliasu
		else {
			$name  = ShakalSql::renderExpressionStatic($table, $this->_DbPrefix, $this->_DbType);
			$alias = $name;
		}
		$this->_from[$alias] = $name;

		if (!is_array($columns)) {
			$c = func_get_args();
			array_shift($c);
			if (count($c) === 0)
				$c[0] = $columns;
			$columns = $c;
		}

		$this->_addFields($name, $alias, $columns);

		return $this;
	}

	private function _addFields($tableName, $tableAlias, $columns)
	{
		if (!isset($this->_fields[$tableAlias]))
			$this->_fields[$tableAlias] = array($tableName, array());

		if (is_array($columns)){
			foreach($columns as $key => $column) {
				array_push($this->_fields[$tableAlias][1], array($key, $column));
			}
		}
		else
			array_push($this->_fields[$tableAlias][1], array(0, $columns));
	}

	private function _joinPriv($type, $table, $cond, $columns, $using = false)
	{
		$tableName  = '';
		$tableAlias = null;
		if (is_array($table))
			list($tableAlias, $tableName) = each($table);
		else
			$tableName  = $table;
		$tableName = ShakalSql::renderExpressionStatic($tableName, $this->_DbPrefix, $this->_DbType);
		if (is_null($tableAlias))
			$tableAlias = $tableName;
		$tableExpr = '';
		if ($tableName == $tableAlias)
			$tableExpr = $tableName;
		else
			$tableExpr = $tableName . ' AS ' . $tableAlias;

		$podmienka = '';
		if ($using) {
			if (is_array($cond)) {
				for ($i = 0; $i < count($cond); ++$i)
					$cond[$i] = ShakalSql::renderExpressionStatic($cond[$i], $this->_DbPrefix, $this->_DbType);
				$podmienka = ' USING (' . implode(', ', $cond) . ')';
			}
			else {
				$podmienka = ' USING (' . ShakalSql::renderExpressionStatic($cond, $this->_DbPrefix, $this->_DbType) . ')';
			}
		}
		elseif ($type != 'CROSS' && $type != 'NATURAL') {
			$podmienka = ' ON ' . ShakalSql::renderExpressionStatic($cond, $this->_DbPrefix, $this->_DbType);
		}
		$this->_addFields($tableName, $tableAlias, $columns);

		array_push($this->_join, $type . ' JOIN ' . $tableExpr . $podmienka);
	}

	/**
	 * Spojenie s inou tabuľkou pri podmienke \a cond. Argument \a columns
	 * je zoznam stĺpcov, ktoré sa z tejto tabuľky vyberú. Stĺpce aj tabuľka
	 * môžu mať aliasy.
	 *
	 * Výsledkom volania tejto metódy je <tt>INNER JOIN</tt>.
	 *
	 * Výsledkom tohto kódu:
	 * @code
	 * $select = new ShakalSqlSelect;
	 * $select->from('#__tabulka_1', 'a');
	 * $select->join('#__tabulka_2', '#__tabulka_1.a = #__tabulka_2.x', array('b', 'c'));
	 * echo $select;
	 * @endcode
	 * bude dotaz <tt>SELECT tabulka_1.a, tabulka_2.b, tabulka_2.c FROM tabulka_1
	 * INNER JOIN tabulka_2 ON tabulka_1.a = tabulka_2.x</tt>.
	 *
	 * @sa joinInner
	 */
	public function &join($table, $cond, $columns = array())
	{
		$this->_joinPriv('INNER', $table, $cond, $columns);
		return $this;
	}

	/// Metóda je aliasom metódy join.
	public function &joinInner($table, $cond, $columns = array())
	{
		$this->_joinPriv('INNER', $table, $cond, $columns);
		return $this;
	}

	/// Metóda má rovnaké argumenty, ako join. Výsledkom volania je <tt>LEFT JOIN</tt>.
	public function &joinLeft($table, $cond, $columns = array())
	{
		$this->_joinPriv('LEFT', $table, $cond, $columns);
		return $this;
	}

	/// Metóda má rovnaké argumenty, ako join. Výsledkom volania je <tt>RIGHT JOIN</tt>.
	public function &joinRight($table, $cond, $columns = array())
	{
		$this->_joinPriv('RIGHT', $table, $cond, $columns);
		return $this;
	}

	/// Metóda má rovnaké argumenty, ako join. Výsledkom volania je <tt>FULL OUTER JOIN</tt>.
	public function &joinFull($table, $cond, $columns = array())
	{
		$this->_joinPriv('FULL OUTER', $table, $cond, $columns);
		return $this;
	}

	/**
	 * Metóda vykoná <tt>NATURAL JOIN</tt>. Argument \a columns je zoznam
	 * vybraných stĺpcov. Dotaz <tt>NATURAL JOIN</tt> nepoužíva žiadnu podmienku
	 * spojenia.
	 */
	public function &joinNatural($table, $columns = array())
	{
		$this->_joinPriv('NATURAL', $table, null, $columns);
		return $this;
	}

	/// Metóda má rovnaké argumenty, ako joinNatural. Výsledkom volania je <tt>CROSS JOIN</tt>.
	public function &joinCross($table, $columns = array())
	{
		$this->_joinPriv('CROSS', $table, null, $columns);
		return $this;
	}

	/**
	 * Spojenie tabuliek s použitím vybraných stĺpcov.
	 *
	 * @param table   Tabuľka, ktorú spájeme.
	 * @param column  Stĺpec, alebo zoznam stĺpcov, podľa ktorých sa uskutoční spojenie.
	 * @param columns Stĺpce, ktoré sa vyberajú z tabuľky.
	 *
	 * Výsledkom kódu:
	 * @code
	 * $select = new ShakalSqlSelect;
	 * $select->from('#__tabulka_1', 'a');
	 * $select->joinUsing('#__tabulka_2', array('x', 'y'), 'b');
	 * echo $select;
	 * @endcode
	 * bude spojenie <tt>SELECT tabulka_1.a, tabulka_2.b FROM tabulka_1 INNER JOIN tabulka_2 USING (x, y)</tt>.
	 *
	 * @sa joinInnerUsing
	 */
	public function &joinUsing($table, $column, $columns = array())
	{
		$this->_joinPriv('INNER', $table, $column, $columns, true);
		return $this;
	}

	/// Alias metódy joinUsing.
	public function &joinInnerUsing($table, $column, $columns = array())
	{
		$this->_joinPriv('INNER', $table, $column, $columns, true);
		return $this;
	}

	/// Spojenie tabuliek <tt>LEFT JOIN</tt> pomocou stĺpcov \a column.
	public function &joinLeftUsing($table, $column, $columns = array())
	{
		$this->_joinPriv('LEFT', $table, $column, $columns, true);
		return $this;
	}

	/// Spojenie tabuliek <tt>RIGHT JOIN</tt> pomocou stĺpcov \a column.
	public function &joinRightUsing($table, $column, $columns = array())
	{
		$this->_joinPriv('RIGHT', $table, $column, $columns, true);
		return $this;
	}

	/// Spojenie tabuliek <tt>FULL OUTER JOIN</tt> pomocou stĺpcov \a column.
	public function &joinFullUsing($table, $column, $columns = array())
	{
		$this->_joinPriv('FULL OUTER', $table, $column, $columns, true);
		return $this;
	}

	/**
	 * Pridanie výberu stĺpcov nepatriacich žiadnej tabuľke.
	 *
	 * Táto funckia umožňuje vybrať ako stĺpec položky, ktoré sa nenachádzajú
	 * v žiadnej z vyberaných tabuliek napr. "NOW()". Pri tomto výraze sa
	 * automaticky predpokladá, že je SQL výraz a preto počiatočný a koncový
	 * znak reťazca nemusia byť '#'. Okrem reťazcov sú samozrejme povolené
	 * hodnoty ShakalSqlExpr a ShakalRawSqlExpr.
	 *
	 * @param columns Zoznam vyberaných stĺpcov. Stĺpce môžu mať svoje aliasy
	 *                definované kľúčom poľa poľom.
	 * @param ...     Stĺpce je možné zapisať aj ako voliteľné argumenty tejto metódy.
	 */
	public function &column($columns)
	{
		if (!is_array($columns))
			$columns = func_get_args();
		foreach ($columns as $key => $val) {
			if (is_string($columns[$key]))
				$columns[$key] = new ShakalSqlExpr($columns[$key]);
		}
		$this->_addFields(null, null, $columns);
		return $this;
	}

	/**
	 * Podmienka pre výber riadkov.
	 *
	 * V jedom selecte je možné použiť niekoľko podmienok. Tie sa automaticky
	 * spoja logickým operátorom AND.
	 *
	 * @param cond Podmienka pre výber riadkov.
	 * @param ...  Parametre podmienky, ktoré sa bezpečne ošetria a dosadia do výrazu.
	 *
	 * @code
	 * Nasledujúci kód:
	 * $select = new ShakalSqlSelect;
	 * $select->from('#__tabulka', 'a');
	 * $select->where('b = %1', 'text');
	 * echo $select;
	 * @endcode
	 *
	 * bude mať výstup <tt>SELECT a FROM tabulka WHERE b = 'text'</tt>.
	 */
	public function &where($cond)
	{
		if ($cond instanceof ShakalSqlExpr || $cond instanceof ShakalRawSqlExpr) {
			array_push($this->_where, $cond);
			return $this;
		}

		$args = func_get_args();
		array_shift($args);
		if (isset($args[0]) && is_array($args[0]))
			$args = $args[0];
		array_push($this->_where, new ShakalSqlExpr($cond, $args));
		return $this;
	}

	/**
	 * Zoskupenie riadkov podľa niektorých stĺpcov.
	 *
	 * @param group Zoznam stĺpcov, podľa ktorých majú byť dáta zoskupené.
	 * @param ...   Zoznam stĺpcov je možné zapísať aj ako postupnosť argumentov
	 *              namiesto jediného poľa.
	 *
	 * Výstupom kódu:
	 * @code
	 * $select = new ShakalSqlSelect;
	 * $select->from('#__tabulka', '#SUM(a)#');
	 * $select->group('b', 'c');
	 * echo $select;
	 * @endcode
	 * je <tt>SELECT SUM(a) FROM tabulka GROUP BY b, c</tt>.
	 *
	 */
	public function &group($group)
	{
		$args = func_get_args();
		if (!is_array($group)) {
			$group = func_get_args();
		}
		$this->_group = array_merge($this->_group, $group);
		return $this;
	}

	/**
	 * Syntax podmienky HAVING je identická ako u WHERE. Argumenty tejto metódy
	 * sú rovnaké ako u where.
	 *
	 * @sa where
	 */
	public function &having($cond)
	{
		$args = func_get_args();
		array_shift($args);
		if (isset($args[0]) && is_array($args[0]))
			$args = $args[0];
		array_push($this->_having, new ShakalSqlExpr($cond, $args));
		return $this;
	}

	/**
	 * Zoradenie výsledkov podľa vybraných stĺpcov. Argumenty funkcie sú rovnaké,
	 * ako u group.
	 *
	 * @sa group
	 */
	public function &order($order)
	{
		$args = func_get_args();
		if (!is_array($order)) {
			$order = func_get_args();
		}
		$this->_order = array_merge($this->_order, $order);
		return $this;
	}

	/**
	 * Obmedzenie počtu výsledkov.
	 *
	 * @param count   Počet riadkov, ktoré sa majú vybrať.
	 * @param offset  Poradie prvého riadku, ktorý sa má vybrať.
	 */
	public function &limit($count, $offset = 0)
	{
		$this->_limit = array((integer)$count, (integer)$offset);
		return $this;
	}

	private function _fullColumnName($fieldName, $tableName, $tableAlias)
	{
		if (is_null($tableName))
			return $fieldName;
		elseif ((count($this->_fields) > 1 && !is_null($tableAlias)) || $tableName != $tableAlias)
			return $tableAlias . '.' . $fieldName;
		else
			return $fieldName;
	}

	private function _renderFields()
	{
		$out = array();
		foreach($this->_fields as $tableAlias => $info) {
			$tableName = $info[0];
			$fields    = $info[1];

			foreach($fields as $field) {
				$aliasStr = '';
				if (!is_numeric($field[0]))
					$aliasStr = ' AS ' . $field[0];

				if (is_string($field[1]))
					$field[1] = ShakalSql::toSqlExpression($field[1]);

				if ($field[1] instanceof ShakalSqlExpr || $field[1] instanceof ShakalRawSqlExpr)
					$fieldRend = ShakalSql::renderExpressionStatic($field[1], $this->_DbPrefix, $this->_DbType);
				else
					$fieldRend = $field[1];

				if (is_string($field[1]))
					$fieldRend = $this->_fullColumnName($fieldRend, $tableName, $tableAlias);
				$fieldRend .= $aliasStr;
				array_push($out, $fieldRend);;
			}
		}

		if (count($out) == 0)
			return '';
		else
			return implode(', ', $out) . ' ';
	}

	private function _renderFrom()
	{
		$out = array();
		foreach($this->_from as $alias => $table) {
			if ($alias == $table)
				array_push($out, $table);
			else
				array_push($out, $table . ' AS ' . $alias);
		}

		if (count($out) == 0)
			return '';
		else
			return 'FROM '.implode(', ', $out);
	}

	private function _renderJoin()
	{
		return ' ' . implode(' ', $this->_join);
	}

	private function _renderWhere($whereStr, $whereArr)
	{
		if (count($whereArr) === 0)
			return '';

		$whereOut = array();
		foreach ($whereArr as $where) {
			array_push($whereOut, ShakalSql::renderExpressionStatic($where, $this->_DbPrefix, $this->_DbType));
		}
		return ' '.$whereStr.' '.implode(' AND ', $whereOut);
	}

	private function _renderFieldsGroup($type, $arr)
	{
		if (count($arr) === 0)
			return '';

		$fields = array();
		foreach ($arr as $g) {
			array_push($fields, ShakalSql::renderExpressionStatic($g, $this->_DbPrefix, $this->_DbType));
		}

		return ' ' . $type . ' '.implode(', ', $fields);
	}

	private function _renderLimit()
	{
		if ($this->_limit === false)
			return '';
		else
			return ' LIMIT '.$this->_limit[0].' OFFSET '.$this->_limit[1];
	}

	/// Prevod príkazu výberu na reťazec, ktorý sa dá spustiť na databázovom serveri.
	public function __toString()
	{
		return 'SELECT '
		       . $this->_distinct
		       . $this->_renderFields()
		       . $this->_renderFrom()
		       . $this->_renderJoin()
		       . $this->_renderWhere('WHERE', $this->_where)
		       . $this->_renderFieldsGroup('GROUP BY', $this->_group)
		       . $this->_renderWhere('HAVING', $this->_having)
		       . $this->_renderFieldsGroup('ORDER BY', $this->_order)
		       . $this->_renderLimit();
	}

	/**
	 * Spustenie SQL príkazu a vrátenie výsledku volania ShakalSql::queryRaw.
	 *
	 * @todo Pridať \e ShakalSqlResult
	 */
	public function exec()
	{
		return $this->_sql->queryRaw($this->__toString());
	}
}

?>
