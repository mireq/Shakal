<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Pripojenie na databázový server.
 */

namespace Shakal;

/**
 * \brief Spojenie s databázovým serverom.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 * \todo Vyhodiť funkciu escape, v selecte sa dá namiesto toho vysladať SQLExpr,
 * ktoré sa následne vyrenderuje.
 */
class SQLConnection
{
	/**
	 * \var SQLDriver
	 */
	private $_driver = null;

	/**
	 * Vytvorenie nového spojenia s databázou. Parameter \a config má rovnakú
	 * štruktúru, ako v metóde connect.
	 *
	 * \param array settings
	 */
	public function __construct($settings)
	{
		$this->connect($settings);
	}

	/**
	 * Pripojenie k databázovému serveru.
	 *
	 * \param array settings Nastavenia pre pripojenie sa k databáze.
	 *
	 * Štandardné položky poľa \a settings sú nasledujúce:
	 * <table>
	 *   <tr><th>Kľúč</th><th>Hodnota</th></tr>
	 *   <tr><td>type</td><td>Typ databázy (napr. mysql, postgresql, ...)</tr></tr>
	 *   <tr><td>server</td><td>Databázový server.</td></tr>
	 *   <tr><td>username</td><td>Meno užívateľa.</td></tr>
	 *   <tr><td>password</td><td>Heslo pre pripojenie k databáze.</td></tr>
	 *   <tr><td>database</td><td>Názov databázy pre pripojenie.</td></tr>
	 * </table>
	 */
	public function connect($settings)
	{
		// odpojenie od databázy pri prípadnom pripojení
		if ($this->isConnected())
			$this->disconnect();

		// načítanie typu databázy
		$dbType = $settings['type'];
		unset($settings['type']);

		// Kontrola existencie driveru
		$driverFile = dirname(__FILE__).DS.'..'.DS.'drivers'.DS.$dbType.'.drv.php';
		if (!is_file($driverFile) || !is_readable($driverFile))
			throw new SystemException('SQL driver \''.$dbType.'\' does not exists.');

		// Vloženie súboru
		require($driverFile);

		// Kontrola existencie triedy
		$className = '\\Shakal\\'.$dbType.'_'.'SQLDriver';
		if (!class_exists($className))
			throw new SystemException('Class \''.$className.'\' does not exists');

		// Vytvorenie novej inštancie
		$this->_driver = new $className;
		// Pripojenie driveru
		$this->_driver->connect($settings);
	}

	/**
	 * Odpojenie sa od databázy.
	 */
	public function disconnect()
	{
		if ($this->isConnected()) {
			$this->_driver->disconnect();
			$this->_driver = null;
		}
	}

	/**
	 * Funkcia vráti \e true, ak je vytvorené spojenie s databázou.
	 */
	private function isConnected()
	{
		return !is_null($this->_driver);
	}

	/**
	 * Spustenie %SQL dotazu.
	 * \todo Zdokumentovať
	 * \return SQLResult
	 */
	public function query($query)
	{
		// načítame všetky (aj voliteľné) argumenty
		$args = func_get_args();

		// Vytvorenie SQLExpr volaním konštruktora s voliteľnými parametrami.
		$expr = call_user_func_array(array(new \ReflectionClass('\Shakal\SQLExpr'), 'newInstance'), $args);

		$nativeQuery = $expr->toNativeExpr($this);
		$result = $this->_driver->nativeQuery($nativeQuery);

		return new SQLResult($result, $this);
	}

	/**
	 * Ošetrenie reťazcov do formy vhodnej pre %SQL server.
	 * \sa ISQLDriver::escape()
	 */
	public function escape($value, $type)
	{
		return $this->_driver->escape($value, $type);
	}

	/**
	 * Vytvorenie nového výberového %SQL dotazu.
	 * \sa SQLSelect
	 * \return SQLSelect
	 */
	public function select()
	{
		return new SQLSelect($this);
	}

	/**
	 * Získanie referencie na ovládač pracujúci s databázou.
	 * \return SQLDriver
	 */
	public function &driver()
	{
		return $this->_driver;
	}

	/**
	 * Prevod názvu tabuľky na natívny %SQL názov. Na vstupe je identifikátor
	 * ako "#__tabulka", v ktorom sa nahradí reťazec "#__" na prefix určený pre
	 * túto tabuľku.
	 * \param string tableName
	 * \return string
	 */
	public function toNativeTableName($tableName)
	{
		/// @todo Dokončiť
		return str_replace('#__', '', $tableName);
	}
}

?>
