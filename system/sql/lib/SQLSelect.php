<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Práca so select-om v %SQL databáze.
 */

namespace Shakal;

class _SQLFieldInfo
{
	private $_id;
	private $_name;
	private $_alias;

	public function __construct($id, $name, $alias = null)
	{
		$this->_id    = $id;
		$this->_name  = $name;
		$this->_alias = $alias;
	}

	public function id()
	{
		return $this->_id;
	}

	public function name()
	{
		return $this->_name;
	}

	public function alias()
	{
		return $this->_alias;
	}
}

class _SQLTableInfo
{
	const Using      = 4;
	const Condition  = 8;
	const Join       = 16;

	const None       = 0;
	const From       = 1;
	const Natural    = 16;
	const Cross      = 17;
	const Inner      = 24;
	const Left       = 25;
	const Right      = 26;
	const Outer      = 27;
	const InnerUsing = 28;
	const LeftUsing  = 29;
	const RightUsing = 30;
	const OuterUsing = 31;

	private $_name   = null;
	private $_alias  = null;
	private $_type   = 1;
	private $_cond   = null;
	private $_fields = array();

	public function __construct($name, $alias, $type = self::None, $cond = null)
	{
		$this->_name  = $name;
		$this->_alias = $alias;
		$this->_type  = $type;
		$this->_cond  = $cond;
	}

	public function name()
	{
		return $this->_name;
	}

	public function alias()
	{
		return $this->_alias;
	}

	public function type()
	{
		return $this->_type;
	}

	public function condition()
	{
		return $this->_cond;
	}

	public function addFields($fields)
	{
		// Automatické číslovanie pre zoradenie podľa poradia pri zápise
		static $nextFieldId = 0;
		foreach ($fields as $alias => $field) {
			// Nie je nastavený alias
			if (is_integer($alias)) {
				array_push($this->_fields, new _SQLFieldInfo($nextFieldId, $field));
			}
			else {
				$this->_fields[$alias] = new _SQLFieldInfo($nextFieldId, $field, $alias);
			}
			$nextFieldId++;
		}
	}

	public function fields()
	{
		return $this->_fields;
	}

	public function isJoin()
	{
		if ($this->_type & self::Join) {
			return true;
		}
		else {
			return false;
		}
	}

	public function isUsing()
	{
		if (($this->_type & self::Join) && ($this->_type & self::Using)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function isFrom()
	{
		return $this->_type === self::From;
	}

	public function hasCondition()
	{
		if (($this->_type & self::Join) && ($this->_type & self::Condition)) {
			return true;
		}
		else {
			return false;
		}
	}
}


/**
 * \brief Výberový %SQL dotaz
 * \ingroup Shakal_SQL
 * \licenses \gpl
 *
 * Táto trieda skúži na zostavenie výberových %SQL dotazov. Spôsob zápisu
 * tabuliek a stĺpcov je v časti \ref shakal_sql_identifikatory
 * "Spôsob zápisu tabuliek a stĺpcov".
 *
 * \section shakal_sql_zapis_stlpcov Zápis zoznamu stĺpcov
 *
 * Zoznam stĺpcov sa môže skladať buď z jednej položky, ktorá je buď reťazcom,
 * alebo %SQL výrazom (ISQLExpr), alebo z poľa položiek. Ak kľúč poľa položiek
 * bude reťazcom, potom je tento kľúč aliasom položky. Všetky funkcie v triede
 * SQLSelect prijímajúce zoznam stĺpcov majú variabilný počet parametrov, stĺpce
 * preto môžu byť vymenované priamo pri volaní funkcie bez vytvárania poľa.
 *
 * Príklady:
 * \code
 * // Výber všetkých stĺpcov tabuľky 'tabulka'.
 * $select->from('tabulka', '*');
 * $select->from('tabulka'); // Alternatívny spôsob výberu všetkých stĺpcov.
 *
 * // Výber stĺpca 'stlpec' z tabuľky 'tabulka'.
 * $select->from('tabulka', 'stlpec');
 *
 * // Výber stĺpca 'stlpec' s aliasom 'alias' z tabuľky 'tabulka'.
 $tableInfo->condition(* $select->from('tabulka', array('alias' => 'tabulka'));
 *
 * // Výber stĺpcov 'stlpec1' a 'stlpec2' z tabuľky 'tabulka'.
 * // S použitím ďalších argumentov
 * $select->from('tabulka', 'stlpec1', 'stlpec2');
 * // S použitím poľa
 * $select->from('tabulka', array('stlpec1', 'stlpec2'))
 * \endcode
 */
class SQLSelect
{
	/**
	 * \var SQLConnection
	 */
	private $_connection;

	private $_distinct = false;

	/**
	 * Pole, ktorého kľúčom je názov tabuľky (nie fiktívny s #__, ale už reálny
	 * používaný v databáze) a hodnotou je číslo záznamu v _tableInfo.
	 */
	private $_tables = array();

	/**
	 * Homogénne pole obsahujúce tabuľky v poradí, v akom boli zadané, ich polia
	 * a informácie o tabuľkách.
	 */
	private $_tablesInfo = array();
	private $_numTables = 0;

	private $_where  = array();
	private $_group  = array();
	private $_having = array();
	private $_order  = array();
	private $_count  = null;
	private $_offset = null;

	private function isAlias($alias)
	{
		return is_string($alias);
	}

	private function insertField(array &$fields, $field, $alias = 0)
	{
		if ($this->isAlias($alias)) {
			$fields[$alias] = $field;
		}
		else {
			array_push($fields, $field);
		}
	}

	/**
	 * Zízkanie zoznamu polí vo funkciách s variabilným počtom premenných. Do
	 * funkcie vstupujú dáta vrátené funkciou func_get_args (v prípade, že pole
	 * má aj iné položky než zoznam položky musí byť o prebytočné položky
	 * skrátené).
	 * \param array arguments
	 * \param mixed defaultField
	 */
	private function getFieldsFromArguments($arguments, $defaultField = null)
	{
		if (!is_null($defaultField)) {
			array_shift($arguments);
			array_unshift($arguments, $defaultField);
		}
		$fields = array();
		foreach ($arguments as $argument) {
			// Ak je argument poľom prechádzame položky a zaraďujeme do polí
			if (is_array($argument)) {
				foreach ($argument as $alias => $field) {
					$this->insertField($fields, $field, $alias);
				}
			}
			else {
				$this->insertField($fields, $argument);
			}
		}
		return $fields;
	}

	/**
	 * Funkcia má na vstupe tabuľku ako ju selectu zadáva užívateľ. Teda buď ako
	 * pole, ktorého kľúčom je alias a hodnotou tabuľka, alebo ako obyčajný
	 * reťazec - názov tabuľky. Návratovou hodnotou je pole s položkami názov
	 * tabuľky a alias tabuľky (názov je prevedený už na nažívny názov v
	 * databáze).
	 */
	private function getTableNameAndAlias($table)
	{
		$name  = '';
		$alias = '';
		if (is_array($table)) {
			list($alias, $name) = each($table);
			$name = $this->_connection->toNativeTableName($name);
			if (!$this->isAlias($alias)) {
				$alias = $name;
			}
		}
		else {
			$name  = $table;
			$name  = $this->_connection->toNativeTableName($name);
			$alias = $name;
		}
		return array($name, $alias);
	}

	private function renderTableName($name, $alias = null)
	{
		$tableAlias = '';
		$tableName  = $this->_connection->escape($name, SQL::Table);
		if (!(is_null($alias) || $name === $alias)) {
			$tableAlias = ' AS '.$this->_connection->escape($alias, SQL::Table);
		}
		return $tableName.$tableAlias;
	}

	private function renderField($field, $alias = null, $table = null)
	{
		// Vyrenderovanie názvu tabuľky (ak sa pri poli vyžaduje)
		$tableText = '';
		if (!is_null($table)) {
			$tableText = $this->renderTableName($table).'.';
		}

		// Ošetrenie názvu stĺpca
		$fieldText = '';
		if ($field instanceof ISQLExpr) {
			$fieldText = $field->toNativeExpr($this->_connection);
		}
		else {
			$fieldText = $this->_connection->escape($field, SQL::Field);
		}

		// Ošetrenie aliasu stĺpca
		$fieldAlias = '';
		if (is_string($alias)) {
			$fieldAlias = ' AS '.$this->_connection->escape($alias, SQL::Field);
		}
		return $tableText.$fieldText.$fieldAlias;
	}

	private function renderJoinCond(_SQLTableInfo $tableInfo)
	{
		$out = '';
		if ($tableInfo->hasCondition()) {
			if ($tableInfo->isUsing()) {
				$out .= ' USING (';
				$tableFields = array();
				$usingColumns = $tableInfo->condition();

				// ak bol zadaný jediný stĺpec nemusí byť obalený v poli
				if (!is_array($usingColumns)) {
					$usingColumns = array($usingColumns);
				}

				foreach ($usingColumns as $tableField) {
					$fieldNative = $this->_connection->escape($tableField, SQL::Field);
					array_push($tableFields, $fieldNative);
				}
				$out .= implode(', ', $tableFields);
				$out .= ')';
			}
			else {
				$out .= ' ON ';
				$cond = $tableInfo->condition();
				if ($cond instanceof ISQLExpr) {
					$out .= $cond->toNativeExpr($this->_connection);
				}
				else {
					$expr = new SQLExpr($cond);
					$out .= $expr->toNativeExpr($this->_connection);
				}
			}
		}
		return $out;
	}

	private function renderConditions($conditions)
	{
		$out = array();
		foreach ($conditions as $condition) {
			array_push($out, $condition->toNativeExpr($this->_connection));
		}

		// Ak máme viacej podmienok ozátvorkujeme
		if (count($out) > 1) {
			array_walk($out, function(&$item, $key){
				$item = '('.$item.')';
			});
		}

		return implode(' AND ', $out);
	}

	/**
	 * Vytvorí novú položku _SQLTableInfo ak ešte nie je vytvorená a vráti odkaz
	 * na ňu.
	 */
	private function &getTableInfo($tableName, $tableAlias, $type = _SQLTableInfo::None, $condition = null)
	{
		$tableInfo = null;
		// Získanie referencie na informácie o tabuľke
		if (array_key_exists($tableAlias, $this->_tables)) {
			$tableId = $this->_tables[$tableAlias];
			$tableInfo = &$this->_tablesInfo[$tableId];

			// Kontrola konfliktu
			if ($tableInfo->name() !== $tableName) {
				throw new SQLException('Alias \''.$tableAlias.'\' already exists.');
			}
		}
		// Vytvorenie informucíí o tabuľke
		else {
			$tableInfo = new _SQLTableInfo($tableName, $tableAlias, $type, $condition);
			$tableId = count($this->_tablesInfo);
			$this->_tables[$tableAlias] = $tableId;
			array_push($this->_tablesInfo, $tableInfo);
			$tableInfo = &$this->_tablesInfo[$tableId];

			if ($tableName !== '') {
				$this->_numTables++;
			}
		}
		return $tableInfo;
	}

	private function useFullFieldName()
	{
		return $this->_numTables > 1;
	}

	private function joinUniversal($type, $table, $fields = array(), $condition = null)
	{
		list($tableName, $tableAlias) = $this->getTableNameAndAlias($table);
		$tableInfo = $this->getTableInfo($tableName, $tableAlias, $type, $condition);
		$tableInfo->addFields($fields);
	}

	/**
	 * Vytvorenie nového výberu používajúceho spojenie \a connection.
	 */
	public function __construct(SQLConnection $connection)
	{
		$this->_connection = $connection;
	}

	/**
	 * Prevod výberu do natívneho reťazca, ktorý sa posiela databáze.
	 */
	public function __toString()
	{
		return 'SELECT'
			.$this->renderDistinct()
			.$this->renderFields()
			.$this->renderFrom()
			.$this->renderJoin()
			.$this->renderWhere()
			.$this->renderGroup()
			.$this->renderHaving()
			.$this->renderOrder()
			.$this->renderLimit();
	}

	/**
	 * Zavolaním tejto metódy sa potlačí vrátenie duplicitných riadkov.
	 *
	 * Kód:
	 * \code
	 * echo $conn->select()
	 * ->distinct()
	 * ->from('#__tabulka', 'stlpec');
	 * \endcode
	 * Bude mať význam podobný výrazu <tt>SELECT DISTINCT `stlpec` FROM
	 * `tabulka`</tt>.
	 * \return SQLSelect
	 */
	public function &distinct()
	{
		$this->_distinct = true;
		return $this;
	}

	/**
	 * Vyber zoznamu stĺpcov z tabuľky \a table.  Zoznam stĺpcov má formát
	 * popísaný v časti \ref shakal_sql_zapis_stlpcov "Zápis zoznamu stĺpcov".
	 * Je možné požadovať výber z tej istej tabuľky niekoľko krát, funkčne je to
	 * zhodné s výberom všetkých stĺpcov naraz v jedinom volaní from.
	 *
	 * Príklady:
	 * \code
	 * // SELECT * FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka');
	 *
	 * // SELECT `stlpec` FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec');
	 *
	 * // SELECT `stlpec1`, `stlpec2` FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec1', 'stlpec2');
	 *
	 * // SELECT `stlpec1`, `stlpec2` FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka', array('stlpec1', 'stlpec2'));
	 *
	 * // SELECT `stlpec1`, `stlpec2` FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec1')
	 *      ->from('#__tabulka', 'stlpec2');
	 *
	 * // SELECT `stlpec1` AS `alias`, `stlpec2` FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka', array('alias' => 'stlpec1', 'stlpec2'));
	 *
	 * // SELECT `tabulka1`.`stlpec`, `tabulka2`.`stlpec` FROM `tabulka1`, `tabulka2`
	 * $conn->select()
	 *      ->from('#__tabulka1', 'stlpec')
	 *      ->from('#__tabulka2', 'stlpec');
	 *
	 * // SELECT `tabulkaA`.*, `tabulkaB`.`stlpec1`, `tabulkaB`.`stlpec2`
	 * //     FROM `tabulkaA`
	 * //     INNER JOIN `tabulkaB` ON podmienka
	 * $conn->select()
	 *      ->from('#__tabulkaA')
	 *      ->joinInner('#__tabulkaB', 'podmienka', 'stlpec1')
	 *      ->from('#__tabulkaB', 'stlpec2');
	 * \endcode
	 * \return SQLSelect
	 */
	public function &from($table, $fields = '*')
	{
		// získanie zoznamu polí
		$arguments = func_get_args();
		array_shift($arguments);

		if ($fields === '*') {
			$fields = new SQLRawExpr('*');
		}

		$fields = $this->getFieldsFromArguments($arguments, $fields);
		list($tableName, $tableAlias) = $this->getTableNameAndAlias($table);

		// Vytvorenie / získanie informácii o tabuľke
		$tableInfo = $this->getTableInfo($tableName, $tableAlias, _SQLTableInfo::From);
		$tableInfo->addFields($fields);

		return $this;
	}

	/// \name Spájanie tabuliek
	//@{
	/**
	 * Spojenie tabuliek <tt>INNER JOIN</tt>. Funkcia je aliasom pre
	 * joinInner().
	 * \return SQLSelect
	 * \sa joinInner
	 */
	public function &join($table, $condition, $columns = array())
	{
		$arguments = func_get_args();
		call_user_func_array(array($this, 'joinInner'), $arguments);
		return $this;
	}

	/**
	 * Spojenie <tt>INNER JOIN</tt> s tabuľkou \a table za podmienky \a
	 * condition. Z tabuľky sa vyberú stĺpce určené argumentom \a columns.
	 * Zoznam stĺpcov má formát popísaný v časti \ref shakal_sql_zapis_stlpcov
	 * "Zápis zoznamu stĺpcov".
	 *
	 * Nasledujúci kód:
	 * \code
	 * $conn->select()
	 *      ->from('#__tabulkaA', array('a' => 'stlpec'))
	 *      ->joinInner('#__tabulkaB', 'podmienka', array('b' => 'stlpec'));
	 * \endcode
	 * bude mať význam podobný ako zápis <tt>SELECT `tabulkaA`.`stlpec` AS `a`,
	 * `tabulkaB`.`stlpec` AS `b` FROM `tabulkaA` INNER JOIN `tabulkaB` ON
	 * podmienka</tt>.
	 * \return SQLSelect
	 */
	public function &joinInner($table, $condition, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::Inner, $table, $fields, $condition);
		return $this;
	}

	/**
	 * Spojenie <tt>LEFT JOIN</tt>. Parametre sú zhodné s funkciou joinInner().
	 * \return SQLSelect
	 * \sa joinInner()
	 */
	public function &joinLeft($table, $condition, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::Left, $table, $fields, $condition);
		return $this;
	}

	/**
	 * Spojenie <tt>RIGHT JOIN</tt>. Parametre sú zhodné s funkciou joinInner().
	 * \return SQLSelect
	 * \sa joinInner()
	 */
	public function &joinRight($table, $condition, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::Right, $table, $fields, $condition);
		return $this;
	}

	/**
	 * Spojenie <tt>FULL OUTER JOIN</tt>. Parametre sú zhodné s funkciou joinInner().
	 * \return SQLSelect
	 * \sa joinInner()
	 */
	public function &joinOuter($table, $condition, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::Outer, $table, $fields, $condition);
		return $this;
	}

	/**
	 * Spojenie tabuliek typu <tt>NATURAL JOIN</tt>. Pri tomto spojení sa
	 * neurčuje žiadna podmienka spojenia, tabuľky sa spájajú podľa stĺpcov,
	 * ktoré majú rovnaký názov.
	 *
	 * Kód:
	 * \code
	 * $conn->select()
	 *      ->from('#__tabulkaA', 'stlpec1')
	 *      ->joinNatural('#__tabulkaB', 'stlpec2');
	 * \endcode
	 * je ekvivalentom <tt>SELECT `tabulkaA`.`stlpec1`, `tabulkaB`.`stlpec2`
	 * FROM `tabulkaA` NATURAL JOIN `tabulkaB`</tt>.
	 * \return SQLSelect
	 */
	public function &joinNatural($table, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 1);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::Natural, $table, $fields);
		return $this;
	}

	/**
	 * Spojenie tabuliek typu <tt>CROSS JOIN</tt>. Toto spojenie vytvorí
	 * kartézsky súčin dvoch tabuliek bez akejkoľvek podmienky. Syntax tejto
	 * metódy je rovnaká ako u joinNatural().
	 * \return SQLSelect
	 */
	public function &joinCross($table, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 1);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::Cross, $table, $fields);
		return $this;
	}

	/**
	 * Alias pre funkciu joinInnerUsing().
	 * \return SQLSelect
	 * \sa joinInnerUsing()
	 */
	public function &joinUsing($table, $usingColumns, $columns = array())
	{
		$arguments = func_get_args();
		call_user_func_array(array($this, 'joinInnerUsing'), $arguments);
		return $this;
	}

	/**
	 * Spojenie <tt>INNER JOIN</tt> s tabuľkou \a table s použitím stĺpcov \a
	 * usingColumns. Ďalšími atribútmi je zoznam stĺpcov, ktoré sa majú z tejto
	 * tabuľky vybrať. Podrobnosti v časti \ref shakal_sql_zapis_stlpcov "Zápis zoznamu stĺpcov".
	 *
	 * Príklady:
	 * \code
	 * // SELECT `tabulkaA`.`stlpec1`, `tabulkaB`.`stlpec2`
	 * //     FROM `tabulkaA`
	 * //     INNER JOIN `tabulkaB` USING (`spojenie`)
	 * $conn->select()
	 *      ->from('#__tabulkaA', 'stlpec1')
	 *      ->joinInnerUsing('#__tabulkaB', 'spojenie', 'stlpec2');
	 *
	 * // SELECT `tabulkaA`.`stlpec1`, `tabulkaB`.`stlpec2`, `tabulkaB`.`stlpec3`
	 * //     FROM `tabulkaA`
	 * //     INNER JOIN `tabulkaB` USING (`spojenie1`, `spojenie2`)
	 * $conn->select()
	 *      ->from('#__tabulkaA', 'stlpec1')
	 *      ->joinInnerUsing('#__tabulkaB',
	 *                       array('spojenie1', 'spojenie2'),
	 *                       array('stlpec2', 'stlpec3'));
	 * // alebo
	 * $conn->select()
	 *      ->from('#__tabulkaA', 'stlpec1')
	 *      ->joinInnerUsing('#__tabulkaB', array('spojenie1', 'spojenie2'))
	 *      ->from('#__tabulkaB', 'stlpec2', 'stlpec3');
	 * \endcode
	 * \return SQLSelect
	 */
	public function &joinInnerUsing($table, $usingColumns, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::InnerUsing, $table, $fields, $usingColumns);
		return $this;
	}

	/**
	 * Spojenie typu <tt>LEFT JOIN</tt>. Atribúty sú zhodné s funkciou
	 * joinInnerUsing().
	 * \return SQLSelect
	 * \sa joinInnerUsing()
	 */
	public function &joinLeftUsing($table, $usingColumns, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::LeftUsing, $table, $fields, $usingColumns);
		return $this;
	}

	/**
	 * Spojenie typu <tt>RIGHT JOIN</tt>. Atribúty sú zhodné s funkciou
	 * joinInnerUsing().
	 * \return SQLSelect
	 * \sa joinInnerUsing()
	 */
	public function &joinRightUsing($table, $usingColumns, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::RightUsing, $table, $fields, $usingColumns);
		return $this;
	}

	/**
	 * Spojenie typu <tt>FULL OUTER JOIN</tt>. Atribúty sú zhodné s funkciou
	 * joinInnerUsing().
	 * \return SQLSelect
	 * \sa joinInnerUsing()
	 */
	public function &joinOuterUsing($table, $usingColumns, $columns = array())
	{
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);
		$fields = $this->getFieldsFromArguments($arguments, $columns);
		$this->joinUniversal(_SQLTableInfo::OuterUsing, $table, $fields, $usingColumns);
		return $this;
	}
	//@}

	/**
	 * Výber stĺpca, alebo stĺpcov, ktoré nepatria žiadnej tabuľke. Reťazce nie
	 * je potrebné obaľovať do SQLExpr, ak nie sú tohto typu automaticky sa
	 * prevedú na SQLExpr.
	 *
	 * Príklady:
	 * \code
	 * // SELECT `stlpec`, NOW(), UNIX_TIMESTAMP(NOW()) AS `unixtime`
	 * //     FROM `tabulka`
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->column('NOW()', array('unixtime' => 'UNIX_TIMESTAMP(NOW())'));
	 * \endcode
	 * \return SQLSelect
	 */
	public function &column($column)
	{
		$arguments = func_get_args();
		$fields = $this->getFieldsFromArguments($arguments, $column);
		$tableInfo = $this->getTableInfo('', '', _SQLTableInfo::None);

		// Konverzia stĺpcov na SQLExpr
		array_walk($fields, function (&$field, $key) {
			if (!$field instanceof ISQLExpr) {
				$field = new SQLExpr($field);
			}
		});
		$tableInfo->addFields($fields);

		return $this;
	}

	/**
	 * Pridanie podmienky \a condition do výberu. Zoznam argumentov sa posiela
	 * konštruktoru SQLExpr. Pri zadaní viacerých podmienok <tt>WHERE</tt> sa
	 * automaticky zreťazia logickým operátorom \e AND.
	 *
	 * Príklady:
	 * \code
	 * // SELECT `stlpec` FROM `tabulka` WHERE (a = 3 OR b = 4) AND (c = 5)
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->where('a = 3 OR b = 4')
	 *      ->where('c = 5');
	 * \endcode
	 * \return SQLSelect
	 * \sa SQLExpr
	 */
	public function &where($condition)
	{
		$expr = call_user_func_array(array(new \ReflectionClass('\Shakal\SQLExpr'), 'newInstance'), func_get_args());
		array_push($this->_where, $expr);
		return $this;
	}

	/**
	 * Volanie tejto metódy pridá do výberu zoskupenie podľa stĺpcov uvedených v
	 * argumentoch. Funkcia má variabilný počet argumentov.
	 *
	 * Príklady:
	 * \code
	 * // SELECT `stlpec` FROM `tabulka` GROUP BY `a`, `b`
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->group('a', 'b');
	 * \endcode
	 * \return SQLSelect
	 */
	public function &group($expr1)
	{
		$arguments = func_get_args();
		$fields = $this->getFieldsFromArguments($arguments, $expr1);
		foreach ($fields as $field) {
			$field = $this->renderField($field);
			array_push($this->_group, $field);
		}
		return $this;
	}

	/**
	 * Pridanie podmienky <tt>HAVING</tt>. Funkcia sa používa rovnako ako
	 * where().
	 * \return SQLSelect
	 * \sa where()
	 */
	public function &having($condition)
	{
		$expr = call_user_func_array(array(new \ReflectionClass('\Shakal\SQLExpr'), 'newInstance'), func_get_args());
		array_push($this->_having, $expr);
		return $this;
	}

	/**
	 * Táto metóda zoradí výsledky podľa podmienky order. Funkcia má variabilný
	 * počet parametrov, je možné zapísať niekoľko podmienok. Podmienka sa
	 * zapisuje spôsobom 'stlpec [ASC|DESC]'. Ak nebude uvedené v akom poradí
	 * majú byť zoradené budú výsledky zoradené vzostupne (ASC). Zoraďovací
	 * výraz je možné zapísať aj ako SQLExpr.
	 *
	 * Príklady:
	 * \code
	 * // SELECT `stlpec` FROM `tabulka` ORDER BY `a` ASC
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->order('a');
	 *
	 * // SELECT `stlpec` FROM `tabulka` ORDER BY `a` DESC, `b` ASC
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->order('a DESC', 'b ASC');
	 * \endcode
	 * \return SQLSelect
	 */
	public function &order($expr1)
	{
		$fields = func_get_args();
		foreach ($fields as $field) {
			$fieldExpr = null;
			if ($field instanceof ISQLExpr) {
				$fieldExpr = $field;
			}
			else {
				if (preg_match("/(.*)\s+DESC/i", $field, $matches)) {
					$fieldExpr = new SQLExpr($this->renderField($matches[1]).' DESC');
				}
				elseif (preg_match("/(.*)\s+ASC/i", $field, $matches)) {
					$fieldExpr = new SQLExpr($this->renderField($matches[1]).' ASC');
				}
				else {
					$fieldExpr = new SQLExpr($this->renderField($field).' ASC');
				}
			}
			array_push($this->_order, $fieldExpr->toNativeExpr($this->_connection));
		}
		return $this;
	}

	/**
	 * Obmedzenie vybraných riadkov na počet \a count od riadku \a offset.
	 *
	 * Príklady:
	 * \code
	 * // SELECT `stlpec` FROM `tabulka` LIMIT 10
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->limit(10);
	 *
	 * // SELECT `stlpec` FROM `tabulka` LIMIT 10 OFFSET 50
	 * $conn->select()
	 *      ->from('#__tabulka', 'stlpec')
	 *      ->limit(10, 50);
	 * \endcode
	 * \return SQLSelect
	 */
	public function &limit($count, $offset = null)
	{
		if (!is_null($offset))
			$offset = (int)$offset;
		$this->_count  = (int)$count;
		$this->_offset = $offset;
		return $this;
	}

	/// \name Renderovanie jednotlivých častí SELECT-u.
	//@{
	private function renderDistinct()
	{
		if ($this->_distinct) {
			return ' DISTINCT';
		}
		else {
			return '';
		}
	}

	private function renderFields()
	{
		$out = array();

		$fieldsArr = array();
		// Predchod všetkými tabuľkami v poradí zadania
		foreach ($this->_tablesInfo as $tableInfo) {
			$fields = $tableInfo->fields();
			// Uloženie všetkých stĺpcov do dočasného poľa fieldsArr
			foreach ($fields as $field) {
				$table = null;
				if ($this->useFullFieldName()) {
					$table = $tableInfo->alias();
				}
				$fieldsArr[$field->id()] = array($table, $field);
			}
		}

		// Usporiadanie a vyrenderovanie v poradí zadania
		ksort($fieldsArr);
		foreach ($fieldsArr as $tf) {
			$table = $tf[0];
			$field = $tf[1];
			array_push($out, $this->renderField($field->name(), $field->alias(), $table));
		}

		return ' '.implode(', ', $out);
	}

	private function renderFrom()
	{
		$out = array();
		foreach ($this->_tablesInfo as $tableInfo) {
			if ($tableInfo->isFrom()) {
				array_push($out, $this->renderTableName($tableInfo->name(), $tableInfo->alias()));
			}
		}
		return ' FROM '.implode(', ', $out);
	}

	private function renderJoin()
	{
		$out = '';
		foreach ($this->_tablesInfo as $tableInfo) {
			if ($tableInfo->isJoin()) {
				$out .= ' ';
				switch($tableInfo->type()) {
					case _SQLTableInfo::Natural:    $out .= 'NATURAL'; break;
					case _SQLTableInfo::Cross:      $out .= 'CROSS'; break;
					case _SQLTableInfo::InnerUsing: // Rovnaké pravidlo
					case _SQLTableInfo::Inner:      $out .= 'INNER'; break;
					case _SQLTableInfo::LeftUsing:  // Rovnaké pravidlo
					case _SQLTableInfo::Left:       $out .= 'LEFT'; break;
					case _SQLTableInfo::RightUsing: // Rovnaké pravidlo
					case _SQLTableInfo::Right:      $out .= 'RIGHT'; break;
					case _SQLTableInfo::OuterUsing: // Rovnaké pravidlo
					case _SQLTableInfo::Outer:      $out .= 'FULL OUTER'; break;
					default:
						throw new SystemException('Undefined table info type.');
				}
				$out .= ' JOIN ';
				$out .= $this->renderTableName($tableInfo->name(), $tableInfo->alias());
				$out .= $this->renderJoinCond($tableInfo);
			}
		}
		return $out;
	}

	private function renderWhere()
	{
		if (count($this->_where) === 0) {
			return '';
		}

		return ' WHERE '.$this->renderConditions($this->_where);
	}

	private function renderGroup()
	{
		if (count($this->_group) === 0) {
			return '';
		}

		return ' GROUP BY '.implode(', ', $this->_group);
	}

	private function renderHaving()
	{
		if (count($this->_having) === 0) {
			return '';
		}

		return ' HAVING '.$this->renderConditions($this->_having);
	}

	private function renderOrder()
	{
		if (count($this->_order) === 0) {
			return '';
		}

		return ' ORDER BY '.implode(', ', $this->_order);
	}

	private function renderLimit()
	{
		if (is_null($this->_count)) {
			return '';
		}

		$offset = '';
		if (!is_null($this->_offset)) {
			$offset = ' OFFSET '.$this->_offset;
		}

		return ' LIMIT '.$this->_count.$offset;
	}

	//@}
}


?>
